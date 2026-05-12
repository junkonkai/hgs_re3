# Frontend Implementation Rules

- **Blade テンプレート内に JavaScript を直接書かない。** このプロジェクトは AJAX 通信 + JS による画面更新を行うため、Blade に直書きした JS はページ遷移後に発火しない。インタラクションは必ず `resources/ts/components/` 配下にコンポーネントを実装し、`HgnTree` シングルトン経由で登録・呼び出す。
- **TypeScript または CSS を変更したら、最後に必ず `npm run build` を実行する。**
- **`<a>` タグに `rel="noreferrer"` は付けない。**
- **Bootstrap は使用不可。** スタイリングは TailwindCSS または自前の CSS で行う。
- **OGP の `og:image` / `twitter:image` には絶対URLを使う。** `asset()` / `secure_asset()` は `ASSET_URL` の設定によって相対パスになる場合があるため、`url()` ヘルパーを使うこと。`url()` は `APP_URL` を元に常に絶対URLを返す。

## Link Conventions (Blade テンプレート)

HGN は SPA 風のナビゲーションを持つため、リンクには `data-hgn-scope` 属性で更新範囲を指定する。実装は `resources/ts/navigation/navigation-controller.ts`。

| `data-hgn-scope` | 動作 |
|---|---|
| `full` | ページ全体を再フェッチ。`current-node-title`・`current-node-content`・ノード一覧を全て更新する。**通常の内部リンクはこれ。** |
| `children` | 子ノード（`#nodes` セクション）のみ差し替え。`current-node-title` は保持される。バックエンドへ `?children_only=1` パラメータが付く。 |
| `node` | クリックしたノード単体のみ差し替え。他のノードはそのまま。 |
| `external` | `location.href` で通常遷移（`target="_blank"` を付けると自動的にこの扱いになる）。 |

```blade
{{-- 内部リンク（全体更新） --}}
<a href="{{ route('Game.Lineup') }}" data-hgn-scope="full">ラインナップ</a>

{{-- 子ノードのみ更新（current-node-title を残す） --}}
<a href="{{ route('SomePage') }}" data-hgn-scope="children">...</a>

{{-- 外部リンク --}}
<a href="https://example.com" target="_blank">外部サイト</a>
```

`data-hgn-scope` を省略すると `full` として扱われる（`scopeFromRel` のフォールバック）。  
History API の挙動を変えたい場合は `data-hgn-url-policy="replace"` を追加する（デフォルトは `push`）。

## ページネーション

ページネーションには `App\Support\Pager` クラスと `common.pager` Blade テンプレートを使う。Laravel の `->links()` や独自ページャーテンプレートは使わない。

**コントローラ側：**

```php
use App\Support\Pager;

$items = SomeModel::query()->paginate(30);
$pager = new Pager(
    $items->currentPage(),
    $items->lastPage(),
    'Route.Name',
    ['param' => $value], // ルートパラメータ（不要なら省略可）
    'full'               // data-hgn-scope（省略時は 'full'）
);
return view('some.view', compact('items', 'pager'));
```

**Blade テンプレート側：**

```blade
@include('common.pager', ['pager' => $pager])
```
