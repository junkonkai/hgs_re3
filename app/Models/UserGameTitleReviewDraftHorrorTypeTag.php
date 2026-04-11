<?php

namespace App\Models;

use App\Enums\HorrorTypeTag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGameTitleReviewDraftHorrorTypeTag extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'draft_id',
        'tag',
    ];

    protected $casts = [
        'tag' => HorrorTypeTag::class,
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
}
