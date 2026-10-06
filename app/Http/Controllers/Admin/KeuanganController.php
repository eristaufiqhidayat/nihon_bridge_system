<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Student;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Support\Fmt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KeuanganController extends Controller
{
    public function __construct(private PaymentService $payments)
    {
    }

    public function index(Request $request): View
    {
        $students = Student::with(['user', 'classroom', ...PaymentService::RELATIONS])->get()->sortBy('nis')->values();
        $rows = $students->map(fn ($s) => [
            'student' => $s,
            'paid' => $this->payments->paidCount($s),
            'stages' => $this->payments->installments($s),
            'remaining' => $this->payments->remaining($s),
            'overdue' => $this->payments->overdue($s),
            'pending' => $this->payments->pending($s),
        ]);

        return view('admin.keuangan', [
            'rows' => $rows,
            'total' => $students->sum(fn ($s) => $this->payments->total($s)),
            'paid' => $students->sum(fn ($s) => $this->payments->paidAmount($s)),
            'tunggakan' => $students->sum(fn ($s) => $this->payments->overdueAmount($s)),
            'pendingRows' => $rows->filter(fn ($r) => $r['pending'])->values(),
            'readonly' => $request->user()->role === 'direktur',
        ]);
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
