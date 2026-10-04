<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\ExamSchedule;
use App\Services\ExamService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ExamService $exams): View
    {
        $user = $request->user();
        $student = $user->student()->with('classroom.wali', 'documents')->firstOrFail();
        $class = $student->classroom;

        // Hari sekolah terdekat (hari ini, atau Senin berikutnya bila akhir pekan)
        $day = Carbon::today();
        $isToday = ! $day->isWeekend();
        while ($day->isWeekend()) {
            $day->addDay();
        }
        $todaySchedule = $class ? $class->schedules()->with('instructor')->where('day', $day->dayOfWeekIso - 1)->orderBy('slot')->get() : collect();

        $chapter = Chapter::where('level', $class?->level)->orderBy('no')->get()
            ->first(fn ($c) => $c->publishedLessons()->whereDoesntHave('progress', fn ($q) => $q->where('user_id', $user->id))->exists());

        $schedules = ExamSchedule::with('package')->where('classroom_id', $class?->id)->orderBy('opens_at')->get();

        return view('peserta.dashboard', [
            'student' => $student,
            'attendance' => $student->attendanceSummary(),
            'certCount' => $user->certificates()->count(),
            'chapter' => $chapter,
            'chapterDone' => $chapter ? $chapter->publishedLessons()->whereHas('progress', fn ($q) => $q->where('user_id', $user->id))->count() : 0,
            'chapterTotal' => $chapter ? $chapter->publishedLessons()->count() : 0,
            'scheduleDay' => $day,
            'isToday' => $isToday,
            'todaySchedule' => $todaySchedule,
            'lastAttempt' => $student->lastAttempt(),
            'running' => $exams->running($user),
            'openExam' => $schedules->first(fn ($s) => $s->isOpen()),
            'nextOnsite' => $schedules->first(fn ($s) => $s->mode === 'onsite' && $s->opens_at->isFuture()),
        ]);
    }
}
