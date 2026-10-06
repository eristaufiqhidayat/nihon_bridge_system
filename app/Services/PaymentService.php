<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentService
{
    public function __construct(private NotificationService $notifier)
    {
    }

    /** Pengaturan pembayaran umum (VA, bank) dan jadwal cadangan bila angkatan belum punya tahapan. */
    public function fee(): array
    {
        return config('nihonbridge.fee');
    }

    /** Relasi yang dibutuhkan perhitungan tagihan; pakai di with() agar tidak N+1. */
    public const RELATIONS = ['payments', 'batch.program', 'batch.installments'];

    /** Total biaya peserta: total biaya peserta sendiri, lalu biaya angkatan/program, lalu biaya bawaan. */
    public function total(Student $s): int
    {
        return $s->total_fee ?? $s->batch?->totalBiaya() ?? (int) $this->fee()['total'];
    }

    /**
     * Jadwal tahapan pembayaran peserta, mengacu ke master angkatan. Bila angkatan
     * belum punya tahapan, dipakai jadwal bawaan di config/nihonbridge.php.
     * Nominal tiap tahap = total ÷ jumlah tahap; sisa pembulatan masuk ke tahap terakhir.
     *
     * @return Collection<int, array{no: int, due: Carbon, amount: int}>
     */
    public function schedule(Student $s): Collection
    {
        $stages = $s->batch?->installments;
        $dues = $stages && $stages->isNotEmpty()
            ? $stages->pluck('jatuh_tempo')->values()
            : collect($this->fee()['due'])->map(fn ($d) => Carbon::parse($d));
        $n = $dues->count();
        $total = $this->total($s);
        $per = intdiv($total, $n);

        return $dues->map(fn (Carbon $due, int $i) => [
            'no' => $i + 1,
            'due' => $due,
            'amount' => $i === $n - 1 ? $total - $per * ($n - 1) : $per,
        ]);
    }

    public function installments(Student $s): int
    {
        return $this->schedule($s)->count();
    }

    /** Nominal tagihan tahap ke-$no. */
    public function amountFor(Student $s, int $no): int
    {
        return $this->schedule($s)->firstWhere('no', $no)['amount'] ?? 0;
    }

    /** Tahap yang ditagih berikutnya, atau null bila semua lunas. */
    public function next(Student $s): ?array
    {
        return $this->schedule($s)->firstWhere('no', $this->paidCount($s) + 1);
    }

    public function paidCount(Student $s): int
    {
        return $s->payments->where('status', 'lunas')->count();
    }

    public function isPaidOff(Student $s): bool
    {
        return $this->paidCount($s) >= $this->installments($s);
    }

    /** Rupiah yang sudah dibayar (lunas). */
    public function paidAmount(Student $s): int
    {
        return (int) $s->payments->where('status', 'lunas')->sum('amount');
    }

    /** Sisa tagihan: nominal tahap yang belum lunas. */
    public function remaining(Student $s): int
    {
        return (int) $this->schedule($s)->where('no', '>', $this->paidCount($s))->sum('amount');
    }

    public function pending(Student $s): ?Payment
    {
        return $s->payments->firstWhere('status', 'menunggu');
    }

    /** Jumlah tahap peserta yang sudah jatuh tempo per hari ini. */
    public function dueByToday(Student $s, ?Carbon $today = null): int
    {
        $today ??= Carbon::today();

        return $this->schedule($s)->filter(fn ($t) => $t['due']->lessThanOrEqualTo($today))->count();
    }

    public function overdue(Student $s): int
    {
        return max(0, $this->dueByToday($s) - $this->paidCount($s));
    }

    /** Rupiah tahap yang lewat jatuh tempo tapi belum lunas. */
    public function overdueAmount(Student $s, ?Carbon $today = null): int
    {
        $today ??= Carbon::today();
        $paid = $this->paidCount($s);

        return (int) $this->schedule($s)->filter(fn ($t) => $t['no'] > $paid && $t['due']->lessThanOrEqualTo($today))->sum('amount');
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
            ['amount' => $this->amountFor($s, $no), 'method' => $method, 'status' => 'lunas', 'paid_at' => $date, 'verified_by' => $by->id]
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
            ['amount' => $this->amountFor($s, $no), 'method' => 'Transfer VA', 'status' => 'menunggu', 'paid_at' => now()->toDateString(),
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

    /** Admin mengoreksi metode dan tanggal bayar sebuah cicilan. */
    public function update(Payment $p, string $method, string $date): void
    {
        $p->update(['method' => $method, 'paid_at' => $date]);
    }

    /**
     * Admin menghapus catatan pembayaran. Cicilan sesudahnya dinomori ulang agar
     * tetap berurutan 1..n, karena cicilan berikutnya dicatat sebagai nomor paidCount + 1.
     */
    public function delete(Payment $p): void
    {
        DB::transaction(function () use ($p) {
            $studentId = $p->student_id;
            $p->delete();
            Payment::where('student_id', $studentId)->orderBy('installment_no')->get()
                ->values()->each(function (Payment $x, int $i) {
                    if ($x->installment_no !== $i + 1) {
                        $x->update(['installment_no' => $i + 1]);
                    }
                });
        });
        if ($p->proof_path) {
            Storage::delete($p->proof_path);
        }
    }
}
