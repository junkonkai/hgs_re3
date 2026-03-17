# Phase6 移行完了と負債整理 実装詳細

## 目的

Phase6 は、Phase1-5 で段階導入してきた新設計を **正式な標準構成として固定するフェーズ** である。

この段階では主要機能は揃っている想定だが、移行途中の互換コードや旧責務がまだ残っている可能性が高い。

例:

- `HorrorGameNetwork` と `HgnTree` の命名揺れ
- `NextNodeCache` の互換用途残存
- `LinkNode` や旧 `rel` 分岐の残骸
- `window as any).hgn` 参照
- 旧 `changeNode()` / `_appearAnimationFunc` 依存の一部残り

Phase6 の目的は、こうした移行負債を整理し、**新アーキテクチャを今後の開発基盤として固定すること**。

---

## Phase6 の達成目標

- 最上位クラス名を正式に `HgnTree` に統一する
- `HorrorGameNetwork` 名義の暫定互換コードを撤去またはラップ層に縮退する
- `NextNodeCache` を廃止または full 更新専用の最小互換層に縮小する
- `LinkNode` 依存を整理し、アンカー駆動へ統一する
- `window as any).hgn` のようなグローバル依存を減らす
- 各 Phase で追加したクラスの責務境界を最終確定する
- 最低限のテスト・確認観点・運用ルールをドキュメント化する

---

## Phase6 の対象ファイル

更新対象:

- `resources/ts/horror-game-network.ts`
- `resources/ts/**/*`
- `resources/css/tree.css`
- `app/Http/Controllers/Controller.php`
- `resources/views/**/*.blade.php`
- `plans/ノード遷移_統合クラス設計書.md`
- `plans/ノード遷移_実装詳細/*.md`

必要に応じて削除候補:

- `resources/ts/node/link-node.ts`
- `resources/ts/node/mixins/link-node-mixin.ts`
- `resources/ts/node/parts/next-node-cache.ts`

---

## 命名統一

## `HgnTree`

Phase6 では、最上位クラス名を正式に `HgnTree` に統一する。

### 方針

- 既存 `HorrorGameNetwork` は最終的にリネームする
- 互換期間が必要なら、一時的に re-export で逃がす

### 例

```ts
export class HgnTree
{
}
```

必要なら互換:

```ts
export { HgnTree as HorrorGameNetwork };
```

ただし、この互換 export も最終的には削除したい。

---

## 廃止・縮退対象

## `NextNodeCache`

### 現状

- Phase1 / Phase2 で旧 full 更新互換の橋渡しに使っている可能性がある

### Phase6 方針

- `NavigationResult` から直接 `CurrentNode.apply*Result()` へ渡せるなら廃止
- 廃止できない場合も、役割を full 更新専用に限定する

### 目標

- 「一時キャッシュにまとめてから適用する」という中間層を減らす

## `LinkNode`

### 方針

- `node-head` の `<a>` を扱うためだけの特別クラスであれば廃止対象
- どの Node でも `<a>` があれば同じ遷移経路に乗る構成へ揃える

### 注意

- `LinkNode` が見た目やレイアウト差分も持っているなら、責務を分割してから整理する

## `rel` ベースの独自制御

### 方針

- `data-hgn-scope`
- `data-hgn-url-policy`

へ統一する。

`rel="external noopener noreferrer"` など標準用途だけ残す。

---

## グローバル依存の整理

## `(window as any).hgn`

### 現状課題

- timestamp
- disappearSpeedRate
- グローバル参照

が複数ファイルに分散している可能性がある。

### Phase6 方針

- 依存注入できるものは DI に寄せる
- Scheduler / Controller / Scene から明示参照する

### 例

- `timestamp` は `AnimationScheduler`
- `navigationController` は `HgnTree`
- `disappearSpeedRate` は専用 service または `HgnTree`

---

## 責務境界の最終整理

Phase6 で確定させる責務は次。

## `HgnTree`

- アプリのルート
- controller / scheduler / scene の組み立て
- popstate, resize, 起動処理

## `NavigationController`

- 遷移要求の解釈
- fetch と履歴と scene 適用の調停

## `CurrentNode`

- scene の適用
- full / children / node の更新反映

## `NodeContentTree`

- 子ノード管理
- 差分更新

## `ScopedHydrator`

- 差し替え範囲だけ初期化

## `AnimationScheduler`

- 実行タイミング管理

## `NodeAnimator`

- ノード単位の進行管理

## `CurveRenderer`

- 接続線描画

## `DepthSceneController` / `DepthEffectController`

- Z軸演出

この境界を README または設計書に固定しておく。

---

## テストと確認

Phase6 では、コード整理だけでなく確認項目も固定する。

## 確認観点

### 遷移

- `full`
- `children`
- `node`
- `external`

の4種が意図通り動く

### URL ポリシー

- `push`
- `keep`
- `replace`
- `popstate`

が期待通り動く

### 差分更新

- 全体再ロードせずに対象範囲だけ差し替わる

### アニメーション

- 静止時に rAF が止まる
- 接続線と free-point が transform ベースで動く

### Z軸

- `transition`
- `persistent`（導入済みなら）
- `prefers-reduced-motion`

### SEO

- 本文とリンクが DOM に残る

---

## ドキュメント整理

Phase6 では、散らばった設計書の役割も整理する。

推奨整理:

- `ノード遷移_統合クラス設計書.md`
  - 全体アーキテクチャ
- `ノード遷移_実装詳細/Phase1_*.md` ～ `Phase6_*.md`
  - 実装順序
- パフォーマンス改善案
  - 補足資料として残す
- Z軸相談資料
  - コンセプトの背景資料として残す

---

## 実装手順

### Step 1

- `HorrorGameNetwork` → `HgnTree` の正式統一方針を決める

### Step 2

- 互換コード一覧を洗い出す
  - `NextNodeCache`
  - `LinkNode`
  - `rel` 分岐
  - `window.hgn`

### Step 3

- 廃止できるものから削る

### Step 4

- 型・import・命名を統一する

### Step 5

- 設計書と実装の差分を埋める

### Step 6

- 運用用の確認観点を残す

---

## 完了判定

Phase6 完了条件:

- 主要命名が `HgnTree` ベースに統一されている
- 移行用互換コードが最小限まで減っている
- `full` / `children` / `node` / `external` と URL policy が実装・設計とも一致している
- Scheduler / Renderer / Depth 系の責務が固定されている
- 設計書と実装の大きなズレがなくなっている

---

## 最終状態のイメージ

Phase6 完了後、この仕組みは次のように見える。

- `HgnTree` がアプリ全体を起動する
- `NavigationController` が遷移を解釈する
- `CurrentNode` / `NodeContentTree` が必要範囲だけ差し替える
- `ScopedHydrator` が差し替え範囲だけ初期化する
- `AnimationScheduler` / `NodeAnimator` が必要なときだけアニメーションを回す
- `CurveRenderer` が軽量に接続線を描く
- `DepthSceneController` が必要に応じて奥行きを与える

この状態になれば、今後の追加機能は「どこに書くべきか」がかなり明確になる。
