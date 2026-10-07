<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Activity;
use App\Models\Expense;
use App\Support\Fmt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pengeluaran (admin): transaksi kas keluar per kode akun beban, dibayar dari akun kas/bank.
 * Nomor bukti (BKK/tahun/bulan/urut) dibuat otomatis saat disimpan dan tidak berubah saat diedit.
 */
class PengeluaranController extends Controller
{
    public function index(Request $request): View
    {
        $bulan = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('bulan')) ? $request->query('bulan') : null;
        $f = [
            'q' => trim((string) $request->query('q', '')),
            'bulan' => $bulan,
            'akun' => $request->integer('akun') ?: null,
            'sumber' => $request->integer('sumber') ?: null,
        ];

        $filtered = fn (): Builder => Expense::query()
            ->when($f['bulan'], function ($q, $b) {
                $start = Carbon::createFromFormat('Y-m-d', "{$b}-01")->startOfMonth();
                $q->whereBetween('tanggal', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()]);
            })
            ->when($f['akun'], fn ($q, $a) => $q->where('account_id', $a))
            ->when($f['sumber'], fn ($q, $s) => $q->where('cash_account_id', $s))
            ->when($f['q'] !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nomor', 'like', "%{$f['q']}%")->orWhere('uraian', 'like', "%{$f['q']}%")->orWhere('penerima', 'like', "%{$f['q']}%")));

        $list = $filtered()->with('account', 'cashAccount', 'user')
            ->orderByDesc('tanggal')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        $perAkun = $filtered()->selectRaw('account_id, count(*) as n, sum(jumlah) as total')
            ->groupBy('account_id')->get()
            ->map(fn ($r) => ['account' => Account::find($r->account_id), 'n' => (int) $r->n, 'total' => (int) $r->total])
            ->sortBy(fn ($r) => $r['account']->kode)->values();

        return view('admin.pengeluaran', [
            'list' => $list,
            'f' => $f,
            'total' => (int) $filtered()->sum('jumlah'),
            'count' => $filtered()->count(),
            'perAkun' => $perAkun,
            'perKelompok' => $perAkun->groupBy(fn ($r) => $r['account']->kelompok_label)->map(fn ($rows) => $rows->sum('total')),
            'months' => $this->months(),
            'expenseAccounts' => Account::expense()->orderBy('kode')->get(),
            'cashAccounts' => Account::cash()->orderBy('kode')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Expense(['tanggal' => now()->toDateString()]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $expense = Expense::create([...$data, 'nomor' => Expense::nextNumber($data['tanggal']), 'user_id' => $request->user()->id]);
        Activity::log('💸', 'ic-bg-orange', "Pengeluaran {$expense->nomor} " . Fmt::rupiah($expense->jumlah) . " dicatat ({$expense->account->nama})");

        return redirect()->route('pengeluaran.index')->with('toast', "Pengeluaran {$expense->nomor} disimpan");
    }

    public function edit(Expense $expense): View
    {
        return $this->form($expense);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $expense->update($this->validated($request, $expense));

        return redirect()->route('pengeluaran.edit', $expense)->with('toast', "Pengeluaran {$expense->nomor} disimpan");
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();
        Activity::log('🗑️', 'ic-bg-grey', "Pengeluaran {$expense->nomor} dihapus");

        return redirect()->route('pengeluaran.index')->with('toast', "Pengeluaran {$expense->nomor} dihapus");
    }

    /** Bulan yang punya transaksi (terbaru dulu), ditambah bulan ini. */
    private function months(): array
    {
        return Expense::pluck('tanggal')->map(fn ($d) => $d->format('Y-m'))
            ->push(now()->format('Y-m'))->unique()->sortDesc()
            ->mapWithKeys(fn ($m) => [$m => Fmt::monthYear("{$m}-01")])->all();
    }

    /** Akun aktif, ditambah akun lama transaksi ini bila sudah dinonaktifkan. */
    private function options(Builder $q, ?int $current): Collection
    {
        return $q->where(fn ($w) => $w->where('is_active', true)->when($current, fn ($w2) => $w2->orWhere('id', $current)))
            ->orderBy('kode')->get();
    }

    private function form(Expense $expense): View
    {
        return view('admin.pengeluaran-form', [
            'expense' => $expense,
            'expenseAccounts' => $this->options(Account::expense(), $expense->account_id),
            'cashAccounts' => $this->options(Account::cash(), $expense->cash_account_id),
        ]);
    }

    private function validated(Request $request, ?Expense $expense = null): array
    {
        $usable = fn (string $scope, ?int $current) => Rule::exists('accounts', 'id')->where(fn ($q) => $q
            ->where('kode', $scope === 'cash' ? 'like' : 'not like', '1-%')
            ->where(fn ($w) => $w->where('is_active', true)->when($current, fn ($w2) => $w2->orWhere('id', $current))));

        return $request->validate([
            'tanggal' => ['required', 'date'],
            'account_id' => ['required', 'integer', $usable('expense', $expense?->account_id)],
            'cash_account_id' => ['required', 'integer', $usable('cash', $expense?->cash_account_id)],
            'penerima' => ['nullable', 'string', 'max:120'],
            'uraian' => ['required', 'string', 'max:255'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:999999999999'],
        ], [
            'tanggal.required' => 'Tanggal pengeluaran wajib diisi.',
            'account_id.required' => 'Pilih kode akun beban.',
            'account_id.exists' => 'Pilih kode akun beban yang aktif (awalan 5-, 6-, atau 8-).',
            'cash_account_id.required' => 'Pilih sumber dana (kas atau bank).',
            'cash_account_id.exists' => 'Pilih sumber dana kas/bank yang aktif (awalan 1-).',
            'uraian.required' => 'Uraian pengeluaran wajib diisi.',
            'jumlah.required' => 'Jumlah pengeluaran wajib diisi.',
            'jumlah.integer' => 'Jumlah diisi angka tanpa titik atau koma.',
            'jumlah.min' => 'Jumlah pengeluaran minimal Rp1.',
        ]);
    }
}
