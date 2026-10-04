<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamPackage extends Model
{
    protected $fillable = ['judul', 'level', 'status', 'published_at', 'question_count', 'composition', 'snapshot'];

    protected function casts(): array
    {
        return ['published_at' => 'date', 'composition' => 'array', 'snapshot' => 'array'];
    }

    /** Soal yang dipilih selama paket masih draf. */
    public function picks(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_package_question')->withPivot('sort_order');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'Terbit';
    }

    /** Hitung jumlah soal per bagian dalam salinan. */
    public function sectionCounts(): array
    {
        $out = [];
        foreach ($this->snapshot ?? [] as $q) {
            $out[$q['section']] = ($out[$q['section']] ?? 0) + 1;
        }

        return $out;
    }
}
