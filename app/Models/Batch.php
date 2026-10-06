<?php

namespace App\Models;

use App\Support\Fmt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Batch = angkatan: peserta yang memulai program pada periode yang sama. Di tampilan disebut "Angkatan". */
class Batch extends Model
{
    protected $fillable = ['program_id', 'kode', 'nama', 'mulai', 'selesai', 'kuota', 'biaya'];

    protected function casts(): array
    {
        return ['mulai' => 'date', 'selesai' => 'date', 'kuota' => 'integer', 'biaya' => 'integer'];
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

    /** Tahapan pembayaran angkatan ini, urut tahap 1..n. */
    public function installments(): HasMany
    {
        return $this->hasMany(BatchInstallment::class)->orderBy('tahap');
    }

    /** Total biaya standar angkatan: biaya angkatan, atau biaya program bila kosong. */
    public function totalBiaya(): ?int
    {
        return $this->biaya ?? $this->program?->biaya;
    }

    /**
     * Data lain yang masih terkait dengan angkatan ini dan menghalangi penghapusan.
     *
     * @return array<string, int> label => jumlah, hanya yang jumlahnya > 0
     */
    public function deleteBlockers(): array
    {
        return array_filter([
            'kelas' => $this->classrooms()->count(),
            'peserta' => $this->students()->count(),
        ]);
    }

    /** "Sep 2025 – Feb 2026" */
    public function getPeriodeAttribute(): string
    {
        return $this->selesai ? Fmt::monthYear($this->mulai) . ' – ' . Fmt::monthYear($this->selesai) : Fmt::monthYear($this->mulai);
    }
}
