<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'ID';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'Username',
        'Email',
        'PasswordHash',
        'RegistrationDate',
        'IsLoggedIn',
        'PreferredTheme',
        'NotificationsEnabled',
        'DemoBalance',
        'RealBalance',
        'PreferredCurrency',
    ];

    protected $hidden = [
        'PasswordHash',
    ];

    protected $casts = [
        'RegistrationDate' => 'datetime',
        'IsLoggedIn' => 'boolean',
        'NotificationsEnabled' => 'boolean',
        'DemoBalance' => 'decimal:2',
        'RealBalance' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Laravel auth compatibility bridge
    |--------------------------------------------------------------------------
    */

    public function getAuthIdentifierName()
    {
        return 'ID';
    }

    public function getAuthPassword()
    {
        return $this->PasswordHash;
    }

    /*
    |--------------------------------------------------------------------------
    | Convenience accessors for legacy columns
    |--------------------------------------------------------------------------
    */

    public function getIdAttribute()
    {
        return $this->attributes['ID'] ?? null;
    }

    public function getNameAttribute()
    {
        return $this->attributes['Username'] ?? null;
    }

    public function setNameAttribute($value)
    {
        $this->attributes['Username'] = $value;
    }

    public function getEmailAttribute()
    {
        return $this->attributes['Email'] ?? null;
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['Email'] = $value;
    }

    public function getPasswordAttribute()
    {
        return $this->attributes['PasswordHash'] ?? null;
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['PasswordHash'] = $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function settings()
    {
        return $this->hasOne(UserSetting::class, 'user_id', 'ID');
    }

    public function tutorialProgress()
    {
        return $this->hasMany(TutorialProgress::class, 'UserID', 'ID');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'UserID', 'ID');
    }

    public function positions()
    {
        return $this->hasMany(Position::class, 'UserID', 'ID');
    }
}