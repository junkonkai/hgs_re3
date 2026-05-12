<?php

namespace App\Http\Controllers\Admin\Manage;

use App\Defines\AdminDefine;
use App\Http\Controllers\Admin\AbstractAdminController;
use App\Models\GameTitle;
use App\Models\User;
use App\Models\UserGameTitleFearMeter;
use App\Models\UserGameTitleFearMeterLog;
use App\Models\UserGameTitleReview;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FearMeterController extends AbstractAdminController
{
    /**
     * 怖さメーター一覧
     *
     * @param Request $request
     * @return Application|Factory|View
     */
    public function index(Request $request): Application|Factory|View
    {
        $query = UserGameTitleFearMeter::query()
            ->select('user_game_title_fear_meters.*')
            ->selectRaw('(
                SELECT COUNT(*)
                FROM user_game_title_fear_meter_comment_reports
                INNER JOIN user_game_title_fear_meter_logs
                    ON user_game_title_fear_meter_comment_reports.fear_meter_log_id = user_game_title_fear_meter_logs.id
                WHERE user_game_title_fear_meter_logs.user_id = user_game_title_fear_meters.user_id
                AND user_game_title_fear_meter_logs.game_title_id = user_game_title_fear_meters.game_title_id
            ) as reports_count')
            ->with(['user', 'gameTitle'])
            ->orderByDesc('updated_at');

        $keyword = trim($request->query('keyword', ''));

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->whereHas('user', fn($u) => $u->where('name', 'like', '%' . $keyword . '%'))
                    ->orWhereHas('gameTitle', fn($g) => $g->where('name', 'like', '%' . $keyword . '%'));
            });
        }

        $search = ['keyword' => $keyword];

        $this->saveSearchSession($search);

        return view('admin.manage.fear_meter.index', [
            'fearMeters' => $query->paginate(AdminDefine::ITEMS_PER_PAGE),
            'search'     => $search,
        ]);
    }

    /**
     * 怖さメーター詳細
     *
     * @param User $user
     * @param GameTitle $gameTitle
     * @return Application|Factory|View
     */
    public function show(User $user, GameTitle $gameTitle): Application|Factory|View
    {
        $fearMeter = UserGameTitleFearMeter::where('user_id', $user->id)
            ->where('game_title_id', $gameTitle->id)
            ->firstOrFail();

        $logs = UserGameTitleFearMeterLog::where('user_id', $user->id)
            ->where('game_title_id', $gameTitle->id)
            ->orderByDesc('id')
            ->get();

        $reportsCount = DB::table('user_game_title_fear_meter_comment_reports')
            ->join('user_game_title_fear_meter_logs', 'user_game_title_fear_meter_comment_reports.fear_meter_log_id', '=', 'user_game_title_fear_meter_logs.id')
            ->where('user_game_title_fear_meter_logs.user_id', $user->id)
            ->where('user_game_title_fear_meter_logs.game_title_id', $gameTitle->id)
            ->count();

        $review = UserGameTitleReview::where('user_id', $user->id)
            ->where('game_title_id', $gameTitle->id)
            ->first();

        return view('admin.manage.fear_meter.detail', [
            'fearMeter'    => $fearMeter,
            'user'         => $user,
            'gameTitle'    => $gameTitle,
            'logs'         => $logs,
            'reportsCount' => $reportsCount,
            'review'       => $review,
        ]);
    }

    /**
     * 怖さメーターへの通報一覧
     *
     * @param User $user
     * @param GameTitle $gameTitle
     * @return Application|Factory|View
     */
    public function reports(User $user, GameTitle $gameTitle): Application|Factory|View
    {
        $fearMeter = UserGameTitleFearMeter::where('user_id', $user->id)
            ->where('game_title_id', $gameTitle->id)
            ->firstOrFail();

        $logs = UserGameTitleFearMeterLog::where('user_id', $user->id)
            ->where('game_title_id', $gameTitle->id)
            ->whereHas('reports')
            ->with(['reports.reporter'])
            ->orderByDesc('id')
            ->get();

        return view('admin.manage.fear_meter.reports', [
            'fearMeter' => $fearMeter,
            'user'      => $user,
            'gameTitle' => $gameTitle,
            'logs'      => $logs,
        ]);
    }

    /**
     * 怖さメーターログのコメントを削除
     *
     * @param User $user
     * @param GameTitle $gameTitle
     * @param UserGameTitleFearMeterLog $log
     * @return RedirectResponse
     */
    public function deleteLog(User $user, GameTitle $gameTitle, UserGameTitleFearMeterLog $log): RedirectResponse
    {
        if ($log->user_id !== $user->id || $log->game_title_id !== $gameTitle->id) {
            abort(404);
        }

        if ($log->is_deleted) {
            return redirect()->back()->with('warning', 'このコメントは既に削除済みです。');
        }

        $log->is_deleted = true;
        $log->deleted_at = now();
        $log->deleted_by_admin_id = Auth::guard('admin')->id();
        $log->save();

        return redirect()->route('Admin.Manage.FearMeter.Reports', [$user->id, $gameTitle->id])
            ->with('success', 'コメントを削除しました。');
    }
}
