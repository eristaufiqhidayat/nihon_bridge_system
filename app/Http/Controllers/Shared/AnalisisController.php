<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Services\AnalysisService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalisisController extends Controller
{
    public function __invoke(Request $request, AnalysisService $analysis): View
    {
        $user = $request->user();
        $packageIds = ExamAttempt::where('status', 'selesai')->whereNotNull('answers')->distinct()->pluck('exam_package_id');
        $packages = ExamPackage::whereIn('id', $packageIds)->where('status', 'Terbit')->orderBy('judul')->get();
        $package = $packages->firstWhere('id', (int) $request->query('paket')) ?? $packages->last();

        $classIds = $user->role === 'instruktur' ? $user->taughtClassroomIds() : Classroom::pluck('id')->all();
        $classes = Classroom::whereIn('id', $classIds)->orderBy('kode')->get();
        $class = $classes->firstWhere('id', (int) $request->query('kelas'))
            ?? ($user->role === 'instruktur' ? $user->waliClasses()->first() : null) ?? $classes->firstWhere('kode', 'N4-A') ?? $classes->first();

        $result = $package ? $analysis->analyse($package, $class?->id) : null;
        $sel = null;
        if ($result && $result['participants']) {
            $sel = collect($result['items'])->firstWhere('i', (int) $request->query('soal', -1)) ?? $result['hard'][0];
        }

        return view('shared.analisis', [
            'packages' => $packages,
            'package' => $package,
            'classes' => $classes,
            'class' => $class,
            'r' => $result,
            'sel' => $sel,
            'classSize' => $class?->students()->count() ?? 0,
        ]);
    }
}
