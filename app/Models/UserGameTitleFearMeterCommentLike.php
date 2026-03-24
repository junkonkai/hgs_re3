<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGameTitleFearMeterCommentLike extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'fear_meter_log_id',
        'user_id',
        'created_at',
    ];

    /**
     * 怖さメーターログ
     *
     * @return BelongsTo
     */
    public function fearMeterLog(): BelongsTo
    {
        return $this->belongsTo(UserGameTitleFearMeterLog::class, 'fear_meter_log_id');
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
