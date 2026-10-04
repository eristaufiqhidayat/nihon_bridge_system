<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    public $timestamps = false;

    protected $fillable = ['user_id', 'lesson_id', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }
}
