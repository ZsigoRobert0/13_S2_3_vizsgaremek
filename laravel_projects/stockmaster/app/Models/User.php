<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Username',
        'Email',
        'PasswordHash',
        'PreferredTheme',
        'NotificationsEnabled',
        'DemoBalance',
        'RealBalance',
        'PreferredCurrency',
    ];

    protected $hidden = [
        'PasswordHash',
    ];

    public function settings()
    {
        return $this->hasOne(UserSetting::class, 'user_id', 'ID');
    }
}