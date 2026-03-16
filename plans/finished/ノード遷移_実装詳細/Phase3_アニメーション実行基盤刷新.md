# Phase3 アニメーション実行基盤刷新 実装詳細

## 目的

Phase3 では、Phase2 までで整理した「更新対象の正しさ」の上に、**アニメーション実行基盤の軽量化と責務分離** を載せる。

Phase2 終了時点では、`full` / `children` / `node` の差分更新と履歴管理は整理される想定だが、アニメーション実行基盤には次の問題が残る。

- `HorrorGameNetwork.update()` が常時 `requestAnimationFrame` を回している
- `CurrentNode.update()` → `NodeContentTree.update()` → `BasicNode.update()` が静止時も毎フレーム走る
- `ConnectionLine` が `style.height` を毎フレーム更新している
- `FreePoint` が `left` / `top` を毎フレーム更新している
- アニメーション進行ロジックが各クラスに分散し、`_appearAnimationFunc` に強く依存している

Phase3 の目的は、これを次の状態へ置き換えること。

- **アニメーション中だけ動くループ**
- **時間管理の一元化**
- **transform ベースの軽量な見た目更新**
- **ノードとアニメーション実行責務の分離**

---

## Phase3 の達成目標

- `AnimationScheduler` を導入し、常時 rAF を停止する
- アニメーション対象だけを `AnimationScheduler` に登録して更新する
- `NodeAnimator` を導入し、ノードの appear / disappear の進行管理を集約する
- `ConnectionLine` を `height` 更新から `scaleY()` ベースへ移行する
- `FreePoint` を `left` / `top` 更新から `transform: translate()` ベースへ移行する
- `(window as any).hgn.timestamp` 依存を段階的に外す
- イージングを明示的に指定できるようにする

Phase3 の終了時点で、**静止状態ではアニメーションループが停止している** ことが重要な完了条件になる。

---

## Phase3 の対象ファイル

新規追加:

- `resources/ts/animation/animation-scheduler.ts`
- `resources/ts/animation/animatable.ts`
- `resources/ts/animation/node-animator.ts`
- `resources/ts/animation/easing.ts`

更新対象:

- `resources/ts/horror-game-network.ts`
- `resources/ts/node/current-node.ts`
- `resources/ts/node/basic-node.ts`
- `resources/ts/node/tree-node.ts`
- `resources/ts/node/parts/node-content-tree.ts`
- `resources/ts/node/parts/connection-line.ts`
- `resources/ts/node/parts/free-point.ts`
- `resources/ts/common/util.ts`

まだやらないこと:

- `CurveCanvas` → `SVG` の置き換え
- `DepthSceneController`
- `DepthEffectController`
- `Canvas` 描画自体の全面差し替え

それらは Phase4 以降で扱う。

---

## Phase3 の基本方針

### 1. ループを常時回さない

今の構造では、静止時でも `requestAnimationFrame()` が回り続ける。

Phase3 では次の方式に変える。

- アニメーション開始時にだけ `AnimationScheduler.requestTick()` を呼ぶ
- 登録中のアニメーションがなくなったらループ停止

### 2. 「ノードが自分で時間を計算する」構造を減らす

今は `BasicNode`, `TreeNode`, `ConnectionLine`, `FreePoint` が個別に `timestamp` を参照し、内部で進行率を計算している。

Phase3 では以下に寄せる。

- 進行管理: `NodeAnimator`
- フレーム管理: `AnimationScheduler`
- ノード本体: 状態変更の受け皿

### 3. レイアウトコストの高いプロパティを避ける

- `height` -> `scaleY()`
- `left/top` -> `transform: translate()`

に置き換える。

---

## クラス設計

## `Animatable`

### 役割

- `AnimationScheduler` に登録できる対象の共通契約

### 新規ファイル

- `resources/ts/animation/animatable.ts`

### インターフェース案

```ts
export interface Animatable
{
    update(timestamp: number): boolean;
}
```

返り値:

- `true`: 次フレームも継続
- `false`: 完了したので登録解除可能

備考:

- 現在の `void update()` ではなく、継続可否を返す設計にする
- 既存クラスにそのまま当てるより、`NodeAnimator` や一部エフェクトクラスを `Animatable` にする

---

## `AnimationScheduler`

### 役割

- rAF の開始・停止を管理する
- 登録された `Animatable` だけ更新する
- 現在時刻を共有する

### 新規ファイル

- `resources/ts/animation/animation-scheduler.ts`

### 公開メソッド案

```ts
export class AnimationScheduler
{
    public register(animatable: Animatable): void
    public unregister(animatable: Animatable): void
    public requestTick(): void
    public get timestamp(): number
    public get isRunning(): boolean
}
```

### 内部仕様

- `_animatables: Set<Animatable>`
- `_isRunning: boolean`
- `_timestamp: number`

### 更新フロー

1. `register()` で追加
2. `requestTick()` で rAF 起動
3. フレームごとに `animatable.update(timestamp)` を実行
4. `false` を返したものは解除
5. Set が空なら rAF 停止

### 期待効果

- 静止時 CPU 消費を抑える
- `HorrorGameNetwork.update()` の常時実行を解消できる

---

## `NodeAnimator`

### 役割

- ノード単位の appear / disappear / disappearSolo を進行管理する
- `BasicNode` / `TreeNode` の `_appearAnimationFunc` 依存を薄める

### 新規ファイル

- `resources/ts/animation/node-animator.ts`

### 公開メソッド案

```ts
export class NodeAnimator implements Animatable
{
    public playAppear(target: BasicNode | TreeNode, options?: NodeAnimationOptions): Promise<void>
    public playDisappear(target: BasicNode | TreeNode, options?: NodeAnimationOptions): Promise<void>
    public playDisappearSolo(target: BasicNode | TreeNode, options?: NodeAnimationOptions): Promise<void>
    public update(timestamp: number): boolean
}
```

### `NodeAnimationOptions`

```ts
type NodeAnimationOptions = {
    isFast?: boolean;
    doNotAppearBehind?: boolean;
    easing?: 'linear' | 'easeOutCubic';
};
```

### 方針

- 初期段階では 1 インスタンス 1 ノードでもよい
- 将来的には `ConnectionLineAnimator` などへ分離可能

### Phase3 でやること

- `BasicNode.appear()` / `disappear()` 内の時間進行を直接持たない形へ寄せる
- ただし完全置換が重い場合は、まず `NodeAnimator` を「ラッパー」として導入してもよい

---

## `Easing`

### 役割

- 進行率に対するイージング関数を提供する

### 新規ファイル

- `resources/ts/animation/easing.ts`

### 関数案

```ts
export class Easing
{
    public static linear(t: number): number
    public static easeOutCubic(t: number): number
    public static easeInOutCubic(t: number): number
}
```

### 方針

- Phase3 では `linear` と `easeOutCubic` があれば十分
- 既存の `Util.getAnimationProgress()` はそのまま使わず、`progress` と `easing` を分ける方向にする

---

## 既存クラスの変更方針

## `HorrorGameNetwork`

### 目的

- 常時 rAF をやめる
- `AnimationScheduler` の所有者になる

### 変更内容

現在:

- `requestAnimationFrame((timestamp) => this.update(timestamp));`
- `update()` の最後で常に次フレームを予約

Phase3 後:

- `AnimationScheduler` を生成
- `HorrorGameNetwork` 自体はアニメーションループを直接回さない
- リサイズや popstate などのアプリイベントだけを扱う

### 公開メソッド案

```ts
public get animationScheduler(): AnimationScheduler
```

### `draw()` の扱い

現状の `draw()` は `CurrentNode.draw()` を直に呼ぶ。

Phase3 では次のいずれかに寄せる。

案A:

- `draw()` は `AnimationScheduler` フレーム内だけ呼ぶ

案B:

- `Animatable` 側が必要時に描画まで面倒を見る

Phase3 では **案A** を推奨する。

理由:

- 今の構造との距離が近い
- `CurrentNode.draw()` を大きく壊さずに移行できる

---

## `CurrentNode`

### 目的

- 常時 `update()` 前提を外す

### 現状課題

- `CurrentNode.update()` が毎フレーム呼ばれる前提
- `_isChanging` や `_appearAnimationFunc` の進行が rAF 常駐に乗っている

### Phase3 方針

- `CurrentNode` 自身は「自分がアニメーション中かどうか」を返せるようにする
- アニメーション開始時に `AnimationScheduler.requestTick()` を要求する

### 追加メソッド案

```ts
public hasActiveAnimation(): boolean
public requestAnimationFrameIfNeeded(): void
```

### 備考

- Phase3 では `CurrentNode.update()` 自体は残してよい
- ただし「Scheduler から呼ばれる update」に位置づけを変える

---

## `BasicNode`

### 目的

- `_appearAnimationFunc` のみで完結しているローカル進行を `NodeAnimator` 側へ寄せる
- アニメーション開始時に Scheduler を起動する

### Phase3 方針

- まずは `BasicNode.appear()` / `disappear()` で Scheduler 起動だけでも入れる
- 次段階で `NodeAnimator` へロジックを寄せる

### 推奨メソッド追加

```ts
public hasActiveAnimation(): boolean
```

### 判定対象

- `_appearAnimationFunc !== null`
- `_updateGradientEndAlphaFunc !== null`
- `_nodeContentBehind?.appearStatus` が進行中

---

## `TreeNode`

### 目的

- 子 `NodeContentTree` を含めて「進行中か」を返せるようにする

### 追加メソッド案

```ts
public hasActiveAnimation(): boolean
```

### 実装方針

- 自身の `_appearAnimationFunc`
- `nodeContentTree.appearStatus`
- `nodeContentTree.hasActiveAnimation()`

をまとめて見る

---

## `NodeContentTree`

### 目的

- 接続線と子ノードの進行状態を集約する

### 追加メソッド案

```ts
public hasActiveAnimation(): boolean
```

### 判定対象

- `_connectionLine.appearStatus`
- `appearAnimationFunc !== null`
- `_nodes.some(node => node.hasActiveAnimation?.())`

### Phase3 の役割

- これにより Scheduler 側が「まだ回す必要があるか」を判断しやすくなる

---

## `ConnectionLine`

### 目的

- `height` 更新をやめ、`transform: scaleY()` ベースにする

### 現状課題

- `style.height` の毎フレーム更新で reflow が起きる
- `offsetTop` / `offsetHeight` 依存が強い

### Phase3 後の構造

- 実高さ `_height` は固定値として保持
- 要素自体の `height` は最終高さに一度だけ設定
- 進行中は `transform: scaleY(progress)` を更新
- `transform-origin: top` を指定

### 変更イメージ

今:

```ts
this._element.style.height = `${this._animationHeight}px`;
```

Phase3:

```ts
this._element.style.height = `${this._height}px`;
this._element.style.transform = `scaleY(${progress})`;
```

### 追加プロパティ案

```ts
private _progress: number;
```

### disappear 時

- `progress` を `1 -> disappearProgress` へ減衰
- 実体高さを書き換えない

### 注意点

- 既存の `getAnimationHeight()` を使う箇所があるため、当面は
  - `getAnimationHeight()` = `_height * _progress`
  として互換維持する

---

## `FreePoint`

### 目的

- `left` / `top` 更新をやめ、`transform: translate()` に置き換える

### 現状課題

- `moveOffset()` が毎フレーム `left` / `top` を更新している

### Phase3 後の構造

- 基準位置だけ `setPos()` で保持
- 実表示位置は `translate3d(x, y, 0)` で反映

### 追加フィールド案

```ts
private _offset: { x: number, y: number };
```

### メソッド案

```ts
private applyTransform(): void
public moveOffset(x: number, y: number): FreePoint
public setElementPos(): FreePoint
```

### 実装イメージ

```ts
this._element.style.transform = `translate3d(${tx}px, ${ty}px, 0)`;
```

### 注意点

- `fixOffset()` は `style.left` / `style.top` を前提にしているため再設計が必要
- Phase3 では `fixOffset()` 利用箇所の確認が必要

---

## `Util`

### 目的

- 時間計算とイージング適用を整理する

### 現状課題

- `getAnimationProgress()` が `window.hgn.timestamp` に直接依存
- 全て線形進行

### Phase3 方針

`Util.getAnimationProgress()` は縮小し、以下のように役割分離する。

```ts
public static getLinearProgress(
    currentTime: number,
    startTime: number,
    duration: number
): number
```

または、`Easing` クラスと併用する。

```ts
const t = Util.getLinearProgress(timestamp, startTime, duration);
const eased = Easing.easeOutCubic(t);
```

### 方針

- `window as any` を減らす
- `timestamp` は `AnimationScheduler` から渡す

---

## 移行戦略

Phase3 は、アニメーションの心臓部を触るので一気に置き換えない。

### Step 1

- `AnimationScheduler` を追加
- `HorrorGameNetwork` の常時 rAF を削る
- 既存 `CurrentNode.update()` / `draw()` を Scheduler 経由で動かす

### Step 2

- `hasActiveAnimation()` を `CurrentNode` / `NodeContentTree` / `BasicNode` / `TreeNode` に追加
- 「動いている間だけ次フレームを要求する」構造にする

### Step 3

- `ConnectionLine` を `scaleY()` 化
- 互換のため `getAnimationHeight()` は残す

### Step 4

- `FreePoint` を `translate3d()` 化
- `fixOffset()` など古い left/top 前提コードを整理

### Step 5

- `NodeAnimator` と `Easing` を追加
- 主要な appear / disappear から順に移行

---

## `HorrorGameNetwork` の変更詳細

### 現状

```ts
private update(timestamp: number): void
{
    this._timestamp = timestamp;
    this._currentNode.update();
    this.draw();
    requestAnimationFrame((timestamp) => this.update(timestamp));
}
```

### Phase3 後のイメージ

```ts
private onAnimationFrame(timestamp: number): void
{
    this._timestamp = timestamp;

    if (this.isForceResize) {
        this.resize();
        this.isForceResize = false;
    }

    this._currentNode.update();
    this.draw();
}
```

この `onAnimationFrame()` は `AnimationScheduler` から必要時だけ呼ばれる。

### 組み立て

```ts
this._animationScheduler = new AnimationScheduler((timestamp) => {
    this.onAnimationFrame(timestamp);
});
```

---

## テスト観点

### 機能

- `full` 更新でアニメーションが最後まで動く
- `children` 更新で CurrentNode の見出しは残る
- `node` 更新で対象ノードだけ動く
- `popstate` 復元時にもループが正しく再開・停止する

### パフォーマンス

- 何もしていない静止状態で rAF が走っていない
- スクロール中や静止中に CPU 使用率が下がる
- `ConnectionLine` 伸縮時にレイアウトスラッシングが減る
- `FreePoint` 移動時に reflow が減る

### 見た目

- `scaleY()` 化しても接続線の見た目が崩れない
- `translate3d()` 化しても free-point の位置ズレが出ない
- 既存の disappear スピード感が大きく変わりすぎない

---

## 暫定互換の扱い

Phase3 中は以下を一時的に残してよい。

- `_appearAnimationFunc`
- `_updateGradientEndAlphaFunc`
- `Util.getAnimationProgress()`

ただし役割は縮小する。

- ループ管理: `AnimationScheduler`
- 時間計算: `timestamp` 引数ベース
- 見た目更新: transform ベース

つまり、Phase3 は「古い関数ポインタ式アニメーションを即全廃する」のではなく、**まずループと描画コストを下げ、そのうえで次段階で整理しやすくするフェーズ** と位置づける。

---

## 完了判定

Phase3 完了条件:

- 常時 `requestAnimationFrame` が廃止されている
- 静止時に `AnimationScheduler` が停止している
- `ConnectionLine` が `height` ではなく `transform: scaleY()` ベースで動く
- `FreePoint` が `left/top` ではなく `transform: translate3d()` ベースで動く
- 主要な appear / disappear で `timestamp` の取得元が一元化されている
- 少なくとも一部のノードアニメーションで `NodeAnimator` または同等の集約レイヤーが使われている

---

## Phase4 への引き継ぎ

Phase3 が終わると、アニメーションの「回し方」は整理される。
次の Phase4 では、描画そのものを見直す。

Phase4 でやること:

- `CurveRenderer`
- `CanvasCurveRenderer`
- `SvgCurveRenderer`
- CSS 変数色のキャッシュ
- Canvas shadowBlur の削減または撤去

つまり Phase3 は、**実行基盤を軽くするフェーズ** であり、Phase4 は **描画方式自体を軽くするフェーズ** になる。
