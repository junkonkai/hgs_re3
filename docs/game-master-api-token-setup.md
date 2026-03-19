# ゲームマスターAPI トークン設定手順

`/api/v1/admin/game/*` を呼び出すための **Sanctum Personal Access Token** の用意から、リクエスト時の認証までの流れです。  
（`X-GPTS-API-KEY` や GPTS 用設定とは別です。）

## 前提

1. **マイグレーション**  
   `personal_access_tokens` テーブルが存在すること。未実行ならサーバーで次を実行する。

   ```bash
   php artisan migrate
   ```

2. **トークンを紐づけるユーザー**  
   アプリの `users` テーブルに、**role が管理者（ADMIN）** のユーザーがいること。  
   トークンはこのユーザーに紐づき、API利用時も「管理者として」扱われる。

## 環境変数（任意）

`.env` で次を指定できます。省略時は `config/game_master_api.php` の既定値が使われます。

| 変数 | 意味 | 省略時の例 |
|------|------|------------|
| `GAME_MASTER_API_TOKEN_ABILITY` | トークンに付与する ability 名 | `game-master:access` |
| `GAME_MASTER_API_TOKEN_NAME` | 発行トークンの表示名（DB上の名前） | `game-master-api` |

**注意:** ability を `.env` で変えた場合、**その ability で発行したトークン**だけがAPIで受理されます。既存トークンは再発行が必要です。

## トークン発行

プロジェクトルートで、**管理者ユーザーのメールアドレス**を指定して実行する。

```bash
php artisan game-master:issue-token admin@example.com
```

表示例:

- 成功時: 平文のトークンが1行で出力される（**再表示不可**のため必ずコピーして安全な場所に保管）。
- 失敗時: ユーザー不在、または role が ADMIN でない場合はエラーメッセージ。

トークン表示名を変えたい場合:

```bash
php artisan game-master:issue-token admin@example.com --name=my-cli-tool
```

## 認証の流れ（API呼び出し）

1. クライアント（CLI・デスクトップアプリ等）は、すべてのリクエストに次を付与する。

   ```http
   Authorization: Bearer {発行時に表示された平文トークン}
   ```

2. Laravel は **Sanctum** でトークンを検証し、対応するユーザーを特定する。

3. 続けてアプリ側で次を確認する。

   - ユーザーが **ADMIN** であること  
   - トークンに **`GAME_MASTER_API_TOKEN_ABILITY` で定義した ability** が付いていること  

4. 上記を満たすと `/api/v1/admin/game/*` の処理が実行される。

## レスポンスの目安

| 状況 | ステータス | 想定メッセージの例 |
|------|------------|-------------------|
| `Authorization` なし・不正トークン | 401 | `Unauthenticated.`（Laravel 標準） |
| トークンは有効だが ADMIN でない | 403 | `ゲームマスターAPIは管理者のトークンのみ利用できます。` |
| ADMIN だが ability 不足 | 403 | `この操作には有効なゲームマスターAPIトークンが必要です。` |

## 補足

- トークンの一覧・削除は `personal_access_tokens` を通じて管理できる（Sanctum の仕様）。漏洩時は該当トークンを失効させ、必要なら再発行する。
- OpenAPI 上の説明は [docs/api/v1/game/README.md](api/v1/game/README.md) および各 YAML の `bearerAuth` も参照。
