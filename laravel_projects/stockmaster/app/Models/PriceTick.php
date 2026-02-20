<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceTick extends Model
{
    protected $table = 'price_ticks';

    protected $fillable = [
        'symbol','ts','price','bid','ask','source',
    ];

    public $timestamps = false;
}