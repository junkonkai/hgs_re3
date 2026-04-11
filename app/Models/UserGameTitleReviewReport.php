<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGameTitleReviewReport extends Model
{
    protected $fillable = [
        'user_id',
        'review_id',
        'review_log_id',
        'reason',
        'is_resolved',
        'resolved_by_admin_id',
        'resolved_at',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    /**
     * 通報者
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
     * 通報時点のバージョンログ
     *
     * @return BelongsTo
     */
    public function reviewLog(): BelongsTo
    {
        return $this->belongsTo(UserGameTitleReviewLog::class, 'review_log_id');
    }
}
