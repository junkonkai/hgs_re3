# ogp-generator

Rust製のOGP画像生成バイナリ。SVGテンプレートにペイロードを埋め込み、PNG画像として出力する。
リポジトリ: `/src/hgn_rust_tools`（`crates/ogp-generator`）

Laravel側からは `App\Jobs\GenerateReviewOgpImage` Queue Job 経由で呼び出す。

## 呼び出し方

```bash
OUTPUT_DIR=... FONT_PATH=... SVG_TEMPLATE_PATH=... /path/to/ogp-generator '<JSON>'
```

設定値は `config/services.php` の `ogp` キーで管理している。

```php
'ogp' => [
    'binary'       => env('OGP_GENERATOR_BINARY'),
    'output_dir'   => env('OGP_OUTPUT_DIR'),
    'font_path'    => env('OGP_FONT_PATH'),
    'template_path'=> env('OGP_SVG_TEMPLATE_PATH'),
],
```

## 環境変数

| 変数名 | 内容 |
|--------|------|
| `OUTPUT_DIR` | 生成した PNG の出力先ディレクトリ（例: `/var/www/html/hgs_re3/public/img/review`） |
| `FONT_PATH` | 描画に使うフォントファイルのパス（Noto Sans CJK 推奨） |
| `SVG_TEMPLATE_PATH` | SVG テンプレートファイルのパス |

## JSONペイロード

第1引数にJSON文字列を渡す。

### type: "review"（レビューOGP）

| フィールド | 型 | 必須 | 内容 |
|------------|----|------|------|
| `type` | string | ✅ | `"review"` 固定 |
| `review_id` | integer | ✅ | `user_game_title_reviews.id` |
| `game_title_name` | string | ✅ | ゲームタイトル名 |
| `user_name` | string | ✅ | ユーザーの表示名（`users.name`） |
| `total_score` | integer\|null | | 総合スコア（0〜100） |
| `fear_meter` | integer\|null | | 怖さメーターのスコア換算値（value × 10、つまり 0〜40） |
| `score_story` | integer\|null | | ストーリースコア（0〜4） |
| `score_atmosphere` | integer\|null | | 雰囲気スコア（0〜4） |
| `score_gameplay` | integer\|null | | ゲーム性スコア（0〜4） |
| `user_score_adjustment` | integer\|null | | ユーザー調整値（さじ加減） |
| `has_spoiler` | boolean | ✅ | ネタバレありかどうか |

**例:**
```json
{
    "type": "review",
    "review_id": 42,
    "game_title_name": "バイオハザード RE:2",
    "user_name": "huckle",
    "total_score": 87,
    "fear_meter": 30,
    "score_story": 4,
    "score_atmosphere": 4,
    "score_gameplay": 3,
    "user_score_adjustment": 0,
    "has_spoiler": false
}
```

## レスポンス

標準出力にJSONを1行出力して終了する。

**成功時（exit code 0）:**
```json
{"ok": true, "filename": "review_42.png"}
```

**失敗時（exit code 1）:**
```json
{"ok": false, "error": "エラーメッセージ"}
```

Laravel側は `$json['ok']` で成否を判定し、成功時は `$json['filename']` を `ogp_image_path` に保存する。

## 出力ファイル

ファイル名は `review_id` をハッシュ化した固定名（`hash::review_id_to_filename`）。
同じ `review_id` で再生成すると同名ファイルが上書きされる。

## 新しい type を追加するには

1. Rust側の `Payload` 構造体に新フィールドを追加
2. `image.rs` の描画ロジックを `type_` で分岐
3. SVGテンプレートを追加
4. Laravel側に新しい Queue Job を作成し、対応するペイロードを渡す
