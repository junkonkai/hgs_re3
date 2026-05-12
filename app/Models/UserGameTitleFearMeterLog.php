<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class UserGameTitleFearMeterLog extends Model
{
    protected $hidden = [];

    protected $fillable = [
        'user_id',
        'game_title_id',
        'old_fear_meter',
        'new_fear_meter',
        'comment',
        'is_deleted',
        'deleted_at',
        'deleted_by_user_id',
        'deleted_by_admin_id',
        'action',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    const UPDATED_AT = null;

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
     * いいね
     *
     * @return HasMany
     */
    public function likes(): HasMany
    {
        return $this->hasMany(UserGameTitleFearMeterCommentLike::class, 'fear_meter_log_id');
    }

    /**
     * 通報
     *
     * @return HasMany
     */
    public function reports(): HasMany
    {
        return $this->hasMany(UserGameTitleFearMeterCommentReport::class, 'fear_meter_log_id');
    }

    /**
     * 公開対象コメントのみ
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeVisibleComments(Builder $query): Builder
    {
        return $query->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->where('is_deleted', false);
    }
}
