<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGameTitleFearMeterCommentReport extends Model
{
    protected $fillable = [
        'fear_meter_log_id',
        'reporter_user_id',
        'reason',
        'status',
        'reviewed_by_admin_id',
        'reviewed_at',
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
     * 通報者ユーザー
     *
     * @return BelongsTo
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    /**
     * レビュー管理者
     *
     * @return BelongsTo
     */
    public function reviewedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_admin_id');
    }
}
