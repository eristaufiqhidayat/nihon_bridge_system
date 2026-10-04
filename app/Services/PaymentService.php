<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class PaymentService
{
    public function __construct(private NotificationService $notifier)
    {
    }

    public function fee(): array
    {
        return config('nihonbridge.fee');
    }

    public function paidCount(Student $s): int
    {
        return $s->payments->where('status', 'lunas')->count();
    }

    public function pending(Student $s): ?Payment
    {
        return $s->payments->firstWhere('status', 'menunggu');
    }

    /** Jumlah cicilan yang sudah jatuh tempo per hari ini. */
    public function dueByToday(?Carbon $today = null): int
    {
        $today ??= Carbon::today();

        return collect($this->fee()['due'])->filter(fn ($d) => Carbon::parse($d)->lessThanOrEqualTo($today))->count();
    }

    public function overdue(Student $s): int
    {
        return max(0, $this->dueByToday() - $this->paidCount($s));
    }

    public function vaNumber(Student $s): string
    {
        $digits = $this->fee()['va_prefix'] . str_pad(preg_replace('/\D/', '', $s->nis), 6, '0', STR_PAD_LEFT) . str_pad((string) ($this->paidCount($s) + 1), 6, '0', STR_PAD_LEFT);

        return trim(chunk_split($digits, 4, ' '));
    }

    /** Admin mencatat pembayaran cicilan berikutnya. */
    public function record(Student $s, string $method, string $date, User $by): Payment
    {
        $no = $this->paidCount($s) + 1;
        $p = Payment::updateOrCreate(
            ['student_id' => $s->id, 'installment_no' => $no],
            ['amount' => $this->fee()['per'], 'method' => $method, 'status' => 'lunas', 'paid_at' => $date, 'verified_by' => $by->id]
        );
        $this->notifier->notify($s->user, '🧾', "Pembayaran cicilan ke-$no diterima. Kuitansi dikirim ke email Anda.", route('tagihan.index'));

        return $p;
    }

    /** Peserta mengunggah bukti transfer cicilan berikutnya. */
    public function uploadProof(Student $s, UploadedFile $file): Payment
    {
        $no = $this->paidCount($s) + 1;
        $p = Payment::updateOrCreate(
            ['student_id' => $s->id, 'installment_no' => $no],
            ['amount' => $this->fee()['per'], 'method' => 'Transfer VA', 'status' => 'menunggu', 'paid_at' => now()->toDateString(),
                'proof_path' => $file->store('bukti-transfer')]
        );
        $this->notifier->notifyRole('admin', '📎', "Bukti transfer cicilan ke-$no dari {$s->name} menunggu verifikasi", route('keuangan.index'));

        return $p;
    }

    public function verify(Payment $p, User $by): void
    {
        $p->update(['status' => 'lunas', 'verified_by' => $by->id]);
        $this->notifier->notify($p->student->user, '✅', "Pembayaran cicilan ke-{$p->installment_no} terverifikasi. Kuitansi dikirim.", route('tagihan.index'));
    }
}
