<?php

namespace App\Console\Commands;

use App\Enums\DiscordChannel;
use App\Services\Discord\DiscordWebhookService;
use Illuminate\Console\Command;

class SendApachePhpErrorLogCommand extends Command
{
    protected $signature = 'log:send-apache-php-errors';

    protected $description = 'Apacheエラーログ内のPHPエラーを抽出・集計してDiscordに送信する';

    // PHP関連行の判定パターン
    private const PHP_MODULE_PATTERN = '/^\[[^\]]+\] \[php:/';
    private const PHP_MESSAGE_PATTERN = '/\bPHP \w/';

    // Apacheエラーログの行パースパターン
    // 例: [Sun Apr 06 10:00:00.000000 2026] [php:error] [pid 1234] [client 1.2.3.4:5678] PHP Fatal error: ...
    private const LOG_LINE_PATTERN = '/^\[([^\]]+)\] \[([^\]]+)\](?:\s+\[pid \d+\])?(?:\s+\[client [^\]]+\])?\s+(.+)$/';

    public function handle(DiscordWebhookService $discord): int
    {
        $logPath = env('APACHE_ERROR_LOG_PATH');

        if (empty($logPath)) {
            $this->error('APACHE_ERROR_LOG_PATH が設定されていません。');
            return self::FAILURE;
        }

        if (! file_exists($logPath) || ! is_readable($logPath)) {
            $this->error("ログファイルが見つからないか読み取れません: {$logPath}");
            return self::FAILURE;
        }

        $yesterday = now()->subDay();
        $targetDate = $yesterday->format('Y-m-d');
        $dateStamp = $yesterday->format('Ymd');

        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = $this->filterByDate($lines, $targetDate);
        $phpLines = $this->filterPhpLines($lines);
        $grouped = $this->groupByMessage($phpLines);

        $outputDir = base_path('resources/logs/send_discord');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $this->deleteOldLogs($outputDir);

        $outputFileName = "php_log_{$dateStamp}.log";
        $outputPath = "{$outputDir}/{$outputFileName}";

        $totalCount = array_sum(array_column($grouped, 'count'));
        $uniqueCount = count($grouped);

        $isStaging = app()->environment('staging');
        $botName = $isStaging ? 'STG Apache Error Log' : 'Apache Error Log';

        $logContent = $this->buildLogContent($targetDate, $logPath, $totalCount, $uniqueCount, $grouped);
        file_put_contents($outputPath, $logContent);

        if ($totalCount === 0) {
            if (! $isStaging) {
                $discord
                    ->to(DiscordChannel::Log)
                    ->username($botName)
                    ->send("✅ PHPエラーログ ({$targetDate}) — PHPエラーは検出されませんでした。");
            }
            $this->info('PHPエラーなし。' . ($isStaging ? 'STG環境のため通知をスキップしました。' : 'Discordに異常なし通知を送信しました。'));
            return self::SUCCESS;
        }

        $discord
            ->to(DiscordChannel::Log)
            ->username($botName)
            ->attach($outputPath, $outputFileName)
            ->send("⚠️ PHPエラーログ ({$targetDate}) — {$totalCount}件 / {$uniqueCount}種類");

        $this->info("Discord にログを送信しました: {$totalCount}件 / {$uniqueCount}種類");
        return self::SUCCESS;
    }

    /**
     * 指定日付の行のみ抽出する（Apache ログのタイムスタンプを解析して比較）
     *
     * @param  string[]  $lines
     * @return string[]
     */
    private function filterByDate(array $lines, string $targetDate): array
    {
        return array_values(array_filter($lines, function (string $line) use ($targetDate): bool {
            if (! preg_match('/^\[([^\]]+)\]/', $line, $matches)) {
                return false;
            }
            $timestamp = strtotime($matches[1]);
            return $timestamp !== false && date('Y-m-d', $timestamp) === $targetDate;
        }));
    }

    /**
     * PHP関連行のみ抽出する
     *
     * @param  string[]  $lines
     * @return string[]
     */
    private function filterPhpLines(array $lines): array
    {
        return array_values(array_filter($lines, function (string $line): bool {
            return preg_match(self::PHP_MODULE_PATTERN, $line)
                || preg_match(self::PHP_MESSAGE_PATTERN, $line);
        }));
    }

    /**
     * 同一メッセージの行をグルーピングし、件数・最終発生時刻を集計する
     *
     * @param  string[]  $lines
     * @return array<int, array{count: int, module: string, last_timestamp: string, message: string}>
     */
    private function groupByMessage(array $lines): array
    {
        $groups = [];

        foreach ($lines as $line) {
            if (! preg_match(self::LOG_LINE_PATTERN, $line, $matches)) {
                // パース失敗行はメッセージ全体をキーにして記録
                $key = trim($line);
                if (! isset($groups[$key])) {
                    $groups[$key] = ['count' => 0, 'module' => '', 'last_timestamp' => '', 'message' => $key];
                }
                $groups[$key]['count']++;
                continue;
            }

            [, $timestamp, $module, $message] = $matches;
            $key = "[{$module}] {$message}";

            if (! isset($groups[$key])) {
                $groups[$key] = ['count' => 0, 'module' => $module, 'last_timestamp' => $timestamp, 'message' => $message];
            }
            $groups[$key]['count']++;
            $groups[$key]['last_timestamp'] = $timestamp;
        }

        // 件数降順でソート
        usort($groups, fn ($a, $b) => $b['count'] <=> $a['count']);

        return $groups;
    }

    /**
     * 出力ログの本文を組み立てる
     *
     * @param  array<int, array{count: int, module: string, last_timestamp: string, message: string}>  $grouped
     */
    private function buildLogContent(
        string $today,
        string $logPath,
        int $totalCount,
        int $uniqueCount,
        array $grouped
    ): string {
        $lines = [];
        $lines[] = "=== PHP エラーログレポート ({$today}) ===";
        $lines[] = "ログファイル: {$logPath}";
        $lines[] = "PHP関連エラー: {$totalCount}件 / {$uniqueCount}種類";
        $lines[] = '';

        if ($totalCount === 0) {
            $lines[] = 'PHPエラーは検出されませんでした。';
            return implode("\n", $lines);
        }

        foreach ($grouped as $index => $entry) {
            $num = $index + 1;
            $lines[] = "[{$num}] {$entry['count']}回発生 | {$entry['module']} | 最終発生: {$entry['last_timestamp']}";
            $lines[] = "    {$entry['message']}";
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * 30日以上経過したログファイルを削除する
     */
    private function deleteOldLogs(string $dir): void
    {
        $threshold = now()->subDays(30)->timestamp;

        foreach (glob("{$dir}/php_log_*.log") ?: [] as $file) {
            if (filemtime($file) < $threshold) {
                unlink($file);
                $this->line("古いログを削除: " . basename($file));
            }
        }
    }
}
