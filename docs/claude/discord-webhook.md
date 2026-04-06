# Discord Webhook

Discord への通知送信機能。Webhook を使って複数チャンネルへの投稿、投稿者名の変更、ファイル添付に対応している。

## ファイル構成

```
app/
├── Enums/
│   └── DiscordChannel.php              # チャンネル定義（送信先の列挙）
├── Services/
│   └── Discord/
│       ├── DiscordMessage.php           # メッセージ値オブジェクト
│       └── DiscordWebhookService.php    # 送信サービス（流れるI/F）
```

## 環境変数

`.env` に以下の形式で Webhook URL を登録する。`XXX` の部分が `DiscordChannel` enum の値に対応する。

```
DISCORD_WEBHOOK_URL_CONTACT=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_URL_LOG=https://discord.com/api/webhooks/...
```

## 使い方

```php
use App\Enums\DiscordChannel;
use App\Services\Discord\DiscordWebhookService;

// テキストのみ
app(DiscordWebhookService::class)
    ->to(DiscordChannel::Contact)
    ->username('お問い合わせBot') // 省略するとDiscord側のデフォルト名
    ->send('新しいお問い合わせが届きました。');

// ファイル添付
app(DiscordWebhookService::class)
    ->to(DiscordChannel::Log)
    ->attach(storage_path('logs/laravel.log'), 'laravel.log')
    ->send('エラーログです。');

// アバター画像も変更する場合
app(DiscordWebhookService::class)
    ->to(DiscordChannel::Contact)
    ->username('HGN Bot')
    ->avatarUrl('https://example.com/avatar.png')
    ->send('テスト投稿');
```

## チャンネルを追加する手順

1. `.env`（および `.env.example`）に `DISCORD_WEBHOOK_URL_XXX=` を追記
2. `app/Enums/DiscordChannel.php` にケースを追加

```php
case Xxx = 'XXX';
```

## 仕様メモ

- `username()` を省略すると Discord の Webhook 作成時に設定したデフォルト名が使われる
- ファイル添付がある場合は自動的に `multipart/form-data` で送信される（呼び出し側は意識不要）
- 送信失敗時（HTTP エラー）は `RuntimeException` をスローする
- `to()` を呼ばずに `send()` すると `LogicException` をスローする
