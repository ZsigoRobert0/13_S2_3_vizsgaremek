<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'UserID',
        'Title',
        'Message',
        'CreatedAt',
        'IsRead'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'UserID', 'ID');
    }
}