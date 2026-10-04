<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Classroom;
use App\Models\Company;
use App\Models\JobOrder;
use App\Models\PassRate;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Models\YearlyStat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Pendaftar, perusahaan mitra (fiktif), job order, pembayaran, dan statistik tahunan.
 */
class OperationsSeeder extends Seeder
{
    public function run(): void
    {
        $file = fn (string $k) => ['name' => "{$k}.jpg", 'size' => '412 KB', 'path' => null];
        $berkas = fn (array $flags) => collect($flags)->filter()->mapWithKeys(fn ($v, $k) => [$k => $file($k)])->all();
        $n5c = Classroom::where('kode', 'N5-C')->first();

        $apps = [
            ['REG-2026-0229', 'Yoga Saputra', 'Lampung', 24, 'D3', 'Magang · Manufaktur', '2026-09-25', '0813-4455-2100', 'baru', ['ktp' => 1, 'ijazah' => 1, 'foto' => 0, 'izin' => 1], []],
            ['REG-2026-0228', 'Putri Handayani', 'Klaten', 20, 'SMA/SMK', 'Tokutei Ginou · Pengolahan Makanan', '2026-09-24', '0857-1100-8821', 'tes', ['ktp' => 1, 'ijazah' => 1, 'foto' => 1, 'izin' => 1], ['tes_jadwal' => 'Kamis, 1 Okt 2026 · 09.00']],
            ['REG-2026-0227', 'Gilang Ramadhan', 'Garut', 23, 'SMA/SMK', 'Magang · Manufaktur', '2026-09-23', '0812-9090-3131', 'berkas', ['ktp' => 1, 'ijazah' => 1, 'foto' => 1, 'izin' => 1], []],
            ['REG-2026-0226', 'Intan Kusuma', 'Cirebon', 21, 'SMA/SMK', 'Tokutei Ginou · Kaigo', '2026-09-22', '0896-7788-1020', 'baru', ['ktp' => 1, 'ijazah' => 1, 'foto' => 1, 'izin' => 0], []],
            ['REG-2026-0224', 'Lina Marlina', 'Bogor', 22, 'SMA/SMK', 'Tokutei Ginou · Kaigo', '2026-09-18', '0878-2323-6060', 'diterima', ['ktp' => 1, 'ijazah' => 1, 'foto' => 1, 'izin' => 1],
                ['classroom_id' => $n5c->id, 'user_id' => User::where('email', 'lina.marlina@nihonbridge.id')->value('id')]],
            ['REG-2026-0221', 'Arif Hidayat', 'Tegal', 29, 'SMP', 'Magang · Konstruksi', '2026-09-15', '0819-5656-7171', 'ditolak', ['ktp' => 1, 'ijazah' => 1, 'foto' => 1, 'izin' => 1],
                ['alasan' => 'Hasil tes kesehatan awal belum memenuhi syarat']],
        ];
        foreach ($apps as [$no, $n, $asal, $usia, $pend, $prog, $tgl, $hp, $st, $b, $extra]) {
            Applicant::create([
                'reg_no' => $no, 'nama' => $n, 'asal' => $asal, 'ttl' => $asal, 'usia' => $usia, 'pendidikan' => $pend, 'program' => $prog,
                'registered_at' => $tgl, 'hp' => $hp, 'email' => $st === 'diterima' ? 'lina.marlina@nihonbridge.id' : str_replace(' ', '.', strtolower($n)) . '@email.com', 'status' => $st, 'berkas' => $berkas($b), 'level_bahasa' => 'Belum pernah belajar', 'referensi' => 'Media sosial',
            ] + $extra);
        }

        // Perusahaan mitra (nama fiktif)
        $hk = Company::create(['nama' => 'Hikari Care Group', 'kota' => 'Osaka', 'bidang' => 'Kaigo', 'sejak' => '2024']);
        $mf = Company::create(['nama' => 'Minato Foods', 'kota' => 'Shizuoka', 'bidang' => 'Pengolahan Makanan', 'sejak' => '2025']);
        $tk = Company::create(['nama' => 'Tokai Seiki', 'kota' => 'Aichi', 'bidang' => 'Manufaktur', 'sejak' => '2025']);
        $sid = fn (string $e) => Student::whereHas('user', fn ($q) => $q->where('email', "$e@nihonbridge.id"))->value('id');

        $jobs = [
            ['JO-2026-014', $hk, 'Perawat lansia (Kaigo)', 'Tokutei Ginou', 4, 'JLPT N4 / JFT-Basic, ujian keterampilan Kaigo, ujian bahasa Kaigo', 'Kamis, 8 Okt 2026 · online', 'Interview', ['siti.r' => 'lulus', 'ahmad.fauzi' => 'menunggu']],
            ['JO-2026-015', $mf, 'Operator pengolahan makanan', 'Tokutei Ginou', 3, 'JLPT N4 / JFT-Basic, ujian keterampilan pengolahan makanan', 'Selasa, 20 Okt 2026 · online', 'Seleksi kandidat', ['dewi.l' => 'lulus']],
            ['JO-2026-016', $tk, 'Operator mesin', 'Magang (Ginou Jisshu)', 5, 'JLPT N5–N4, tes fisik, tes matematika dasar', 'Nov 2026 (belum ditetapkan)', 'Seleksi kandidat', ['rizky.p' => 'menunggu']],
        ];
        foreach ($jobs as [$code, $co, $pos, $jalur, $kuota, $syarat, $iv, $st, $cands]) {
            $j = JobOrder::create(['code' => $code, 'company_id' => $co->id, 'posisi' => $pos, 'jalur' => $jalur, 'kuota' => $kuota, 'syarat' => $syarat, 'interview' => $iv, 'status' => $st]);
            foreach ($cands as $e => $hasil) {
                $j->candidates()->create(['student_id' => $sid($e), 'hasil' => $hasil]);
            }
        }

        // Pembayaran cicilan (jumlah cicilan lunas per peserta)
        $fee = config('nihonbridge.fee');
        $paid = ['ahmad.fauzi' => 4, 'siti.r' => 5, 'dewi.l' => 6, 'rizky.p' => 3, 'budi.s' => 2, 'nur.a' => 2];
        foreach (Student::with('user')->get() as $s) {
            $key = explode('@', $s->user->email)[0];
            $n = $paid[$key] ?? ($s->classroom?->kode === 'N4-A' ? 4 : 1);
            for ($i = 1; $i <= $n; $i++) {
                Payment::create([
                    'student_id' => $s->id, 'installment_no' => $i, 'amount' => $fee['per'], 'method' => $i % 2 ? 'Transfer VA' : 'Transfer bank',
                    'status' => 'lunas', 'paid_at' => Carbon::parse($fee['due'][$i - 1])->subDays(3 + $i),
                ]);
            }
        }

        // Statistik tahunan & tingkat kelulusan JLPT resmi
        YearlyStat::create(['year' => 2026, 'peserta' => 350, 'kelas' => 12, 'lulus' => 82, 'berangkat' => 60, 'levels' => ['N5' => 140, 'N4' => 110, 'N3' => 70, 'N2' => 25, 'N1' => 5]]);
        YearlyStat::create(['year' => 2025, 'peserta' => 312, 'kelas' => 12, 'lulus' => 68, 'berangkat' => 46, 'levels' => ['N5' => 130, 'N4' => 98, 'N3' => 60, 'N2' => 20, 'N1' => 4]]);
        YearlyStat::create(['year' => 2024, 'peserta' => 270, 'kelas' => 10, 'lulus' => 55, 'berangkat' => 38]);
        foreach (['N5' => [62, 68, 74, 81], 'N4' => [48, 55, 61, 67], 'N3' => [30, 34, 40, 46]] as $lv => $rates) {
            foreach ($rates as $i => $r) {
                PassRate::create(['level' => $lv, 'year' => 2023 + $i, 'rate' => $r]);
            }
        }
    }
}
