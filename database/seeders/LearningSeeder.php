<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Database\Seeder;

class LearningSeeder extends Seeder
{
    public function run(): void
    {
        $b15 = Chapter::create(['level' => 'N4', 'book' => 'Minna no Nihongo', 'no' => 15, 'judul' => '〜ています',
            'deskripsi' => 'aktivitas yang sedang berlangsung dan keadaan', 'contoh_jp' => 'わたしは　べんきょうしています。／かれは　ジャカルタに　すんでいます。']);
        $lessons15 = [
            ['Penjelasan 〜ています', '15:30', '〜ています', 'いま　なにを　していますか。'],
            ['Contoh Kalimat', '10:20', 'れいぶん', 'わたしは　にほんごを　べんきょうしています。'],
            ['Latihan Percakapan', '12:15', 'かいわ', 'A：どこに　すんでいますか。　B：ジャカルタに　すんでいます。'],
            ['Rangkuman & Kosakata Bab 15', '08:40', 'まとめ', 'しっています・すんでいます・けっこんしています'],
        ];
        $created = [];
        foreach ($lessons15 as $i => [$t, $dur, $jp, $sub]) {
            $created[] = $b15->lessons()->create(['judul' => $t, 'jenis' => 'Video', 'durasi' => $dur, 'status' => 'Terbit', 'jp_title' => $jp, 'jp_sub' => $sub, 'sort_order' => $i + 1]);
        }

        $b16 = Chapter::create(['level' => 'N4', 'book' => 'Minna no Nihongo', 'no' => 16, 'judul' => '〜てもいいです',
            'deskripsi' => 'meminta dan memberi izin', 'contoh_jp' => 'しゃしんを　とっても　いいですか。']);
        $b16->lessons()->createMany([
            ['judul' => 'Penjelasan 〜てもいいです', 'jenis' => 'Video', 'durasi' => '14:05', 'status' => 'Terbit', 'jp_title' => '〜てもいいです', 'jp_sub' => 'しゃしんを　とっても　いいですか。', 'sort_order' => 1],
            ['judul' => 'Lembar latihan Bab 16', 'jenis' => 'PDF', 'durasi' => '6 hlm', 'status' => 'Terbit', 'jp_title' => 'れんしゅう', 'sort_order' => 2],
            ['judul' => 'Percakapan: minta izin di tempat kerja', 'jenis' => 'Video', 'durasi' => '09:30', 'status' => 'Draf', 'jp_title' => 'かいわ', 'sort_order' => 3],
        ]);

        Chapter::create(['level' => 'N4', 'book' => 'Minna no Nihongo', 'no' => 17, 'judul' => '〜なければなりません', 'deskripsi' => 'keharusan']);

        // Ahmad sudah menyelesaikan dua bagian pertama Bab 15
        $ahmad = User::where('email', 'ahmad.fauzi@nihonbridge.id')->first();
        foreach (array_slice($created, 0, 2) as $k => $l) {
            LessonProgress::create(['user_id' => $ahmad->id, 'lesson_id' => $l->id, 'completed_at' => now()->subDays(6 - $k)]);
        }
    }
}
