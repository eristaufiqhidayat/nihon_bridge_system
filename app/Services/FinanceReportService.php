<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Laporan keuangan bulanan (basis kas):
 * - Pemasukan = pembayaran peserta berstatus lunas, menurut tanggal bayar.
 * - Pengeluaran = transaksi kas keluar (modul Pengeluaran), menurut tanggal transaksi.
 * - Laba = pemasukan − beban (5- pokok, 6- operasional, 8- lain-lain).
 * - Cashflow = saldo awal + kas masuk − kas keluar = saldo akhir; saldo awal dihitung dari seluruh transaksi sebelumnya.
 */
class FinanceReportService
{
    /** Tahun yang punya transaksi, ditambah tahun berjalan; terbaru dulu. */
    public function years(): array
    {
        return Payment::where('status', 'lunas')->whereNotNull('paid_at')->pluck('paid_at')
            ->merge(Expense::pluck('tanggal'))
            ->map(fn ($d) => (int) $d->year)
            ->push((int) now()->year)
            ->unique()->sortDesc()->values()->all();
    }

    /** Ambil tahun & bulan dari query (?ktahun=2026&kbulan=10) dengan nilai bawaan yang aman. */
    public function period(?string $year, ?string $month): array
    {
        $years = $this->years();
        $y = in_array((int) $year, $years, true) ? (int) $year : (int) now()->year;
        $m = (int) $month >= 1 && (int) $month <= 12 ? (int) $month : ($y === (int) now()->year ? (int) now()->month : 12);

        return [$years, $y, $m];
    }

    /** Ringkasan per bulan untuk satu tahun beserta rincian bulan terpilih. */
    public function report(?string $year = null, ?string $month = null): array
    {
        [$years, $y, $m] = $this->period($year, $month);
        $start = Carbon::create($y, 1, 1)->startOfDay();
        $end = $start->copy()->endOfYear();

        $income = $this->payments()->whereBetween('paid_at', [$start->toDateString(), $end->toDateString()])->get();
        $expenses = Expense::with('account', 'cashAccount')->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])->get();

        $saldo = $this->balanceBefore($start);
        $months = collect(range(1, 12))->map(function (int $i) use ($income, $expenses, &$saldo) {
            $in = (int) $income->filter(fn ($p) => $p->paid_at->month === $i)->sum('amount');
            $out = (int) $expenses->filter(fn ($e) => $e->tanggal->month === $i)->sum('jumlah');
            $row = ['month' => $i, 'pemasukan' => $in, 'pengeluaran' => $out, 'laba' => $in - $out, 'saldo_awal' => $saldo, 'saldo_akhir' => $saldo + $in - $out];
            $saldo = $row['saldo_akhir'];

            return $row;
        });

        $cur = $months[$m - 1];
        $mIncome = $income->filter(fn ($p) => $p->paid_at->month === $m);
        $mExpenses = $expenses->filter(fn ($e) => $e->tanggal->month === $m);

        return [
            'years' => $years,
            'year' => $y,
            'month' => $m,
            'months' => $months,
            'cur' => $cur,
            'prev' => $m > 1 ? $months[$m - 2] : null,
            'totals' => [
                'pemasukan' => (int) $months->sum('pemasukan'),
                'pengeluaran' => (int) $months->sum('pengeluaran'),
                'laba' => (int) $months->sum('laba'),
                'saldo_awal' => $months->first()['saldo_awal'],
                'saldo_akhir' => $months->last()['saldo_akhir'],
            ],
            'labaRugi' => $this->profitLoss($cur['pemasukan'], $mExpenses),
            'incomeByBatch' => $mIncome->groupBy(fn ($p) => $p->student?->batch?->nama ?? 'Tanpa angkatan')
                ->map(fn ($g) => ['n' => $g->count(), 'total' => (int) $g->sum('amount')])->sortByDesc('total'),
            'incomeByMethod' => $mIncome->groupBy(fn ($p) => $p->method ?: 'Lainnya')->map(fn ($g) => (int) $g->sum('amount'))->sortDesc(),
            'outByCash' => $mExpenses->groupBy(fn ($e) => $e->cashAccount?->label ?? 'Tanpa akun kas')->map(fn ($g) => (int) $g->sum('jumlah'))->sortDesc(),
        ];
    }

    private function payments()
    {
        return Payment::with('student.batch')->where('status', 'lunas')->whereNotNull('paid_at');
    }

    /** Saldo kas kumulatif (masuk − keluar) dari seluruh transaksi sebelum tanggal ini. */
    private function balanceBefore(Carbon $date): int
    {
        return (int) Payment::where('status', 'lunas')->where('paid_at', '<', $date->toDateString())->sum('amount')
            - (int) Expense::where('tanggal', '<', $date->toDateString())->sum('jumlah');
    }

    /** Laba rugi sederhana gaya SAK EMKM: pendapatan, beban per kelompok akun, laba kotor/operasional/bersih. */
    private function profitLoss(int $pendapatan, Collection $expenses): array
    {
        $groups = collect(Account::GROUPS)->except('1')->map(function ($g) use ($expenses) {
            $rows = $expenses->filter(fn ($e) => $e->account?->kelompok === $g[0]);

            return [
                'label' => $g[1],
                'total' => (int) $rows->sum('jumlah'),
                'accounts' => $rows->groupBy(fn ($e) => $e->account->label)->map(fn ($r) => (int) $r->sum('jumlah'))->sortKeys(),
            ];
        });
        $kotor = $pendapatan - $groups['5']['total'];
        $operasional = $kotor - $groups['6']['total'];

        return [
            'pendapatan' => $pendapatan,
            'groups' => $groups,
            'laba_kotor' => $kotor,
            'laba_operasional' => $operasional,
            'laba_bersih' => $operasional - $groups['8']['total'],
        ];
    }
}
