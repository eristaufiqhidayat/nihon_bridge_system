<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\ExamPackage;
use App\Models\ExamSchedule;
use App\Models\Question;
use App\Services\PackageService;
use App\Support\Catalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaketController extends Controller
{
    public function __construct(private PackageService $service)
    {
    }

    public function index(Request $request): View
    {
        $packages = ExamPackage::withCount('picks')->orderBy('id')->get();
        $package = $packages->firstWhere('id', (int) $request->query('paket')) ?? $packages->firstWhere('status', 'Draf') ?? $packages->last();
        $package?->load('picks');
        $filter = array_key_exists($request->query('bagian'), Catalog::SECTIONS) ? $request->query('bagian') : 'Semua';

        return view('admin.paket', [
            'packages' => $packages,
            'package' => $package,
            'filter' => $filter,
            'pool' => $package ? Question::where('level', $package->level)->when($filter !== 'Semua', fn ($q) => $q->where('section', $filter))->orderBy('code')->get() : collect(),
            'counts' => $package ? $this->service->counts($package) : [],
            'ok' => $package ? $this->service->compositionOk($package) : false,
            'schedules' => ExamSchedule::with('package', 'classroom')->where('mode', 'cbt')->whereNotNull('exam_package_id')->orderBy('opens_at')->get(),
            'classes' => Classroom::orderBy('kode')->get(),
        ]);
    }

    public function store(): RedirectResponse
    {
        $p = $this->service->createDraft();

        return redirect()->route('paket.index', ['paket' => $p->id])->with('toast', "{$p->judul} dibuat sebagai draf");
    }

    public function pick(Request $request, ExamPackage $package): RedirectResponse
    {
        $q = Question::findOrFail($request->validate(['question_id' => ['required', 'integer']])['question_id']);
        $this->service->togglePick($package, $q, $request->boolean('on'));

        return back();
    }

    public function autoPick(ExamPackage $package): RedirectResponse
    {
        $this->service->autoPick($package);

        return back()->with('toast', $package->picks()->count() . ' soal dipilih sesuai komposisi');
    }

    public function publish(ExamPackage $package): RedirectResponse
    {
        $this->service->publish($package);

        return redirect()->route('paket.index', ['paket' => $package->id, 'jadwalkan' => 1])->with('toast', 'Paket terbit');
    }

    public function schedule(Request $request, ExamPackage $package): RedirectResponse
    {
        $data = $request->validate([
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'duration' => ['required', 'integer', 'between:15,180'],
            'opens_at' => ['required', 'date'],
            'closes_at' => ['required', 'date', 'after_or_equal:opens_at'],
        ], ['closes_at.after_or_equal' => 'Tanggal tutup harus setelah tanggal buka.']);
        $s = $this->service->schedule($package, $data);

        return redirect()->route('paket.index', ['paket' => $package->id])
            ->with('toast', "{$package->judul} dijadwalkan untuk {$s->classroom->kode}. Peserta diberi notifikasi.");
    }
}
