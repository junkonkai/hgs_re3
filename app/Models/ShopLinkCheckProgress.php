<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopLinkCheckProgress extends Model
{
    protected $table = 'shop_link_check_progresses';

    protected $fillable = ['source_table', 'last_checked_id'];
}
