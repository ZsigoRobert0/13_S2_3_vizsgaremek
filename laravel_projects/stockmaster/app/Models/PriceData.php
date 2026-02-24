<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceData extends Model
{
    protected $table = 'pricedata';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'AssetID',
        'Timestamp',
        'OpenPrice',
        'HighPrice',
        'LowPrice',
        'ClosePrice',
        'Volume'
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'AssetID', 'ID');
    }
}