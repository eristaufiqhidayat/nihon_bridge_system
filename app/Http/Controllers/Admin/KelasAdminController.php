<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\User;
use App\Services\ScheduleService;
use App\Support\Catalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KelasAdminController extends Controller
{
    public function index(Request $request): View
    {
        $classes = Classroom::with('wali', 'batch')->withCount('students')->orderBy('level', 'desc')->orderBy('kode')->get();
        $class = $classes->firstWhere('kode', $request->query('kelas')) ?? $classes->firstWhere('kode', 'N4-A') ?? $classes->first();

        return view('admin.kelas', [
            'classes' => $classes,
            'class' => $class,
            'grid' => $class?->grid() ?? [],
            'batches' => Batch::orderBy('mulai')->get(),
            'instructors' => User::where('role', 'instruktur')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['kode' => strtoupper(trim((string) $request->input('kode')))]);
        $data = $request->validate([
            'batch_id' => ['required', 'exists:batches,id'],
            'kode' => ['required', 'regex:/^N[1-5]-[A-Z]$/', 'unique:classrooms,kode'],
            'level' => ['required', Rule::in(Catalog::LEVELS)],
            'wali_id' => ['required', 'exists:users,id'],
            'ruang' => ['nullable', 'string', 'max:10'],
        ], [
            'kode.regex' => 'Format kode: level-huruf, mis. N5-D.',
            'kode.unique' => 'Kelas :input sudah ada.',
            'batch_id.required' => 'Pilih angkatan kelas ini.',
        ]);
        $start = now()->addMonth()->startOfMonth();
        $c = Classroom::create($data + ['periode' => \App\Support\Fmt::monthYear($start) . ' – ' . \App\Support\Fmt::monthYear($start->copy()->addMonths(5))]);

        return redirect()->route('kelas-admin.index', ['kelas' => $c->kode])->with('toast', "Kelas {$c->kode} dibuat. Isi jadwalnya di bawah.");
    }

    public function slot(Request $request, Classroom $classroom, ScheduleService $schedules): RedirectResponse
    {
        $data = $request->validate([
            'slot' => ['required', 'integer', Rule::in(array_keys(Catalog::SLOTS))],
            'day' => ['required', 'integer', Rule::in(array_keys(Catalog::DAYS))],
            'subject' => ['nullable', Rule::in(array_keys(Catalog::MAPEL))],
            'instructor_id' => ['required_with:subject', 'nullable', 'exists:users,id'],
        ]);
        $row = $schedules->saveSlot($classroom, (int) $data['slot'], (int) $data['day'], $data['subject'] ?? null, $data['instructor_id'] ?? null);

        return redirect()->route('kelas-admin.index', ['kelas' => $classroom->kode])
            ->with('toast', $row ? 'Jadwal disimpan. Peserta dan instruktur diberi notifikasi.' : 'Sesi dikosongkan');
    }
}
