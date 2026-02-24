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

    public function progress()
    {
        return $this->hasMany(TutorialProgress::class, 'TutorialID', 'ID');
    }
}