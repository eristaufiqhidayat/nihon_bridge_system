<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Classroom;
use App\Services\ApplicantService;
use App\Support\Catalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PendaftaranController extends Controller
{
    public function __construct(private ApplicantService $service)
    {
    }

    public function index(Request $request): View
    {
        $all = Applicant::orderByDesc('registered_at')->orderByDesc('id')->get();
        $f = array_key_exists($request->query('status'), Catalog::APP_STATUS) ? $request->query('status') : 'Semua';
        $list = $f === 'Semua' ? $all : $all->where('status', $f)->values();
        $sel = $all->firstWhere('id', (int) $request->query('sel')) ?? $list->first() ?? $all->first();

        return view('admin.pendaftaran', [
            'all' => $all, 'list' => $list, 'f' => $f, 'sel' => $sel,
            'n5Classes' => Classroom::where('level', 'N5')->orderBy('kode')->get(),
        ]);
    }

    public function file(Applicant $applicant, string $key): StreamedResponse
    {
        $f = ($applicant->berkas ?? [])[$key] ?? null;
        abort_unless($f && ! empty($f['path']) && Storage::exists($f['path']), 404);

        return Storage::download($f['path'], $f['name']);
    }

    public function passDocuments(Applicant $applicant): RedirectResponse
    {
        $this->service->passDocuments($applicant);

        return back()->with('toast', "{$applicant->nama}: Lolos berkas");
    }

    public function scheduleTest(Request $request, Applicant $applicant): RedirectResponse
    {
        $data = $request->validate(['tanggal' => ['required', 'date', 'after_or_equal:today'], 'jam' => ['required', Rule::in(['09.00', '13.00'])]]);
        $this->service->scheduleTest($applicant, \App\Support\Fmt::dayDate($data['tanggal']) . ' · ' . $data['jam']);

        return back()->with('toast', "Undangan tes dikirim ke {$applicant->nama} lewat WhatsApp {$applicant->hp}");
    }

    public function accept(Request $request, Applicant $applicant): RedirectResponse
    {
        $class = Classroom::findOrFail($request->validate(['classroom_id' => ['required', 'exists:classrooms,id']])['classroom_id']);
        $this->service->accept($applicant, $class);

        return back()->with('toast', "{$applicant->nama} diterima di {$class->kode}. Akun dibuat.");
    }

    public function reject(Request $request, Applicant $applicant): RedirectResponse
    {
        $data = $request->validate(['alasan' => ['required', Rule::in(Catalog::REJECT_REASONS)]]);
        $this->service->reject($applicant, $data['alasan']);

        return back()->with('toast', "{$applicant->nama} ditolak. Pemberitahuan dikirim.");
    }

    public function remind(Applicant $applicant): RedirectResponse
    {
        return back()->with('toast', "Pengingat berkas untuk {$applicant->nama} dicatat. Kirim ke WhatsApp {$applicant->hp}.");
    }
}
