<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGameTitleReviewLike extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'review_id',
        'review_log_id',
    ];

    /**
     * いいねしたユーザー
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
     * いいねした時点のバージョンログ
     *
     * @return BelongsTo
     */
    public function reviewLog(): BelongsTo
    {
        return $this->belongsTo(UserGameTitleReviewLog::class, 'review_log_id');
    }
}
