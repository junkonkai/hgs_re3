<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGameTitleReviewDraftPackage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'draft_id',
        'game_package_id',
    ];

    /**
     * 下書き
     *
     * @return BelongsTo
     */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(UserGameTitleReviewDraft::class, 'draft_id');
    }

    /**
     * ゲームパッケージ
     *
     * @return BelongsTo
     */
    public function gamePackage(): BelongsTo
    {
        return $this->belongsTo(GamePackage::class, 'game_package_id');
    }
}
