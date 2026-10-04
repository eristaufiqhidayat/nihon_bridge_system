<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = ['chapter_id', 'judul', 'jenis', 'durasi', 'status', 'jp_title', 'jp_sub', 'file_path', 'sort_order'];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function isDoneBy(User $user): bool
    {
        return $this->progress()->where('user_id', $user->id)->exists();
    }
}
