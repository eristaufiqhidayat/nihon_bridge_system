<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExamAttempt extends Model
{
    protected $fillable = [
        'user_id', 'exam_schedule_id', 'exam_package_id', 'label', 'attempt_no', 'status', 'started_at', 'expires_at',
        'submitted_at', 'answers', 'flags', 'current', 'section_scores', 'section_counts', 'correct', 'question_count',
        'total', 'auto_submitted',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'answers' => 'array',
            'flags' => 'array',
            'section_scores' => 'array',
            'section_counts' => 'array',
            'auto_submitted' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ExamSchedule::class, 'exam_schedule_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ExamPackage::class, 'exam_package_id');
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    public function isRunning(): bool
    {
        return $this->status === 'berjalan';
    }

    public function secondsLeft(): int
    {
        return $this->expires_at ? max(0, now()->diffInSeconds($this->expires_at, false)) : 0;
    }

    /** Soal yang dipakai percobaan ini (salinan paket). */
    public function questions(): array
    {
        return $this->package?->snapshot ?? [];
    }

    public function hasReview(): bool
    {
        return is_array($this->answers) && $this->questions() !== [];
    }
}
