<?php

namespace App\Models;

use App\Enums\Rating;
use App\Models\Extensions\KeyFindTrait;
use App\Models\Extensions\OgpTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class GameTitle extends Model
{
    use KeyFindTrait;
    use OgpTrait;
    use Searchable;

    protected $guarded = ['id'];
    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'rating' => Rating::class,
    ];

    /**
     * @var array デフォルト値
     */
    protected $attributes = [
        'rating'           => Rating::None,
        'first_release_int' => 99999999,
    ];

    /**
     * モデルの起動処理
     */
    protected static function boot(): void
    {
        parent::boot();

        static::updated(function (GameTitle $title) {
            $franchise = $title->getFranchise();
            if ($franchise !== null) {
                $franchise->update(['last_title_update_at' => now()]);
            }
        });
    }

    /**
     * フランチャイズを取得
     *
     * @return BelongsTo
     */
    public function franchise(): BelongsTo
    {
        return $this->belongsTo(GameFranchise::class, 'game_franchise_id');
    }

    /**
     * シリーズを取得
     *
     * @return BelongsTo
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(GameSeries::class, 'game_series_id');
    }

    /**
     * 自身もしくはシリーズに桃づけられたフランチャイズを取得
     *
     * @return GameFranchise|null
     */
    public function getFranchise(): ?GameFranchise
    {
        if ($this->series) {
            return $this->series->franchise;
        } else {
            return $this->franchise;
        }
    }

    /**
     * 原点のパッケージを取得
     *
     * @return HasOne
     */
    public function originalPackage(): HasOne
    {
        return $this->hasOne(GamePackage::class, 'id', 'original_package_id');
    }

    /**
     * パッケージグループを取得
     *
     * @return BelongsToMany
     */
    public function packageGroups(): BelongsToMany
    {
        return $this->belongsToMany(GamePackageGroup::class, GameTitlePackageGroupLink::class);
    }

    /**
     * 関連商品を取得
     *
     * @return BelongsToMany
     */
    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(GameRelatedProduct::class, GameTitleRelatedProductLink::class);
    }

    /**
     * メディアミックスを取得
     *
     * @return BelongsToMany
     */
    public function mediaMixes(): BelongsToMany
    {
        return $this->belongsToMany(GameMediaMix::class);
    }

    /**
     * お気に入りに登録しているユーザーを取得
     *
     * @return BelongsToMany
     */
    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, UserFavoriteGameTitle::class, 'game_title_id', 'user_id');
    }

    /**
     * 怖さメーター統計を取得
     *
     * @return HasOne
     */
    public function fearMeterStatistic(): HasOne
    {
        return $this->hasOne(GameTitleFearMeterStatistic::class, 'game_title_id');
    }

    /**
     * 紐づいているパッケージから最初の発売日を設定
     *
     * @return self
     */
    public function setFirstReleaseInt(): self
    {
        $minReleaseInt = 99999999;

        foreach ($this->packageGroups as $pkgGroup) {
            foreach ($pkgGroup->packages as $pkg) {
                if ($pkg->sort_order < $minReleaseInt) {
                    $minReleaseInt = $pkg->sort_order;
                }
            }
        }

        $this->first_release_int = $minReleaseInt;
        return $this;
    }

    /**
     * 保存
     *
     * @throws \Throwable
     */
    public function save(array $options = []): void
    {
        if ($this->game_franchise_id !== null && $this->game_series_id !== null) {
            $this->game_series_id = null;
        }

        parent::save($options);
    }

    /**
     * 前のタイトルを取得
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
     * 次のタイトルを取得
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
     * 検索可能な配列を取得
     *
     * @return array
     */
    public function toSearchableArray(): array
    {
        $array = [
            'id' => $this->id,
            'name' => $this->name,
            'phonetic' => $this->phonetic,
        ];

        // search_synonymsを改行で分割して配列に追加
        if (!empty($this->search_synonyms)) {
            $synonyms = preg_split('/\r\n|\r|\n/', $this->search_synonyms);
            $synonyms = array_filter(array_map('trim', $synonyms), function ($synonym) {
                return !empty($synonym);
            });
            $array['search_synonyms'] = array_values($synonyms);
        } else {
            $array['search_synonyms'] = [];
        }

        return $array;
    }
}
