<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candle extends Model
{
    protected $table = 'candles';

    protected $fillable = [
        'symbol','tf','open_ts','close_ts','open','high','low','close','ticks',
    ];

    public $timestamps = false;
}