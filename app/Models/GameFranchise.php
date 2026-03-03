<?php

namespace App\Models;

use App\Models\Extensions\KeyFindTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use App\Enums\Rating;

class GameFranchise extends Model
{
    use KeyFindTrait;

    protected $guarded = ['id'];
    protected $hidden = ['created_at', 'updated_at'];
    protected $casts = [
        'rating' => Rating::class,
        'last_title_update_at' => 'datetime',
    ];

    /**
     * @var array デフォルト値
     */
    protected $attributes = [
        'name'     => '',
        'phonetic' => '',
        'rating'   => Rating::None,
    ];

    /**
     * シリーズ
     *
     * @return HasMany
     */
    public function series(): HasMany
    {
        return $this->hasMany(GameSeries::class, 'game_franchise_id');
    }

    /**
     * タイトル
     *
     * @return HasMany
     */
    public function titles(): HasMany
    {
        return $this->hasMany(GameTitle::class, 'game_franchise_id');
    }

    /**
     * メディアミックスグループ
     *
     * @return HasMany
     */
    public function mediaMixGroups(): HasMany
    {
        return $this->hasMany(GameMediaMixGroup::class);
    }

    /**
     * メディアミックス
     *
     * @return HasMany
     */
    public function mediaMixes(): HasMany
    {
        return $this->hasMany(GameMediaMix::class);
    }

    /**
     * 前のフランチャイズを取得
     *
     * @return self
     */
    public function prev(): self
    {
        $prev = self::where('id', '<', $this->id)->orderBy('id', 'desc')->first();
        if ($prev !== null) {
            return $prev;
        } else {
            // idが最大のデータを取得
            return self::orderBy('id', 'desc')->first();
        }
    }

    /**
     * 次のフランチャイズを取得
     *
     * @return self
     */
    public function next(): self
    {
        $next = self::where('id', '>', $this->id)->orderBy('id', 'asc')->first();
        if ($next !== null) {
            return $next;
        } else {
            // idが最小のデータを取得
            return self::orderBy('id', 'asc')->first();
        }
    }

    /**
     * タイトル数を取得
     *
     * @return int
     */
    public function getTitleNum(): int
    {
        $num = 0;

        $this->series()->each(function (GameSeries $series) use (&$num) {
            $num += $series->titles()->count();
        });

        $num += $this->titles()->count();

        return $num;
    }

    /**
     * タイトルから設定するパラメーター
     *
     * @return self
     */
    public function setTitleParam(): self
    {
        $this->rating = Rating::None;
        foreach ($this->titles as $title) {
            if ($title->rating == Rating::R18A) {
                $this->rating = Rating::R18A;
                break;
            }
        }

        return $this;
    }
}
