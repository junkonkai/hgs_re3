# コンポーネントの破棄タイミング

## 結論

コンポーネント（`LineupSearch` 含む）は **`ComponentManager.disposeComponents()`** が呼ばれたときに破棄される。

`disposeComponents()` が呼ばれるのは次の **2 箇所** のみ。

---

## 1. 全体更新時（`applyFullResult`）

**ファイル:** `resources/ts/node/current-node.ts`  
**メソッド:** `applyFullResult(result, request)`（約 539 行目）

**いつ:** ナビゲーションの `scope: 'full'` のとき（フォームの `data-child-only` が無い、または `'0'` のとき）。

**流れ:**

1. `componentManager.disposeComponents()` で全コンポーネントを破棄
2. `this.dispose()` で CurrentNode のノードツリーも破棄
3. `_currentNodeContentElement.innerHTML = result.currentNodeContent` で**現在ノードの HTML を差し替え**
4. `_treeContentElement.innerHTML = result.nodes` で**ツリー部分の HTML を差し替え**
5. `this._scopedHydrator.hydrate(this._treeContentElement, result.components)` で**コンポーネントを再初期化**

→ DOM ごと差し替えるので、破棄・再生成の不整合は出ない。

---

## 2. 子ノードのみ更新時（`applyChildrenResult`）★ ラインナップ検索でここになる

**ファイル:** `resources/ts/node/current-node.ts`  
**メソッド:** `applyChildrenResult(result, request)`（約 573 行目）

**いつ:** ナビゲーションの `scope: 'children'` のとき。  
**ラインナップ画面の検索フォームは `data-child-only="1"` のため、検索ボタン送信でここを通る。**

**流れ:**

1. **`componentManager.disposeComponents()`** で全コンポーネントを破棄（`LineupSearch` も破棄）
2. **`_currentNodeContentElement`（検索フォームがある現在ノード）の HTML は差し替えない**
3. `_treeContentElement` の子だけ `replaceChildren(result.currentChildrenHtml ?? result.nodes)` で差し替え
4. **`this._scopedHydrator.hydrate(this._treeContentElement, result.components)`** で `result.components`（`LineupSearch` 含む）を**再初期化**

→ **詳細検索を開いた状態で検索すると:**

- フォームがある **current-node-content の DOM はそのまま**（`#advanced-search-wrapper` に `.open` が付いたまま）
- その時点で **LineupSearch は破棄され、同じ DOM に対して新しい LineupSearch が 1 つ作られる**
- 新しいインスタンスは **`_isOpen = false`** で初期化され、**「開く」ラベル・▽** になる
- 結果として **見た目は開いたまま、トグルだけ「開く」** という表示崩れになる

---

## 補足: ノード 1 個だけ差し替え（`applyNodeResult`）

**メソッド:** `applyNodeResult(result, request)`（約 606 行目）

- **`disposeComponents()` は呼ばれない**
- 対象ノードだけ `replaceNodeById` で差し替え、そのノードに対して `hydrate` するだけ。

---

## まとめ

| 操作                     | 破棄のタイミング           | current-node-content |
|--------------------------|----------------------------|-----------------------|
| 検索ボタン（ラインナップ） | `applyChildrenResult` の先頭 | 差し替えず            |
| 通常のページ遷移（full）  | `applyFullResult` の先頭    | 差し替える            |
| ノード 1 個の差し替え     | 破棄しない                 | 対象外                |

**表示崩れの原因:**  
「詳細検索を開いた状態で検索」→ `applyChildrenResult` で `disposeComponents()` により LineupSearch が破棄されるが、フォームの DOM は差し替えず、続けて `hydrate` で新しい LineupSearch が同じ DOM に紐づく。DOM には `.open` が残っているのに、新インスタンスは閉じている前提で初期化されるため不整合が起きる。
