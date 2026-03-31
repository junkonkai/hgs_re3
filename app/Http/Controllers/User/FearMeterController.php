<?php

namespace App\Http\Controllers\User;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\FearMeterDestroyRequest;
use App\Http\Requests\FearMeterStoreRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\FearMeterStatisticsDirtyTitle;
use App\Models\GameTitle;
use App\Models\UserFearMeterRestriction;
use App\Models\UserGameTitleFearMeter;
use App\Models\UserGameTitleFearMeterLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FearMeterController extends Controller
{
    /**
     * 怖さメーター一覧表示
     *
     * @return JsonResponse|Application|Factory|View
     */
    public function index(): JsonResponse|Application|Factory|View
    {
        $user = Auth::user();
        $fearMeters = UserGameTitleFearMeter::where('user_id', $user->id)
            ->with('gameTitle')
            ->orderBy('updated_at', 'desc')
            ->paginate(30);

        return $this->tree(
            view('user.fear_meter.index', compact('fearMeters')),
            options: [
                'url' => route('User.FearMeter.Index'),
            ]
        );
    }

    /**
     * 怖さメーター入力画面表示
     *
     * @param string $titleKey
     * @return JsonResponse|Application|Factory|View
     */
    public function form(string $titleKey): JsonResponse|Application|Factory|View
    {
        $title = GameTitle::findByKey($titleKey);
        if (!$title) {
            abort(404);
        }

        $user = Auth::user();
        $fearMeter = UserGameTitleFearMeter::where('user_id', $user->id)
            ->where('game_title_id', $title->id)
            ->first();
        $fearMeterComment = UserGameTitleFearMeterLog::query()
            ->where('user_id', $user->id)
            ->where('game_title_id', $title->id)
            ->where('is_deleted', false)
            ->latest('id')
            ->value('comment');

        $franchise = $title->getFranchise();
        $shortcutRoute = [
            'title-detail-node' => [
                'title' => $title->name,
                'url' => route('Game.TitleDetail', ['titleKey' => $title->key]),
                'children' => [
                    'franchise-detail-node' => [
                        'title' => $franchise->name . "フランチャイズ",
                        'url' => route('Game.FranchiseDetail', ['franchiseKey' => $franchise->key]),
                        'children' => [
                            'root-node' => [
                                'title' => 'ルート',
                                'url' => route('Root'),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $myNodeShortcutRoute = [
            'fear-meter-list-node' => [
                'title' => '怖さメーター一覧',
                'url' => route('User.FearMeter.Index'),
                'children' => [
                    'mynode' => [
                        'title' => 'マイノード',
                        'url' => route('User.MyNode.Top'),
                    ],
                ],
            ],
        ];

        $from = request()->query('from');

        return $this->tree(
            view('user.fear_meter.form', compact('user', 'title', 'fearMeter', 'fearMeterComment', 'shortcutRoute', 'myNodeShortcutRoute', 'from')),
            options: [
                'csrfToken' => csrf_token(),
                'url' => route('User.FearMeter.Form', ['titleKey' => $title->key]),
            ]
        );
    }

    /**
     * 怖さメーターを保存
     *
     * @param FearMeterStoreRequest $request
     * @return RedirectResponse
     */
    public function store(FearMeterStoreRequest $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }
        $title = GameTitle::findByKey($request->validated('title_key'));
        if (!$title) {
            abort(404);
        }
        $isRestricted = UserFearMeterRestriction::query()
            ->where('user_id', $user->id)
            ->active()
            ->exists();
        if ($isRestricted) {
            return redirect()->back()
                ->with('warning', 'このアカウントは怖さメーター入力を制限されています。');
        }
        $newFearMeter = (int) $request->validated('fear_meter');
        $comment = trim((string) $request->validated('comment', ''));
        $comment = $comment === '' ? null : $comment;
        $alreadyExists = UserGameTitleFearMeter::where('user_id', $user->id)
            ->where('game_title_id', $title->id)
            ->exists();
        if ($alreadyExists) {
            return redirect()->back()
                ->with('warning', '怖さメーターは編集できません。削除してから再入力してください。');
        }

        UserGameTitleFearMeter::create([
            'user_id' => $user->id,
            'game_title_id' => $title->id,
            'fear_meter' => $newFearMeter,
        ]);

        UserGameTitleFearMeterLog::create([
            'user_id' => $user->id,
            'game_title_id' => $title->id,
            'old_fear_meter' => null,
            'new_fear_meter' => $newFearMeter,
            'comment' => $comment,
        ]);

        $successMessage = "怖さメーターを登録しました。\r\nゲームタイトルへの反映はしばらく時間がかかります。\r\n時間をおいてから再度ご確認ください。";
        $from = $request->input('from');

        if ($from === 'title-detail') {
            return redirect()->route('Game.TitleDetail', ['titleKey' => $title->key])
                ->with('success', $successMessage);
        }

        return redirect()->route('User.FearMeter.Index')
            ->with('success', $successMessage);
    }

    /**
     * 怖さメーターを削除
     *
     * @param FearMeterDestroyRequest $request
     * @return RedirectResponse
     */
    public function destroy(FearMeterDestroyRequest $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }
        $title = GameTitle::findByKey($request->validated('title_key'));
        if (!$title) {
            abort(404);
        }

        $fearMeter = UserGameTitleFearMeter::where('user_id', $user->id)
            ->where('game_title_id', $title->id)
            ->first();
        if (!$fearMeter) {
            return redirect()->back()
                ->with('warning', '削除対象の怖さメーターが見つかりませんでした。');
        }

        DB::transaction(function () use ($fearMeter, $user, $title) {
            UserGameTitleFearMeter::where('user_id', $fearMeter->user_id)
                ->where('game_title_id', $fearMeter->game_title_id)
                ->delete();

            $latestLog = UserGameTitleFearMeterLog::where('user_id', $user->id)
                ->where('game_title_id', $title->id)
                ->orderByDesc('id')
                ->first();
            if ($latestLog) {
                $latestLog->is_deleted = true;
                $latestLog->deleted_at = now();
                $latestLog->deleted_by_user_id = $user->id;
                $latestLog->deleted_by_admin_id = null;
                $latestLog->save();
            }

            if (Schema::hasTable('fear_meter_statistics_dirty_titles')) {
                FearMeterStatisticsDirtyTitle::updateOrCreate(
                    ['game_title_id' => $title->id],
                    []
                );
            }
        });

        $successMessage = '怖さメーターを削除しました。再入力できます。';
        $from = $request->validated('from');
        if ($from === 'title-detail') {
            return redirect()->route('Game.TitleDetail', ['titleKey' => $title->key])
                ->with('success', $successMessage);
        }

        return redirect()->route('User.FearMeter.Index')
            ->with('success', $successMessage);
    }
}

