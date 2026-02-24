<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $table = 'positions';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'UserID',
        'AssetID',
        'OpenTime',
        'CloseTime',
        'Quantity',
        'EntryPrice',
        'ExitPrice',
        'PositionType',
        'IsOpen',
        'ProfitLoss'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'UserID', 'ID');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'AssetID', 'ID');
    }
}