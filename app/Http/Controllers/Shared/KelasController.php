<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\User;
use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class KelasController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $isInstructor = $user->role === 'instruktur';
        $class = $isInstructor
            ? ($user->waliClasses()->first() ?? Classroom::find($user->taughtClassroomIds()[0] ?? 0))
            : $user->student?->classroom;
        abort_unless($class, 404, 'Anda belum ditempatkan di kelas.');
        $class->load('wali', 'students.user');

        $monday = Carbon::today()->startOfWeek();
        if (Carbon::today()->isWeekend()) {
            $monday->addWeek();
        }
        $dates = collect(range(0, 4))->map(fn ($i) => $monday->copy()->addDays($i));

        $attendance = null;
        if ($isInstructor) {
            $rows = Attendance::whereIn('student_id', $class->students->pluck('id'))->get(['student_id', 'status']);
            $per = $rows->groupBy('student_id')->map(fn ($r) => Fmt::pct($r->where('status', 'H')->count(), $r->count()));
            $attendance = [
                'avg' => $per->isEmpty() ? 0 : (int) round($per->avg()),
                'low' => $class->students->filter(fn ($s) => ($per[$s->id] ?? 100) < config('nihonbridge.attendance_min'))
                    ->map(fn ($s) => ['name' => $s->name, 'pct' => $per[$s->id]])->sortBy('pct')->values(),
            ];
        } else {
            $attendance = $user->student->attendanceSummary();
            $attendance['detail'] = Attendance::with('session')->where('student_id', $user->student->id)->whereIn('status', ['I', 'S', 'A'])
                ->get()->groupBy('status');
        }

        $instructors = User::whereIn('id', $class->schedules()->pluck('instructor_id'))->get()->map(function ($u) use ($class) {
            $subjects = $class->schedules()->where('instructor_id', $u->id)->get()->map->subject_name->unique()->values()->all();

            return $u->sensei_name . ' (' . implode(', ', $subjects) . ')';
        });

        return view('shared.kelas', [
            'class' => $class,
            'isInstructor' => $isInstructor,
            'grid' => $class->grid(),
            'dates' => $dates,
            'attendance' => $attendance,
            'announcements' => Announcement::where('classroom_id', $class->id)->orWhereNull('classroom_id')->latest()->limit(4)->get(),
            'instructors' => $instructors,
        ]);
    }
}
