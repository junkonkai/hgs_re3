<?php

namespace App\Console\Commands;

use App\Enums\Shop;
use App\Models\GamePackageShop;
use App\Models\GameRelatedProductShop;
use App\Models\ShopLinkCheckProgress;
use App\Models\ShopLinkSoldOutResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckShopLinksCommand extends Command
{
    protected $signature = 'shop:check-links';

    protected $description = 'ショップリンクの販売状況を10件ずつチェックし、販売終了リンクを記録する';

    private const BATCH_SIZE = 10;

    public function handle(): int
    {
        $pkgProgress  = ShopLinkCheckProgress::where('source_table', 'game_package_shops')->first();
        $relProgress  = ShopLinkCheckProgress::where('source_table', 'game_related_product_shops')->first();

        if ($pkgProgress === null || $relProgress === null) {
            $this->error('進捗レコードが見つかりません。マイグレーションを実行してください。');
            return 1;
        }

        $pkgItems = GamePackageShop::where('id', '>', $pkgProgress->last_checked_id)
            ->orderBy('id')
            ->limit(self::BATCH_SIZE)
            ->get();

        $remaining = self::BATCH_SIZE - $pkgItems->count();

        $relItems = collect();
        if ($remaining > 0) {
            $relItems = GameRelatedProductShop::where('id', '>', $relProgress->last_checked_id)
                ->orderBy('id')
                ->limit($remaining)
                ->get();
        }

        // 両テーブルともチェック済みの場合はリセットして次サイクルへ
        if ($pkgItems->isEmpty() && $relItems->isEmpty()) {
            $pkgProgress->last_checked_id = 0;
            $pkgProgress->save();
            $relProgress->last_checked_id = 0;
            $relProgress->save();
            $this->info('全件チェック完了。次回から再スキャンします。');
            return 0;
        }

        $checkedCount  = 0;
        $skippedCount  = 0;
        $soldOutCount  = 0;
        $restoredCount = 0;

        foreach ($pkgItems as $item) {
            $result = $this->checkItem('game_package_shops', $item->id, $item->shop_id, $item->url);
            $checkedCount++;
            if ($result === null) {
                $skippedCount++;
            } elseif ($result['sold_out']) {
                $soldOutCount++;
            } elseif ($result['restored']) {
                $restoredCount++;
            }
            $pkgProgress->last_checked_id = $item->id;
            sleep(1);
        }
        $pkgProgress->save();

        foreach ($relItems as $item) {
            $result = $this->checkItem('game_related_product_shops', $item->id, $item->shop_id, $item->url);
            $checkedCount++;
            if ($result === null) {
                $skippedCount++;
            } elseif ($result['sold_out']) {
                $soldOutCount++;
            } elseif ($result['restored']) {
                $restoredCount++;
            }
            $relProgress->last_checked_id = $item->id;
            usleep(500000);
        }
        $relProgress->save();

        $this->info("チェック完了: {$checkedCount} 件（スキップ: {$skippedCount}、販売終了: {$soldOutCount}、復活: {$restoredCount}）");

        return 0;
    }

    /**
     * 年齢確認ページかどうか判定する
     * ショップ固有のキーワードがHTML内にいずれか含まれれば年齢確認ページと判断する
     */
    private function isAgeGatePage(Shop $shop, string $html): bool
    {
        foreach ($shop->ageGateKeywords() as $keyword) {
            if (str_contains($html, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 1件チェックする
     *
     * @return array{sold_out: bool, restored: bool}|null  null=スキップ
     */
    private function checkItem(string $sourceTable, int $sourceId, int $shopId, string $url): ?array
    {
        $shop = Shop::tryFrom($shopId);

        if ($shop === null || !$shop->isCheckable()) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'ja,en-US;q=0.7,en;q=0.3',
            ])->timeout(10)->get($url);

            $isSoldOut    = false;
            $reason       = null;
            $matchedWord  = null;

            if ($response->status() === 404) {
                $isSoldOut = true;
                $reason    = '404';
            } elseif ($response->successful()) {
                // 年齢確認ページなら販売中と判断してスキップ
                if ($this->isAgeGatePage($shop, $response->body())) {
                    return ['sold_out' => false, 'restored' => false];
                }

                $html     = $response->body();
                $keywords = $shop->soldOutKeywords();
                foreach ($keywords as $keyword) {
                    if (str_contains($html, $keyword)) {
                        $isSoldOut   = true;
                        $reason      = 'keyword';
                        $matchedWord = $keyword;
                        break;
                    }
                }
            }

            $existing = ShopLinkSoldOutResult::where('source_table', $sourceTable)
                ->where('source_id', $sourceId)
                ->first();

            if ($isSoldOut) {
                if ($existing === null) {
                    ShopLinkSoldOutResult::create([
                        'source_table'    => $sourceTable,
                        'source_id'       => $sourceId,
                        'shop_id'         => $shopId,
                        'url'             => $url,
                        'reason'          => $reason,
                        'matched_keyword' => $matchedWord,
                        'detected_at'     => now(),
                    ]);
                }
                return ['sold_out' => true, 'restored' => false];
            }

            if ($existing !== null) {
                $existing->delete();
                return ['sold_out' => false, 'restored' => true];
            }

            return ['sold_out' => false, 'restored' => false];
        } catch (\Exception $e) {
            Log::warning('shop:check-links HTTP エラー' . PHP_EOL . $e->getMessage(), [
                'source_table' => $sourceTable,
                'source_id'    => $sourceId,
                'url'          => $url,
                'error'        => $e->getMessage(),
            ]);
            return null;
        }
    }
}
