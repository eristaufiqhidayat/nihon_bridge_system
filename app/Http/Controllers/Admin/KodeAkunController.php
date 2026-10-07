<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Kode Akun (admin): master bagan akun untuk modul Pengeluaran.
 * Akun yang sudah dipakai transaksi tidak bisa dihapus (nonaktifkan saja) dan kelompoknya tidak bisa diganti.
 */
class KodeAkunController extends Controller
{
    public function index(Request $request): View
    {
        $f = [
            'q' => trim((string) $request->query('q', '')),
            'kelompok' => array_key_exists((string) $request->query('kelompok'), Account::GROUPS) ? $request->query('kelompok') : null,
        ];

        $list = Account::withCount(['expenses', 'cashExpenses'])
            ->withSum('expenses', 'jumlah')
            ->withSum('cashExpenses', 'jumlah')
            ->when($f['q'] !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('kode', 'like', "%{$f['q']}%")->orWhere('nama', 'like', "%{$f['q']}%")))
            ->when($f['kelompok'], fn ($q, $k) => $q->where('kode', 'like', "{$k}-%"))
            ->orderBy('kode')
            ->get();

        return view('admin.kode-akun', [
            'groups' => $list->groupBy(fn ($a) => substr($a->kode, 0, 1)),
            'f' => $f,
            'total' => Account::count(),
        ]);
    }

    public function create(Request $request): View
    {
        $prefix = array_key_exists((string) $request->query('kelompok'), Account::GROUPS) ? $request->query('kelompok') : '6';

        return $this->form(new Account(['is_active' => true, 'kode' => "{$prefix}-"]));
    }

    public function store(Request $request): RedirectResponse
    {
        $account = Account::create($this->validated($request));
        Activity::log('📒', 'ic-bg-blue', "Kode akun {$account->label} ditambahkan");

        return redirect()->route('kode-akun.index')->with('toast', "Akun {$account->label} ditambahkan");
    }

    public function edit(Account $account): View
    {
        return $this->form($account);
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        $data = $this->validated($request, $account);
        if ($account->deleteBlockers() && substr($data['kode'], 0, 1) !== substr($account->kode, 0, 1)) {
            throw ValidationException::withMessages([
                'kode' => 'Akun ini sudah dipakai transaksi, jadi kelompoknya tidak bisa diganti. Ubah kode dalam kelompok yang sama (awalan ' . substr($account->kode, 0, 1) . '-).',
            ]);
        }
        $account->update($data);

        return redirect()->route('kode-akun.edit', $account)->with('toast', "Akun {$account->label} disimpan");
    }

    public function destroy(Account $account): RedirectResponse
    {
        $blockers = $account->deleteBlockers();
        if ($blockers) {
            $rincian = collect($blockers)->map(fn ($n, $label) => "{$n} {$label}")->join(', ', ' dan ');

            return back()->withErrors([
                'hapus' => "Akun {$account->label} tidak bisa dihapus karena masih dipakai {$rincian}. "
                    . 'Nonaktifkan akunnya agar tidak bisa dipilih lagi, atau pindahkan transaksinya ke akun lain lebih dulu.',
            ]);
        }

        $account->delete();
        Activity::log('🗑️', 'ic-bg-grey', "Kode akun {$account->label} dihapus");

        return redirect()->route('kode-akun.index')->with('toast', "Akun {$account->label} dihapus");
    }

    private function form(Account $account): View
    {
        return view('admin.kode-akun-form', [
            'account' => $account,
            'blockers' => $account->exists ? $account->deleteBlockers() : [],
        ]);
    }

    private function validated(Request $request, ?Account $account = null): array
    {
        $request->merge(['kode' => strtoupper(trim((string) $request->input('kode')))]);
        $data = $request->validate([
            'kode' => ['required', 'regex:' . Account::KODE_REGEX, Rule::unique('accounts')->ignore($account?->id)],
            'nama' => ['required', 'string', 'max:120'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ], [
            'kode.required' => 'Kode akun wajib diisi.',
            'kode.regex' => 'Format kode: 1-11xx / 1-12xx untuk Kas & Bank, atau 5-xxxx, 6-xxxx, 8-xxxx untuk akun beban (mis. 6-1301).',
            'kode.unique' => 'Kode akun sudah dipakai akun lain.',
            'nama.required' => 'Nama akun wajib diisi.',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
