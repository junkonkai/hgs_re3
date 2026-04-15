<?php

namespace App\Models;

use App\Enums\PlayStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGameTitleReviewLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'review_id',
        'user_id',
        'version',
        'play_status',
        'game_package_ids',
        'body',
        'has_spoiler',
        'score_story',
        'score_atmosphere',
        'score_gameplay',
        'user_score_adjustment',
        'base_score',
        'total_score',
        'horror_type_tags',
    ];

    protected $casts = [
        'play_status'       => PlayStatus::class,
        'game_package_ids'  => 'array',
        'has_spoiler'       => 'boolean',
        'horror_type_tags'  => 'array',
        'created_at'        => 'datetime',
    ];

    /**
     * レビュー
     *
     * @return BelongsTo
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(UserGameTitleReview::class, 'review_id');
    }

    /**
     * ユーザー
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
