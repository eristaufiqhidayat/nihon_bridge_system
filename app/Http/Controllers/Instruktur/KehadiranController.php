<?php

namespace App\Http\Controllers\Instruktur;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KehadiranController extends Controller
{
    public function __construct(private AttendanceService $attendance)
    {
    }

    public function index(Request $request): View
    {
        $sessions = $this->attendance->recentSessions($request->user());
        $cur = $sessions->firstWhere('key', $request->query('sesi')) ?? $sessions->first();
        $students = collect();
        $saved = null;
        $values = [];
        if ($cur) {
            $students = $cur['classroom']->students()->with('user')->get()->sortBy(fn ($s) => $s->name)->values();
            $saved = $this->attendance->findSession($cur);
            $existing = $saved ? $saved->attendances()->pluck('status', 'student_id')->all() : [];
            foreach ($students as $s) {
                $values[$s->id] = $existing[$s->id] ?? 'H';
            }
        }

        return view('instruktur.kehadiran', compact('sessions', 'cur', 'students', 'saved', 'values'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['sesi' => ['required', 'string'], 'status' => ['required', 'array']]);
        $cur = $this->attendance->recentSessions($request->user())->firstWhere('key', $data['sesi']);
        abort_unless($cur, 404);
        $this->attendance->save($cur, $data['status'], $request->user());
        $h = collect($data['status'])->filter(fn ($v) => $v === 'H')->count();

        return redirect()->route('kehadiran.index', ['sesi' => $cur['key']])
            ->with('toast', "Kehadiran disimpan: $h hadir dari " . count($data['status']) . '. Peserta alpa diberi notifikasi.');
    }
}
