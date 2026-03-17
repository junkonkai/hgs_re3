# Phase5 Z軸演出導入 実装詳細

## 目的

Phase5 では、Phase1-4 で整理した

- ナビゲーションモデル
- 差分更新
- 履歴管理
- 軽量なアニメーション基盤
- 軽量な接続線描画

の上に、**Z軸（奥行き）演出** を導入する。

この Phase のテーマは「子ノードの階層が奥にある」という体験を、DOM を正としたまま視覚的に成立させること。

---

## Phase5 の達成目標

- `DepthSceneController` を導入し、シーン全体の奥行きモードを制御できる
- `DepthEffectController` を導入し、ノード単位で Z 演出を適用できる
- パターンA（遷移時のみ Z 演出）を正式実装する
- 条件付きでパターンB（常時奥行き表現）を限定導入できる設計にする
- `prefers-reduced-motion` に応じたフォールバックを用意する
- DOM + transform ベースで実現し、SEO を維持する

---

## この Phase の前提

このプロジェクトでの Z 軸方針は次。

- ノード本文・見出し・リンクは HTML のままにする
- Canvas に全文字を描かない
- エースコンバット7寄りの「奥に階層がある」雰囲気は取り入れる
- ただし構造は横ではなく **縦に伸びる**
- 奥のノードは **小さく・かすんでいてもよい**

つまりこの Phase は、**SEO と縦レイアウトを守りながら奥行きだけを足す** フェーズである。

---

## Phase5 の対象ファイル

新規追加:

- `resources/ts/depth/depth-scene-controller.ts`
- `resources/ts/depth/depth-effect-controller.ts`
- `resources/ts/depth/depth-types.ts`

更新対象:

- `resources/ts/horror-game-network.ts`
- `resources/ts/node/current-node.ts`
- `resources/ts/node/basic-node.ts`
- `resources/ts/node/tree-node.ts`
- `resources/css/tree.css`

必要に応じて:

- `resources/views/layout.blade.php`
- `resources/views/**/*.blade.php`

---

## 基本方針

### 1. Phase5 の中心は DOM + transform

使う主な手段:

- `transform: translateZ()`
- `transform: scale()`
- `opacity`
- `filter: blur()`
- `perspective`

### 2. まずはパターンAから入る

パターンA:

- 普段は今まで通り 2D に近い表示
- クリック時だけ「奥へ抜ける」「奥から手前に出る」演出を入れる

理由:

- 実装コストが低い
- 既存体験を壊しにくい
- 乗り物酔いリスクや情報可読性低下を抑えやすい

### 3. パターンBは限定導入

パターンB:

- 常時 depth を持つ

これは次の条件を満たした場合だけ導入する。

- パターンA の体感が良い
- パフォーマンス劣化が小さい
- 可読性に致命傷がない

---

## 型設計

## `resources/ts/depth/depth-types.ts`

```ts
export type DepthMode = 'none' | 'transition' | 'persistent';

export type DepthLevel = number;

export type DepthAnimationKind = 'enter' | 'exit' | 'focus' | 'background';
```

---

## クラス設計

## `DepthSceneController`

### 役割

- 画面全体で Z 演出モードを切り替える
- 現在の mode を保持する
- `CurrentNodeScene` と `DepthEffectController` をつなぐ

### 新規ファイル

- `resources/ts/depth/depth-scene-controller.ts`

### 公開メソッド案

```ts
export class DepthSceneController
{
    public setMode(mode: DepthMode): void
    public get mode(): DepthMode
    public focusNode(nodeId: string): void
    public reset(): void
}
```

### モード意味

- `none`: Z 演出なし
- `transition`: パターンA
- `persistent`: パターンB

---

## `DepthEffectController`

### 役割

- 個々のノード要素へ depth 表現を適用する

### 新規ファイル

- `resources/ts/depth/depth-effect-controller.ts`

### 公開メソッド案

```ts
export class DepthEffectController
{
    public applyDepth(element: HTMLElement, depth: number): void
    public clearDepth(element: HTMLElement): void
    public playEnter(element: HTMLElement, depth: number): void
    public playExit(element: HTMLElement, depth: number): void
    public setFocused(element: HTMLElement): void
    public setBackground(element: HTMLElement, depth: number): void
}
```

### 見た目ルール例

- 深いほど `scale` を下げる
- 深いほど `opacity` を下げる
- 深いほど `blur` を少し強める

例:

```ts
scale = 1 - depth * 0.05
opacity = 1 - depth * 0.15
blur = depth * 1.2
```

ただし mobile では blur を弱くする。

---

## CSS 設計

## `resources/css/tree.css`

追加クラス例:

```css
.tree-depth-root {
    perspective: 1200px;
    transform-style: preserve-3d;
}

.node-depth-layer {
    transform-style: preserve-3d;
    will-change: transform, opacity, filter;
}

.node-depth-entering {
    transition: transform 220ms ease-out, opacity 220ms ease-out, filter 220ms ease-out;
}

.node-depth-exiting {
    transition: transform 180ms ease-in, opacity 180ms ease-in, filter 180ms ease-in;
}
```

### 注意点

- perspective はシーン全体の親にかける
- 個別ノードは `translateZ()` 単体ではなく `scale()` も組み合わせる
- `filter: blur()` は強くしすぎない

---

## 実装戦略

## パターンA: 遷移時のみ Z

### 目的

- クリックされたノードが奥へ抜ける
- または新ノードが奥から手前へ来る

### フロー

#### `scope = node`

1. ノードクリック
2. `DepthSceneController.mode === 'transition'`
3. 旧ノードに `playExit(element, depth=1)`
4. 差し替え
5. 新ノードに `playEnter(element, depth=1)`

#### `scope = children`

1. CurrentNode 配下の子ツリーが一度奥へ引く
2. 新しい子ノード群が奥から現れる

### 見た目

- 旧ノード:
  - `translateZ(-80px)`
  - `scale(0.92)`
  - `opacity: 0.4`
  - `blur(2px)`
- 新ノード:
  - 初期 `translateZ(-80px)`
  - 終了 `translateZ(0)`
  - `scale(1)`
  - `opacity: 1`

### 利点

- 既存 UI を壊しにくい
- 実装コストが低い
- ユーザーに「奥へ遷移した」感覚を与えやすい

---

## パターンB: 常時 depth

### 目的

- 子ノードは常に奥に存在するように見せる

### フロー

- ノード生成時に `depth` を計算
- depth に応じて常時 `translateZ()`, `scale`, `opacity`, `blur` を適用
- フォーカス対象だけ手前寄り補正する

### depth の決め方

例:

- CurrentNode 直下: depth 0
- その子: depth 1
- 孫: depth 2

### 注意点

- ヒットテストは DOM のままなので、見た目上小さくても実タップ領域が確保されるか確認する
- 接続線の見た目は 2D のままでもよい
- まずは `tree-node` のみ対象にする限定導入が安全

---

## `CurrentNode` / `BasicNode` / `TreeNode` への影響

## `CurrentNode`

### 変更内容

- ルート要素に `tree-depth-root` を付与
- 差し替え後ノードへ depth 適用

### 追加メソッド案

```ts
public applyDepthToTree(): void
```

## `BasicNode`

### 変更内容

- 自ノード要素を `DepthEffectController` に渡せるようにする

### 追加メソッド案

```ts
public applyDepth(depth: number): void
public clearDepth(): void
```

## `TreeNode`

### 変更内容

- 子ツリーを持つので depth 計算の起点になりやすい
- `getNodeById()` だけでなく「深度付き走査」が将来的に必要になる

### 補助メソッド案

```ts
public walkDepth(visitor: (node: NodeType, depth: number) => void, depth?: number): void
```

---

## 接続線との整合

Phase5 時点では、接続線は必ずしも 3D 化しない。

方針:

- ノード本体に奥行きを与える
- 接続線は 2D のままでもよい
- まずは体験を優先する

必要なら後続で、

- 線の opacity を depth に合わせる
- 線自体を軽く縮退させる

程度から始める。

接続線まで完全 3D 投影にするとコストが一気に上がるため、Phase5 では見送る。

---

## アクセシビリティとフォールバック

### `prefers-reduced-motion`

方針:

- `transition` mode でも Z 移動を抑える
- `opacity` と軽い scale だけにする

### 実装案

```ts
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
```

これが `true` の場合:

- `translateZ()` を使わない
- 速いフェードと縮小だけで代替

### 可読性配慮

- depth 1 までは読める程度にする
- depth 2 以降は「存在の示唆」優先でよい

---

## テスト観点

### 体感

- `node` 更新で「奥へ入る」感覚がある
- `children` 更新で CurrentNode は維持され、子だけが奥から切り替わる
- `full` 更新でも過剰に酔わない

### 可読性

- 主要なクリック対象の可読性が確保される
- 小さくかすんだ奥ノードが「存在の示唆」として機能する

### SEO

- 本文・見出し・リンクが DOM に残る
- hidden ではなく transform / opacity ベースなのでクロール可能性を落とさない

### パフォーマンス

- `transition` mode で frame drop が大きく増えない
- `persistent` mode は限定導入で検証する

---

## 実装手順

### Step 1

- `DepthMode` と `DepthSceneController` を追加
- mode を `none` / `transition` / `persistent` で切り替え可能にする

### Step 2

- `DepthEffectController` を追加
- 単一ノードへの enter / exit 演出を実装

### Step 3

- `node` 更新にパターンAを適用

### Step 4

- `children` 更新にパターンAを適用

### Step 5

- 問題なければ `persistent` mode を一部ノードで試験導入

---

## 完了判定

Phase5 完了条件:

- `transition` mode で `node` / `children` 更新に Z 演出が入る
- `prefers-reduced-motion` フォールバックがある
- `persistent` mode を限定導入できる構造が整っている
- DOM + transform ベースで動作している
- 縦レイアウトと SEO 方針が崩れていない

---

## Phase6 への引き継ぎ

Phase5 が終わると、主要機能は揃う。
次の Phase6 は、移行完了と負債整理のフェーズになる。

Phase6 でやること:

- 旧 API / 旧クラスの撤去
- 命名の統一
- `HgnTree` への正式リネーム
- テストとドキュメント整備
