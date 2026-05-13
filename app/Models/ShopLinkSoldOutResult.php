<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopLinkSoldOutResult extends Model
{
    protected $fillable = [
        'source_table',
        'source_id',
        'shop_id',
        'url',
        'reason',
        'matched_keyword',
        'detected_at',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
    ];
}
