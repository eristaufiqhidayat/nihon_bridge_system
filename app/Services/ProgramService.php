<?php

namespace App\Services;

use App\Models\Company;
use App\Models\JobCandidate;
use App\Models\JobOrder;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Support\Catalog;
use Illuminate\Support\Collection;

class ProgramService
{
    public function __construct(private NotificationService $notifier)
    {
    }

    public function setDocument(Student $s, string $key, string $status): void
    {
        StudentDocument::updateOrCreate(['student_id' => $s->id, 'doc_key' => $key], ['status' => $status]);
    }

    /** Peserta yang cocok untuk job order: bidang sama, lulus tryout (≥ 60), sudah di tahap Interview, belum diajukan. */
    public function eligible(JobOrder $job): Collection
    {
        $used = JobCandidate::pluck('student_id');
        $bidang = mb_strtolower($job->company->bidang);

        return Student::with('user')->whereNotIn('id', $used)->where('stage', '>=', 3)->get()
            ->filter(fn (Student $s) => str_contains(mb_strtolower($s->program), $bidang) && $s->nilai >= config('nihonbridge.exam.pass_total'))
            ->values();
    }

    public function addCandidate(JobOrder $job, Student $s): void
    {
        JobCandidate::firstOrCreate(['job_order_id' => $job->id, 'student_id' => $s->id], ['hasil' => 'menunggu']);
        $this->notifier->notify($s->user, '🏢', "Anda diajukan ke {$job->company->nama} untuk posisi {$job->posisi}. Interview: {$job->interview}", route('program.index'));
    }

    /** Set hasil interview; bila lulus di tahap Interview, tahap program maju ke MCU. */
    public function setInterview(JobCandidate $c, string $hasil): string
    {
        $c->update(['hasil' => $hasil]);
        $s = $c->student;
        $dates = $s->stage_dates ?? [];

        if ($hasil === 'lulus' && $s->stage === 3) {
            $s->stage = 4;
            if ($dates) {
                $dates[3] = \App\Support\Fmt::date(now()) . ' · Lulus';
                $s->stage_dates = $dates;
            }
            $s->save();
            $this->notifier->notify($s->user, '🎉', "Selamat, Anda lulus interview {$c->jobOrder->company->nama}. Tahap berikutnya: MCU.", route('program.index'));

            return "{$s->name} lulus interview. Tahap program maju ke MCU.";
        }
        if ($hasil !== 'lulus' && $s->stage === 4) {
            $s->stage = 3;
            $s->save();

            return "Hasil interview {$s->name} diubah ke " . Catalog::INTERVIEW[$hasil][0] . '.';
        }

        return "Hasil interview {$s->name}: " . Catalog::INTERVIEW[$hasil][0];
    }

    /** Ringkasan per perusahaan untuk kartu di atas daftar job order. */
    public function companySummaries(): Collection
    {
        return Company::with('jobOrders.candidates')->get()->map(fn (Company $c) => [
            'company' => $c,
            'jobs' => $c->jobOrders->count(),
            'kuota' => $c->jobOrders->sum('kuota'),
            'lulus' => $c->jobOrders->sum(fn ($j) => $j->candidates->where('hasil', 'lulus')->count()),
        ]);
    }
}
