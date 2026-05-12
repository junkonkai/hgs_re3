<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewStatisticsDirtyTitle extends Model
{
    protected $table = 'review_statistics_dirty_titles';

    protected $primaryKey = 'game_title_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'game_title_id',
    ];
}
