<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Certificate;
use App\Models\ExamAttempt;
use App\Models\ExamSchedule;
use App\Models\User;
use App\Support\Catalog;
use App\Support\Fmt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamService
{
    public function __construct(private NotificationService $notifier)
    {
    }

    /** Percobaan yang masih berjalan milik pengguna (setelah menutup yang waktunya habis). */
    public function running(User $user): ?ExamAttempt
    {
        $this->expireOverdue($user);

        return $user->attempts()->where('status', 'berjalan')->with('package')->latest('id')->first();
    }

    /** Kirim otomatis jawaban percobaan yang waktunya sudah habis. */
    public function expireOverdue(User $user): void
    {
        $user->attempts()->where('status', 'berjalan')->where('expires_at', '<=', now())
            ->get()->each(fn (ExamAttempt $a) => $this->submit($a, true));
    }

    public function start(User $user, ExamSchedule $schedule): ExamAttempt
    {
        if ($running = $this->running($user)) {
            return $running;
        }
        if (! $schedule->isOpen() || $schedule->classroom_id !== $user->student?->classroom_id) {
            throw ValidationException::withMessages(['exam' => 'Ujian ini belum dibuka atau bukan untuk kelas Anda.']);
        }
        $package = $schedule->package;
        $no = $user->attempts()->where('exam_package_id', $package->id)->count() + 1;

        return ExamAttempt::create([
            'user_id' => $user->id,
            'exam_schedule_id' => $schedule->id,
            'exam_package_id' => $package->id,
            'label' => $this->shortLabel($package->judul) . ($no > 1 ? " · percobaan $no" : ''),
            'attempt_no' => $no,
            'status' => 'berjalan',
            'started_at' => now(),
            'expires_at' => now()->addMinutes($schedule->duration),
            'answers' => [],
            'flags' => [],
            'question_count' => count($package->snapshot ?? []),
        ]);
    }

    /** "Simulasi JLPT N4 · Paket 3" → "Paket 3". */
    public function shortLabel(string $judul): string
    {
        $parts = explode(' · ', $judul);

        return count($parts) > 1 ? end($parts) : $judul;
    }

    /** Simpan satu jawaban / tanda ragu-ragu (autosave dari CBT). */
    public function save(ExamAttempt $attempt, array $data): ExamAttempt
    {
        if (! $attempt->isRunning() || $attempt->secondsLeft() <= 0) {
            return $attempt;
        }
        $n = count($attempt->questions());
        $answers = $attempt->answers ?? [];
        $flags = $attempt->flags ?? [];

        foreach ($data['answers'] ?? [] as $i => $opt) {
            $i = (int) $i;
            if ($i < 0 || $i >= $n) {
                continue;
            }
            if ($opt === null || $opt === '') {
                unset($answers[$i]);
            } elseif (in_array((int) $opt, [0, 1, 2, 3], true)) {
                $answers[$i] = (int) $opt;
            }
        }
        foreach ($data['flags'] ?? [] as $i => $on) {
            $i = (int) $i;
            if ($i >= 0 && $i < $n) {
                $flags[$i] = filter_var($on, FILTER_VALIDATE_BOOLEAN);
            }
        }
        $attempt->answers = $answers;
        $attempt->flags = array_filter($flags);
        if (isset($data['current'])) {
            $attempt->current = max(0, min($n - 1, (int) $data['current']));
        }
        $attempt->save();

        return $attempt;
    }

    /**
     * Hitung nilai per bagian.
     *
     * @return array{sections: array<string,int>, counts: array, correct: int, total: int}
     */
    public function score(array $questions, array $answers): array
    {
        $c = $t = array_fill_keys(array_keys(Catalog::SECTIONS), 0);
        foreach ($questions as $i => $q) {
            $t[$q['section']]++;
            if (array_key_exists($i, $answers) && (int) $answers[$i] === (int) $q['key']) {
                $c[$q['section']]++;
            }
        }
        $sections = [];
        foreach ($t as $s => $n) {
            $sections[$s] = Fmt::pct($c[$s], $n);
        }
        $correct = array_sum($c);

        return ['sections' => $sections, 'counts' => ['c' => $c, 't' => $t], 'correct' => $correct, 'total' => Fmt::pct($correct, count($questions))];
    }

    public function submit(ExamAttempt $attempt, bool $auto = false): ExamAttempt
    {
        if (! $attempt->isRunning()) {
            return $attempt;
        }

        return DB::transaction(function () use ($attempt, $auto) {
            $r = $this->score($attempt->questions(), $attempt->answers ?? []);
            $attempt->fill([
                'status' => 'selesai',
                'submitted_at' => $auto && $attempt->expires_at && $attempt->expires_at->isPast() ? $attempt->expires_at : now(),
                'section_scores' => $r['sections'],
                'section_counts' => $r['counts'],
                'correct' => $r['correct'],
                'total' => $r['total'],
                'auto_submitted' => $auto,
            ])->save();

            $user = $attempt->user;
            if (self::isPass($attempt)) {
                $cert = $this->issueCertificate($attempt);
                $this->notifier->notify($user, '🏅', "Selamat, Anda lulus {$attempt->package->judul} dengan nilai {$attempt->total}. Sertifikat {$cert->number} terbit.", route('sertifikat.index', $cert));
                Activity::log('✓', 'ic-bg-green', "{$user->name} lulus {$this->baseTitle($attempt->package->judul)} ({$attempt->total})");
            } else {
                $this->notifier->notify($user, '📊', "Hasil {$attempt->package->judul}: nilai {$attempt->total}. Belum lulus, ayo latihan lagi.", route('hasil.index', $attempt));
            }

            return $attempt;
        });
    }

    public static function isPass(ExamAttempt $a): bool
    {
        $cfg = config('nihonbridge.exam');
        $sections = $a->section_scores ?? [];

        return $a->total !== null && $a->total >= $cfg['pass_total']
            && collect($sections)->every(fn ($v) => $v >= $cfg['pass_section']);
    }

    /** Indeks kesiapan = 70% nilai total + 30% nilai bagian terendah. */
    public static function readiness(ExamAttempt $a): int
    {
        $min = $a->section_scores ? min($a->section_scores) : 0;

        return (int) round(0.7 * $a->total + 0.3 * $min);
    }

    /** "Simulasi JLPT N4 · Paket 3" → "Simulasi JLPT N4". */
    public function baseTitle(string $judul): string
    {
        return explode(' · ', $judul)[0];
    }

    public function issueCertificate(ExamAttempt $attempt): Certificate
    {
        if ($attempt->certificate) {
            return $attempt->certificate;
        }
        $base = $this->baseTitle($attempt->package->judul);
        $prefix = 'NB-JLPT-' . $attempt->submitted_at->year . '-';
        $max = Certificate::where('number', 'like', $prefix . '%')->max('number');
        $no = $prefix . str_pad((string) ($max ? ((int) substr($max, -4)) + 1 : 1), 4, '0', STR_PAD_LEFT);

        return Certificate::create([
            'user_id' => $attempt->user_id,
            'exam_attempt_id' => $attempt->id,
            'number' => $no,
            'title' => 'Ujian ' . $base,
            'kind' => 'SERTIFIKAT UJIAN ' . mb_strtoupper($base),
            'description' => "Telah mengikuti dan lulus Ujian {$attempt->package->judul} pada tanggal " . Fmt::dateLong($attempt->submitted_at) . " dengan nilai {$attempt->total} dari 100",
            'score' => $attempt->total,
            'issued_at' => $attempt->submitted_at->toDateString(),
        ]);
    }
}
