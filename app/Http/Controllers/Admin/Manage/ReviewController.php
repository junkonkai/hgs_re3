<?php

namespace App\Http\Controllers\Admin\Manage;

use App\Defines\AdminDefine;
use App\Http\Controllers\Admin\AbstractAdminController;
use App\Models\ReviewStatisticsDirtyTitle;
use App\Models\User;
use App\Models\UserGameTitleFearMeter;
use App\Models\UserGameTitleReview;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends AbstractAdminController
{
    /**
     * レビュー一覧
     *
     * @param Request $request
     * @return Application|Factory|View
     */
    public function index(Request $request): Application|Factory|View
    {
        $query = UserGameTitleReview::query()
            ->with(['user', 'gameTitle'])
            ->orderByDesc('id');

        $keyword = trim($request->query('keyword', ''));
        $status  = $request->query('status', '');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->whereHas('user', fn($u) => $u->where('name', 'like', '%' . $keyword . '%'))
                    ->orWhereHas('gameTitle', fn($g) => $g->where('name', 'like', '%' . $keyword . '%'));
            });
        }

        if ($status === 'deleted') {
            $query->where('is_deleted', true);
        } elseif ($status === 'hidden') {
            $query->where('is_hidden', true)->where('is_deleted', false);
        } elseif ($status === 'public') {
            $query->where('is_hidden', false)->where('is_deleted', false);
        }

        $search = [
            'keyword' => $keyword,
            'status'  => $status,
        ];

        $this->saveSearchSession($search);

        return view('admin.manage.review.index', [
            'reviews' => $query->paginate(AdminDefine::ITEMS_PER_PAGE),
            'search'  => $search,
        ]);
    }

    /**
     * レビュー詳細
     *
     * @param UserGameTitleReview $review
     * @return Application|Factory|View
     */
    public function show(UserGameTitleReview $review): Application|Factory|View
    {
        $review->load([
            'user',
            'gameTitle',
            'packages.gamePackage',
            'likes',
            'reports',
        ]);

        $fearMeter = UserGameTitleFearMeter::where('user_id', $review->user_id)
            ->where('game_title_id', $review->game_title_id)
            ->first();

        return view('admin.manage.review.detail', [
            'review'    => $review,
            'fearMeter' => $fearMeter,
        ]);
    }

    /**
     * レビュー詳細（ユーザー文脈）
     *
     * @param User $user
     * @param UserGameTitleReview $review
     * @return Application|Factory|View
     */
    public function showForUser(User $user, UserGameTitleReview $review): Application|Factory|View
    {
        if ($review->user_id !== $user->id) {
            abort(404);
        }

        $this->overwriteBreadcrumb([
            'Reviews' => route('Admin.Manage.User.Reviews', $user),
        ]);

        $review->load([
            'user',
            'gameTitle',
            'packages.gamePackage',
            'likes',
            'reports',
        ]);

        $fearMeter = UserGameTitleFearMeter::where('user_id', $review->user_id)
            ->where('game_title_id', $review->game_title_id)
            ->first();

        return view('admin.manage.review.detail', [
            'review'    => $review,
            'fearMeter' => $fearMeter,
            'backUrl'   => route('Admin.Manage.User.Reviews', $user),
        ]);
    }

    /**
     * レビューへの通報一覧
     *
     * @param UserGameTitleReview $review
     * @return Application|Factory|View
     */
    public function reports(UserGameTitleReview $review): Application|Factory|View
    {
        $review->load(['user', 'gameTitle', 'reports.user']);

        return view('admin.manage.review.reports', [
            'review' => $review,
        ]);
    }

    /**
     * レビューを強制削除（論理削除）
     *
     * @param UserGameTitleReview $review
     * @return RedirectResponse
     */
    public function forceDelete(UserGameTitleReview $review): RedirectResponse
    {
        if ($review->is_deleted) {
            return redirect()->back()->with('warning', 'このレビューは既に削除済みです。');
        }

        $review->is_deleted = true;
        $review->save();

        ReviewStatisticsDirtyTitle::updateOrCreate(['game_title_id' => $review->game_title_id]);

        return redirect()->route('Admin.Manage.Review.Reports', $review)
            ->with('success', 'レビューを削除しました。');
    }
}
