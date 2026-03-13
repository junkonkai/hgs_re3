# Phase4 描画方式刷新 実装詳細

## 目的

Phase4 では、Phase3 で整理したアニメーション実行基盤の上に、**接続線描画そのものの方式見直し** を行う。

Phase3 終了時点では、

- 常時 rAF の廃止
- transform ベースの軽量化
- ノードアニメーション進行管理の整理

まではできている想定だが、接続線描画自体にはまだ次の課題が残る。

- `CurveCanvas` が Canvas 2D API を使っている
- 毎回の `clearRect`、`quadraticCurveTo`、`stroke` が残る
- `shadowBlur` が高コスト
- CSS 変数の色取得や `getBoundingClientRect()` 依存が描画コストを押し上げる

Phase4 の目的は、これを **Renderer 抽象化 + SVG 実装** に置き換えること。

---

## Phase4 の達成目標

- `CurveRenderer` インターフェースを導入する
- 既存 `CurveCanvas` を互換実装 `CanvasCurveRenderer` に寄せる
- 新実装 `SvgCurveRenderer` を導入する
- 基本の接続線描画を SVG path + `stroke-dashoffset` に置き換える
- behind 曲線も SVG ベースへ寄せる
- 色取得は CSS 変数直参照または初期キャッシュにし、毎フレーム `getComputedStyle()` を避ける
- `shadowBlur` を撤去または最小化し、SVG フィルターか CSS 側で表現する

Phase4 が終わると、**接続線の主要描画が Canvas 依存から脱却** する。

---

## Phase4 の対象ファイル

新規追加:

- `resources/ts/node/parts/renderers/curve-renderer.ts`
- `resources/ts/node/parts/renderers/svg-curve-renderer.ts`
- `resources/ts/node/parts/renderers/canvas-curve-renderer.ts`

更新対象:

- `resources/ts/node/basic-node.ts`
- `resources/ts/node/tree-node.ts`
- `resources/ts/node/parts/node-content-behind.ts`
- `resources/ts/node/parts/curve-canvas.ts`
- `resources/css/tree.css`
- 必要に応じて `resources/ts/common/util.ts`

この Phase ではまだやらないこと:

- Z軸の常時 3D 配置
- Node 本体の DOM 3D 制御
- `DepthSceneController`

---

## 基本方針

### 1. Renderer を抽象化する

ノード側は「線をどう描くか」を知らず、

- 開始点
- 終了点
- 進行率
- behind 用の複数パス

だけを `CurveRenderer` に渡す。

### 2. まずは互換実装を挟む

すぐに `CurveCanvas` を捨てるのではなく、

- `CanvasCurveRenderer`
- `SvgCurveRenderer`

の二段構えにする。

### 3. HTML は引き続き正とする

ノード本文・見出し・リンクは DOM のまま維持する。
SVG 化するのは **接続線だけ**。

---

## クラス設計

## `CurveRenderer`

### 役割

- 接続線描画の共通契約

### 新規ファイル

- `resources/ts/node/parts/renderers/curve-renderer.ts`

### インターフェース案

```ts
export interface CurveRenderer
{
    resize(): void;
    clear(): void;
    setPath(startPoint: Point, endPoint: Point): void;
    setProgress(progress: number): void;
    setGradient(startAlpha: number, endAlpha: number): void;
    drawBehindCurve(
        startPoint: Point,
        endPoint: Point,
        index: number,
        progress: number
    ): void;
    show(): void;
    hide(): void;
    dispose(): void;
}
```

### 備考

- `draw()` と `setProgress()` を分けるかどうかは実装しやすい形でよい
- Phase4 では「ノード側が Canvas を直接知らない」ことが最重要

---

## `CanvasCurveRenderer`

### 役割

- 既存 `CurveCanvas` の互換実装

### 新規ファイル

- `resources/ts/node/parts/renderers/canvas-curve-renderer.ts`

### 位置づけ

- 一時的な互換層
- SVG への移行期間だけ使う

### 方針

- 既存 `CurveCanvas` をそのままコピーするのではなく、`CurveRenderer` 契約へ寄せる
- 色取得や `shadowBlur` など、重い処理だけ先に改善可能

---

## `SvgCurveRenderer`

### 役割

- SVG + CSS アニメーションで接続線を表現する新実装

### 新規ファイル

- `resources/ts/node/parts/renderers/svg-curve-renderer.ts`

### 基本構造

```html
<svg class="node-curve">
    <defs>
        <linearGradient id="curveGrad-xxx">...</linearGradient>
        <filter id="curveGlow-xxx">...</filter>
    </defs>
    <path class="curve-path"></path>
    <path class="behind-path behind-path-0"></path>
    <path class="behind-path behind-path-1"></path>
</svg>
```

### 公開メソッド案

```ts
export class SvgCurveRenderer implements CurveRenderer
{
    public constructor(parentNode: NodeBase)
    public setPath(startPoint: Point, endPoint: Point): void
    public setProgress(progress: number): void
    public setGradient(startAlpha: number, endAlpha: number): void
    public drawBehindCurve(
        startPoint: Point,
        endPoint: Point,
        index: number,
        progress: number
    ): void
    public clear(): void
    public resize(): void
    public show(): void
    public hide(): void
    public dispose(): void
}
```

### パス生成ルール

既存の見た目を踏襲する。

- 開始点: 親ノードの中央上
- 終了点: 子ノード接続点
- 制御点: `(startPoint.x, endPoint.y)`

式:

```ts
const d = `M${sx},${sy} Q${sx},${ey} ${ex},${ey}`;
```

### アニメーション方式

#### 基本接続線

- `path.getTotalLength()`
- `stroke-dasharray`
- `stroke-dashoffset`

で進行率を表現する。

```ts
dashoffset = totalLength * (1 - progress)
```

#### behind 曲線

- 1 SVG 内に最大4本の `<path>` を持つ
- 各パスごとに `opacity` と `dashoffset` を設定

### フィルター方針

- Canvas の `shadowBlur = 10` は撤去
- SVG の `feGaussianBlur` は `stdDeviation 2-3` に抑える
- 可能なら CSS の `filter: drop-shadow()` を優先してもよい

---

## CSS 設計

## `resources/css/tree.css`

追加するクラス例:

```css
.node-curve-svg {
    position: absolute;
    inset: 0;
    overflow: visible;
    pointer-events: none;
}

.node-curve-svg .curve-path {
    transition: stroke-dashoffset 100ms linear;
}

.node-curve-svg .behind-path {
    transition: stroke-dashoffset 100ms linear, opacity 100ms linear;
}
```

### 方針

- `contain: layout style paint` を使って描画範囲を閉じる
- pointer-events は無効にする
- SVG のサイズは都度 `viewBox` か `width/height` で制御する

---

## 既存クラスの変更方針

## `BasicNode`

### 変更内容

- `_curveCanvas` を `CurveRenderer` 型へ抽象化する
- constructor で Renderer を受け取るか、Factory 経由で生成する

### フィールド案

```ts
protected _curveRenderer: CurveRenderer;
```

### 変更方針

今:

- `this._curveCanvas.appearProgress`
- `this._curveCanvas.drawCurvedLine(...)`

Phase4:

- `this._curveRenderer.setPath(...)`
- `this._curveRenderer.setProgress(...)`
- `this._curveRenderer.setGradient(...)`

### 注意点

- `CurveCanvas` 固有の `gradientStartAlpha` / `gradientEndAlpha` は Renderer 共通 API に再設計する

## `TreeNode`

### 変更内容

- 子ツリーの接続表現も `_curveRenderer` ベースに揃える
- `homewardDisappearAnimation2()` 内の再描画も Renderer に寄せる

### 備考

- Phase4 ではロジック全置換より、描画命令の行き先を変えるのが中心

## `NodeContentBehind`

### 変更内容

- `curveCanvas.drawBehindCurvedLine()` 依存を `CurveRenderer.drawBehindCurve()` に置き換える
- `getBoundingClientRect()` を毎フレーム取らないようにする

### 方針

- canvasRect 相当は `resize()` 時にキャッシュ
- behind ノード位置も再計算タイミングを限定する

---

## 色とスタイルの最適化

## CSS 変数の扱い

現在の課題:

- `getComputedStyle(document.body)` が毎フレーム呼ばれる

Phase4 の方針:

- SVG 側で `var(--node-pt-light)` を直接参照する
- もしくは Renderer 初期化時に一度だけ色を解決する

### 推奨

- 可能な限り CSS 変数を SVG / CSS 側に残し、JS 側で RGB 変換しない

## blur の扱い

現在の課題:

- Canvas `shadowBlur`
- behind-node の CSS `blur`

Phase4 の方針:

- 接続線の発光は弱い表現に留める
- behind-node の blur は継続する場合でも `will-change: filter` を検討
- 強い発光表現は避ける

---

## 移行戦略

### Step 1

- `CurveRenderer` インターフェースを追加
- `CanvasCurveRenderer` を作る
- `BasicNode` / `TreeNode` を `CurveRenderer` 依存へ寄せる

### Step 2

- `SvgCurveRenderer` を追加
- フラグや一時設定で一部ノードを SVG 描画へ切り替える

### Step 3

- 基本接続線を SVG 化
- behind 曲線も SVG 化

### Step 4

- CSS 変数参照を Renderer 側へ整理
- `shadowBlur` と不要な `getComputedStyle()` を撤去

### Step 5

- 問題がなければ `CurveCanvas` を縮退

---

## テスト観点

### 見た目

- 接続線の形状が従来と大きく変わらない
- 出現方向が「上から下、途中で右へカーブ」のまま維持される
- behind 曲線が 1-4 本で破綻しない

### パフォーマンス

- `BasicNode.draw()` の重さが減る
- `shadowBlur` 起因の GPU 負荷が下がる
- 毎フレームの色解決が消える

### 互換性

- `homewardDisappear` でも崩れない
- `children` / `node` 更新でも描画が追従する
- リサイズ後に SVG の位置ズレが出ない

---

## 完了判定

Phase4 完了条件:

- 基本接続線が `SvgCurveRenderer` で描画される
- behind 曲線も SVG へ移行できている
- `CurveCanvas` 直参照が `BasicNode` / `TreeNode` から消えている
- `getComputedStyle()` の毎フレーム呼び出しが解消されている
- 強い `shadowBlur` 依存が撤去または大幅軽減されている

---

## Phase5 への引き継ぎ

Phase4 が終わると、接続線描画は軽くなる。
次の Phase5 では、その上で **Z軸演出をどう乗せるか** を扱う。

Phase5 でやること:

- `DepthSceneController`
- `DepthEffectController`
- パターンAの Z 演出
- 必要に応じてパターンBの限定導入
