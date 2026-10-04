<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Student;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Support\Catalog;
use App\Support\Fmt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KeuanganController extends Controller
{
    public function __construct(private PaymentService $payments)
    {
    }

    public function index(Request $request): View
    {
        $fee = $this->payments->fee();
        $students = Student::with('user', 'payments', 'classroom')->get()->sortBy('nis')->values();
        $rows = $students->map(fn ($s) => [
            'student' => $s,
            'paid' => $this->payments->paidCount($s),
            'overdue' => $this->payments->overdue($s),
            'pending' => $this->payments->pending($s),
        ]);

        return view('admin.keuangan', [
            'fee' => $fee,
            'rows' => $rows,
            'total' => $students->count() * $fee['total'],
            'paid' => $rows->sum(fn ($r) => $r['paid'] * $fee['per']),
            'tunggakan' => $rows->sum(fn ($r) => $r['overdue'] * $fee['per']),
            'pendingRows' => $rows->filter(fn ($r) => $r['pending'])->values(),
            'readonly' => $request->user()->role === 'direktur',
        ]);
    }

    public function record(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'method' => ['required', Rule::in(Catalog::PAYMENT_METHODS)],
            'paid_at' => ['required', 'date'],
        ]);
        $s = Student::with('payments', 'user')->findOrFail($data['student_id']);
        abort_if($this->payments->paidCount($s) >= $this->payments->fee()['installments'], 422, 'Semua cicilan sudah lunas.');
        $this->payments->record($s, $data['method'], $data['paid_at'], $request->user());

        return back()->with('toast', "Pembayaran {$s->name} dicatat. Kuitansi dikirim.");
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
        $student->load('payments', 'user');
        $od = $this->payments->overdue($student);
        $notifier->notify($student->user, '💳', "Pengingat: $od cicilan lewat jatuh tempo (" . Fmt::rupiah($od * $this->payments->fee()['per']) . '). Mohon segera dibayar.', route('tagihan.index'));

        return back()->with('toast', "Pengingat tagihan dikirim ke {$student->name}");
    }
}
