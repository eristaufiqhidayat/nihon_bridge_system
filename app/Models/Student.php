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
        'user_id', 'batch_id', 'classroom_id', 'nis', 'program', 'stage', 'stage_dates', 'guardian_contact', 'note',
        'nilai_tryout', 'materi_selesai', 'materi_total', 'target_departure',
        // biodata
        'birth_place', 'birth_date', 'gender', 'height_cm', 'religion', 'marital_status', 'passport_no',
        'address_ktp', 'address_domicile',
        // keikutsertaan
        'class_mode', 'class_start', 'class_end', 'enrollment_status', 'total_fee',
    ];

    protected function casts(): array
    {
        return [
            'stage_dates' => 'array', 'stage' => 'integer', 'birth_date' => 'date', 'class_start' => 'date', 'class_end' => 'date',
            'height_cm' => 'integer', 'total_fee' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
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

    /**
     * Data lain yang masih terkait dengan peserta ini dan menghalangi penghapusan.
     *
     * @return array<string, int> label => jumlah, hanya yang jumlahnya > 0
     */
    public function deleteBlockers(): array
    {
        $counts = [
            'pembayaran' => $this->payments()->count(),
            'ujian' => ExamAttempt::where('user_id', $this->user_id)->count(),
            'sertifikat' => Certificate::where('user_id', $this->user_id)->count(),
            'kehadiran' => $this->attendances()->count(),
            'kandidat job order' => $this->candidacies()->count(),
            'dokumen program' => $this->documents()->count(),
            'progres materi' => LessonProgress::where('user_id', $this->user_id)->count(),
            'pesan terkirim' => Message::where('user_id', $this->user_id)->count(),
        ];

        return array_filter($counts);
    }

    /** NIS berikutnya untuk tahun berjalan, mis. NB-26-0012. */
    public static function nextNis(): string
    {
        $prefix = 'NB-' . now()->format('y') . '-';
        $max = static::where('nis', 'like', $prefix . '%')->max('nis');

        return $prefix . str_pad((string) ($max ? ((int) substr($max, -4)) + 1 : 1), 4, '0', STR_PAD_LEFT);
    }

    public function getNameAttribute(): string
    {
        return $this->user->name;
    }

    /** "Palembang, 14 Mei 2002" */
    public function getTtlAttribute(): string
    {
        return implode(', ', array_filter([$this->birth_place, $this->birth_date ? Fmt::dateLong($this->birth_date) : null])) ?: '–';
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
