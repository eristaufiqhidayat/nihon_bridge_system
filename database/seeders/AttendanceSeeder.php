<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Schedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Riwayat kehadiran kelas N4-A: 60 sesi pagi (6 Jul – 25 Sep 2026).
 */
class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $class = Classroom::where('kode', 'N4-A')->first();
        $students = $class->students()->with('user')->orderBy('id')->get();
        $grid = Schedule::where('classroom_id', $class->id)->where('slot', 0)->get()->keyBy('day');

        // Persentase ketidakhadiran per email (selain Ahmad yang diatur khusus)
        $miss = ['nur.a' => 22, 'budi.s' => 17, 'rizky.p' => 9, 'fajar.s' => 8, 'ikhsan.m' => 7, 'yuni.p' => 6, 'indra.k' => 5];
        $ahmad = ['2026-07-21' => 'S', '2026-08-14' => 'I', '2026-09-03' => 'I'];

        $date = Carbon::parse('2026-07-06');
        $n = 0;
        while ($n < 60) {
            if (! $date->isWeekend()) {
                $dow = $date->dayOfWeekIso - 1;
                $row = $grid[$dow];
                $session = AttendanceSession::create(['classroom_id' => $class->id, 'date' => $date->toDateString(), 'slot' => 0, 'subject' => $row->subject, 'instructor_id' => $row->instructor_id]);
                foreach ($students as $j => $s) {
                    $key = explode('@', $s->user->email)[0];
                    if ($key === 'ahmad.fauzi') {
                        $st = $ahmad[$date->toDateString()] ?? 'H';
                    } else {
                        $rate = $miss[$key] ?? 3;
                        $roll = ($n * 37 + $j * 53) % 100;
                        $st = $roll < $rate ? ['I', 'S', 'A', 'I'][($n + $j) % 4] : 'H';
                    }
                    Attendance::create(['attendance_session_id' => $session->id, 'student_id' => $s->id, 'status' => $st]);
                }
                $n++;
            }
            $date->addDay();
        }
    }
}
