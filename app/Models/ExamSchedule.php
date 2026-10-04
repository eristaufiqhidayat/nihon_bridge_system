<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ExamSchedule extends Model
{
    protected $fillable = ['exam_package_id', 'classroom_id', 'title', 'description', 'mode', 'opens_at', 'closes_at', 'start_time', 'duration'];

    protected function casts(): array
    {
        return ['opens_at' => 'date', 'closes_at' => 'date'];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ExamPackage::class, 'exam_package_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->title ?: ($this->package?->judul ?? 'Ujian');
    }

    public function isOpen(?Carbon $now = null): bool
    {
        $now ??= Carbon::now();

        return $this->mode === 'cbt'
            && $this->package?->isPublished()
            && $now->greaterThanOrEqualTo($this->opens_at->startOfDay())
            && (! $this->closes_at || $now->lessThanOrEqualTo($this->closes_at->copy()->endOfDay()));
    }
}
