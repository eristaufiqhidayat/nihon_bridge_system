<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Data Pengguna (admin): akun non-peserta (admin, instruktur, direktur, peran tambahan).
 * Peserta dikelola lewat Data Peserta karena terikat biodata siswa.
 * Admin tidak bisa menghapus, menonaktifkan, atau mengganti peran akunnya sendiri.
 */
class PenggunaController extends Controller
{
    public function index(Request $request): View
    {
        $roles = $this->roles();
        $f = [
            'q' => trim((string) $request->query('q', '')),
            'role' => $roles->contains('key', $request->query('role')) ? $request->query('role') : null,
            'status' => in_array($request->query('status'), ['aktif', 'nonaktif'], true) ? $request->query('status') : null,
        ];

        $list = User::with('roleInfo', 'waliClasses')
            ->where('role', '!=', 'peserta')
            ->when($f['q'] !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$f['q']}%")->orWhere('email', 'like', "%{$f['q']}%")))
            ->when($f['role'], fn ($q, $r) => $q->where('role', $r))
            ->when($f['status'], fn ($q, $st) => $q->where('is_active', $st === 'aktif'))
            ->orderBy('name')
            ->paginate(25)->withQueryString();

        return view('admin.pengguna', [
            'list' => $list,
            'f' => $f,
            'roles' => $roles,
            'total' => User::where('role', '!=', 'peserta')->count(),
            'peserta' => User::where('role', 'peserta')->count(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new User(['is_active' => true, 'role' => 'instruktur']));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $user = User::create([...$data, 'password' => Hash::make(Str::random(32))]);
        Activity::log('👤', 'ic-bg-blue', "{$user->name} ditambahkan sebagai {$user->role_label}");
        Password::broker()->sendResetLink(['email' => $user->email]);

        return redirect()->route('pengguna-admin.index')->with('toast', "{$user->name} ditambahkan. Tautan aktivasi dikirim ke email.");
    }

    public function edit(User $user): View|RedirectResponse
    {
        if ($user->role === 'peserta') {
            return $this->toPeserta($user);
        }

        return $this->form($user);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($user->role === 'peserta') {
            return $this->toPeserta($user);
        }
        $data = $this->validated($request, $user);
        if ($user->id === $request->user()->id && ($data['role'] !== $user->role || ! $data['is_active'])) {
            throw ValidationException::withMessages(['role' => 'Tidak bisa mengganti peran atau menonaktifkan akun sendiri.']);
        }
        $user->update($data);

        return redirect()->route('pengguna-admin.edit', $user)->with('toast', "Data {$user->name} disimpan");
    }

    /** Buat password sementara; pengguna wajib menggantinya saat login berikutnya. */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        if ($user->role === 'peserta') {
            return $this->toPeserta($user);
        }
        abort_if($user->id === $request->user()->id, 422, 'Ganti password akun sendiri lewat menu Profil.');

        $temp = Str::password(10, symbols: false);
        $user->forceFill(['password' => Hash::make($temp), 'must_change_password' => true, 'remember_token' => Str::random(60)])->save();
        Activity::log('🔑', 'ic-bg-orange', "Password {$user->name} direset oleh admin");

        return redirect()->route('pengguna-admin.edit', $user)
            ->with('temp_password', $temp)
            ->with('toast', "Password {$user->name} direset");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->role === 'peserta') {
            return $this->toPeserta($user);
        }
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['hapus' => 'Tidak bisa menghapus akun sendiri.']);
        }
        $blockers = $user->deleteBlockers();
        if ($blockers) {
            $rincian = collect($blockers)->map(fn ($n, $label) => "{$n} {$label}")->join(', ', ' dan ');

            return back()->withErrors([
                'hapus' => "{$user->name} tidak bisa dihapus karena masih terhubung dengan data lain: {$rincian}. "
                    . 'Pindahkan data tersebut ke pengguna lain lebih dulu, atau nonaktifkan akunnya.',
            ]);
        }

        $user->delete();
        Activity::log('🗑️', 'ic-bg-grey', "Akun {$user->name} dihapus");

        return redirect()->route('pengguna-admin.index')->with('toast', "Akun {$user->name} dihapus");
    }

    private function toPeserta(User $user): RedirectResponse
    {
        $student = $user->student;

        return $student
            ? redirect()->route('peserta-admin.edit', $student)->with('toast', 'Akun peserta dikelola lewat Data Peserta')
            : redirect()->route('peserta-admin.index')->with('toast', 'Akun peserta dikelola lewat Data Peserta');
    }

    /** Peran yang bisa dipilih: semua kecuali Peserta. */
    private function roles()
    {
        return Role::where('key', '!=', 'peserta')->orderByDesc('is_system')->orderBy('id')->get();
    }

    private function form(User $user): View
    {
        return view('admin.pengguna-form', [
            'user' => $user,
            'roles' => $this->roles(),
            'self' => $user->exists && $user->id === auth()->id(),
            'blockers' => $user->exists ? $user->deleteBlockers() : [],
        ]);
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::exists('roles', 'key')->whereNot('key', 'peserta')],
            'is_active' => ['boolean'],
        ], [
            'name.required' => 'Nama pengguna wajib diisi.',
            'email.required' => 'Email wajib diisi sebagai alamat login.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah dipakai akun lain.',
            'role.required' => 'Pilih peran pengguna.',
            'role.exists' => 'Pilih peran yang tersedia. Peserta ditambahkan lewat Data Peserta.',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
