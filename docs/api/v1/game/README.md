# ゲームマスターAPI OpenAPI 設計書（v1）

ゲーム系マスターデータの管理API（`/api/v1/admin/game`）の OpenAPI 3.0 定義です。

## 構成

- **common.yaml** … 共通定義（認証 Bearer、ページネーション用 parameters、PaginationMeta / PaginationLinks / ErrorResponse）
- **title.yaml** … タイトル（CRUD + package-groups / related-products / media-mixes 紐づけ）
- **franchise.yaml** … フランチャイズ（CRUD + series / titles 紐づけ）
- **series.yaml** … シリーズ（CRUD + titles 紐づけ）
- **package-group.yaml** … パッケージグループ（CRUD + titles / packages 紐づけ）
- **package.yaml** … パッケージ（CRUD + makers / package-groups 紐づけ + shops 子リソース）
- **related-product.yaml** … 関連商品（CRUD + platforms / titles / media-mixes 紐づけ + shops 子リソース）
- **platform.yaml** … プラットフォーム（CRUD + related-products 紐づけ）
- **media-mix.yaml** … メディアミックス（CRUD + titles / related-products 紐づけ）
- **media-mix-group.yaml** … メディアミックスグループ（CRUD + media-mixes 紐づけ）
- **maker.yaml** … メーカー（CRUD + packages 紐づけ）

各リソースYAMLは `common.yaml` を `$ref` で参照しています。参照時は同一ディレクトリ（`docs/api/v1/game/`）にすべてのファイルを置いてください。

## 認証

読み取り・書き込みとも **`Authorization: Bearer {token}`**（Laravel Sanctum Personal Access Token）が必須です。

- トークンは **role が管理者（ADMIN）のユーザー** にのみ `php artisan game-master:issue-token {メールアドレス}` で発行できます。
- 設定: [`config/game_master_api.php`](../../../../config/game_master_api.php)（環境変数 `GAME_MASTER_API_TOKEN_ABILITY` / `GAME_MASTER_API_TOKEN_NAME`）。GPTS 用の `X-GPTS-API-KEY` とは別です。

## 検索

一覧 `GET /{resource}` ではクエリ `q` で検索できます。name と phonetic があるリソース（titles, franchises, makers）は両方で部分一致（OR）です。
