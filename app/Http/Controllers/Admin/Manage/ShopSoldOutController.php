<?php

namespace App\Http\Controllers\Admin\Manage;

use App\Defines\AdminDefine;
use App\Enums\Shop;
use App\Http\Controllers\Admin\AbstractAdminController;
use App\Models\GamePackageShop;
use App\Models\GameRelatedProductShop;
use App\Models\ShopLinkSoldOutResult;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ShopSoldOutController extends AbstractAdminController
{
    /**
     * 販売終了リンク一覧
     */
    public function index(): Application|Factory|View
    {
        $results = ShopLinkSoldOutResult::query()
            ->orderByDesc('detected_at')
            ->paginate(AdminDefine::ITEMS_PER_PAGE);

        return view('admin.manage.shop_sold_out.index', [
            'results' => $results,
        ]);
    }

    /**
     * 販売終了リンクを元テーブルごと削除
     */
    public function destroy(ShopLinkSoldOutResult $result): RedirectResponse
    {
        $modelClass = match ($result->source_table) {
            'game_package_shops' => GamePackageShop::class,
            'game_related_product_shops' => GameRelatedProductShop::class,
        };

        $modelClass::find($result->source_id)?->delete();
        $result->delete();

        return redirect()->route('Admin.Manage.ShopSoldOut');
    }
}
