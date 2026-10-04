<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\User;
use App\Support\Catalog;
use App\Support\Fmt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function __construct(private NotificationService $notifier)
    {
    }

    /**
     * Sesi mengajar instruktur dalam 7 hari terakhir (termasuk hari ini), terbaru dulu.
     *
     * @return Collection<int, array{key:string, classroom:Classroom, date:Carbon, slot:int, subject:string, label:string}>
     */
    public function recentSessions(User $instructor, int $days = 7): Collection
    {
        $rows = Schedule::with('classroom')->where('instructor_id', $instructor->id)->get();
        $out = collect();
        $today = Carbon::today();
        for ($i = 0; $i < $days; $i++) {
            $date = $today->copy()->subDays($i);
            if ($date->isWeekend()) {
                continue;
            }
            $dow = $date->dayOfWeekIso - 1; // 0 = Senin
            foreach ($rows->where('day', $dow)->sortByDesc('slot') as $r) {
                $out->push([
                    'key' => $r->classroom_id . '_' . $date->toDateString() . '_' . $r->slot,
                    'classroom' => $r->classroom,
                    'date' => $date,
                    'slot' => $r->slot,
                    'subject' => $r->subject,
                    'label' => Fmt::dayDate($date) . ' · ' . Catalog::SLOTS[$r->slot] . ' · ' . $r->classroom->kode . ' · ' . Catalog::MAPEL[$r->subject],
                ]);
            }
        }

        return $out;
    }

    public function findSession(array $s): ?AttendanceSession
    {
        return AttendanceSession::where(['classroom_id' => $s['classroom']->id, 'date' => $s['date']->toDateString(), 'slot' => $s['slot']])->first();
    }

    /** @param array<int,string> $statuses student_id => H/I/S/A */
    public function save(array $s, array $statuses, User $by): AttendanceSession
    {
        return DB::transaction(function () use ($s, $statuses, $by) {
            $session = AttendanceSession::updateOrCreate(
                ['classroom_id' => $s['classroom']->id, 'date' => $s['date']->toDateString(), 'slot' => $s['slot']],
                ['subject' => $s['subject'], 'instructor_id' => $by->id]
            );
            $valid = $s['classroom']->students()->with('user')->get()->keyBy('id');
            foreach ($statuses as $sid => $st) {
                if (! isset($valid[$sid]) || ! isset(Catalog::ATTENDANCE[$st])) {
                    continue;
                }
                Attendance::updateOrCreate(['attendance_session_id' => $session->id, 'student_id' => $sid], ['status' => $st]);
                if ($st === 'A') {
                    $this->notifier->notify($valid[$sid]->user, '⚠️', 'Anda tercatat alpa pada ' . $s['label'] . '.', route('kelas.index'));
                }
            }

            return $session;
        });
    }
}
