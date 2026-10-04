<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Services\ExamService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HasilController extends Controller
{
    public function __invoke(Request $request, ?ExamAttempt $attempt = null): View
    {
        $user = $request->user();
        $history = $user->attempts()->with('package', 'certificate')->where('status', 'selesai')
            ->orderByDesc('submitted_at')->orderByDesc('id')->get();
        if ($attempt) {
            abort_unless($attempt->user_id === $user->id && $attempt->status === 'selesai', 404);
        } else {
            $attempt = $history->first();
        }

        return view('peserta.hasil', [
            'attempt' => $attempt?->load('package', 'certificate'),
            'history' => $history,
            'pass' => $attempt ? ExamService::isPass($attempt) : false,
            'readiness' => $attempt ? ExamService::readiness($attempt) : 0,
            'showReview' => $request->boolean('pembahasan') && $attempt?->hasReview(),
            'wrongOnly' => $request->boolean('salah'),
        ]);
    }
}
