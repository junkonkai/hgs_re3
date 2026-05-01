<?php

namespace App\Models;

use App\Enums\PlayStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Sqids\Sqids;

class UserGameTitleReview extends Model
{
    use HasFactory;
    protected $fillable = [
        'key',
        'user_id',
        'game_title_id',
        'is_hidden',
        'hidden_by_admin_id',
        'hidden_at',
        'play_status',
        'body',
        'has_spoiler',
        'score_story',
        'score_atmosphere',
        'score_gameplay',
        'user_score_adjustment',
        'base_score',
        'total_score',
        'current_log_id',
        'ogp_image_filename',
        'is_deleted',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->key)) {
                $sqids = new Sqids(
                    alphabet: config('services.sqids.alphabet'),
                    minLength: config('services.sqids.min_length'),
                );
                $model->key = $sqids->encode([$model->game_title_id, $model->user_id]);
            }
        });
    }

    protected $casts = [
        'is_hidden'    => 'boolean',
        'hidden_at'    => 'datetime',
        'play_status'  => PlayStatus::class,
        'has_spoiler'  => 'boolean',
        'is_deleted'   => 'boolean',
    ];

    /**
     * ユーザー
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
     * プレイ環境
     *
     * @return HasMany
     */
    public function packages(): HasMany
    {
        return $this->hasMany(UserGameTitleReviewPackage::class, 'review_id');
    }

    /**
     * スナップショットログ
     *
     * @return HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(UserGameTitleReviewLog::class, 'review_id');
    }

    /**
     * 現在の公開バージョンのログ
     *
     * @return BelongsTo
     */
    public function currentLog(): BelongsTo
    {
        return $this->belongsTo(UserGameTitleReviewLog::class, 'current_log_id');
    }

    /**
     * いいね
     *
     * @return HasMany
     */
    public function likes(): HasMany
    {
        return $this->hasMany(UserGameTitleReviewLike::class, 'review_id');
    }

    /**
     * 通報
     *
     * @return HasMany
     */
    public function reports(): HasMany
    {
        return $this->hasMany(UserGameTitleReviewReport::class, 'review_id');
    }

    /**
     * ベーススコアを計算する
     * ベーススコア = 怖さ×10 + ストーリー + 雰囲気 + ゲーム性
     *
     * @param int|null $fearMeter
     * @param int|null $story
     * @param int|null $atmosphere
     * @param int|null $gameplay
     * @return int|null
     */
    public static function calcBaseScore(?int $fearMeter, ?int $story, ?int $atmosphere, ?int $gameplay): ?int
    {
        if ($fearMeter === null && $story === null && $atmosphere === null && $gameplay === null) {
            return null;
        }

        return ($fearMeter ?? 0) * 10
            + ($story ?? 0)
            + ($atmosphere ?? 0)
            + ($gameplay ?? 0);
    }

    /**
     * 総合スコアを計算する
     * 総合スコア = clamp(ベーススコア + ユーザー調整, 0, 100)
     *
     * @param int|null $base
     * @param int|null $adjustment
     * @return int|null
     */
    public static function calcTotalScore(?int $base, ?int $adjustment): ?int
    {
        if ($base === null) {
            return null;
        }

        return max(0, min(100, $base + ($adjustment ?? 0)));
    }
}
