<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chapter extends Model
{
    protected $fillable = ['level', 'book', 'no', 'judul', 'deskripsi', 'contoh_jp', 'is_locked'];

    protected function casts(): array
    {
        return ['is_locked' => 'boolean'];
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order')->orderBy('id');
    }

    public function publishedLessons(): HasMany
    {
        return $this->lessons()->where('status', 'Terbit');
    }
}
