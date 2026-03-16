# rel="internal-node" 対応のための根本設計変更案（別プラン）

このドキュメントは、「ノード内だけ更新」を**現行のクラス設計の延長で対応する**のではなく、**更新モデル・アニメーション・API を整理し直す**案を別プランとしてまとめたものです。  
先行プラン（現行設計の延長で internal-node を追加する案）と比較し、中長期でどちらを採用するか判断するための材料にしてください。

---

## 現行設計で「単一ノード更新」が重くなる理由

- **更新の単位が「ツリー全体」に固定されている**  
  `moveNode` → `NextNodeCache` → `changeNode()` の流れは「ツリー全体の HTML 差し替え + 全ノードの dispose / loadNodes」のみを想定している。単一ノード更新は「例外パス」として別メソッド・別レスポンス項目で足す形になりやすい。
- **アニメーションが「帰路（homeward）」に強く紐づいている**  
  `prepareDisappear(homewardNode)` が「どのノードを起点にツリーを消すか」を決め、接続線の長さや他ノードの disappear がそれに依存している。「このノードだけ消す」は別種のアニメーションだが、現状は同じ prepareDisappear / homeward の延長で扱いづらい。
- **NodeContentTree が「全子ノードの一括再構築」だけを持つ**  
  `loadNodes()` は常に「直下の section.node を全部列挙してインスタンス化」。1 ノードだけ差し替える場合、現状は「dispose 全部 → 1 要素だけ replaceChild → 再度 loadNodes」となり、無駄が多く、差し替え対象の「どのノードか」の管理も分散しがち。
- **ナビゲーションの入口が分散している**  
  `clickLink`（BasicNode）と LinkNodeMixin の `click` の両方で「内部遷移か・外部か」を判定し、CurrentNode の `moveNode` 等を呼んでいる。「更新スコープ（全体かノードか）」の決定がクリック側に埋もれ、CurrentNode 側は「フルツリー用」の API しか持たない。

これらを解消するために、以下では「更新モデル」「アニメーション」「ツリーの部分更新」「ナビゲーション入口」を整理する根本案を示します。

---

## 1. 更新モデルの抽象化（UpdateScope / NavigationResult）

**目的**: 「フルツリー更新」と「単一ノード更新」を、同じパイプラインで扱える形にする。

- **更新スコープの型**（例）  
  `type UpdateScope = 'full' | 'node'`  
  - `full`: 従来どおりツリー全体 + カレントノード内容の差し替え。  
  - `node`: 指定した 1 ノードの HTML だけ差し替え。

- **ナビゲーション要求**（例）  
  `NavigationRequest { url: string, scope: UpdateScope, targetNodeId?: string }`  
  - リンクの `rel` から決定: `rel="internal"` → `scope: 'full'`, `rel="internal-node"` → `scope: 'node'`, `targetNodeId` はクリックされたノードの id。

- **サーバー応答の統一**  
  現行の `nodes` / `currentNodeContent` に加え、**常に**「どう更新するか」を表す形にする。  
  例: `{ updateType: 'full' | 'node', nodes?: string, currentNodeContent?: string, internalNodeHtml?: string, ... }`  
  - `updateType: 'full'`: 既存と同様に `nodes` / `currentNodeContent` 等でフル更新。  
  - `updateType: 'node'`: `internalNodeHtml`（と必要なら `targetNodeId`）で 1 ノード分だけ返す。  
  あるいは、`updateType` を省略した場合は従来互換の「フル」とみなす。

- **CurrentNode 側の適用**  
  - 単一メソッド `applyNavigationResult(data)` を設け、`data.updateType` に応じて  
    - フル: 既存の `changeNode()` 相当（ツリー差し替え + loadNodes）。  
    - ノード: 後述の NodeContentTree の「1 ノード差し替え」を呼ぶ。  
  - これにより「どこで分岐するか」が CurrentNode の一か所にまとまり、`moveNode` と「単一ノード用の別メソッド」の二重化を避けられる。

---

## 2. アニメーションの責務分離（範囲 vs 対象）

**目的**: 「何を更新するか」と「どの範囲をアニメーションするか」を一致させ、単一ノード用のアニメーションを first-class にする。

- **アニメーション範囲の型**（例）  
  `type DisappearScope = 'full' | 'node'`  
  - `full`: 従来の帰路（homeward）に沿ったツリー全体の disappear。  
  - `node`: クリックされたノードだけの disappear（接続線・他ノードは触らない）。

- **BasicNode / TreeNode 側**  
  - `disappear(scope: DisappearScope)` のようなオーバーロード、または  
  - `disappearFull()`（従来の prepareDisappear 経由の帰路）と `disappearSolo(onComplete?)`（そのノードだけ）を分ける。  
  単一ノード更新時は `disappearSolo` のみを呼び、完了コールバックで fetch 結果を適用する。

- **NodeContentTree**  
  - `disappear(homewardNode?)` のまま、`homewardNode == null` のときは従来どおり全ノード disappear。  
  - 単一ノード更新時は **NodeContentTree の disappear は呼ばない**。クリックされたノードの `disappearSolo` だけが走る。  
  これにより「帰路」と「ノード単体」のアニメーションがコード上も分離する。

- **出現側**  
  - フル更新: 既存どおり `appear()` の連鎖。  
  - 単一ノード: 差し替え後にそのノードだけ `appear()` を呼ぶ。  
  「どのノードが新規に出現したか」は、後述のツリーの部分更新が返す「新ノード参照」で分かるようにする。

---

## 3. NodeContentTree の部分更新（replaceNodeById）

**目的**: 1 ノードだけ差し替えたあと、ツリー全体を dispose / loadNodes し直さずに、そのノードだけ再構築する。

- **新メソッド**（例）  
  `replaceNodeById(nodeId: string, newHtml: string): NodeType | null`  
  - `nodeId` に該当するノードを `_nodes` から検索。  
  - 該当ノードを `dispose()`。  
  - そのノードの `_nodeElement`（section.node）の親に対して、`newHtml` から生成した要素で `replaceChild`。  
  - 新しい `section.node` に対して、既存の `loadNodes` と同様の「1 要素だけノード種別判定して new LinkNode / TreeNode / ...」を行う。  
  - 生成したノードを `_nodes` の同じインデックスに挿入（または `_nodes` を再構築するなら、その 1 ノード分だけ追加）。  
  - 戻り値は新ノード。呼び出し側でこのノードに `appear()` をかける。

- **親が TreeNode の場合**  
  - 差し替え対象が CurrentNode の直下でない場合（例: ツリーの 2 階層目）、その親が持つ `NodeContentTree` に対して `replaceNodeById` を呼ぶ必要がある。  
  - クリックされたノードから「自分を直接の子として持つ NodeContentTree」をたどれるようにする（例: `node.parentNode.nodeContentTree` が自分を子に持つような構造）。  
  - または、CurrentNode から `getNodeById` でクリックされたノードを取得し、そのノードの `parentNode` が TreeNode なら `parentNode.nodeContentTree.replaceNodeById(id, html)` を呼ぶ設計にする。

これにより、「単一ノード更新のたびに disposeNodes + loadNodes で全体をやり直す」必要がなく、差分だけ更新する形にできる。

---

## 4. ナビゲーション入口の一元化

**目的**: リンククリック時の「内部 / 外部 / フル / ノード」の判定を一か所にまとめ、CurrentNode には「スコープ付きのナビゲーション要求」だけ渡す。

- **単一のハンドラ**（例）  
  `handleLinkClick(anchor: HTMLAnchorElement, e: MouseEvent, sourceNode: NodeType)`  
  - `rel === 'external'`: 通常の遷移。  
  - `rel === 'internal'`: `NavigationRequest { url, scope: 'full' }` を組み立て、CurrentNode に渡す。  
  - `rel === 'internal-node'`: `NavigationRequest { url, scope: 'node', targetNodeId: sourceNode.id }` を組み立て、CurrentNode に渡す。  
  - `rel` がない場合のデフォルト（例: link-node の a.node-head-text）: 現状どおり内部遷移とするなら `scope: 'full'`。  
  - 実際の fetch は CurrentNode が `NavigationRequest` に基づき行い、`scope` に応じてクエリに `internal_node=1` を付与するかどうかを決める。

- **BasicNode / LinkNodeMixin**  
  - クリック時は「どのノードからクリックされたか」と anchor だけを渡し、`handleLinkClick(anchor, e, this)` を呼ぶ。  
  - 分岐（internal / internal-node / external）はすべてこのハンドラ内に集約する。

- **CurrentNode**  
  - `navigate(request: NavigationRequest)` のような API を 1 本用意。  
  - 内部で `request.scope` に応じてアニメーション（full なら prepareDisappear + 既存 disappear、node ならそのノードの disappearSolo）と、fetch 後の `applyNavigationResult` に渡すデータを切り替える。

これで、rel の解釈と更新スコープの決定が一か所になり、今後の rel 拡張（例: 別種の部分更新）も追加しやすくなる。

---

## 5. バックエンドの応答形の整理

**目的**: 「フル」と「ノード」を同じエンドポイントで扱い、クライアントが `updateType` で分岐するだけにできるようにする。

- **既存の tree() の拡張**  
  - リクエストに `internal_node=1` があれば、従来の `nodes` / `currentNodeContent` に加え、`updateType: 'node'` と `internalNodeHtml` を返す（あるいは常に `updateType` を持たせ、通常時は `'full'`）。  
  - クライアントは 1 回の fetch で受け取った JSON をそのまま `applyNavigationResult(data)` に渡し、`data.updateType` でフルかノードかを判定する。

- **単一ノード用 HTML の内容**  
  - 先行プランと同様、現在ページの「1 ノード分」を表す HTML（例: section.node 1 個ぶん）をサーバーで組み立て、`internalNodeHtml` に格納する。

---

## 6. クラス・ファイルの役割整理（イメージ）

- **HorrorGameNetwork / CurrentNode**  
  - ナビゲーションの起点: `navigate(request)` を受け、スコープに応じたアニメーションと fetch、`applyNavigationResult` の呼び出しを行う。  
  - `moveNode` は内部で `navigate({ url, scope: 'full', ... })` に寄せるか、互換のため残しつつ中で navigate を呼んでもよい。

- **NodeContentTree**  
  - `loadNodes()`: 従来どおり全子ノードの初期化。  
  - `replaceNodeById(nodeId, newHtml)`: 1 ノードの差し替えと再インスタンス化、戻り値で新ノードを返す。

- **BasicNode / TreeNode**  
  - `disappearFull()`: 従来の `disappearStart` → prepareDisappear 経由の帰路の disappear。  
  - `disappearSolo(onComplete?)`: そのノードだけの disappear、完了時に onComplete。

- **ナビゲーション層**（新規 or Util）  
  - `handleLinkClick(anchor, e, sourceNode)`: rel の解釈と `NavigationRequest` の組み立て、CurrentNode.navigate の呼び出し。

---

## 7. この案の利点・トレードオフ

- **利点**  
  - フル更新と単一ノード更新が同じ「ナビゲーション結果の適用」の上に乗り、分岐が明確になる。  
  - 単一ノード更新時にツリー全体を dispose / loadNodes し直さず、差分更新にできる。  
  - アニメーションの「範囲」（full vs node）が型とメソッドで分離され、将来の拡張（例: 複数ノードだけ更新）も考えやすい。  
  - リンクの rel 解釈が一か所にまとまり、rel を増やしてもハンドラの拡張だけで済む。

- **トレードオフ**  
  - 変更範囲が大きい（CurrentNode、NodeContentTree、BasicNode/TreeNode、リンクバインド箇所、バックエンドの応答形）。  
  - 既存の「帰路」アニメーションや changeChildNodes 等との整合をとる必要がある。  
  - 実装順序としては、まず「replaceNodeById」「disappearSolo」「applyNavigationResult の分岐」を足し、その上で handleLinkClick と navigate を整える形が現実的。

---

## 8. 先行プランとの使い分け

- **先行プラン（現行の延長）**: 変更を最小限にし、`rel="internal-node"` と単一ノード用 HTML を「追加」で実装する。短期でリリードしたい場合向け。  
- **本プラン（根本設計変更）**: 上記のように更新モデル・アニメーション・ツリーの部分更新・ナビゲーション入口を整理してから internal-node を組み込む。中長期でツリーの部分更新や別種の rel を増やしていく場合向け。

両方のプランは並立可能です。まず先行プランで internal-node を届け、負債が目立ってきたタイミングで本プランの方に段階的に寄せていく、という進め方もできます。
