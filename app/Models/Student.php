<?php

namespace App\Models;

use App\Support\Catalog;
use App\Support\Fmt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'user_id', 'classroom_id', 'nis', 'program', 'stage', 'stage_dates', 'birth_place_date', 'address',
        'guardian_contact', 'note', 'nilai_tryout', 'materi_selesai', 'materi_total', 'target_departure',
    ];

    protected function casts(): array
    {
        return ['stage_dates' => 'array', 'stage' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function candidacies(): HasMany
    {
        return $this->hasMany(JobCandidate::class);
    }

    public function getNameAttribute(): string
    {
        return $this->user->name;
    }

    public function getInitialsAttribute(): string
    {
        return Fmt::initials($this->user->name);
    }

    /** Ujian terakhir yang selesai di sistem ini. */
    public function lastAttempt(): ?ExamAttempt
    {
        return ExamAttempt::where('user_id', $this->user_id)->where('status', 'selesai')
            ->orderByDesc('submitted_at')->orderByDesc('id')->first();
    }

    /** Nilai tryout terakhir: dari ujian di sistem, atau nilai impor dari sistem lama. */
    public function getNilaiAttribute(): int
    {
        return (int) ($this->lastAttempt()?->total ?? $this->nilai_tryout ?? 0);
    }

    public function getProgresBelajarAttribute(): int
    {
        return Fmt::pct($this->materi_selesai, $this->materi_total);
    }

    public function stageLabel(int $i): string
    {
        $dates = $this->stage_dates ?? [];
        if (! empty($dates[$i])) {
            return $dates[$i];
        }

        return $i < $this->stage ? 'Selesai' : ($i === $this->stage ? 'Proses' : 'Belum');
    }

    public function docStatus(array $doc): string
    {
        [$key, , $doneAt] = $doc;
        $row = $this->relationLoaded('documents')
            ? $this->documents->firstWhere('doc_key', $key)
            : $this->documents()->where('doc_key', $key)->first();
        if ($row) {
            return $row->status;
        }
        if ($this->stage >= $doneAt) {
            return 'selesai';
        }

        return $this->stage === $doneAt - 1 ? 'proses' : 'belum';
    }

    public function stageName(): string
    {
        return Catalog::STAGES[$this->stage] ?? '–';
    }

    /** Rekap kehadiran: [hadir, total, persen, rincian per status]. */
    public function attendanceSummary(): array
    {
        $rows = $this->attendances()->get(['status']);
        $total = $rows->count();
        $hadir = $rows->where('status', 'H')->count();

        return [
            'hadir' => $hadir,
            'total' => $total,
            'persen' => Fmt::pct($hadir, $total),
            'by' => $rows->countBy('status')->all(),
        ];
    }
}
