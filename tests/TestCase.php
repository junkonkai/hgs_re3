<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TestFailedNotification;
use Tests\Subscribers\TestFailedSubscriber;
abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $viewCompiledPath = env('VIEW_COMPILED_PATH');
        if (is_string($viewCompiledPath) && $viewCompiledPath !== '' && !is_dir($viewCompiledPath)) {
            @mkdir($viewCompiledPath, 0777, true);
        }

        // スキーマ復元はテスト用DB（hgs_re3_test）に対してのみ実行する
        $connection = DB::connection();
        $databaseName = $connection->getDatabaseName();
        if ($databaseName !== 'hgs_re3_test') {
            throw new \RuntimeException(
                "テストはデータベース 'hgs_re3_test' に対してのみ実行してください。"
                . " 現在の接続先は '{$databaseName}' です。"
                . " 設定キャッシュが有効な場合は 'php artisan config:clear' を実行し、"
                . " phpunit.xml の DB_DATABASE=hgs_re3_test が効くようにしてください。"
            );
        }

        // テスト開始時にスキーマファイルからテーブルを復元
        $schemaPath = database_path('schema/mariadb-schema.sql');
        if (file_exists($schemaPath)) {
            $sql = file_get_contents($schemaPath);
            DB::unprepared($sql);
        }

        // テスト用初期データを登録（SQLファイルがあれば実行）
        $testSeedPath = database_path('schema/test-seed.sql');
        if (file_exists($testSeedPath)) {
            $seedSql = file_get_contents($testSeedPath);
            DB::unprepared($seedSql);
        }
    }

    protected function tearDown(): void
    {
        $failedSubscriber = TestFailedSubscriber::getInstance();
        if (!empty($failedSubscriber->getFailures())) {
            Notification::route('slack', config('services.slack.test_error_webhook_url'))
                ->notify(new TestFailedNotification($failedSubscriber->getFailures()));

            $failedSubscriber->clearFailures();
        }

        parent::tearDown();
    }
}
