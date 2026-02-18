<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $table = 'user_settings';

    protected $fillable = [
        'user_id',
        'timezone',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
