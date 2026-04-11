<?php

namespace App\Models;

use App\Enums\HorrorTypeTag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGameTitleReviewHorrorTypeTag extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'review_id',
        'tag',
    ];

    protected $casts = [
        'tag' => HorrorTypeTag::class,
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
}
