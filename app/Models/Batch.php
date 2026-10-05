<?php

namespace App\Models;

use App\Support\Fmt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Batch = angkatan: peserta yang memulai program pada periode yang sama. Di tampilan disebut "Angkatan". */
class Batch extends Model
{
    protected $fillable = ['program_id', 'kode', 'nama', 'mulai', 'selesai', 'kuota'];

    protected function casts(): array
    {
        return ['mulai' => 'date', 'selesai' => 'date'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /** "Sep 2025 – Feb 2026" */
    public function getPeriodeAttribute(): string
    {
        return $this->selesai ? Fmt::monthYear($this->mulai) . ' – ' . Fmt::monthYear($this->selesai) : Fmt::monthYear($this->mulai);
    }
}
