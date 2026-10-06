<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagihanController extends Controller
{
    public function __construct(private PaymentService $payments)
    {
    }

    public function index(Request $request): View
    {
        $student = $request->user()->student()->with(PaymentService::RELATIONS)->firstOrFail();

        return view('peserta.tagihan', [
            'student' => $student,
            'fee' => $this->payments->fee(),
            'total' => $this->payments->total($student),
            'schedule' => $this->payments->schedule($student),
            'next' => $this->payments->next($student),
            'paid' => $this->payments->paidCount($student),
            'pending' => $this->payments->pending($student),
            'va' => $this->payments->vaNumber($student),
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate(['bukti' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:' . config('nihonbridge.upload_max_kb')]], [
            'bukti.max' => 'Ukuran bukti transfer maksimal 2 MB.',
            'bukti.mimes' => 'Unggah foto (JPG/PNG) atau PDF.',
        ]);
        $student = $request->user()->student()->with(PaymentService::RELATIONS)->firstOrFail();
        abort_if($this->payments->isPaidOff($student), 422);
        $this->payments->uploadProof($student, $request->file('bukti'));

        return back()->with('toast', 'Bukti transfer terkirim. Menunggu verifikasi admin.');
    }
}
