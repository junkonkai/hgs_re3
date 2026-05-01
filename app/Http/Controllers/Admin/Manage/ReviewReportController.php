<?php

namespace App\Http\Controllers\Admin\Manage;

use App\Defines\AdminDefine;
use App\Http\Controllers\Admin\AbstractAdminController;
use App\Http\Requests\Admin\Manage\ReviewReportStatusUpdateRequest;
use App\Models\ReviewStatisticsDirtyTitle;
use App\Models\UserGameTitleReviewReport;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReviewReportController extends AbstractAdminController
{
    /**
     * 通報一覧
     *
     * @param Request $request
     * @return Application|Factory|View
     */
    public function index(Request $request): Application|Factory|View
    {
        $query = UserGameTitleReviewReport::query()
            ->with(['review.user', 'review.gameTitle', 'user'])
            ->orderByDesc('created_at');

        $search = [
            'is_resolved' => (string) $request->query('is_resolved', ''),
        ];

        if ($search['is_resolved'] !== '') {
            $query->where('is_resolved', $search['is_resolved'] === '1');
        }

        $this->saveSearchSession($search);

        return view('admin.manage.review_report.index', [
            'reports' => $query->paginate(AdminDefine::ITEMS_PER_PAGE),
            'search' => $search,
        ]);
    }

    /**
     * 通報詳細
     *
     * @param UserGameTitleReviewReport $report
     * @return Application|Factory|View
     */
    public function show(UserGameTitleReviewReport $report): Application|Factory|View
    {
        $report->load(['review.user', 'review.gameTitle', 'user', 'reviewLog']);

        return view('admin.manage.review_report.show', [
            'report' => $report,
        ]);
    }

    /**
     * 通報ステータス更新
     *
     * @param ReviewReportStatusUpdateRequest $request
     * @param UserGameTitleReviewReport $report
     * @return RedirectResponse
     */
    public function updateStatus(
        ReviewReportStatusUpdateRequest $request,
        UserGameTitleReviewReport $report
    ): RedirectResponse {
        $report->is_resolved = $request->validated('is_resolved');
        $report->resolved_by_admin_id = Auth::guard('admin')->id();
        $report->resolved_at = $report->is_resolved ? now() : null;
        $report->save();

        return redirect()->route('Admin.Manage.ReviewReport.Show', $report)
            ->with('success', '通報ステータスを更新しました。');
    }

    /**
     * レビューを非表示にする
     *
     * @param UserGameTitleReviewReport $report
     * @return RedirectResponse
     */
    public function hideReview(UserGameTitleReviewReport $report): RedirectResponse
    {
        $report->load('review');
        $review = $report->review;

        if ($review === null || $review->is_deleted) {
            return redirect()->back()->with('warning', 'レビューが見つかりません。');
        }

        if ($review->is_hidden) {
            return redirect()->back()->with('warning', 'このレビューは既に非表示です。');
        }

        $review->is_hidden = true;
        $review->hidden_by_admin_id = Auth::guard('admin')->id();
        $review->hidden_at = now();
        $review->save();

        ReviewStatisticsDirtyTitle::updateOrCreate(['game_title_id' => $review->game_title_id]);

        return redirect()->route('Admin.Manage.ReviewReport.Show', $report)
            ->with('success', 'レビューを非表示にしました。');
    }

    /**
     * レビューを物理削除する
     *
     * @param UserGameTitleReviewReport $report
     * @return RedirectResponse
     */
    public function deleteReview(UserGameTitleReviewReport $report): RedirectResponse
    {
        $report->load('review');
        $review = $report->review;

        if ($review === null) {
            return redirect()->back()->with('warning', 'レビューが見つかりません（既に削除済みの可能性があります）。');
        }

        $gameTitleId = $review->game_title_id;

        DB::transaction(function () use ($review) {
            $review->likes()->delete();
            $review->reports()->delete();
            $review->logs()->delete();
            $review->packages()->delete();
            $review->delete();
        });

        ReviewStatisticsDirtyTitle::updateOrCreate(['game_title_id' => $gameTitleId]);

        return redirect()->route('Admin.Manage.ReviewReport.Show', $report)
            ->with('success', 'レビューを削除しました。');
    }
}
