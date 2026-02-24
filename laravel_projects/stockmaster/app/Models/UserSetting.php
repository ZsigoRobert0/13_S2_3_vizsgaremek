<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $table = 'user_settings';

    protected $fillable = [
        'user_id',

        'timezone',
        'chart_interval',
        'chart_theme',
        'chart_limit_initial',
        'chart_backfill_chunk',

        'news_limit',
        'news_per_symbol_limit',
        'news_portfolio_total_limit',
        'calendar_limit',

        'auto_login',
        'receive_notifications',

        'data',
    ];

    protected $casts = [
        'auto_login' => 'boolean',
        'receive_notifications' => 'boolean',
        'chart_limit_initial' => 'integer',
        'chart_backfill_chunk' => 'integer',
        'news_limit' => 'integer',
        'news_per_symbol_limit' => 'integer',
        'news_portfolio_total_limit' => 'integer',
        'calendar_limit' => 'integer',
        'data' => 'array',
    ];

    public function user()
    {
        // legacy users PK = ID
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }
}