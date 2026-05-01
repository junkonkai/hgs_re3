<?php

namespace App\Models;

use App\Enums\PlayStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserGameTitleReviewDraft extends Model
{
    protected $fillable = [
        'user_id',
        'game_title_id',
        'review_id',
        'play_status',
        'body',
        'has_spoiler',
        'score_story',
        'score_atmosphere',
        'score_gameplay',
        'user_score_adjustment',
    ];

    protected $casts = [
        'play_status' => PlayStatus::class,
        'has_spoiler' => 'boolean',
    ];

    /**
     * ユーザー
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ゲームタイトル
     *
     * @return BelongsTo
     */
    public function gameTitle(): BelongsTo
    {
        return $this->belongsTo(GameTitle::class, 'game_title_id');
    }

    /**
     * 編集中の公開済みレビュー
     *
     * @return BelongsTo
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(UserGameTitleReview::class, 'review_id');
    }

    /**
     * 下書き用プレイ環境
     *
     * @return HasMany
     */
    public function packages(): HasMany
    {
        return $this->hasMany(UserGameTitleReviewDraftPackage::class, 'draft_id');
    }
}
