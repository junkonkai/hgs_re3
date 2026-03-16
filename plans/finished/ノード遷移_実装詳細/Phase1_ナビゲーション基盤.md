# Phase1 ナビゲーション基盤 実装詳細

## 目的

`HgnTree` ベースの新設計に移行する第一段階として、まず **遷移の意味付けを統一する基盤** を作る。

Phase 1 では次を達成する。

- 遷移スコープを `full` / `children` / `node` / `external` の4種類に統一する
- URL 更新ポリシーを `push` / `keep` / `replace` / `popstate` の独立軸として扱う
- クリック時の遷移解釈を `NavigationController` に集約する
- `CurrentNode` から fetch の責務を外す
- 既存の `internal` / `internal-node` / `external` ベースの分岐を、`data-hgn-*` ベースへ移行する足場を作る

この Phase では、**全面的なアニメーション刷新や SVG 移行までは行わない**。まずは「どう遷移するか」の責務分離を優先する。

---

## Phase1 の到達点

実装完了時の状態は以下。

- どの Node でも、`node-head` / `node-content` を問わず `<a>` があれば同じ経路で遷移を解釈できる
- `<a>` から `data-hgn-scope` / `data-hgn-url-policy` を読んで `NavigationRequest` を生成できる
- `CurrentNode` は「fetch を開始するクラス」ではなく、「取得結果を画面に適用するクラス」になる
- `external` は通常遷移、`full` / `children` / `node` は Ajax 遷移として整理される
- `pushState` するか URL を維持するかは `scope` に依存せず指定できる

---

## Phase1 の対象ファイル

新規追加:

- `resources/ts/navigation/types.ts`
- `resources/ts/navigation/navigation-controller.ts`
- `resources/ts/navigation/navigation-fetcher.ts`
- `resources/ts/navigation/navigation-state-store.ts`

更新対象:

- `resources/ts/horror-game-network.ts`
- `resources/ts/node/current-node.ts`
- `resources/ts/node/basic-node.ts`
- `resources/ts/node/tree-node.ts`
- `resources/ts/node/parts/next-node-cache.ts`
- 必要に応じて `resources/views/**/*.blade.php`

この Phase ではまだ作らないもの:

- `AnimationScheduler`
- `NodeAnimator`
- `HistoryCoordinator`
- `NodeTree.replaceChildren()`
- `NodeTree.replaceNodeById()`
- `DepthSceneController`

これらは Phase 2 以降で導入する。

---

## 型定義

## `resources/ts/navigation/types.ts`

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
```

注意:

- `children` は「CurrentNode の `node-head` と `#current-node-content` を維持し、子ツリーだけ更新する」
- `node` は「クリックされたノード自身と、その配下だけ更新する」
- `external` は Ajax を行わない

---

## HTML 属性仕様

Phase 1 では、独自遷移指定を `data-*` に寄せる。

```html
<a
    href="/games/search"
    data-hgn-scope="full"
    data-hgn-url-policy="push"
>
```

利用ルール:

- `data-hgn-scope`
  - `full`
  - `children`
  - `node`
  - `external`
- `data-hgn-url-policy`
  - `push`
  - `keep`
  - `replace`

`popstate` はユーザーが直接 HTML に書く値ではなく、履歴復元時に内部生成する。

デフォルト値:

- `data-hgn-scope` 未指定: `full`
- `data-hgn-url-policy` 未指定: `push`
- `target="_blank"` がある場合は `scope=external` を優先してよい

---

## クラス責務

## `NavigationController`

役割:

- アンカーから `NavigationRequest` を組み立てる
- `scope` ごとに分岐する
- `NavigationFetcher` と `CurrentNode` をつなぐ

公開メソッド案:

```ts
export class NavigationController
{
    public navigateFromAnchor(anchor: HTMLAnchorElement, sourceNode: NodeType): void
    public navigate(request: NavigationRequest): void
}
```

内部処理:

1. `anchor.dataset.hgnScope` と `anchor.dataset.hgnUrlPolicy` を読む
2. `sourceNode.id` を `sourceNodeId` に詰める
3. `scope === 'external'` の場合は `location.href = request.url`
4. それ以外は `NavigationFetcher.fetch(request)`
5. 取得結果を `CurrentNode.applyNavigationResult(result, request)` に渡す

Phase 1 の簡略方針:

- disappear / appear の既存制御は極力温存する
- まずは「誰が fetch を始めるか」だけを `CurrentNode` から切り離す

## `NavigationFetcher`

役割:

- Ajax リクエストの構築
- レスポンス JSON の正規化

公開メソッド案:

```ts
export class NavigationFetcher
{
    public fetch(request: NavigationRequest): Promise<NavigationResult>
}
```

クエリ付与ルール:

- 共通で `a=1`
- `scope === 'node'` のとき `internal_node=1`
- `scope === 'children'` のとき、必要なら `children_only=1`

備考:

- `children_only=1` は Phase 1 実装時にバックエンドへ追加する想定
- もしバックエンド変更を最小化したい場合は、当面 `nodes` を `currentChildrenHtml` 相当として流用してもよい

## `NavigationStateStore`

役割:

- 直近の `NavigationRequest` と `NavigationResult` を保持する
- 遷移中フラグを持つ

公開メソッド案:

```ts
export class NavigationStateStore
{
    public start(request: NavigationRequest): void
    public resolve(result: NavigationResult): void
    public clear(): void
    public get isNavigating(): boolean
}
```

備考:

- 既存の `_nextNodeCache` と `_tmpStateData` の橋渡しとして導入する
- Phase 1 では `HistoryCoordinator` までは切り出さず、最低限の一時保持だけ行う

---

## 既存クラスの変更方針

## `CurrentNode`

Phase 1 のゴール:

- `moveNode()` の中で fetch しない
- 遷移結果の適用だけ担当する

追加メソッド案:

```ts
public applyNavigationResult(
    result: NavigationResult,
    request: NavigationRequest
): void

private applyFullResult(result: NavigationResult): void

private applyChildrenResult(result: NavigationResult): void

private applyNodeResult(result: NavigationResult, request: NavigationRequest): void
```

Phase 1 での現実的な実装方針:

- `applyFullResult()`:
  - 既存 `changeNode()` 相当の処理に寄せる
- `applyChildrenResult()`:
  - `#current-node-content` と CurrentNode の見出しを触らず
  - 子ツリーの HTML だけ差し替える
- `applyNodeResult()`:
  - まだ `NodeTree.replaceNodeById()` がない場合、暫定的には `disposeNodes() -> loadNodes()` ベースでもよい
  - ただし API と責務だけは先に `node` スコープへ分ける

重要:

- Phase 1 では、**実装内部は暫定でもインターフェースは新設計に合わせる**
- つまり内部が一部古くても、呼び出し口は `NavigationRequest` / `NavigationResult` ベースにする

## `BasicNode`

変更方針:

- `clickLink()` で URL 遷移先を決めない
- `<a>` を検出して `NavigationController.navigateFromAnchor()` へ委譲する

追加メソッド案:

```ts
public getNavigableAnchors(): HTMLAnchorElement[]
```

アンカー収集対象:

- `:scope > .node-head a`
- `:scope > .node-content a`

重要:

- `node-head` のリンクを `LinkNode` 専用にしない
- どの Node でも、`<a>` があれば同じ遷移入口に乗せる

## `TreeNode`

Phase 1 での変更は最小限。

やること:

- `BasicNode` ベースのアンカー委譲方式に合わせる
- `node` スコープ用に `sourceNodeId` が取れるよう、`id` ベースの識別を安定させる

まだやらないこと:

- `replaceNodeById()` の本格導入
- `disappearSolo()` の全面再設計

## `NextNodeCache`

役割縮小:

- 既存互換のため一時的に残してよい
- ただし最終的には `NavigationStateStore` に寄せる

Phase 1 での対応:

- `currentChildrenHtml?: string`
- `internalNodeHtml?: string`

を追加してもよい

---

## バックエンド応答

Phase 1 で必要な応答項目は以下。

- `updateType`
- `url`
- `title`
- `currentNodeTitle`
- `currentNodeContent`
- `nodes`
- `currentChildrenHtml` またはそれに相当する値
- `internalNodeHtml`

推奨方針:

- 既存 `tree()` を拡張し、同一エンドポイントで返す
- クライアントは `updateType` を見て適用先を決める

例:

```json
{
  "updateType": "children",
  "url": "/games/search",
  "title": "タイトル検索",
  "nodes": "<section class=\"node\">...</section>",
  "currentChildrenHtml": "<section class=\"node\">...</section>"
}
```

---

## `HgnTree` との接続

Phase 1 では `HgnTree` を完全実装しなくてもよいが、少なくとも次の組み立てを意識する。

```ts
const currentNode = new CurrentNode(...);
const navigationStateStore = new NavigationStateStore();
const navigationFetcher = new NavigationFetcher();
const navigationController = new NavigationController(
    currentNode,
    navigationFetcher,
    navigationStateStore
);
```

`HgnTree` の責務:

- これらを生成して参照を配る
- `popstate` 時は `NavigationRequest` を内部生成して `NavigationController.navigate()` に渡す

Phase 1 では、既存 `HorrorGameNetwork` 内に暫定的に組み込んでもよい。

---

## 実装手順

### 1. 型と Controller を追加

- `resources/ts/navigation/types.ts`
- `resources/ts/navigation/navigation-controller.ts`
- `resources/ts/navigation/navigation-fetcher.ts`
- `resources/ts/navigation/navigation-state-store.ts`

まず型を固定する。

### 2. `BasicNode` のクリック処理を差し替え

- `node-head` と `node-content` の両方から `<a>` を拾う
- `NavigationController.navigateFromAnchor()` に委譲する
- `LinkNode` 前提の分岐を減らす

### 3. `CurrentNode` を「適用担当」に寄せる

- fetch 開始責務を外す
- `applyNavigationResult()` 系メソッドを追加する
- 既存 `changeNode()` ロジックは `applyFullResult()` に寄せる

### 4. バックエンドに `updateType` を追加

- `full`
- `children`
- `node`

を返せるようにする

### 5. `data-hgn-*` をビューに付与

- 最初は主要なリンクだけでよい
- 未指定時は `full` / `push` 扱いで後方互換を持たせる

### 6. `popstate` の入口だけ整える

- `urlPolicy='popstate'` を内部利用する
- 履歴詳細管理は Phase 2 に回す

---

## 実装時の暫定ルール

Phase 1 は基盤整備が目的なので、以下は暫定実装でよい。

- `children` 更新の内部実装が一度 `loadNodes()` に寄ってもよい
- `node` 更新が一時的に親ツリー全体の再ロードでもよい
- disappear / appear が一部旧実装依存でもよい

ただし、以下は Phase 1 時点で固定する。

- 外部 API は `NavigationRequest` / `NavigationResult`
- HTML 属性は `data-hgn-scope` / `data-hgn-url-policy`
- fetch の起点は `NavigationController`
- `CurrentNode` は結果適用担当

---

## 完了判定

Phase 1 完了の判定条件は次。

- `<a data-hgn-scope="full">` で全体更新できる
- `<a data-hgn-scope="children">` で CurrentNode メタを維持した子ノード更新ができる
- `<a data-hgn-scope="node">` で選択ノード更新の入口が統一される
- `<a data-hgn-scope="external">` で通常遷移できる
- 同じ `scope` でも `data-hgn-url-policy="push"` と `keep` を切り替えられる
- `node-head` 内の `<a>` でも `node-content` 内の `<a>` でも同じ制御に乗る

---

## Phase2 への引き継ぎ

Phase 1 が終わったら次に進む内容は以下。

- `NodeTree.replaceChildren()`
- `NodeTree.replaceNodeById()`
- `ScopedHydrator`
- `HistoryCoordinator`
- `sourceNodeId` を使った厳密な部分差し替え

つまり Phase 1 は、**遷移の意味と責務の分離** を先に終わらせるフェーズです。描画最適化や Z 軸強化はこのあとに乗せる。
