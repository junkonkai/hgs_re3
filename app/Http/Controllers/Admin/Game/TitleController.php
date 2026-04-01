<?php

namespace App\Http\Controllers\Admin\Game;

use App\Defines\AdminDefine;
use App\Http\Controllers\Admin\AbstractAdminController;
use App\Http\Requests\Admin\Game\LinkMultiMediaMixRequest;
use App\Http\Requests\Admin\Game\LinkMultiPackageGroupRequest;
use App\Http\Requests\Admin\Game\LinkMultiRelatedProductRequest;
use App\Http\Requests\Admin\Game\TitleMultiUpdateRequest;
use App\Http\Requests\Admin\Game\TitleRequest;
use App\Models\Extensions\GameTree;
use App\Models\FearMeterStatisticsDirtyTitle;
use App\Models\GameMediaMix;
use App\Models\GameTitle;
use App\Models\User;
use App\Models\UserGameTitleFearMeter;
use App\Models\UserGameTitleFearMeterLog;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class TitleController extends AbstractAdminController
{
    /**
     * インデックス
     *
     * @param Request $request
     * @return Application|Factory|View
     */
    public function index(Request $request): Application|Factory|View
    {
        return view('admin.game.title.index', $this->search($request));
    }

    /**
     * 検索処理
     *
     * @param Request $request
     * @return array
     */
    private function search(Request $request): array
    {
        $titles = GameTitle::orderBy('id');

        $searchName = trim($request->query('name', ''));
        $search = ['name' => ''];

        if (!empty($searchName)) {
            $search['name'] = $searchName;
            $words = explode(' ', $searchName);

            $titles->where(function ($query) use ($words) {
                foreach ($words as $word) {
                    $query->where('name', operator: 'LIKE', value: '%' . $word . '%');
                }
            });

            // 俗称も探す
            // $words配列の中にある文字列にsynonym関数を適用する
            $synonymWords = [];
            foreach ($words as $word) {
                $synonymWords[] = synonym($word);
            }

            // title_synonymsカラム内の改行区切り文字列から検索
            if (!empty($synonymWords)) {
                $titles->orWhere(function ($query) use ($synonymWords) {
                    foreach ($synonymWords as $synonymWord) {
                        $query->orWhere('search_synonyms', 'LIKE', '%' . $synonymWord . '%');
                    }
                });
            }
        }

        $this->saveSearchSession($search);

        return [
            'titles' => $titles->paginate(AdminDefine::ITEMS_PER_PAGE),
            'search' => $search
        ];
    }

    /**
     * 詳細
     *
     * @param GameTitle $title
     * @return Application|Factory|View
     */
    public function detail(GameTitle $title): Application|Factory|View
    {
        $fearMeters = UserGameTitleFearMeter::query()
            ->where('game_title_id', $title->id)
            ->with('user')
            ->orderByDesc('updated_at')
            ->paginate(30, ['*'], 'fear_meter_page');

        return view('admin.game.title.detail', [
            'model' => $title,
            'tree'  => GameTree::getTree($title),
            'fearMeters' => $fearMeters,
        ]);
    }

    /**
     * 追加画面
     *
     * @return Application|Factory|View
     */
    public function add(): Application|Factory|View
    {
        return view('admin.game.title.add', [
            'model' => new GameTitle(),
        ]);
    }

    /**
     * 追加処理
     *
     * @param TitleRequest $request
     * @return RedirectResponse
     * @throws Throwable
     */
    public function store(TitleRequest $request): RedirectResponse
    {
        $title = new GameTitle();
        $title->fill($request->validated());
        $title->setOgpInfo($request->post('ogp_url'));
        $title->save();

        $franchise = $title->getFranchise();
        if ($franchise !== null) {
            $franchise->setTitleParam();
            $franchise->save();
        }

        $series = $title->series;
        if ($series !== null) {
            $series->setTitleParam();
            $series->save();
        }

        return redirect()->route('Admin.Game.Title.Detail', $title);
    }

    /**
     * 一括更新
     *
     * @param Request $request
     * @return Application|Factory|View
     */
    public function editMulti(Request $request): Application|Factory|View
    {
        return view('admin.game.title.edit_multi', $this->search($request));
    }

    /**
     * 更新処理
     *
     * @param TitleMultiUpdateRequest $request
     * @return RedirectResponse
     * @throws Throwable
     */
    public function updateMulti(TitleMultiUpdateRequest $request): RedirectResponse
    {
        $nodeNames = $request->validated(['node_name']);
        $keys = $request->validated(['key']);
        foreach ($nodeNames as $id => $nodeName) {
            $model = GameTitle::find($id);
            if ($model !== null) {
                $model->node_name = $nodeName;
                $model->key = $keys[$id];
                $model->save();
            }

            $franchise = $model->getFranchise();
            if ($franchise !== null) {
                $franchise->setTitleParam();
                $franchise->save();
            }

            $series = $model->series;
            if ($series !== null) {
                $series->setTitleParam();
                $series->save();
            }
        }

        return redirect()->back();
    }

    /**
     * 編集画面
     *
     * @param GameTitle $title
     * @return Application|Factory|View
     */
    public function edit(GameTitle $title): Application|Factory|View
    {
        return view('admin.game.title.edit', [
            'model' => $title
        ]);
    }

    /**
     * 更新処理
     *
     * @param TitleRequest $request
     * @param GameTitle $title
     * @return RedirectResponse
     * @throws Throwable
     */
    public function update(TitleRequest $request, GameTitle $title): RedirectResponse
    {
        $title->fill($request->validated());
        $title->setOgpInfo($request->post('ogp_url'));
        $title->save();

        $franchise = $title->getFranchise();
        if ($franchise !== null) {
            $franchise->setTitleParam();
            $franchise->save();
        }

        $series = $title->series;
        if ($series !== null) {
            $series->setTitleParam();
            $series->save();
        }

        return redirect()->route('Admin.Game.Title.Detail', $title);
    }

    /**
     * 削除
     *
     * @param GameTitle $title
     * @return RedirectResponse
     */
    public function delete(GameTitle $title): RedirectResponse
    {
        $franchise = $title->getFranchise();
        
        $title->packages()->detach();
        $title->packageGroups()->detach();
        $title->relatedProducts()->detach();
        $title->delete();

        if ($franchise !== null) {
            $franchise->setTitleParam();
            $franchise->save();
        }

        $series = $title->series;
        if ($series !== null) {
            $series->setTitleParam();
            $series->save();
        }

        return redirect()->route('Admin.Game.Title');
    }

    /**
     * パッケージグループとリンク
     *
     * @param Request $request
     * @param GameTitle $title
     * @return Application|Factory|View
     */
    public function linkPackageGroup(Request $request, GameTitle $title): Application|Factory|View
    {
        $packageGroups = \App\Models\GamePackageGroup::orderBy('id')->get(['id', 'name']);
        return view('admin.game.title.link_package_groups', [
            'model'                 => $title,
            'linkedPackageGroupIds' => $title->packageGroups()->pluck('id')->toArray(),
            'packageGroups'         => $packageGroups,
        ]);
    }

    /**
     * パッケージグループと同期処理
     *
     * @param LinkMultiPackageGroupRequest $request
     * @param GameTitle $title
     * @return RedirectResponse
     * @throws Throwable
     */
    public function syncPackageGroup(LinkMultiPackageGroupRequest $request, GameTitle $title): RedirectResponse
    {
        $title->packageGroups()
              ->sync($request->validated('game_package_group_ids'));
        $title->setFirstReleaseInt()
              ->save();

        $series = $title->series;
        if ($series !== null) {
            $series->setTitleParam();
            $series->save();
        }

        return redirect()->route('Admin.Game.Title.Detail', $title);
    }

    /**
     * 関連パッケージの一括更新
     *
     * @param Request $request
     * @param GameTitle $title
     * @return Application|Factory|View
     */
    public function editPackageGroupMulti(Request $request, GameTitle $title): Application|Factory|View
    {
        $packages = [];
        if ($title->packages->isEmpty()) {
            foreach ($title->packageGroups as $pg) {
                foreach ($pg->packages as $package) {
                    $packages[] = $package;
                }
            }
        } else {
            $packages = $title->packages;
        }
        return view('admin.game.title.edit_package_group_multi', compact('packages', 'title'));
    }

    /**
     * 関連商品とリンク
     *
     * @param GameTitle $title
     * @return Application|Factory|View
     */
    public function linkRelatedProduct(GameTitle $title): Application|Factory|View
    {
        $relatedProducts = \App\Models\GameRelatedProduct::orderBy('id')->get(['id', 'name']);
        return view('admin.game.media_mix.link_related_product', [
            'model' => $title,
            'linkedRelatedProductIds' => $title->relatedProducts()->pluck('id')->toArray(),
            'relatedProducts' => $relatedProducts,
        ]);
    }

    /**
     * 関連商品と同期処理
     *
     * @param LinkMultiRelatedProductRequest $request
     * @param GameTitle $title
     * @return RedirectResponse
     */
    public function syncRelatedProduct(LinkMultiRelatedProductRequest $request, GameTitle $title): RedirectResponse
    {
        $title->relatedProducts()->sync($request->validated('game_related_product_ids'));
        return redirect()->route('Admin.Game.Title.Detail', $title);
    }

    /**
     * メディアミックスとリンク
     *
     * @param GameTitle $title
     * @return Application|Factory|View
     */
    public function linkMediaMix(GameTitle $title): Application|Factory|View
    {
        return view('admin.game.title.link_media_mix', [
            'model' => $title,
            'linkedMediaMixIds' => $title->mediaMixes()->pluck('id')->toArray(),
            'mediaMixes' => GameMediaMix::orderBy('id')->get(['id', 'name']),
        ]);
    }

    /**
     * メディアミックスと同期処理
     *
     * @param LinkMultiMediaMixRequest $request
     * @param GameTitle $title
     * @return RedirectResponse
     */
    public function syncMediaMix(LinkMultiMediaMixRequest $request, GameTitle $title): RedirectResponse
    {
        $title->mediaMixes()->sync($request->validated('game_media_mix_ids'));
        return redirect()->route('Admin.Game.Title.Detail', $title);
    }

    /**
     * 怖さメーター入力を管理者削除
     *
     * @param GameTitle $title
     * @param User $user
     * @return RedirectResponse
     */
    public function deleteFearMeter(GameTitle $title, User $user): RedirectResponse
    {
        $fearMeter = UserGameTitleFearMeter::query()
            ->where('game_title_id', $title->id)
            ->where('user_id', $user->id)
            ->first();
        if ($fearMeter === null) {
            return redirect()->back()->with('warning', '削除対象の怖さメーターが見つかりません。');
        }

        DB::transaction(function () use ($fearMeter, $title, $user) {
            UserGameTitleFearMeter::query()
                ->where('game_title_id', $title->id)
                ->where('user_id', $user->id)
                ->delete();

            $latestLog = UserGameTitleFearMeterLog::query()
                ->where('game_title_id', $title->id)
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->first();
            if ($latestLog !== null) {
                $latestLog->is_deleted = true;
                $latestLog->deleted_at = now();
                $latestLog->deleted_by_user_id = null;
                $latestLog->deleted_by_admin_id = Auth::guard('admin')->id();
                $latestLog->save();
            }

            FearMeterStatisticsDirtyTitle::updateOrCreate([
                'game_title_id' => $title->id,
            ], []);
        });

        return redirect()->route('Admin.Game.Title.Detail', $title)
            ->with('success', '怖さメーター入力を削除しました。');
    }
}
