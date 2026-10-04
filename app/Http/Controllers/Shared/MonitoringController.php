<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Student;
use App\Services\ProgramService;
use App\Support\Catalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $classIds = $user->role === 'instruktur' ? $user->taughtClassroomIds() : Classroom::pluck('id')->all();
        $classes = Classroom::whereIn('id', $classIds)->orderBy('kode')->get();
        $default = $user->role === 'instruktur' ? ($user->waliClasses()->first() ?? $classes->first()) : $classes->firstWhere('kode', 'N4-A') ?? $classes->first();
        $class = $classes->firstWhere('id', (int) $request->query('kelas')) ?? $default;

        $all = Student::with('user', 'documents', 'classroom')->where('classroom_id', $class?->id)->get()->sortBy('nis')->values();
        $q = trim((string) $request->query('q', ''));
        $list = $q === '' ? $all : $all->filter(fn ($s) => str_contains(mb_strtolower($s->name), mb_strtolower($q)))->values();
        $sel = $all->firstWhere('id', (int) $request->query('siswa')) ?? $all->first();

        return view('shared.monitoring', [
            'classes' => $classes,
            'class' => $class,
            'all' => $all,
            'list' => $list,
            'q' => $q,
            'sel' => $sel,
            'editable' => $user->role !== 'direktur',
            'avg' => $all->isEmpty() ? 0 : (int) round($all->avg(fn ($s) => $s->nilai)),
            'attn' => $all->filter(fn ($s) => $s->nilai < 60)->count(),
            'interview' => $all->filter(fn ($s) => $s->stage >= 3)->count(),
        ]);
    }

    public function document(Request $request, Student $student, ProgramService $program): RedirectResponse
    {
        $data = $request->validate([
            'doc' => ['required', Rule::in(array_column(Catalog::DOCS, 0))],
            'status' => ['required', Rule::in(array_keys(Catalog::DOC_STATUS))],
        ]);
        if ($request->user()->role === 'instruktur') {
            abort_unless(in_array($student->classroom_id, $request->user()->taughtClassroomIds(), true), 403);
        }
        $program->setDocument($student, $data['doc'], $data['status']);

        return back()->with('toast', 'Status dokumen diperbarui: ' . Catalog::DOC_STATUS[$data['status']]);
    }
}
