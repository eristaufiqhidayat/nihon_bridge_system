<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = ['user_id', 'exam_attempt_id', 'number', 'title', 'kind', 'description', 'score', 'issued_at'];

    protected function casts(): array
    {
        return ['issued_at' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function verifyUrl(): string
    {
        return route('verifikasi', ['no' => $this->number]);
    }
}
