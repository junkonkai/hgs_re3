<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewDestroyRequest;
use App\Http\Requests\ReviewDraftSaveRequest;
use App\Http\Requests\ReviewPublishRequest;
use App\Jobs\GenerateReviewOgpImage;
use App\Models\FearMeterStatisticsDirtyTitle;
use App\Models\GameTitle;
use App\Models\ReviewStatisticsDirtyTitle;
use App\Models\UserGameTitleFearMeter;
use App\Models\UserGameTitleFearMeterDraft;
use App\Models\UserGameTitleFearMeterLog;
use App\Models\UserGameTitleReview;
use App\Models\UserGameTitleReviewDraft;
use App\Models\UserGameTitleReviewDraftPackage;
use App\Models\UserGameTitleReviewLog;
use App\Models\UserGameTitleReviewPackage;
use App\Support\Pager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * マイレビュー一覧
     *
     * @return JsonResponse|Application|Factory|View
     */
    public function index(): JsonResponse|Application|Factory|View
    {
        $user = Auth::user();
        $reviews = UserGameTitleReview::where('user_id', $user->id)
            ->where('is_deleted', false)
            ->with(['gameTitle'])
            ->orderBy('updated_at', 'desc')
            ->paginate(10);

        $draftTitleIds = UserGameTitleReviewDraft::where('user_id', $user->id)
            ->pluck('game_title_id')
            ->toArray();

        $fearMeters = UserGameTitleFearMeter::where('user_id', $user->id)
            ->whereIn('game_title_id', $reviews->pluck('game_title_id'))
            ->get()
            ->keyBy('game_title_id');

        $pager = new Pager($reviews->currentPage(), $reviews->lastPage(), 'User.Review.Index', [], 'children');

        return $this->tree(
            view('user.review.index', compact('reviews', 'draftTitleIds', 'fearMeters', 'pager')),
            options: [
                'url' => route('User.Review.Index'),
            ]
        );
    }

    /**
     * レビュー投稿・編集フォーム
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

        $review = UserGameTitleReview::where('user_id', $user->id)
            ->where('game_title_id', $title->id)
            ->where('is_deleted', false)
            ->with(['packages'])
            ->first();

        $draft = UserGameTitleReviewDraft::where('user_id', $user->id)
            ->where('game_title_id', $title->id)
            ->with(['packages'])
            ->first();

        $fearMeter = UserGameTitleFearMeter::where('user_id', $user->id)
            ->where('game_title_id', $title->id)
            ->first();

        $fearMeterDraft = UserGameTitleFearMeterDraft::where('user_id', $user->id)
            ->where('game_title_id', $title->id)
            ->first();

        $fearMeterLogComment = UserGameTitleFearMeterLog::query()
            ->where('user_id', $user->id)
            ->where('game_title_id', $title->id)
            ->where('is_deleted', false)
            ->latest('id')
            ->value('comment');

        $title->loadMissing(['packageGroups.packages.platform']);
        $packages = $title->packageGroups->flatMap(fn ($pg) => $pg->packages)->unique('id')->values();

        return $this->tree(
            view('user.review.form', compact(
                'title', 'review', 'draft', 'fearMeter', 'fearMeterDraft', 'fearMeterLogComment', 'packages',
            )),
            options: [
                'csrfToken' => csrf_token(),
                'components' => [
                    'FearMeterFormInput' => [],
                    'ReviewFormInput' => [],
                ],
                'url' => route('User.Review.Form', ['titleKey' => $title->key]),
            ]
        );
    }

    /**
     * 下書き保存
     *
     * @param ReviewDraftSaveRequest $request
     * @return RedirectResponse
     */
    public function saveDraft(ReviewDraftSaveRequest $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $title = GameTitle::findByKey($request->validated('title_key'));
        if (!$title) {
            abort(404);
        }

        $existingReview = UserGameTitleReview::where('user_id', $user->id)
            ->where('game_title_id', $title->id)
            ->where('is_deleted', false)
            ->first();

        $draftData = [
            'review_id'            => $existingReview?->id,
            'play_status'          => $request->validated('play_status'),
            'body'                 => $request->validated('body'),
            'has_spoiler'          => (bool) $request->validated('has_spoiler', false),
            'score_story'          => $request->validated('score_story') !== null ? (int) $request->validated('score_story') : null,
            'score_atmosphere'     => $request->validated('score_atmosphere') !== null ? (int) $request->validated('score_atmosphere') : null,
            'score_gameplay'       => $request->validated('score_gameplay') !== null ? (int) $request->validated('score_gameplay') : null,
            'user_score_adjustment' => $request->validated('user_score_adjustment') !== null ? (int) $request->validated('user_score_adjustment') : null,
        ];

        $draft = UserGameTitleReviewDraft::updateOrCreate(
            ['user_id' => $user->id, 'game_title_id' => $title->id],
            $draftData
        );

        $draft->packages()->delete();
        foreach ($request->validated('packages', []) ?? [] as $packageId) {
            UserGameTitleReviewDraftPackage::create([
                'draft_id'        => $draft->id,
                'game_package_id' => (int) $packageId,
            ]);
        }

        // 怖さメータードラフト保存
        if ($request->validated('fear_meter') !== null) {
            $rawComment = trim((string) $request->validated('fear_meter_comment', ''));
            UserGameTitleFearMeterDraft::updateOrCreate(
                ['user_id' => $user->id, 'game_title_id' => $title->id],
                [
                    'fear_meter' => (int) $request->validated('fear_meter'),
                    'comment'    => $rawComment === '' ? null : $rawComment,
                ]
            );
        }

        return redirect()->route('User.Review.Form', ['titleKey' => $title->key])
            ->with('success', '下書きを保存しました。');
    }

    /**
     * レビューを公開
     *
     * @param ReviewPublishRequest $request
     * @return RedirectResponse
     */
    public function publish(ReviewPublishRequest $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $title = GameTitle::findByKey($request->validated('title_key'));
        if (!$title) {
            abort(404);
        }

        $fearMeterValue = (int) $request->validated('fear_meter');
        $story      = (int) $request->validated('score_story');
        $atmosphere = (int) $request->validated('score_atmosphere');
        $gameplay   = (int) $request->validated('score_gameplay');
        $adjustment = $request->validated('user_score_adjustment') !== null ? (int) $request->validated('user_score_adjustment') : null;

        $rawComment = trim((string) $request->validated('fear_meter_comment', ''));
        $fearMeterComment = $rawComment === '' ? null : $rawComment;

        $baseScore  = UserGameTitleReview::calcBaseScore($fearMeterValue, $story, $atmosphere, $gameplay);
        $totalScore = UserGameTitleReview::calcTotalScore($baseScore, $adjustment);

        DB::transaction(function () use ($user, $title, $request, $fearMeterValue, $fearMeterComment, $story, $atmosphere, $gameplay, $adjustment, $baseScore, $totalScore) {
            // 1. 怖さメーター保存
            $existingFearMeter = UserGameTitleFearMeter::where('user_id', $user->id)
                ->where('game_title_id', $title->id)
                ->first();

            if ($existingFearMeter) {
                $oldFearMeterValue = $existingFearMeter->fear_meter->value;
                UserGameTitleFearMeter::where('user_id', $user->id)
                    ->where('game_title_id', $title->id)
                    ->update(['fear_meter' => $fearMeterValue]);
                UserGameTitleFearMeterLog::create([
                    'user_id'        => $user->id,
                    'game_title_id'  => $title->id,
                    'old_fear_meter' => $oldFearMeterValue,
                    'new_fear_meter' => $fearMeterValue,
                    'comment'        => $fearMeterComment,
                    'action'         => 2,
                ]);
                if ($oldFearMeterValue !== $fearMeterValue) {
                    FearMeterStatisticsDirtyTitle::updateOrCreate(['game_title_id' => $title->id]);
                }
            } else {
                UserGameTitleFearMeter::create([
                    'user_id'       => $user->id,
                    'game_title_id' => $title->id,
                    'fear_meter'    => $fearMeterValue,
                ]);
                UserGameTitleFearMeterLog::create([
                    'user_id'        => $user->id,
                    'game_title_id'  => $title->id,
                    'old_fear_meter' => null,
                    'new_fear_meter' => $fearMeterValue,
                    'comment'        => $fearMeterComment,
                    'action'         => 1,
                ]);
                FearMeterStatisticsDirtyTitle::updateOrCreate(['game_title_id' => $title->id]);
            }

            // 2. レビュー保存
            $reviewData = [
                'play_status'          => $request->validated('play_status'),
                'body'                 => $request->validated('body'),
                'has_spoiler'          => (bool) $request->validated('has_spoiler', false),
                'score_story'          => $story,
                'score_atmosphere'     => $atmosphere,
                'score_gameplay'       => $gameplay,
                'user_score_adjustment' => $adjustment,
                'base_score'           => $baseScore,
                'total_score'          => $totalScore,
                'is_deleted'           => false,
            ];

            $existingReview = UserGameTitleReview::where('user_id', $user->id)
                ->where('game_title_id', $title->id)
                ->first();

            if ($existingReview) {
                foreach ($reviewData as $key => $val) {
                    $existingReview->$key = $val;
                }
                $existingReview->save();
                $review = $existingReview;
            } else {
                $review = UserGameTitleReview::create(array_merge($reviewData, [
                    'user_id'       => $user->id,
                    'game_title_id' => $title->id,
                ]));
            }

            // 3. スナップショットログ記録
            $maxVersion  = UserGameTitleReviewLog::where('review_id', $review->id)->max('version') ?? 0;
            $packageIds  = $request->validated('packages') ? array_map('intval', (array) $request->validated('packages')) : null;

            $log = UserGameTitleReviewLog::create([
                'review_id'            => $review->id,
                'user_id'              => $user->id,
                'version'              => $maxVersion + 1,
                'play_status'          => $reviewData['play_status'],
                'game_package_ids'     => $packageIds,
                'body'                 => $reviewData['body'],
                'has_spoiler'          => $reviewData['has_spoiler'],
                'score_story'          => $story,
                'score_atmosphere'     => $atmosphere,
                'score_gameplay'       => $gameplay,
                'user_score_adjustment' => $adjustment,
                'base_score'           => $baseScore,
                'total_score'          => $totalScore,
            ]);

            // 4. current_log_id 更新
            $review->current_log_id = $log->id;
            $review->save();

            // 5. パッケージ・ホラータグ sync
            $review->packages()->delete();
            foreach ($request->validated('packages', []) ?? [] as $packageId) {
                UserGameTitleReviewPackage::create([
                    'review_id'       => $review->id,
                    'game_package_id' => (int) $packageId,
                ]);
            }

            // 6. 下書き削除・怖さメータードラフト削除
            UserGameTitleReviewDraft::where('user_id', $user->id)
                ->where('game_title_id', $title->id)
                ->delete();

            UserGameTitleFearMeterDraft::where('user_id', $user->id)
                ->where('game_title_id', $title->id)
                ->delete();

            // 7. dirty flag
            ReviewStatisticsDirtyTitle::updateOrCreate(['game_title_id' => $title->id]);
        });

        $publishedReview = UserGameTitleReview::where('user_id', $user->id)
            ->where('game_title_id', $title->id)
            ->where('is_deleted', false)
            ->value('id');

        if ($publishedReview) {
            GenerateReviewOgpImage::dispatch($publishedReview);
        }

        return redirect()->route('User.Review.Index')
            ->with('success', 'レビューを投稿しました。' . PHP_EOL . '総合スコアへの反映はしばらく時間がかかります。');
    }

    /**
     * 下書きを破棄
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function discardDraft(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $request->validate([
            'title_key' => ['required', 'string', 'exists:game_titles,key'],
        ]);

        $title = GameTitle::findByKey($request->input('title_key'));
        if (!$title) {
            abort(404);
        }

        UserGameTitleReviewDraft::where('user_id', $user->id)
            ->where('game_title_id', $title->id)
            ->delete();

        return redirect()->route('User.Review.Form', ['titleKey' => $title->key])
            ->with('success', '下書きを破棄しました。');
    }

    /**
     * レビューを削除（ソフトデリート）
     *
     * @param ReviewDestroyRequest $request
     * @return RedirectResponse
     */
    public function destroy(ReviewDestroyRequest $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $title = GameTitle::findByKey($request->validated('title_key'));
        if (!$title) {
            abort(404);
        }

        $review = UserGameTitleReview::where('user_id', $user->id)
            ->where('game_title_id', $title->id)
            ->where('is_deleted', false)
            ->first();

        if (!$review) {
            return redirect()->back()
                ->with('warning', '削除対象のレビューが見つかりませんでした。');
        }

        $alsoDeleteFearMeter = (bool) $request->validated('also_delete_fear_meter', false);
        $ogpImagePath = $review->ogp_image_filename;

        DB::transaction(function () use ($user, $title, $review, $alsoDeleteFearMeter) {
            // 1. レビューをソフトデリート
            $review->is_deleted = true;
            $review->save();

            // 2. 怖さメーターも削除
            if ($alsoDeleteFearMeter) {
                $fearMeter = UserGameTitleFearMeter::where('user_id', $user->id)
                    ->where('game_title_id', $title->id)
                    ->first();
                if ($fearMeter) {
                    UserGameTitleFearMeter::where('user_id', $user->id)
                        ->where('game_title_id', $title->id)
                        ->delete();

                    $latestLog = UserGameTitleFearMeterLog::where('user_id', $user->id)
                        ->where('game_title_id', $title->id)
                        ->orderByDesc('id')
                        ->first();
                    if ($latestLog) {
                        $latestLog->action             = 3;
                        $latestLog->is_deleted         = true;
                        $latestLog->deleted_at         = now();
                        $latestLog->deleted_by_user_id = $user->id;
                        $latestLog->deleted_by_admin_id = null;
                        $latestLog->save();
                    }

                    FearMeterStatisticsDirtyTitle::updateOrCreate(['game_title_id' => $title->id]);
                }
            }

            // 3. 下書き削除
            UserGameTitleReviewDraft::where('user_id', $user->id)
                ->where('game_title_id', $title->id)
                ->delete();

            // 4. dirty flag
            ReviewStatisticsDirtyTitle::updateOrCreate(['game_title_id' => $title->id]);
        });

        // OGP画像ファイルを削除
        if ($ogpImagePath) {
            $fullPath = public_path('img/review/' . $ogpImagePath);
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }

        return redirect()->route('User.Review.Index')
            ->with('success', 'レビューを削除しました。');
    }
}
