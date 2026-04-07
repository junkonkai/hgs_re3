<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorAuthCode extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'expires_at',
        'failed_attempts',
        'resend_count',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * 有効期限切れか
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * 失敗回数上限に達してロックされているか
     */
    public function isLocked(): bool
    {
        return $this->failed_attempts >= 5;
    }

    /**
     * 再送信上限に達しているか（最大2回再送＝計3通）
     */
    public function canResend(): bool
    {
        return $this->resend_count < 2;
    }

    /**
     * 対象ユーザー
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
