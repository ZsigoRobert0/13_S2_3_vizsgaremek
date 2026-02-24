<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TutorialProgress extends Model
{
    protected $table = 'tutorialprogress';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'UserID',
        'TutorialID',
        'IsCompleted',
        'StartedAt',
        'CompletedAt'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'UserID', 'ID');
    }

    public function tutorial()
    {
        return $this->belongsTo(Tutorial::class, 'TutorialID', 'ID');
    }
}