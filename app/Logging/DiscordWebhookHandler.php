<?php

namespace App\Logging;

use App\Enums\DiscordChannel;
use App\Services\Discord\DiscordWebhookService;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class DiscordWebhookHandler extends AbstractProcessingHandler
{
    public function __construct(int|string|Level $level = Level::Warning, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        $hr      = '─────────────────────';
        $level   = $record->level->getName();
        $message = $record->message;
        $lines   = ["{$hr}", "レベル: {$level}", "メッセージ: {$message}"];

        // 例外情報があれば追記
        $stackTrace = null;
        if (isset($record->context['exception']) && $record->context['exception'] instanceof \Throwable) {
            $e = $record->context['exception'];
            $lines[] = "例外: " . get_class($e) . ": " . $e->getMessage();
            $lines[] = "場所: " . $e->getFile() . ":" . $e->getLine();
            $stackTrace = $e->getTraceAsString();
        }

        $lines[] = $hr;

        $body = implode("\n", $lines);

        try {
            $service = app(DiscordWebhookService::class)
                ->to(DiscordChannel::Log)
                ->username("{$level}通知");

            // スタックトレースがある場合はファイル添付で送る
            if ($stackTrace !== null) {
                $tmpPath = tempnam(sys_get_temp_dir(), 'stack_');
                file_put_contents($tmpPath, $stackTrace);
                $service->attach($tmpPath, 'stacktrace.txt');
            }

            $service->send("[{$level}] ログが出力されました\n{$body}");

            if (isset($tmpPath)) {
                @unlink($tmpPath);
            }
        } catch (\Throwable) {
            // Discord送信失敗時はログループを防ぐため握り潰す
        }
    }
}
