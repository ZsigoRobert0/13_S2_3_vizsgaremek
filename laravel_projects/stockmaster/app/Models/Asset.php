<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $table = 'assets';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Symbol',
        'Name',
        'IsTradable'
    ];

    public function positions()
    {
        return $this->hasMany(Position::class, 'AssetID', 'ID');
    }

    public function priceData()
    {
        return $this->hasMany(PriceData::class, 'AssetID', 'ID');
    }
}