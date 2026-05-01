<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class GameTitleReviewStatistic extends Model
{
    public const CREATED_AT = null;

    protected $table = 'game_title_review_statistics';

    protected $primaryKey = 'game_title_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'game_title_id',
        'review_count',
        'avg_total_score',
        'avg_story',
        'avg_atmosphere',
        'avg_gameplay',
    ];

    protected $casts = [
        'avg_total_score' => 'decimal:2',
        'avg_story'       => 'decimal:2',
        'avg_atmosphere'  => 'decimal:2',
        'avg_gameplay'    => 'decimal:2',
    ];

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
     * この統計を再計算して保存
     *
     * @return self
     */
    public function recalculate(): self
    {
        $reviews = UserGameTitleReview::where('game_title_id', $this->game_title_id)
            ->where('is_deleted', false)
            ->where('is_hidden', false)
            ->select([
                DB::raw('COUNT(*) as review_count'),
                DB::raw('AVG(total_score) as avg_total_score'),
                DB::raw('AVG(score_story) as avg_story'),
                DB::raw('AVG(score_atmosphere) as avg_atmosphere'),
                DB::raw('AVG(score_gameplay) as avg_gameplay'),
            ])
            ->first();

        $reviewCount = (int) $reviews->review_count;

        if ($reviewCount === 0) {
            if ($this->exists) {
                $this->delete();
            }

            return $this;
        }

        $this->review_count    = $reviewCount;
        $this->avg_total_score = $reviews->avg_total_score !== null ? round((float) $reviews->avg_total_score, 2) : null;
        $this->avg_story       = $reviews->avg_story !== null ? round((float) $reviews->avg_story, 2) : null;
        $this->avg_atmosphere  = $reviews->avg_atmosphere !== null ? round((float) $reviews->avg_atmosphere, 2) : null;
        $this->avg_gameplay    = $reviews->avg_gameplay !== null ? round((float) $reviews->avg_gameplay, 2) : null;
        $this->save();

        return $this;
    }
}
