<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\ExamSchedule;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UjianController extends Controller
{
    public function __construct(private ExamService $exams)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $class = $user->student?->classroom;
        $schedules = ExamSchedule::with('package')->where('classroom_id', $class?->id)
            ->orderByRaw("CASE WHEN mode = 'cbt' THEN 0 ELSE 1 END")->orderBy('opens_at')->get();
        $attemptCounts = $user->attempts()->selectRaw('exam_package_id, count(*) as n')->groupBy('exam_package_id')->pluck('n', 'exam_package_id');

        return view('peserta.ujian', [
            'class' => $class,
            'schedules' => $schedules,
            'attemptCounts' => $attemptCounts,
            'running' => $this->exams->running($user),
        ]);
    }

    public function start(Request $request, ExamSchedule $schedule): RedirectResponse
    {
        $attempt = $this->exams->start($request->user(), $schedule);

        return redirect()->route('ujian.cbt', $attempt)->with('toast', $attempt->wasRecentlyCreated ? 'Ujian dimulai. Semoga sukses!' : 'Melanjutkan ujian yang sedang berjalan');
    }

    public function cbt(Request $request, ExamAttempt $attempt): View|RedirectResponse
    {
        $this->authorizeAttempt($request, $attempt);
        if ($attempt->isRunning() && $attempt->secondsLeft() <= 0) {
            $this->exams->submit($attempt, true);
        }
        if (! $attempt->isRunning()) {
            return redirect()->route('hasil.index', $attempt)->with('toast', 'Waktu habis. Jawaban dikirim otomatis.');
        }

        return view('peserta.cbt', ['attempt' => $attempt->load('package', 'schedule'), 'questions' => $attempt->questions()]);
    }

    /** Autosave jawaban (dipanggil dari JavaScript CBT). */
    public function save(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $data = $request->validate([
            'answers' => ['array'],
            'flags' => ['array'],
            'current' => ['nullable', 'integer'],
        ]);
        if ($attempt->isRunning() && $attempt->secondsLeft() <= 0) {
            $this->exams->submit($attempt, true);

            return response()->json(['ok' => false, 'expired' => true, 'redirect' => route('hasil.index', $attempt)]);
        }
        $this->exams->save($attempt, $data);

        return response()->json(['ok' => true, 'left' => $attempt->secondsLeft(), 'answered' => count($attempt->answers ?? [])]);
    }

    public function submit(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        $this->authorizeAttempt($request, $attempt);
        if ($request->filled('payload')) {
            $this->exams->save($attempt, json_decode($request->input('payload'), true) ?: []);
        }
        $auto = $request->boolean('auto') || $attempt->secondsLeft() <= 0;
        $this->exams->submit($attempt, $auto);

        return redirect()->route('hasil.index', $attempt)
            ->with('toast', $auto ? 'Waktu habis. Jawaban dikirim otomatis.' : "Jawaban terkirim. Nilai Anda {$attempt->total}.");
    }

    private function authorizeAttempt(Request $request, ExamAttempt $attempt): void
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
    }
}
