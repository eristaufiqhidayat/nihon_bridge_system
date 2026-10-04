<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Announcement;
use App\Models\AppNotification;
use App\Models\Classroom;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Percakapan, notifikasi, pengumuman kelas, dan log aktivitas admin.
 */
class CommunicationSeeder extends Seeder
{
    public function run(): void
    {
        $u = fn (string $e) => User::where('email', "$e@nihonbridge.id")->first();
        $ahmad = $u('ahmad.fauzi');
        $sato = $u('sato.sensei');
        $rina = $u('rina.info');
        $dir = $u('direktur');
        $budi = $u('budi.s');

        /** @param array<int, array{0: User, 1: string, 2: string}> $msgs */
        $conv = function (array $people, array $msgs, array $readAt = [], ?string $title = null, ?Classroom $class = null) {
            $c = Conversation::create(['title' => $title, 'is_group' => (bool) $title, 'classroom_id' => $class?->id]);
            foreach ($people as $p) {
                $c->participants()->attach($p->id, ['last_read_at' => $readAt[$p->id] ?? null]);
            }
            foreach ($msgs as [$from, $body, $at]) {
                Message::create(['conversation_id' => $c->id, 'user_id' => $from->id, 'body' => $body, 'created_at' => $at, 'updated_at' => $at]);
            }

            return $c;
        };

        $conv([$ahmad, $sato], [
            [$sato, 'Ahmad-san, hasil tryout Paket 3 bagus sekali. Pertahankan Bunpou-nya.', '2026-09-10 16:02'],
            [$ahmad, 'Terima kasih, Sensei! Saya akan latihan 〜ています lagi.', '2026-09-10 16:10'],
            [$sato, 'Untuk interview 8 Oktober, siapkan jikoshoukai 1 menit ya. Kita latihan hari Rabu.', '2026-09-27 19:30'],
        ], [$ahmad->id => '2026-09-10 16:10', $sato->id => '2026-09-27 19:30']);

        $conv([$ahmad, $rina], [
            [$rina, 'Jadwal interview dengan perusahaan: Kamis, 8 Okt 2026 pukul 09.00 WIB di kantor LPK.', '2026-09-25 10:00'],
            [$rina, 'Mohon bawa paspor asli dan pas foto 3×4 (2 lembar).', '2026-09-25 10:01'],
        ], [$rina->id => '2026-09-25 10:01']);

        $n4a = Classroom::where('kode', 'N4-A')->first();
        $members = $n4a->students()->with('user')->get()->pluck('user')->push($sato);
        $siti = $u('siti.r');
        $rizky = $u('rizky.p');
        $conv($members->all(), [
            [$siti, 'Besok tryout nasional dibuka pendaftarannya ya.', '2026-09-26 08:12'],
            [$rizky, 'Ada yang punya catatan Bab 15?', '2026-09-26 20:41'],
            [$ahmad, 'Ada, nanti saya kirim.', '2026-09-26 20:45'],
        ], $members->mapWithKeys(fn ($m) => [$m->id => '2026-09-26 21:00'])->all(), 'Grup Kelas N4-A', $n4a);

        $conv([$sato, $budi], [[$budi, 'Sensei, kapan jadwal remedial Bunpou?', '2026-09-26 14:20']], [$budi->id => '2026-09-26 14:20']);
        $conv([$rina, $sato], [
            [$rina, 'Sensei, mohon rekap kehadiran N4-A bulan September paling lambat Rabu.', '2026-09-26 09:10'],
            [$sato, 'Siap, Bu Rina.', '2026-09-26 09:30'],
        ], [$rina->id => '2026-09-26 09:30', $sato->id => '2026-09-26 09:30']);
        $conv([$dir, $rina], [
            [$dir, 'Rina, tolong siapkan laporan kelulusan Q3 untuk rapat Jumat.', '2026-09-26 08:00'],
            [$rina, 'Baik Pak, draf saya kirim Kamis.', '2026-09-26 08:20'],
        ], [$dir->id => '2026-09-26 08:00', $rina->id => '2026-09-26 08:20']);
        $conv([$dir, $sato], [[$sato, 'Pak Direktur, 3 peserta N4-A siap interview batch Oktober.', '2026-09-24 15:00']], [$dir->id => '2026-09-24 16:00', $sato->id => '2026-09-24 15:00']);

        // Notifikasi
        $now = Carbon::now();
        $notifs = [
            [$ahmad, '📅', 'Interview dijadwalkan Kamis, 8 Okt 2026 pukul 09.00 WIB', route('program.index'), 2],
            [$ahmad, '📘', 'Materi baru: Bab 16 〜てもいいです', route('materi.show'), 1],
            [$ahmad, '✉️', 'Pesan baru dari Sato Sensei', route('pesan.index'), 1],
            [$sato, '⚠️', '2 peserta N4-A perlu perhatian (nilai < 60)', route('monitoring.index'), 0],
            [$sato, '✉️', 'Budi Santoso mengirim pesan', route('pesan.index'), 2],
            [$rina, '📄', 'Dokumen peserta menunggu verifikasi', route('monitoring.index'), 0],
            [$rina, '🗂️', 'Sato Sensei menambahkan 3 soal N4', route('banksoal.index'), 1],
            [$rina, '✉️', 'Pesan dari Direktur', route('pesan.index'), 3],
            [$dir, '📈', 'Laporan kelulusan September tersedia', route('laporan.index'), 0],
            [$dir, '✉️', 'Balasan dari Rina Wulandari', route('pesan.index'), 3],
        ];
        foreach ($notifs as [$user, $icon, $msg, $url, $daysAgo]) {
            AppNotification::create(['user_id' => $user->id, 'icon' => $icon, 'message' => $msg, 'url' => $url, 'created_at' => $now->copy()->subDays($daysAgo)->subHours(2)]);
        }

        // Pengumuman kelas
        foreach ([
            ['📣', 'ic-bg-sakura', 'Tryout JLPT N4 Nasional', 'Selasa 13 Okt · daftar paling lambat 5 Okt'],
            ['🧑‍💼', 'ic-bg-blue', 'Interview batch Oktober', '6–9 Okt · 3 peserta N4-A'],
            ['📘', 'ic-bg-green', 'Bab 16 dibuka', 'Mulai Senin 28 Sep'],
        ] as $k => [$icon, $color, $title, $body]) {
            Announcement::create(['classroom_id' => $n4a->id, 'icon' => $icon, 'color' => $color, 'title' => $title, 'body' => $body, 'created_at' => $now->copy()->subDays(5 - $k)]);
        }

        // Aktivitas terbaru (dasbor admin)
        foreach ([
            ['🏫', 'ic-bg-purple', 'Kelas N5-C dibuka (18 peserta)', '2026-09-21 09:00'],
            ['✓', 'ic-bg-green', 'Ahmad Fauzi lulus Simulasi JLPT N4 (83)', '2026-09-10 10:20'],
            ['📄', 'ic-bg-orange', 'Siti Rahmawati mengunggah hasil MCU', '2026-09-27 11:05'],
            ['🗂️', 'ic-bg-blue', 'Sato Sensei menambah 3 soal N4', '2026-09-27 16:20'],
        ] as [$icon, $color, $msg, $at]) {
            Activity::create(['icon' => $icon, 'color' => $color, 'message' => $msg, 'created_at' => $at, 'updated_at' => $at]);
        }
    }
}
