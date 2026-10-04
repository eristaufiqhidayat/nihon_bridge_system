<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\LearningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MateriController extends Controller
{
    public function show(Request $request, ?Lesson $lesson = null): View
    {
        $user = $request->user();
        $student = $user->student()->with('classroom')->firstOrFail();
        $chapters = Chapter::with('publishedLessons')->where('level', $student->classroom?->level)->orderBy('no')->get()
            ->filter(fn ($c) => $c->publishedLessons->isNotEmpty() || $c->is_locked);
        $doneIds = LessonProgress::where('user_id', $user->id)->pluck('lesson_id')->all();

        // Bab terbuka bila tidak dikunci, atau semua bagian bab sebelumnya sudah selesai.
        $unlocked = [];
        $prevDone = true;
        foreach ($chapters as $c) {
            $unlocked[$c->id] = ! $c->is_locked || $prevDone;
            $prevDone = $c->publishedLessons->every(fn ($l) => in_array($l->id, $doneIds, true)) && $c->publishedLessons->isNotEmpty();
        }

        if ($lesson) {
            abort_unless($lesson->status === 'Terbit' && ($unlocked[$lesson->chapter_id] ?? false), 404);
            $chapter = $chapters->firstWhere('id', $lesson->chapter_id);
        } else {
            $chapter = $chapters->first(fn ($c) => ($unlocked[$c->id] ?? false) && $c->publishedLessons->contains(fn ($l) => ! in_array($l->id, $doneIds, true)))
                ?? $chapters->first(fn ($c) => $unlocked[$c->id] ?? false);
            $lesson = $chapter?->publishedLessons->first(fn ($l) => ! in_array($l->id, $doneIds, true)) ?? $chapter?->publishedLessons->first();
        }

        return view('peserta.materi', compact('student', 'chapters', 'chapter', 'lesson', 'doneIds', 'unlocked'));
    }

    public function complete(Request $request, Lesson $lesson, LearningService $learning): RedirectResponse
    {
        $student = $request->user()->student;
        $learning->markDone($request->user(), $lesson);
        $student->refresh();

        return redirect()->route('materi.show', $lesson)->with('toast', "Materi selesai. Progres belajar sekarang {$student->progres_belajar}%");
    }
}
