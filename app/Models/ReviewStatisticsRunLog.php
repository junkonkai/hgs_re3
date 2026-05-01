<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewStatisticsRunLog extends Model
{
    protected $table = 'review_statistics_run_log';

    protected $fillable = [
        'last_completed_at',
    ];

    protected $casts = [
        'last_completed_at' => 'datetime',
    ];
}
