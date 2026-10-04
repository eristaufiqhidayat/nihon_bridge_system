<?php

namespace App\Models;

use App\Support\Catalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    protected $fillable = ['classroom_id', 'date', 'slot', 'subject', 'instructor_id'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function getSubjectNameAttribute(): string
    {
        return Catalog::MAPEL[$this->subject] ?? $this->subject;
    }
}
