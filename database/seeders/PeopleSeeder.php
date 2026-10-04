<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Pengguna (4 peran), kelas, jadwal mingguan, dan data peserta.
 */
class PeopleSeeder extends Seeder
{
    public const PASSWORD = 'sakura2026';

    public function run(): void
    {
        $pw = Hash::make(self::PASSWORD);
        $mk = fn (string $name, string $email, string $role, ?string $phone = null, bool $active = true) => User::create([
            'name' => $name, 'email' => $email, 'password' => $pw, 'role' => $role, 'phone' => $phone, 'is_active' => $active,
            'email_verified_at' => now(),
        ]);

        // Staf
        $ins = [
            'S' => $mk('Sato Kenji', 'sato.sensei@nihonbridge.id', 'instruktur', '0813-2200-4411'),
            'Y' => $mk('Yamada Aiko', 'yamada.sensei@nihonbridge.id', 'instruktur', '0813-2200-4412'),
            'T' => $mk('Tanaka Hiroshi', 'tanaka.sensei@nihonbridge.id', 'instruktur', '0813-2200-4413'),
            'K' => $mk('Kobayashi Mei', 'kobayashi.sensei@nihonbridge.id', 'instruktur', '0813-2200-4414'),
            'D' => $mk('Dian Puspita', 'dian.sensei@nihonbridge.id', 'instruktur', '0813-2200-4415'),
            'N' => $mk('Nakamura Ren', 'nakamura.sensei@nihonbridge.id', 'instruktur', '0813-2200-4416'),
        ];
        $mk('Rina Wulandari', 'rina.info@nihonbridge.id', 'admin', '0811-9087-1122');
        $mk(config('nihonbridge.org.director'), 'direktur@nihonbridge.id', 'direktur', '[No. HP Direktur]');

        // Kelas & jadwal (kode mapel-instruktur per slot, Senin–Jumat)
        $classes = [
            ['N5-A', 'N5', 'Y', '1A', 'Jul – Des 2026', ['TB-Y TB-Y TB-Y TB-Y TB-Y', 'KK-D KK-D KK-D KK-D KK-D', 'PC-K BD-D PC-K BD-D PC-K']],
            ['N5-B', 'N5', 'K', '1B', 'Jul – Des 2026', ['KK-K KK-K KK-K KK-K KK-K', 'TB-K TB-K TB-K TB-K TB-K', 'PC-Y PC-Y PC-Y PC-Y PC-Y']],
            ['N5-C', 'N5', 'D', '1C', 'Sep 2026 – Feb 2027', ['PC-D PC-D PC-D PC-D PC-D', 'KK-S TB-Y KK-S TB-Y KK-S', 'TB-S TB-S TB-S TB-S TB-S']],
            ['N4-A', 'N4', 'S', '3B', 'Jul – Des 2026', ['TB-S KK-S TB-S KK-S TB-S', 'PC-Y BC-S PC-Y BC-S PC-Y', 'LS-T LJ-T LS-T LJ-T LS-T']],
            ['N4-B', 'N4', 'T', '3C', 'Jul – Des 2026', ['LS-T LS-T LS-T LS-T LS-T', 'LJ-T LJ-T LJ-T LJ-T LJ-T', 'KG-D BC-K KG-D BC-K KG-D']],
            ['N3-A', 'N3', 'N', '4A', 'Jul – Des 2026', ['TB-N TB-N TB-N TB-N TB-N', 'BC-N BC-N BC-N BC-N BC-N', 'LS-N - LS-N - LS-N']],
        ];
        $cls = [];
        foreach ($classes as [$kode, $level, $wali, $ruang, $periode, $rows]) {
            $c = Classroom::create([
                'kode' => $kode, 'level' => $level, 'wali_id' => $ins[$wali]->id, 'ruang' => $ruang, 'periode' => $periode,
                'nama' => $level === 'N4' ? 'Pemantapan N4' : ($level === 'N5' ? 'Dasar N5' : 'Menengah N3'),
            ]);
            $cls[$kode] = $c;
            foreach ($rows as $slot => $row) {
                foreach (explode(' ', $row) as $day => $cell) {
                    if ($cell === '-') {
                        continue;
                    }
                    [$m, $i] = explode('-', $cell);
                    Schedule::create(['classroom_id' => $c->id, 'day' => $day, 'slot' => $slot, 'subject' => $m, 'instructor_id' => $ins[$i]->id]);
                }
            }
        }

        // Peserta kelas N4-A: [nama, email, NIS, program, tahap, nilai impor, % belajar, catatan, HP]
        $peserta = [
            ['Ahmad Fauzi', 'ahmad.fauzi', 'NB-26-0142', 'Tokutei Ginou · Kaigo (perawat lansia)', 3, null, null, null, '0812-3456-7890'],
            ['Siti Rahmawati', 'siti.r', 'NB-26-0137', 'Tokutei Ginou · Kaigo', 4, 88, 84, null, '0812-1111-2201'],
            ['Dewi Lestari', 'dewi.l', 'NB-26-0129', 'Tokutei Ginou · Pengolahan Makanan', 5, 91, 92, null, '0812-1111-2202'],
            ['Rizky Pratama', 'rizky.p', 'NB-26-0151', 'Magang (Ginou Jisshu) · Manufaktur', 3, 71, 70, null, '0812-1111-2203'],
            ['Budi Santoso', 'budi.s', 'NB-26-0148', 'Magang (Ginou Jisshu) · Konstruksi', 2, 57, 58, 'Nilai Bunpou di bawah 40. Perlu kelas remedial sebelum tryout ulang.', '0812-1111-2204'],
            ['Nur Aisyah', 'nur.a', 'NB-26-0155', 'Tokutei Ginou · Kaigo', 2, 49, 61, 'Kehadiran 78%. Hubungi wali peserta.', '0812-1111-2205'],
            ['Indra Kusuma', 'indra.k', 'NB-26-0156', 'Tokutei Ginou · Kaigo', 2, 74, 66, null, '0812-1111-2206'],
            ['Yuni Pratiwi', 'yuni.p', 'NB-26-0157', 'Tokutei Ginou · Pengolahan Makanan', 2, 66, 72, null, '0812-1111-2207'],
            ['Mega Fitriani', 'mega.f', 'NB-26-0158', 'Tokutei Ginou · Kaigo', 1, 81, 75, null, '0812-1111-2208'],
            ['Fajar Siddiq', 'fajar.s', 'NB-26-0159', 'Magang (Ginou Jisshu) · Manufaktur', 1, 63, 60, null, '0812-1111-2209'],
            ['Ikhsan Maulana', 'ikhsan.m', 'NB-26-0160', 'Magang (Ginou Jisshu) · Konstruksi', 1, 69, 64, null, '0812-1111-2210'],
            ['Rani Oktaviani', 'rani.o', 'NB-26-0161', 'Tokutei Ginou · Kaigo', 1, 77, 70, null, '0812-1111-2211'],
        ];
        foreach ($peserta as [$n, $e, $nis, $prog, $stage, $nilai, $belajar, $note, $hp]) {
            $u = $mk($n, "$e@nihonbridge.id", 'peserta', $hp);
            $isAhmad = $e === 'ahmad.fauzi';
            Student::create([
                'user_id' => $u->id,
                'classroom_id' => $cls['N4-A']->id,
                'nis' => $nis,
                'program' => $prog,
                'stage' => $stage,
                'stage_dates' => $isAhmad ? ['10 Jan 2026', 'Feb – Agu 2026', '10 Sep 2026', 'Jadwal 8 Okt 2026', 'Target Nov 2026', 'Target Jan 2027', 'Rencana Mar 2027'] : null,
                'birth_place_date' => $isAhmad ? 'Palembang, 14 Mei 2002' : null,
                'address' => $isAhmad ? 'Jl. KH Azhari No. 12, Seberang Ulu II, Palembang' : null,
                'guardian_contact' => $isAhmad ? 'Sulastri (ibu) · 0812-7788-9900' : null,
                'note' => $note,
                'nilai_tryout' => $nilai,
                'materi_selesai' => $isAhmad ? 82 : (int) round($belajar * 1.1),
                'materi_total' => 110,
                'target_departure' => $isAhmad ? 'Mar 2027' : null,
            ]);
        }

        // Peserta kelas lain
        $fajar = $mk('Fajar Nugroho', 'fajar.n@nihonbridge.id', 'peserta', '0812-1111-2290', false);
        Student::create(['user_id' => $fajar->id, 'classroom_id' => $cls['N5-B']->id, 'nis' => 'NB-26-0090', 'program' => 'Magang (Ginou Jisshu) · Manufaktur', 'stage' => 1, 'materi_total' => 90, 'materi_selesai' => 30]);
        $lina = $mk('Lina Marlina', 'lina.marlina@nihonbridge.id', 'peserta', '0878-2323-6060');
        Student::create(['user_id' => $lina->id, 'classroom_id' => $cls['N5-C']->id, 'nis' => 'NB-26-0162', 'program' => 'Tokutei Ginou · Kaigo', 'stage' => 1, 'birth_place_date' => 'Bogor, 2004', 'materi_total' => 90]);
    }
}
