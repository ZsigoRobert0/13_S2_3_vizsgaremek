<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tutorial extends Model
{
    protected $table = 'tutorials';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Title',
        'DifficultyLevel',
        'Tags',
        'Content'
    ];

    protected $casts = [
        'Tags' => 'array'
    ];

    public function progress()
    {
        return $this->hasMany(TutorialProgress::class, 'TutorialID', 'ID');
    }

    public function userProgress($userId)
    {
        return $this->progress()->where('UserID', $userId)->first();
    }
}