<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Classroom;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSchedule;
use App\Models\Question;
use App\Models\User;
use App\Services\ExamService;
use App\Support\Catalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Bank soal, paket & jadwal ujian, riwayat ujian peserta N4-A, dan sertifikat.
 */
class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $data = require __DIR__ . '/data/questions.php';
        $sato = User::where('email', 'sato.sensei@nihonbridge.id')->first();

        // 30 soal N4 — urutan opsi diputar agar kunci tidak selalu A
        $n4 = [];
        foreach ($data['n4'] as $i => $r) {
            [$sec, $q, $opts, $exp] = $r;
            $k = $data['rotation'][$i % 8];
            $o = array_map(fn ($j) => $opts[($j + $k) % 4], range(0, 3));
            $n4[] = Question::create([
                'code' => 'S-' . (1001 + $i), 'section' => $sec, 'level' => 'N4', 'question' => $q, 'options' => $o,
                'answer_key' => (4 - $k) % 4, 'explanation' => $exp,
                'passage' => ! empty($r[4]) ? $data['passage'] : null,
                'audio_script' => $r[5] ?? null,
                'created_by' => $sato->id,
            ]);
        }
        foreach ($data['extra'] as [$code, $sec, $lvl, $q, $opts, $exp]) {
            Question::create(['code' => $code, 'section' => $sec, 'level' => $lvl, 'question' => $q, 'options' => $opts, 'answer_key' => 0, 'explanation' => $exp]);
        }
        $snapshot = array_map(fn ($q) => $q->toSnapshot(), $n4);

        // Paket
        $pk = [];
        foreach ([1 => '2026-08-01', 2 => '2026-08-20', 3 => '2026-09-02'] as $n => $date) {
            $pk[$n] = ExamPackage::create([
                'judul' => "Simulasi JLPT N4 · Paket $n", 'level' => 'N4', 'status' => 'Terbit', 'published_at' => $date,
                'composition' => Catalog::COMPOSE, 'snapshot' => $snapshot, 'question_count' => 30,
            ]);
            $pk[$n]->picks()->sync(collect($n4)->mapWithKeys(fn ($q, $i) => [$q->id => ['sort_order' => $i]])->all());
        }
        ExamPackage::create(['judul' => 'Simulasi JLPT N4 · Paket 4', 'level' => 'N4', 'status' => 'Draf', 'composition' => Catalog::COMPOSE]);
        $jft = ExamPackage::create(['judul' => 'Simulasi JFT-Basic · Paket 1', 'level' => 'N4', 'status' => 'Terbit', 'published_at' => '2026-09-25',
            'composition' => Catalog::COMPOSE, 'snapshot' => $snapshot, 'question_count' => 30]);
        $quizQs = array_values(array_filter($snapshot, fn ($q) => $q['section'] === 'bunpou'));
        $quiz = ExamPackage::create(['judul' => 'Kuis Bab 15 · 〜ています', 'level' => 'N4', 'status' => 'Terbit', 'published_at' => '2026-09-28',
            'composition' => ['bunpou' => 10], 'snapshot' => $quizQs, 'question_count' => count($quizQs)]);

        // Jadwal
        $n4a = Classroom::where('kode', 'N4-A')->first();
        $n4b = Classroom::where('kode', 'N4-B')->first();
        $p3a = ExamSchedule::create(['exam_package_id' => $pk[3]->id, 'classroom_id' => $n4a->id, 'opens_at' => '2026-09-02', 'closes_at' => '2026-10-31', 'duration' => 60,
            'description' => 'Latihan menjelang Tryout Nasional. Nilai tertinggi dan terakhir tercatat di rapor.']);
        ExamSchedule::create(['exam_package_id' => $pk[3]->id, 'classroom_id' => $n4b->id, 'opens_at' => '2026-09-09', 'closes_at' => '2026-10-31', 'duration' => 60]);
        $p1 = ExamSchedule::create(['exam_package_id' => $pk[1]->id, 'classroom_id' => $n4a->id, 'opens_at' => '2026-08-03', 'closes_at' => '2026-08-31', 'duration' => 60]);
        $p2 = ExamSchedule::create(['exam_package_id' => $pk[2]->id, 'classroom_id' => $n4a->id, 'opens_at' => '2026-08-21', 'closes_at' => '2026-09-15', 'duration' => 60]);
        ExamSchedule::create(['exam_package_id' => $quiz->id, 'classroom_id' => $n4a->id, 'opens_at' => '2026-10-01', 'closes_at' => '2026-10-15', 'duration' => 15,
            'description' => 'Kuis singkat setelah materi Bab 15.']);
        ExamSchedule::create(['exam_package_id' => $jft->id, 'classroom_id' => $n4a->id, 'opens_at' => '2026-10-05', 'closes_at' => '2026-11-30', 'duration' => 60,
            'description' => 'Latihan format Japan Foundation Test for Basic Japanese, alternatif JLPT N4 untuk Tokutei Ginou.']);
        ExamSchedule::create(['classroom_id' => $n4a->id, 'title' => 'Tryout JLPT N4 Nasional', 'mode' => 'onsite', 'opens_at' => '2026-10-13', 'start_time' => '08.00', 'duration' => 105,
            'description' => 'Diawasi di lokasi. Pendaftaran lewat admin LPK.']);

        $exams = app(ExamService::class);

        // Ahmad: Paket 1 & 2 dari sistem lama (tanpa rincian jawaban), Paket 3 lengkap
        $ahmad = User::where('email', 'ahmad.fauzi@nihonbridge.id')->first();
        foreach ([[1, $p1, '2026-08-12 10:05', ['kosakata' => 60, 'bunpou' => 50, 'dokkai' => 40, 'choukai' => 60], 53],
            [2, $p2, '2026-08-28 10:12', ['kosakata' => 70, 'bunpou' => 50, 'dokkai' => 60, 'choukai' => 40], 57]] as [$n, $sch, $at, $sec, $total]) {
            ExamAttempt::create([
                'user_id' => $ahmad->id, 'exam_schedule_id' => $sch->id, 'exam_package_id' => $pk[$n]->id, 'label' => "Paket $n", 'status' => 'selesai',
                'started_at' => Carbon::parse($at)->subMinutes(55), 'expires_at' => Carbon::parse($at)->addMinutes(5), 'submitted_at' => $at,
                'answers' => null, 'section_scores' => $sec, 'total' => $total, 'question_count' => 30,
            ]);
        }
        $ans = [];
        foreach ($snapshot as $i => $q) {
            $ans[$i] = in_array($i, [1, 11, 14, 22, 27], true) ? ($q['key'] + 1) % 4 : $q['key'];
        }
        $a3 = $this->finished($exams, $ahmad, $p3a, $pk[3], $snapshot, $ans, '2026-09-10 10:20', 'Paket 3');

        // Sertifikat Ahmad
        Certificate::create(['user_id' => $ahmad->id, 'number' => 'NB-KLS-2026-0217', 'title' => 'Kelulusan Kelas N5', 'kind' => 'SERTIFIKAT KELULUSAN KELAS N5',
            'description' => 'Telah menyelesaikan Kelas Bahasa Jepang Level N5 pada tanggal 28 Maret 2026 dengan nilai akhir 88 dari 100', 'score' => 88, 'issued_at' => '2026-03-28']);
        Certificate::create(['user_id' => $ahmad->id, 'number' => 'NB-JLPT-2026-0033', 'title' => 'Ujian Simulasi JLPT N5', 'kind' => 'SERTIFIKAT UJIAN SIMULASI JLPT N5',
            'description' => 'Telah mengikuti dan lulus Ujian Simulasi JLPT N5 pada tanggal 18 April 2026 dengan nilai 86 dari 100', 'score' => 86, 'issued_at' => '2026-04-18']);
        Certificate::create(['user_id' => $ahmad->id, 'exam_attempt_id' => $a3->id, 'number' => 'NB-JLPT-2026-0061', 'title' => 'Ujian Simulasi JLPT N4', 'kind' => 'SERTIFIKAT UJIAN SIMULASI JLPT N4',
            'description' => "Telah mengikuti dan lulus Ujian Simulasi JLPT N4 · Paket 3 pada tanggal 10 September 2026 dengan nilai {$a3->total} dari 100", 'score' => $a3->total, 'issued_at' => '2026-09-10']);

        // Peserta lain mengerjakan Paket 3 (untuk analisis butir soal)
        $base = ['kosakata' => 80, 'bunpou' => 66, 'dokkai' => 74, 'choukai' => 62];
        $over = [14 => 41, 19 => 36, 18 => 45, 27 => 48, 11 => 52];
        $difficulty = [];
        foreach ($snapshot as $i => $q) {
            $difficulty[$i] = $over[$i] ?? max(35, min(96, $base[$q['section']] + (($i * 37) % 23) - 11));
        }
        $students = User::where('role', 'peserta')->whereHas('student', fn ($s) => $s->where('classroom_id', $n4a->id))
            ->where('email', '!=', 'ahmad.fauzi@nihonbridge.id')->where('email', '!=', 'fajar.s@nihonbridge.id')->with('student')->orderBy('id')->get();
        foreach ($students as $j => $u) {
            $target = $u->student->nilai_tryout;
            $c = (int) round($target * 30 / 100);
            $rank = [];
            foreach ($difficulty as $i => $p) {
                $rank[$i] = $p + ((($i * 31 + $j * 17) % 21) - 10);
            }
            arsort($rank);
            $correct = array_slice(array_keys($rank), 0, $c);
            if ($u->email === 'budi.s@nihonbridge.id') {
                // Bunpou di bawah 40: maksimal 3 dari 10 benar
                $bunpou = array_values(array_filter($correct, fn ($i) => $snapshot[$i]['section'] === 'bunpou'));
                $drop = array_slice($bunpou, 3);
                $correct = array_values(array_diff($correct, $drop));
                $extra = array_values(array_filter(array_keys($rank), fn ($i) => ! in_array($i, $correct, true) && $snapshot[$i]['section'] !== 'bunpou'));
                $correct = array_merge($correct, array_slice($extra, 0, count($drop)));
            }
            $ans = [];
            foreach ($snapshot as $i => $q) {
                if (in_array($i, $correct, true)) {
                    $ans[$i] = $q['key'];
                    continue;
                }
                $wrong = array_values(array_diff([0, 1, 2, 3], [$q['key']]));
                $top = $wrong[$i % 3];
                $others = array_values(array_diff($wrong, [$top]));
                $ans[$i] = (($i + $j) % 5) < 3 ? $top : $others[($i + $j) % 2];
            }
            $day = 3 + ($j * 3) % 18;
            $this->finished($exams, $u, $p3a, $pk[3], $snapshot, $ans, sprintf('2026-09-%02d %02d:%02d', $day, 9 + $j % 5, 10 + $j), 'Paket 3');
        }
    }

    private function finished(ExamService $exams, User $u, ExamSchedule $s, ExamPackage $p, array $snapshot, array $ans, string $at, string $label): ExamAttempt
    {
        $r = $exams->score($snapshot, $ans);

        return ExamAttempt::create([
            'user_id' => $u->id, 'exam_schedule_id' => $s->id, 'exam_package_id' => $p->id, 'label' => $label, 'status' => 'selesai',
            'started_at' => Carbon::parse($at)->subMinutes(48), 'expires_at' => Carbon::parse($at)->addMinutes(12), 'submitted_at' => $at,
            'answers' => $ans, 'flags' => [], 'section_scores' => $r['sections'], 'section_counts' => $r['counts'],
            'correct' => $r['correct'], 'total' => $r['total'], 'question_count' => count($snapshot),
        ]);
    }
}
