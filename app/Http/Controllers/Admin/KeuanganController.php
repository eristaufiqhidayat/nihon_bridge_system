<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Payment;
use App\Models\Student;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Support\Fmt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KeuanganController extends Controller
{
    public function __construct(private PaymentService $payments)
    {
    }

    /** Ringkasan keuangan per angkatan; tombol "Lihat peserta" membuka rincian angkatan. */
    public function index(Request $request): View
    {
        $rows = $this->rows(Student::with(['user', 'classroom', ...PaymentService::RELATIONS])->get());
        $groups = $rows->groupBy(fn ($r) => $r['student']->batch_id ?? self::NO_BATCH)
            ->map(fn ($g, $key) => [
                'key' => $key,
                'batch' => $g->first()['student']->batch,
                'count' => $g->count(),
                'lunas' => $g->filter(fn ($r) => $r['paid'] >= $r['stages'])->count(),
                'menunggak' => $g->filter(fn ($r) => $r['overdue'] > 0)->count(),
                ...$this->sums($g),
            ])
            // Angkatan terbaru di atas; peserta tanpa angkatan paling bawah.
            ->sortByDesc(fn ($g) => $g['batch']?->mulai?->timestamp ?? PHP_INT_MIN)->values();

        return view('admin.keuangan', [
            'groups' => $groups,
            ...$this->sums($rows),
            'pendingRows' => $rows->filter(fn ($r) => $r['pending'])->values(),
            'readonly' => $request->user()->role === 'direktur',
        ]);
    }

    /** Rincian per peserta dalam satu angkatan ($key = id angkatan, atau "tanpa"). */
    public function batch(Request $request, string $key): View
    {
        $batch = $key === self::NO_BATCH ? null : Batch::with('program', 'installments')->findOrFail($key);
        $students = Student::with(['user', 'classroom', ...PaymentService::RELATIONS])
            ->where('batch_id', $batch?->id)->get();
        $rows = $this->rows($students);

        return view('admin.keuangan-angkatan', [
            'batch' => $batch,
            'rows' => $rows,
            ...$this->sums($rows),
            'readonly' => $request->user()->role === 'direktur',
        ]);
    }

    private const NO_BATCH = 'tanpa';

    private function rows(Collection $students): Collection
    {
        return $students->sortBy('nis')->values()->map(fn ($s) => [
            'student' => $s,
            'paid' => $this->payments->paidCount($s),
            'stages' => $this->payments->installments($s),
            'total' => $this->payments->total($s),
            'paidAmount' => $this->payments->paidAmount($s),
            'remaining' => $this->payments->remaining($s),
            'overdue' => $this->payments->overdue($s),
            'overdueAmount' => $this->payments->overdueAmount($s),
            'pending' => $this->payments->pending($s),
        ]);
    }

    /** @return array{total: int, paid: int, sisa: int, tunggakan: int} */
    private function sums(Collection $rows): array
    {
        return [
            'total' => (int) $rows->sum('total'),
            'paid' => (int) $rows->sum('paidAmount'),
            'sisa' => (int) $rows->sum('remaining'),
            'tunggakan' => (int) $rows->sum('overdueAmount'),
        ];
    }

    public function verify(Request $request, Payment $payment): RedirectResponse
    {
        $this->payments->verify($payment->load('student.user'), $request->user());

        return back()->with('toast', 'Pembayaran terverifikasi. Kuitansi dikirim ke peserta.');
    }

    public function proof(Payment $payment): StreamedResponse
    {
        abort_unless($payment->proof_path && Storage::exists($payment->proof_path), 404);

        return Storage::download($payment->proof_path);
    }

    public function remind(Student $student, NotificationService $notifier): RedirectResponse
    {
        $student->load(['user', ...PaymentService::RELATIONS]);
        $od = $this->payments->overdue($student);
        $notifier->notify($student->user, '💳', "Pengingat: $od tahap pembayaran lewat jatuh tempo (" . Fmt::rupiah($this->payments->overdueAmount($student)) . '). Mohon segera dibayar.', route('tagihan.index'));

        return back()->with('toast', "Pengingat tagihan dikirim ke {$student->name}");
    }
}
