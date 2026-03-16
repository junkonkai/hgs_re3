---
name: internal-node ノード内更新
overview: "`rel=\"internal-node\"` を付与したリンク（.node-content.basic 内の a および section.link-node 内の a.node-head-text）のクリック時、ツリー全体ではなくそのノード内だけで消える／表示されるアニメーションと通信・DOM 更新を行うようにする。"
todos: []
isProject: false
---

# rel="internal-node" によるノード内のみ更新の実装プラン

## 現状の整理

- **内部リンク（`rel="internal"`）**: [basic-node.ts](resources/ts/node/basic-node.ts) で `.node-content.basic a[rel="internal"]` にクリックをバインド。クリック時に `currentNode.moveNode(href)` + `this.disappearStart()` により、**ツリー全体**の disappear → 取得 → ツリー差し替え → appear が行われる。
- **link-node のヘッダリンク**: [LinkNodeMixin](resources/ts/node/mixins/link-node-mixin.ts) の `click(e)` が `this._parentInstance.clickLink(this.anchor, e)` を呼ぶ。`rel="internal"` がなくても同じ `clickLink` 経由で内部遷移している（external 以外は moveNode + disappearStart）。
- **API**: [Controller::tree()](app/Http/Controllers/Controller.php) が Ajax 時に `nodes` / `currentNodeContent` / `currentNodeTitle` 等を JSON で返し、[CurrentNode::changeNode()](resources/ts/node/current-node.ts) で `_treeContentElement.innerHTML = _nextNodeCache.nodes` などによりツリー全体を差し替えている。

## 目標挙動

1. `**rel="internal-node"` の a（.node-content.basic 内）**: クリック時は「そのノードだけ」disappear → 通信 → そのノードの DOM を取得 HTML で差し替え → そのノードだけ appear。
2. **section.link-node の div.node-head 内の a.node-head-text に `rel="internal-node"`**: 同上、その link-node（親ノード）だけを更新。

両方とも同じ `clickLink()` に流れるため、`clickLink` 内で `rel="internal-node"` を分岐すればよい。

## 実装方針

- 属性は `**rel="internal-node"**` で統一（`ref` ではなく `rel` で既存の internal/external と揃える）。
- バックエンドは「internal_node 用の 1 ノード分 HTML」を返すオプションを追加。
- フロントは「単一ノード用の disappear → fetch → DOM 差し替え → ツリー再ロード → 該当ノードのみ appear」の新フローを追加する。

---

## 1. バックエンド: 単一ノード用 HTML の返却

**対象**: [app/Http/Controllers/Controller.php](app/Http/Controllers/Controller.php)

- `tree()` 内で、Ajax 時かつ `request()->query('internal_node') == 1` のとき、既存の `renderSections()` 結果から **現在ノード 1 個分の HTML** を組み立て、JSON に `internalNodeHtml` として追加する。
- 組み立て内容: 現在ページの「1 つの section.node」として、`current-node-title` 用ヘッダ＋`current-node-content`＋`nodes` を包んだ HTML 文字列。既存の `$rendered['title']` 等と同様に `$rendered['current-node-title']`, `$rendered['current-node-content']`, `$rendered['nodes']` を使って PHP 側で文字列結合すればよい。
- 通常の `nodes` / `currentNodeContent` 等はそのまま返す（internal_node=1 でもフル応答を返してよい）。

これにより、同一 URL を `?a=1&internal_node=1` で取得すると「差し替え用の 1 ノード HTML」が `internalNodeHtml` で取れるようにする。

---

## 2. フロント: 単一ノード用の fetch とキャッシュ

**対象**: [resources/ts/node/current-node.ts](resources/ts/node/current-node.ts)、[resources/ts/node/parts/next-node-cache.ts](resources/ts/node/parts/next-node-cache.ts)

- **NextNodeCache**: `internalNodeHtml?: string` を追加（optional）。
- **CurrentNode**: 単一ノード更新用のメソッドを追加する。
  - 例: `updateSingleNode(url: string, clickedNode: NodeType): void`
  - やること:
    - `pushState` 用に URL を保持（既存の `_tmpStateData` と役割を揃えるか、単一ノード用のフラグを state に持つ）。
    - `fetch(url + (url.includes('?') ? '&' : '?') + 'a=1&internal_node=1')` で取得。
    - レスポンスの `internalNodeHtml` を受け取り、**どのノードを差し替えるか**の情報と合わせて後続処理に渡す必要がある。そのため「disappear 完了後に実行するコールバック」に `internalNodeHtml` と「差し替え対象のノード（またはその親の NodeContentTree + インデックス/id）」を渡す形が扱いやすい。
  - 既存の `moveNode` はフルツリー用のため、単一ノード用は別メソッド（上記 `updateSingleNode`）とし、内部で `nextNodeCache` は使わず、受け取った `internalNodeHtml` をその場で使う形でもよい。

---

## 3. フロント: clickLink で rel="internal-node" を分岐

**対象**: [resources/ts/node/basic-node.ts](resources/ts/node/basic-node.ts)

- **セレクタ**: `.node-content.basic` 内の `a[rel="internal-node"]` にもクリックをバインドする（既存の `a[rel="internal"]` と同様）。
- **clickLink(anchor, e)**:
  - `rel === 'external'` のときは現状どおり `location.href`。
  - `**rel === 'internal-node'`** のとき:
    - `e.preventDefault()`
    - `currentNode.updateSingleNode(anchor.href, this)` を呼ぶ。
    - `**this.disappearOnlyThisNode(callback)**` のように「このノードだけ消す」処理を開始し、完了後に `updateSingleNode` 側で fetch を実行するようにする（あるいは `updateSingleNode` の引数で「disappear 完了コールバック」を受け取り、そこで fetch する設計でもよい）。
  - 上記以外（従来の内部リンク）は現状どおり `currentNode.moveNode(anchor.href, false)` + `this.disappearStart()`。

link-node の `a.node-head-text` は既に `clickLink(this.anchor, e)` に流れるため、ここで `rel="internal-node"` を判定すれば「親ノードを更新」も同じ経路で実現できる。

---

## 4. フロント: 単一ノードの disappear / appear

**対象**: [resources/ts/node/basic-node.ts](resources/ts/node/basic-node.ts)、必要に応じて [resources/ts/node/tree-node.ts](resources/ts/node/tree-node.ts) や [resources/ts/node/parts/node-content-tree.ts](resources/ts/node/parts/node-content-tree.ts)

- **disappearOnlyThisNode(onComplete?: () => void)**  
  - 呼び出すのは「クリックされたノード」＝そのリンクを含む `section.node` に対応する BasicNode/TreeNode（LinkNode 含む）。
  - **やること**: `prepareDisappear` は呼ばない。**このノードだけ**の見た目の消滅（例: ヘッダ＋コンテンツの disappear、必要なら `_nodeContentBehind` の disappear）。接続線や他ノードには触れない。
  - 既存の `disappearContents()` / `nodeHead.disappear()` 等を流用し、アニメーション完了時に `onComplete` を呼ぶ。必要なら `_appearAnimationFunc` で「自ノードの disappear 完了」を検知する。
- **DOM 差し替えとツリー再構築**  
  - disappear 完了後、CurrentNode 側で fetch 済みの `internalNodeHtml` を使う。
  - 差し替え対象: クリックされたノードの `_nodeElement`（section.node）。その親要素に対して `replaceChild(新 section.node, 旧 section.node)` で置換。
  - 置換後、**そのツリーを保持している NodeContentTree** に対して、一度 `disposeNodes()` してから `loadNodes(parentNode)` で再構築する。これで新しい `section.node` が新しい Node インスタンスとして登録される。
  - 新しいノードは「置換した位置」のノードなので、インデックスまたは id で特定し、そのノードだけ **appear()** する（既存の `appear()` でよい）。

---

## 5. 履歴（pushState / popstate）

- 単一ノード更新時も URL を変えたい場合は、fetch 前に `history.pushState({ url, isInternalNode: true }, '', url)` のように state を積む。
- [horror-game-network.ts](resources/ts/horror-game-network.ts) の `popState` では、`state.isInternalNode` のときは「単一ノード更新の戻り」として、同じ URL で `updateSingleNode` 相当（または該当ノードだけ再取得して差し替え）を行う必要がある。実装の詳細は「差し替え対象ノードの識別」と合わせて設計する（例: state に nodeId を保持する等）。

---

## 6. ビューでの利用方法

- **.node-content.basic 内のリンク**: ノード内だけ更新したい場合は `rel="internal-node"` を付与。
- **section.link-node の a.node-head-text**: 同様に `rel="internal-node"` を付けると、その link-node 自身が「そのノード内だけ」更新される。

`rel="internal"` は従来どおりツリー全体の更新に使う。

---

## 7. 補足・注意点

- **replaceChild 後の loadNodes**: 現在の [NodeContentTree#loadNodes](resources/ts/node/parts/node-content-tree.ts) は `_contentElement.querySelectorAll(':scope > section.node')` で子を列挙している。差し替え後は 1 要素が置き換わった状態なので、`disposeNodes()` のあと `loadNodes(parentNode)` を呼べば新しい子が再度パースされる。親が CurrentNode でない場合（ツリーがネストしている場合）は、**差し替えたノードの親**が NodeContentTree を持つノードになるので、その親の `nodeContentTree` に対して dispose + loadNodes する。
- **components / CSRF**: 差し替え後の HTML にフォームやコンポーネントが含まれる場合は、既存の `setupFormEvents()` や ComponentManager の初期化を、差し替え後にそのノード以下に対してのみ実行する必要がある。全画面の `changeNode` と同様の処理を「そのノードの子だけ」にスコープするか、既存の初期化を流用できるかは実装時に確認する。
- **internalNodeHtml の構造**: バックエンドで組み立てる「1 ノード分」は、`<section class="node" id="...">` で包み、中に node-head 相当と node-content 相当（＋必要なら nodes 相当）を含め、クライアントの `replaceChild` でそのまま 1 つの `section.node` と置き換えられる形にする。

---

## 実装順序の提案

1. バックエンドで `internal_node=1` 時の `internalNodeHtml` を返すようにする。
2. BasicNode に `disappearOnlyThisNode(onComplete)` を追加し、テスト用にボタンなどで呼んで挙動を確認。
3. CurrentNode に `updateSingleNode(url, clickedNode)` を追加し、disappear 完了コールバックから fetch → replaceChild → disposeNodes → loadNodes → 新ノードの appear までをつなぐ。
4. clickLink 内で `rel="internal-node"` 分岐を入れ、.node-content.basic と link-node の両方で動作確認。
5. pushState / popstate で internal-node 用の履歴を扱う（必要なら nodeId などを state に持つ）。

これで「クリックされたリンクがあるノード内だけで消える／表示される」動きを実現できる。