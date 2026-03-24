<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFearMeterRestriction extends Model
{
    protected $fillable = [
        'user_id',
        'reason',
        'source',
        'is_active',
        'started_at',
        'ended_at',
        'created_by_admin_id',
        'released_by_admin_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * 有効な制限
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $builder) {
                $builder->whereNull('ended_at')
                    ->orWhere('ended_at', '>', now());
            });
    }

    /**
     * 対象ユーザー
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
