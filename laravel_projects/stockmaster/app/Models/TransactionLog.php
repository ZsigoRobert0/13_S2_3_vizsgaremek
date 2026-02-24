<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionLog extends Model
{
    protected $table = 'transactionslog';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'UserID',
        'Type',
        'Amount',
        'TransactionTime',
        'Description'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'UserID', 'ID');
    }
}