<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGameTitleReviewPackage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'review_id',
        'game_package_id',
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
     * ゲームパッケージ
     *
     * @return BelongsTo
     */
    public function gamePackage(): BelongsTo
    {
        return $this->belongsTo(GamePackage::class, 'game_package_id');
    }
}
