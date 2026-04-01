<?php

namespace App\Models;

use App\Enums\FearMeter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GameTitleFearMeterStatistic extends Model
{
    public const CREATED_AT = null;

    protected $primaryKey = 'game_title_id';
    public $incrementing = false;
    protected $hidden = ['updated_at'];

    protected $fillable = [
        'game_title_id',
        'average_rating',
        'fear_meter',
        'total_count',
        'rating_0_count',
        'rating_1_count',
        'rating_2_count',
        'rating_3_count',
        'rating_4_count',
    ];

    protected $casts = [
        'average_rating' => 'decimal:2',
        'fear_meter' => FearMeter::class,
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
        // UserGameTitleFearMeterから集計
        $ratings = UserGameTitleFearMeter::where('game_title_id', $this->game_title_id)
            ->select('fear_meter', DB::raw('COUNT(*) as count'))
            ->groupBy('fear_meter')
            ->pluck('count', 'fear_meter')
            ->toArray();

        $totalCount = array_sum($ratings);
        $rating0Count = $ratings[0] ?? 0;
        $rating1Count = $ratings[1] ?? 0;
        $rating2Count = $ratings[2] ?? 0;
        $rating3Count = $ratings[3] ?? 0;
        $rating4Count = $ratings[4] ?? 0;

        // 平均評価を計算
        $sum = 0;
        foreach ($ratings as $rating => $count) {
            $sum += $rating * $count;
        }
        $averageRating = $totalCount > 0 ? round($sum / $totalCount, 2) : 0.00;
        
        // 四捨五入した値をfear_meterに設定
        $fearMeterValue = (int) round($averageRating);
        $fearMeterValue = max(0, min(4, $fearMeterValue));

        if ($totalCount === 0) {
            if ($this->exists) {
                $this->delete();
            }

            return $this;
        }

        // インスタンスの属性を更新して保存
        $this->average_rating = $averageRating;
        $this->fear_meter = $fearMeterValue;
        $this->total_count = $totalCount;
        $this->rating_0_count = $rating0Count;
        $this->rating_1_count = $rating1Count;
        $this->rating_2_count = $rating2Count;
        $this->rating_3_count = $rating3Count;
        $this->rating_4_count = $rating4Count;
        $this->save();

        return $this;
    }
}
