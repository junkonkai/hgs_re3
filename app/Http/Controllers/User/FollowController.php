<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserGameTitleReviewLike;
use App\Support\Pager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FollowController extends Controller
{
    /**
     * お気に入りタイトル一覧
     *
     * @return JsonResponse|Application|Factory|View
     */
    public function favoriteTitles(): JsonResponse|Application|Factory|View
    {
        $user = Auth::user();
        $favoriteTitles = $user->favoriteGameTitles()->orderBy('created_at', 'desc')->get();

        return $this->tree(view('user.follow.favorite_titles', compact('favoriteTitles')), options: ['url' => route('User.Follow.FavoriteTitles')]);
    }

    /**
     * いいねしたレビュー一覧
     *
     * @return JsonResponse|Application|Factory|View
     */
    public function reviewLikes(): JsonResponse|Application|Factory|View
    {
        $user = Auth::user();

        $likes = UserGameTitleReviewLike::where('user_id', $user->id)
            ->with(['review.user', 'review.gameTitle'])
            ->orderByDesc('created_at')
            ->paginate(10);

        $pager = new Pager($likes->currentPage(), $likes->lastPage(), 'User.MyNode.ReviewLikes', [], 'children');

        return $this->tree(
            view('user.my_node.review_likes', compact('likes', 'pager')),
            options: ['url' => route('User.MyNode.ReviewLikes')]
        );
    }
}

