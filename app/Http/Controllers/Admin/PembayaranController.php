<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Payment;
use App\Models\Student;
use App\Services\PaymentService;
use App\Support\Catalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pembayaran (admin): entri pembayaran cicilan, dikelompokkan per peserta, plus edit dan hapus.
 */
class PembayaranController extends Controller
{
    public function __construct(private PaymentService $payments)
    {
    }

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $list = Student::with(['user', 'classroom', 'batch.program', 'batch.installments', 'payments' => fn ($p) => $p->orderBy('installment_no')])
            ->when($q !== '', fn ($s) => $s->where(fn ($w) => $w
                ->where('nis', 'like', "%{$q}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"))))
            ->orderBy('nis')
            ->paginate(15)->withQueryString();

        return view('admin.pembayaran', [
            'list' => $list,
            'q' => $q,
            'pay' => $this->payments,
            // Pilihan peserta di form entri: hanya yang tahapannya belum lunas semua.
            'options' => Student::with(['user', ...PaymentService::RELATIONS])->orderBy('nis')->get()
                ->reject(fn ($s) => $this->payments->isPaidOff($s))->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'method' => ['required', Rule::in(Catalog::PAYMENT_METHODS)],
            'paid_at' => ['required', 'date'],
        ]);
        $s = Student::with(['user', ...PaymentService::RELATIONS])->findOrFail($data['student_id']);
        abort_if($this->payments->isPaidOff($s), 422, 'Semua tahap pembayaran sudah lunas.');
        $this->payments->record($s, $data['method'], $data['paid_at'], $request->user());

        return back()->with('toast', "Pembayaran {$s->name} dicatat. Kuitansi dikirim.");
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate([
            'method' => ['required', Rule::in(Catalog::PAYMENT_METHODS)],
            'paid_at' => ['required', 'date'],
        ]);
        $this->payments->update($payment, $data['method'], $data['paid_at']);

        return back()->with('toast', "Pembayaran cicilan ke-{$payment->installment_no} {$payment->student->name} diperbarui.");
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $name = $payment->student->name;
        $no = $payment->installment_no;
        $this->payments->delete($payment);
        Activity::log('🗑️', 'ic-bg-grey', "Pembayaran cicilan ke-{$no} {$name} dihapus");

        return back()->with('toast', "Pembayaran cicilan ke-{$no} {$name} dihapus.");
    }
}
