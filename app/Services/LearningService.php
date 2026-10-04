<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LearningService
{
    public function markDone(User $user, Lesson $lesson): bool
    {
        if ($lesson->isDoneBy($user)) {
            return false;
        }
        DB::transaction(function () use ($user, $lesson) {
            LessonProgress::create(['user_id' => $user->id, 'lesson_id' => $lesson->id, 'completed_at' => now()]);
            $user->student?->increment('materi_selesai');
        });

        return true;
    }

    public function addChapter(string $level): Chapter
    {
        $no = (int) Chapter::where('level', $level)->max('no') + 1;

        return Chapter::create(['level' => $level, 'no' => $no, 'judul' => '(judul bab)', 'is_locked' => true]);
    }

    public function addLesson(Chapter $chapter, array $data, ?UploadedFile $file): Lesson
    {
        $path = $file?->store('materi/bab-' . $chapter->no, 'public');
        $lesson = $chapter->lessons()->create([
            'judul' => $data['judul'],
            'jenis' => $data['jenis'],
            'durasi' => $data['durasi'] ?: '–',
            'status' => $data['status'],
            'jp_title' => $data['judul'],
            'file_path' => $path,
            'sort_order' => (int) $chapter->lessons()->max('sort_order') + 1,
        ]);
        if ($lesson->status === 'Terbit') {
            $this->adjustTotal($chapter->level, 1);
        }

        return $lesson;
    }

    public function toggleStatus(Lesson $lesson): Lesson
    {
        $lesson->status = $lesson->status === 'Terbit' ? 'Draf' : 'Terbit';
        $lesson->save();
        $this->adjustTotal($lesson->chapter->level, $lesson->status === 'Terbit' ? 1 : -1);

        return $lesson;
    }

    public function move(Lesson $lesson, int $dir): void
    {
        $siblings = $lesson->chapter->lessons()->get()->values();
        $i = $siblings->search(fn ($l) => $l->id === $lesson->id);
        $j = $i + $dir;
        if ($j < 0 || $j >= $siblings->count()) {
            return;
        }
        DB::transaction(function () use ($siblings, $i, $j) {
            $ordered = $siblings->all();
            [$ordered[$i], $ordered[$j]] = [$ordered[$j], $ordered[$i]];
            foreach ($ordered as $k => $l) {
                $l->update(['sort_order' => $k + 1]);
            }
        });
    }

    public function delete(Lesson $lesson): void
    {
        DB::transaction(function () use ($lesson) {
            $level = $lesson->chapter->level;
            $doneBy = $lesson->progress()->pluck('user_id');
            Student::whereIn('user_id', $doneBy)->where('materi_selesai', '>', 0)->decrement('materi_selesai');
            if ($lesson->status === 'Terbit') {
                $this->adjustTotal($level, -1);
            }
            if ($lesson->file_path) {
                Storage::disk('public')->delete($lesson->file_path);
            }
            $lesson->delete();
        });
    }

    /** Ubah jumlah total materi untuk semua peserta di level yang sama. */
    private function adjustTotal(string $level, int $delta): void
    {
        $q = Student::whereHas('classroom', fn ($c) => $c->where('level', $level));
        $delta > 0 ? $q->increment('materi_total', $delta) : $q->where('materi_total', '>', 0)->decrement('materi_total', -$delta);
    }
}
