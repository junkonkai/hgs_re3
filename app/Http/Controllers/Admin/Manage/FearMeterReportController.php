<?php

namespace App\Http\Controllers\Admin\Manage;

use App\Defines\AdminDefine;
use App\Http\Controllers\Admin\AbstractAdminController;
use App\Http\Requests\Admin\Manage\FearMeterReportStatusUpdateRequest;
use App\Http\Requests\Admin\Manage\UserFearMeterRestrictionStoreRequest;
use App\Models\UserFearMeterRestriction;
use App\Models\UserGameTitleFearMeterCommentReport;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FearMeterReportController extends AbstractAdminController
{
    private const REPORT_THRESHOLD = 3;

    /**
     * 通報一覧
     *
     * @param Request $request
     * @return Application|Factory|View
     */
    public function index(Request $request): Application|Factory|View
    {
        $query = UserGameTitleFearMeterCommentReport::query()
            ->with(['fearMeterLog.user', 'fearMeterLog.gameTitle', 'reporter', 'reviewedByAdmin'])
            ->orderByDesc('created_at');

        $search = [
            'status' => (string) $request->query('status', ''),
            'threshold_only' => (string) $request->query('threshold_only', ''),
        ];

        if ($search['status'] !== '') {
            $query->where('status', $search['status']);
        }

        if ($search['threshold_only'] === '1') {
            $logIds = UserGameTitleFearMeterCommentReport::query()
                ->select('fear_meter_log_id')
                ->whereIn('status', ['open', 'reviewed', 'resolved'])
                ->groupBy('fear_meter_log_id')
                ->havingRaw('COUNT(*) >= ?', [self::REPORT_THRESHOLD])
                ->pluck('fear_meter_log_id')
                ->toArray();
            $query->whereIn('fear_meter_log_id', $logIds);
        }

        $this->saveSearchSession($search);

        return view('admin.manage.fear_meter_report.index', [
            'reports' => $query->paginate(AdminDefine::ITEMS_PER_PAGE),
            'search' => $search,
            'statuses' => ['open', 'reviewed', 'rejected', 'resolved'],
            'reportThreshold' => self::REPORT_THRESHOLD,
        ]);
    }

    /**
     * 通報詳細
     *
     * @param UserGameTitleFearMeterCommentReport $report
     * @return Application|Factory|View
     */
    public function show(UserGameTitleFearMeterCommentReport $report): Application|Factory|View
    {
        $report->load(['fearMeterLog.user', 'fearMeterLog.gameTitle', 'reporter', 'reviewedByAdmin']);

        $reportedUserId = $report->fearMeterLog?->user_id;
        $activeRestriction = null;
        if ($reportedUserId !== null) {
            $activeRestriction = UserFearMeterRestriction::query()
                ->where('user_id', $reportedUserId)
                ->active()
                ->first();
        }

        return view('admin.manage.fear_meter_report.detail', [
            'model' => $report,
            'statuses' => ['open', 'reviewed', 'rejected', 'resolved'],
            'reportThreshold' => self::REPORT_THRESHOLD,
            'activeRestriction' => $activeRestriction,
        ]);
    }

    /**
     * 通報ステータス更新
     *
     * @param FearMeterReportStatusUpdateRequest $request
     * @param UserGameTitleFearMeterCommentReport $report
     * @return RedirectResponse
     */
    public function updateStatus(
        FearMeterReportStatusUpdateRequest $request,
        UserGameTitleFearMeterCommentReport $report
    ): RedirectResponse {
        $report->status = $request->validated('status');
        $report->reviewed_by_admin_id = Auth::guard('admin')->id();
        $report->reviewed_at = now();
        $report->save();

        return redirect()->route('Admin.Manage.FearMeterReport.Show', $report)
            ->with('success', '通報ステータスを更新しました。');
    }

    /**
     * 通報対象ユーザーを入力制限
     *
     * @param UserFearMeterRestrictionStoreRequest $request
     * @param UserGameTitleFearMeterCommentReport $report
     * @return RedirectResponse
     */
    public function restrictUser(
        UserFearMeterRestrictionStoreRequest $request,
        UserGameTitleFearMeterCommentReport $report
    ): RedirectResponse {
        $report->load('fearMeterLog');
        $targetUserId = $report->fearMeterLog?->user_id;
        if ($targetUserId === null) {
            return redirect()->back()->with('warning', '対象ユーザーが見つかりません。');
        }

        $activeExists = UserFearMeterRestriction::query()
            ->where('user_id', $targetUserId)
            ->active()
            ->exists();
        if ($activeExists) {
            return redirect()->back()->with('warning', 'このユーザーは既に入力制限中です。');
        }

        UserFearMeterRestriction::create([
            'user_id' => $targetUserId,
            'reason' => $request->validated('reason'),
            'source' => $request->validated('source', 'report_threshold'),
            'is_active' => true,
            'started_at' => now(),
            'created_by_admin_id' => Auth::guard('admin')->id(),
        ]);

        return redirect()->route('Admin.Manage.FearMeterReport.Show', $report)
            ->with('success', '対象ユーザーを怖さメーター入力制限にしました。');
    }
}
