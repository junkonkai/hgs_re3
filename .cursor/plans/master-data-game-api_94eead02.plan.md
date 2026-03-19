---
name: master-data-game-api
overview: Admin側のゲーム系マスター管理を、一覧・詳細・追加・変更・削除および紐づけの取得・追加・更新・削除に限定したAPIとして提供し、Claude Windowsアプリからトークン認証で利用できるようにする。
todos:
  - id: inventory-endpoints
    content: 各リソースのCRUDと紐づけ（取得・追加・更新・削除）エンドポイント一覧を確定する
    status: pending
  - id: auth-sanctum
    content: Sanctum を導入して Personal Access Token による認証・権限スコープを設計する
    status: pending
  - id: api-routes-controllers
    content: "`/api/v1/admin/game/*` のルーティングと API コントローラー群（JSON）を実装する（派生値更新含む）"
    status: pending
  - id: openapi-docs
    content: "`docs/api/v1/game/` を新規作成し、リソースごとに title/franchise/series/package-group/package/related-product/platform/media-mix/media-mix-group/maker の各 YAML で OpenAPI を記述する"
    status: pending
  - id: api-feature-tests
    content: 各リソースの API 実装に合わせて PHPUnit Feature テストを追加し、実装後にテストを実行して通す
    status: pending
isProject: false
---

# ゲーム系マスターデータAPI（精査→設計→実装）

## ゴール

- `app/Http/Controllers/Admin/Game` のうち、**一覧・詳細・追加・変更・削除** および **特定1件との紐づけの取得・追加・更新・削除** を API 化し、Claude Windows アプリから **トークン認証で** 利用できるようにする。
- **対象外（今回スコープ外）**: 一括更新、APIとしての複製。

## 対象機能の棚卸し（Adminコントローラー由来）

対象は以下10系統。

- `[app/Http/Controllers/Admin/Game/TitleController.php](app/Http/Controllers/Admin/Game/TitleController.php)`
- `[app/Http/Controllers/Admin/Game/FranchiseController.php](app/Http/Controllers/Admin/Game/FranchiseController.php)`
- `[app/Http/Controllers/Admin/Game/SeriesController.php](app/Http/Controllers/Admin/Game/SeriesController.php)`
- `[app/Http/Controllers/Admin/Game/PackageGroupController.php](app/Http/Controllers/Admin/Game/PackageGroupController.php)`
- `[app/Http/Controllers/Admin/Game/PackageController.php](app/Http/Controllers/Admin/Game/PackageController.php)`
- `[app/Http/Controllers/Admin/Game/RelatedProductController.php](app/Http/Controllers/Admin/Game/RelatedProductController.php)`
- `[app/Http/Controllers/Admin/Game/PlatformController.php](app/Http/Controllers/Admin/Game/PlatformController.php)`
- `[app/Http/Controllers/Admin/Game/MediaMixController.php](app/Http/Controllers/Admin/Game/MediaMixController.php)`
- `[app/Http/Controllers/Admin/Game/MediaMixGroupController.php](app/Http/Controllers/Admin/Game/MediaMixGroupController.php)`
- `[app/Http/Controllers/Admin/Game/MakerController.php](app/Http/Controllers/Admin/Game/MakerController.php)`

共通パターン（今回API化する範囲）

- **一覧（検索 + paginate）**: 各リソースで検索パラメータを用意。**name と phonetic があるテーブルは両方で部分一致**、それ以外は name 等で部分一致。スペース分割・synonym は既存 Admin 同様。
- **詳細**: 1件取得（関連データ含む場合は include 等で制御するか検討）
- **追加 / 更新 / 削除**: FormRequest 相当のバリデーション、レスポンスは JSON
- **紐づけ**: 特定1リソースに紐づく他リソースの **取得（一覧）・追加・更新（sync）・削除**

対象外（今回スコープ外）

- 一括更新（editMulti/updateMulti）
- APIとしての複製（copy/makeCopy）
- ショップの一括更新（updateShopMulti）

## APIで提供するエンドポイント（確定スコープ）

ルート案: `**/api/v1/admin/game/`***（書き込みは `auth:sanctum`）

### 1) 各マスターの CRUD

- **makers / platforms / franchises / series / titles / package-groups / packages / related-products / media-mix-groups / media-mixes** の各リソースで共通:
  - 一覧: `GET /{resource}`
  - 詳細: `GET /{resource}/{id}`
  - 追加: `POST /{resource}`
  - 変更: `PUT /{resource}/{id}`
  - 削除: `DELETE /{resource}/{id}`
- platforms/makers は synonyms 含む。titles は ogp_url・first_release 等の派生更新を維持。

**検索（各リソースの一覧で対応）**

- 各リソースの一覧 `GET /{resource}` に **検索パラメータ**（例: `q` または `name`）を用意し、部分一致で絞り込めるようにする。
- **name と phonetic の両方があるテーブル**（例: titles, franchises, makers）では、検索時に **name と phonetic の両方で部分一致**を行う（OR 条件）。phonetic がないリソースは name のみで部分一致。
- 既存 Admin と同様、スペース区切りで複数語指定する場合は AND 条件、synonym 検索があるリソース（titles, platforms, makers 等）は synonym も考慮する。

### 2) 紐づけの取得・追加・更新・削除

特定1件（例: title id=5）に紐づく他リソースを扱う。

- **取得**: `GET /titles/{id}/package-groups` などで、その title に紐づく ID 一覧（または簡易オブジェクト一覧）を返す。
- **追加**: 1件または複数件を紐づけに追加（例: `POST /titles/{id}/package-groups` で body に `game_package_group_ids`）。
- **更新**: 紐づけを指定IDの集合で上書き（sync）。例: `PUT /titles/{id}/package-groups`。
- **削除**: 特定の紐づけ1件を外す。例: `DELETE /titles/{id}/package-groups/{packageGroupId}`。または更新で空配列を送って全削除。

Admin で存在する紐づけ関係（取得・追加・更新・削除を提供）:

- **titles**: package-groups, related-products, media-mixes
- **franchises**: series, titles（制約: シリーズ未所属のみ等は維持）
- **series**: titles
- **package-groups**: titles, packages
- **packages**: makers, package-groups
- **platforms**: related-products
- **related-products**: platforms, titles, media-mixes
- **media-mixes**: titles, related-products
- **media-mix-groups**: media-mixes（外部キー付け替え）

### 3) ショップ子リソース（Package / RelatedProduct のみ）

1 package / 1 related-product に属する shop レコードの CRUD（一括更新は対象外）。

- 取得: `GET /packages/{id}/shops`（一覧）, `GET /packages/{id}/shops/{shopId}`（1件）
- 追加: `POST /packages/{id}/shops` / `POST /related-products/{id}/shops`
- 更新: `PUT /packages/{id}/shops/{shopId}` / `PUT /related-products/{id}/shops/{shopId}`
- 削除: `DELETE /packages/{id}/shops/{shopId}` / `DELETE /related-products/{id}/shops/{shopId}`

### 4) サジェスト（既存・参照用）

- 既存: `[app/Http/Controllers/Api/GameMakerController.php](app/Http/Controllers/Api/GameMakerController.php)` の `GET /api/game/maker/suggest`
- 同様に title/platform なども必要なら追加

## 認証（Sanctum）導入方針

- 書き込み系 API を `auth:sanctum` で保護し、Claude Windows アプリは **Personal Access Token** を保持して呼び出す。
- 追加で **tokenの権限スコープ**（例: `game-master:write`）を付け、誤爆防止を強化。

実装手順（次フェーズで実施）

- Sanctum導入（composer）と migrate
- `routes/api.php` に `Route::prefix('v1/admin/game')->middleware('auth:sanctum')` などでルーティング
- `app/Http/Controllers/Api/Admin/Game/`* にコントローラーを新設（Adminの処理を API 仕様に合わせて整理）
- 既存 Admin FormRequest を API 用に再利用できる箇所は流用、レスポンスは JSON に統一
- **実装時は対象リソースごとに PHPUnit Feature テストも実装し、テストを実行して通すこと**（HTTP は Apache 経由ではなくアプリ内で擬似リクエスト。`tests/TestCase` の DB・スキーマ前提に従う）

## OpenAPI設計書

- ルール上 `docs/api/v1` を更新対象にする。現状ディレクトリが存在しないため **新規作成**する。
- **最初からリソースごとに分割**し、Admin/Game のコントローラーと同じ分け方で配置する。
  - `docs/api/v1/game/title.yaml`
  - `docs/api/v1/game/franchise.yaml`
  - `docs/api/v1/game/series.yaml`
  - `docs/api/v1/game/package-group.yaml`
  - `docs/api/v1/game/package.yaml`
  - `docs/api/v1/game/related-product.yaml`
  - `docs/api/v1/game/platform.yaml`
  - `docs/api/v1/game/media-mix.yaml`
  - `docs/api/v1/game/media-mix-group.yaml`
  - `docs/api/v1/game/maker.yaml`
- 共通の components（schemas, securitySchemes）は `docs/api/v1/game/common.yaml` などに切り出し、各 YAML から参照する形にしてもよい。

## 実装時の方針（確定）

- **Package作成の仕様**: 複数 platform 指定は行わない。`POST /packages` は **1 platform あたり1レコード** で、1件ずつ登録する。
- **派生値更新**: sync/CRUDのたびに `setTitleParam/setFirstReleaseInt/setRating` を呼ぶのは維持（現状互換）。
- **制約**: Franchiseの `linkTitle` は「シリーズ未所属のみ」の制約があるため、APIの紐づけ更新にも同様制約を入れる。

### テスト（実装時必須）

- **API を実装するときは、同じリソース単位で Feature テスト（`tests/Feature/...`）を追加する**。CRUD・紐づけ・バリデーションのうち、仕様上重要なものをカバーする。
- **実装完了後は PHPUnit で該当テストを実行し、パスすることを確認する**（例: `php vendor/bin/phpunit --filter <テストクラス名>`）。
- 公開URLが `http://localhost/hgs_re3/public/api/...` であっても、PHPUnit はアプリ内リクエストのため **Apache の URL 形とは独立**（`phpunit.xml` の `APP_URL` 等はテスト用）。

## 参照（ルーティング現状）

- APIルート: `[routes/api.php](routes/api.php)`
- Adminルート: `[routes/web.php](routes/web.php)`

