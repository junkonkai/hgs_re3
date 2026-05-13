# Artisan コマンド一覧

自作の Artisan コマンド（`app/Console/Commands/`）とシーダーのまとめ。

---

## カスタムコマンド

| コマンド | ファイル | 説明 |
|---|---|---|
| `email-change:cleanup` | `CleanupExpiredEmailChangeRequests.php` | 有効期限切れのメールアドレス変更リクエストを削除する |
| `contact:close-resolved` | `CloseResolvedContacts.php` | 解決済みから2週間経過したお問い合わせをクローズする |
| `user:invalidate-unverified` | `InvalidateUnverifiedUsers.php` | 10分以内にメール確認が完了していないアカウントを無効化する |
| `user:purge-withdrawn [--dry-run]` | `PurgeWithdrawnUsersCommand.php` | 退会から100日経過したユーザーを削除する。`--dry-run` で件数確認のみ |
| `fear-meter:recalculate-statistics [--force-full] [--no-progress]` | `RecalculateFearMeterStatisticsCommand.php` | 怖さメーター統計を再集計する（差分のみ。`--force-full` で全件） |
| `review:recalculate-statistics [--force-full] [--no-progress]` | `RecalculateReviewStatisticsCommand.php` | レビュー統計を再集計する（差分のみ。`--force-full` で全件） |
| `log:send-apache-php-errors` | `SendApachePhpErrorLogCommand.php` | Apacheエラーログ内のPHPエラーを集計してDiscordに送信する |
| `game-master:issue-token` | `IssueGameMasterApiToken.php` | ゲームマスターAPI用 Sanctum トークンを発行する（平文表示は一度のみ） |
| `mail:test` | `TestMailCommand.php` | メール送信テストを実行する |
| `test:create-show-tests [type]` | `CreateShowTestsCommand.php` | Playwright 用の基本ページアクセステストを生成する |
| `shop:check-links` | `CheckShopLinksCommand.php` | ショップリンクの販売状況を10件ずつチェックし、販売終了リンクを記録する |

---

## シーダー

```bash
php artisan db:seed --class=UserSeeder        # テストユーザー投入
php artisan db:seed --class=ReviewSeeder      # レビューのテストデータ投入
php artisan db:seed --class=FearMeterSeeder   # 怖さメーターのテストデータ投入
php artisan db:seed                           # 全シーダーを実行（上記の順で実行される）
```

| シーダー | ファイル | 内容 |
|---|---|---|
| `UserSeeder` | `database/seeders/UserSeeder.php` | テストユーザーを30人作成する |
| `ReviewSeeder` | `database/seeders/ReviewSeeder.php` | 既存ユーザーからランダムに20人選び、各ユーザーに3〜8件のレビューをランダム生成 |
| `FearMeterSeeder` | `database/seeders/FearMeterSeeder.php` | 既存ユーザー全員に対して3〜10件の怖さメーターをランダム生成 |
