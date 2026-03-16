# Phase2 差分更新と履歴管理 実装詳細

## 目的

Phase2 では、Phase1 で導入したナビゲーション基盤の上に、**差分更新を正式導入** する。

Phase1 の時点では、遷移スコープの統一と `NavigationController` への責務移譲はできているが、内部実装にはまだ暫定処理が残っている。

代表例:

- `children` 更新でもツリー全体の再構築に寄りやすい
- `node` 更新でも対象ノード単体ではなく親ツリー全体再読込に寄りやすい
- `popstate` は `isChildOnly` ベースの簡易分岐で、`sourceNodeId` を持たない
- DOM 差し替え後の初期化が全体志向で、部分初期化が弱い

Phase2 の目的は、これらを「正式な部分更新」と「復元可能な履歴管理」に置き換えること。

---

## Phase2 の達成目標

- `NodeContentTree.replaceChildren()` を導入し、CurrentNode 配下の子ノード群だけを正式に差し替えられる
- `NodeContentTree.replaceNodeById()` を導入し、選択ノード自身とその子ツリーだけを正式に差し替えられる
- `ScopedHydrator` を導入し、差し替えた範囲だけフォーム・コンポーネント初期化できる
- `HistoryCoordinator` を導入し、`scope` と `sourceNodeId` を履歴に保存・復元できる
- `popstate` 時に `full` / `children` / `node` を区別して再現できる

Phase2 が終わると、`children` と `node` は「内部は暫定的に全体再読込」ではなく、**更新対象だけを差し替える正式機能** になる。

---

## Phase2 の対象ファイル

新規追加:

- `resources/ts/navigation/history-coordinator.ts`
- `resources/ts/hydrate/scoped-hydrator.ts`

更新対象:

- `resources/ts/navigation/types.ts`
- `resources/ts/navigation/navigation-controller.ts`
- `resources/ts/navigation/navigation-fetcher.ts`
- `resources/ts/navigation/navigation-state-store.ts`
- `resources/ts/horror-game-network.ts`
- `resources/ts/node/current-node.ts`
- `resources/ts/node/parts/node-content-tree.ts`
- `resources/ts/node/basic-node.ts`
- `resources/ts/node/tree-node.ts`
- `resources/ts/node/parts/next-node-cache.ts`
- `app/Http/Controllers/Controller.php`

まだやらないこと:

- `AnimationScheduler`
- `NodeAnimator`
- `CurveRenderer`
- `DepthSceneController`
- SVG 移行

---

## Phase2 の前提整理

現状の Phase1 実装は、次の構造になっている。

- `NavigationController` が `NavigationRequest` を受ける
- `NavigationFetcher` が `updateType` を含む JSON を返す
- `CurrentNode` が `NextNodeCache` または `applyNodeResult()` で画面適用する
- `HorrorGameNetwork.popState()` は `isChildOnly` だけで `full` / `children` を分岐する

つまり、**入口は新設計だが、出口の DOM 更新は旧設計がまだ混在している**。

Phase2 では、これを以下に置き換える。

- 全体更新: `applyFullResult()`
- 子ノード全更新: `replaceChildren()`
- 選択ノード更新: `replaceNodeById()`
- 履歴復元: `HistoryCoordinator` 経由

---

## 型の拡張

## `resources/ts/navigation/types.ts`

Phase2 では履歴復元用の情報を正式に持たせる。

```ts
export type NavigationScope = 'full' | 'children' | 'node' | 'external';

export type UrlPolicy = 'push' | 'keep' | 'replace' | 'popstate';

export type NavigationRequest = {
    url: string;
    scope: NavigationScope;
    urlPolicy: UrlPolicy;
    sourceNodeId?: string;
};

export type NavigationResult = {
    updateType: 'full' | 'children' | 'node' | 'external';
    url: string;
    title: string;
    currentNodeTitle?: string;
    currentNodeContent?: string;
    nodes?: string;
    currentChildrenHtml?: string;
    internalNodeHtml?: string;
    targetNodeId?: string;
    colorState?: string;
    csrfToken?: string;
    components?: { [key: string]: any | null };
};

export type NavigationHistoryState = {
    url: string;
    scope: 'full' | 'children' | 'node';
    urlPolicy: 'push' | 'replace' | 'popstate';
    sourceNodeId?: string;
};
```

重要:

- `isChildOnly` は廃止方向
- 履歴 state には `scope` と `sourceNodeId` を正式に保存する

---

## クラス設計

## `HistoryCoordinator`

### 役割

- `pushState` / `replaceState` / `popstate` の一元管理
- `NavigationRequest` から履歴 state を生成
- `PopStateEvent` から `NavigationRequest` を復元

### 新規ファイル

- `resources/ts/navigation/history-coordinator.ts`

### 公開メソッド案

```ts
export class HistoryCoordinator
{
    public push(request: NavigationRequest): void
    public replace(request: NavigationRequest): void
    public createRequestFromPopState(state: NavigationHistoryState): NavigationRequest
}
```

### 仕様

- `urlPolicy === 'push'` の場合は `pushState`
- `urlPolicy === 'replace'` の場合は `replaceState`
- `urlPolicy === 'keep'` の場合は履歴を操作しない
- `popstate` 時は `createRequestFromPopState()` で `urlPolicy: 'popstate'` を持つ request を返す

### Phase2 で解消する課題

現状の `HorrorGameNetwork.popState()` は、

- `isChildOnly === true` なら `children`
- それ以外は `full`

という判定しかできず、`node` の復元ができない。

Phase2 では `scope` と `sourceNodeId` をそのまま保存することで、

- `children` の戻る
- `node` の戻る

を区別できるようにする。

---

## `ScopedHydrator`

### 役割

- 差し替えた DOM 範囲だけを再初期化する
- 全画面向けの `setupFormEvents()` と `ComponentManager.initializeComponents()` を、部分更新に対応させる

### 新規ファイル

- `resources/ts/hydrate/scoped-hydrator.ts`

### 公開メソッド案

```ts
export class ScopedHydrator
{
    public hydrate(root: HTMLElement, components?: { [key: string]: any | null }): void
    public dispose(root?: HTMLElement): void
}
```

### 対象

- `form`
- `a[data-hgn-scope]`
- コンポーネント初期化対象

### Phase2 での目的

現在は、DOM 差し替え後に全体初期化に寄りやすい。
これを「差し替えたノードの配下だけ初期化」に変える。

---

## `NodeContentTree`

Phase2 の中核クラス。

### 追加メソッド 1: `replaceChildren()`

```ts
public replaceChildren(html: string): NodeType[]
```

### 役割

- CurrentNode 直下の子ノード群を全部差し替える
- 旧ノードを `dispose()`
- 新しい `section.node` 群を生成
- `_nodes` を再構築
- 新しいノード配列を返す

### 実装手順

1. `disposeNodes()`
2. `_contentElement.innerHTML = html`
3. `loadNodes(parentNode)`
4. `resizeConnectionLine(...)`
5. `_nodes` を返す

### 備考

- `children` 更新では `#current-node-content` と CurrentNode の `node-head` を触らない
- `nodes` が未設定なら `currentChildrenHtml` を優先する

### 追加メソッド 2: `replaceNodeById()`

```ts
public replaceNodeById(nodeId: string, html: string): NodeType | null
```

### 役割

- 対象 node id を持つ 1 ノードを差し替える
- 置換後の新ノードインスタンスを返す

### 実装手順

1. `_nodes` から `nodeId` 一致のノードを探す
2. 見つからなければ子 `TreeNode` に再帰委譲
3. 見つかったら
   - 対象ノードを `dispose()`
   - `section.node` 1個を `replaceChild`
   - 新しい要素から `createNodeFromElement()`
   - `_nodes` の同じ index に差し替え
4. `resizeConnectionLine(...)`
5. 新ノードを返す

### Phase2 で解消する課題

現在は `node` 更新時でも、ノード単体差し替えが暫定処理になりやすい。
Phase2 では、このメソッドを正式な唯一ルートにする。

### 補足

現在 `createNodeFromElement()` は `LinkNode` 分岐をまだ持っているが、今後は整理対象。
Phase2 では全面廃止まで行かなくてもよいが、`<a>` ベースの遷移入口と矛盾しないよう責務を狭める。

---

## `CurrentNode`

Phase2 では、「結果適用クラス」としての責務をさらに明確にする。

### 追加・整理メソッド

```ts
public applyNavigationResult(
    result: NavigationResult,
    request: NavigationRequest
): void

private applyFullResult(result: NavigationResult, request: NavigationRequest): void

private applyChildrenResult(result: NavigationResult, request: NavigationRequest): void

public applyNodeResult(result: NavigationResult, request: NavigationRequest): void
```

### `applyChildrenResult()`

役割:

- CurrentNode タイトルと `#current-node-content` は維持
- 直下の子ツリーだけ差し替える

処理:

1. `NodeContentTree.replaceChildren(html)` を呼ぶ
2. 返されたノード群に `appear()` をかける
3. `ScopedHydrator.hydrate(treeContentElement, result.components)` を呼ぶ

### `applyNodeResult()`

役割:

- `request.sourceNodeId` または `result.targetNodeId` を使って差し替え対象を確定

処理:

1. 対象 id を決定
2. `NodeContentTree.replaceNodeById(targetId, html)` を呼ぶ
3. 新ノードを `appear()` させる
4. 差し替えたノード要素だけ `ScopedHydrator.hydrate(nodeElement, result.components)` を呼ぶ

### `applyFullResult()`

Phase2 では大きな責務変更はない。
ただし `NextNodeCache` 依存を少しずつ減らす。

方針:

- `changeNode()` の中に埋まっている適用処理を、明示的なメソッドへ寄せる
- `NextNodeCache` は移行期間の互換層に留める

---

## `NavigationController`

Phase2 では `HistoryCoordinator` と `ScopedHydrator` を前提に責務を調整する。

### 変更点

- request 実行前に `HistoryCoordinator` を呼ぶ
- `node` 更新時、`request.sourceNodeId` を必須級情報として扱う
- `children` / `node` の結果適用を `CurrentNode` の正式差分 API に寄せる

### 想定フロー

#### `scope = full`

1. request 開始
2. 履歴更新
3. disappear
4. fetch
5. `applyFullResult()`
6. appear

#### `scope = children`

1. request 開始
2. 履歴更新
3. CurrentNode の子ツリーだけ disappear
4. fetch
5. `applyChildrenResult()`
6. 子ツリーのみ appear

#### `scope = node`

1. request 開始
2. 履歴更新
3. 対象ノードだけ disappear
4. fetch
5. `applyNodeResult()`
6. 対象ノードだけ appear

---

## `NavigationFetcher`

Phase2 では fetch 自体は大きく変わらないが、応答の期待値が明確になる。

### 期待する応答

#### `full`

- `currentNodeTitle`
- `currentNodeContent`
- `nodes`

#### `children`

- `currentChildrenHtml` または `nodes`

#### `node`

- `internalNodeHtml`
- `targetNodeId`

### 推奨

- `children` の場合は `currentChildrenHtml` を明示的に返す
- `node` の場合は `targetNodeId` を返す

これにより、クライアント側の「どこを差し替えるか」の曖昧さを減らせる。

---

## バックエンドの詳細

## `app/Http/Controllers/Controller.php`

Phase2 では、`tree()` の応答に差分更新向けの意味を正式に持たせる。

### 対応内容

- `children_only=1` のとき:
  - `updateType: 'children'`
  - `currentChildrenHtml`
- `internal_node=1` のとき:
  - `updateType: 'node'`
  - `internalNodeHtml`
  - `targetNodeId`
- それ以外:
  - `updateType: 'full'`

### `targetNodeId` の決め方

原則:

- クライアント request の `sourceNodeId` と一致する id を返す

もしサーバー側で「クリックされたノードが別 URL のノードへ置き換わる」仕様にしたい場合:

- 返却側で明示的に新しい `targetNodeId` を返す

ただし Phase2 時点では、**差し替え対象は基本的にクリック元ノード id と同じ** とする方が単純。

---

## 履歴管理の詳細

## 保存する state

```ts
{
    url: "/games/search",
    scope: "node",
    urlPolicy: "push",
    sourceNodeId: "node-game-search"
}
```

### 保存ルール

- `push`: `pushState`
- `replace`: `replaceState`
- `keep`: 保存しない

### 復元ルール

- `popstate` 発火
- `HistoryCoordinator.createRequestFromPopState()` で request 化
- `urlPolicy` は内部的に `popstate`
- `NavigationController.navigate(request)` を呼ぶ

### Phase2 で改善される点

現状のように「子ノード更新かどうか」だけではなく、

- どの scope だったか
- どのノード起点だったか

まで復元できる。

---

## 実装手順

### 1. `HistoryCoordinator` を追加

- state 生成
- `push` / `replace`
- `popstate` 復元

### 2. `types.ts` に `NavigationHistoryState` を追加

- `isChildOnly` 依存を段階的にやめる

### 3. `NodeContentTree.replaceChildren()` を実装

- `children` 更新を正式化

### 4. `NodeContentTree.replaceNodeById()` を実装

- `node` 更新を正式化

### 5. `ScopedHydrator` を追加

- 差し替え範囲だけ初期化

### 6. `CurrentNode.applyChildrenResult()` / `applyNodeResult()` を正式差分更新へ置換

- 暫定的な全体再ロード処理を削る

### 7. `NavigationController` に `HistoryCoordinator` を組み込む

- request 実行前の履歴更新

### 8. `HorrorGameNetwork.popState()` を置換

- 直接判定をやめ、`HistoryCoordinator` 経由にする

### 9. バックエンド応答を確定

- `currentChildrenHtml`
- `internalNodeHtml`
- `targetNodeId`

---

## 暫定互換の扱い

Phase2 中は、移行のために以下を一時的に残してよい。

- `NextNodeCache`
- `isChildOnly`
- `setTmpStateData()`

ただし、方針としては以下。

- `children` / `node` は新差分 API を優先
- `full` のみ一時的に旧 `changeNode()` と共存可

つまり Phase2 は、**差分更新だけ先に新設計へ完全移行し、full 更新の旧ロジックは最後に整理する** フェーズでもある。

---

## 完了判定

Phase2 完了条件:

- `children` 更新が `replaceChildren()` 経由で動く
- `node` 更新が `replaceNodeById()` 経由で動く
- 差し替え後の初期化が `ScopedHydrator` によって範囲限定される
- `pushState` された `node` 更新を `popstate` で戻せる
- `children` 更新と `node` 更新で、全体再ロードせずに対象範囲のみ差し替わる

---

## Phase3 への引き継ぎ

Phase2 が終わると、更新対象と履歴は整理される。
次の Phase3 では、これを前提にアニメーション実行基盤を置き換える。

Phase3 でやること:

- `AnimationScheduler`
- `NodeAnimator`
- 常時 rAF 停止
- transform ベース最適化

つまり Phase2 は、**「どこを更新するか」を正しく定義するフェーズ** であり、Phase3 は **「どう軽く動かすか」を整理するフェーズ** になる。
