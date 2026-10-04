<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Catalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /** Akun contoh untuk tombol "masuk cepat" (hanya bila demo_login aktif). */
    public const DEMO = [
        'peserta' => 'ahmad.fauzi@nihonbridge.id',
        'instruktur' => 'sato.sensei@nihonbridge.id',
        'admin' => 'rina.info@nihonbridge.id',
        'direktur' => 'direktur@nihonbridge.id',
    ];

    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $cred = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Isi email/username dan password.',
            'password.required' => 'Isi email/username dan password.',
        ]);

        $field = str_contains($cred['email'], '@') ? 'email' : 'name';
        if (! Auth::attempt([$field => $cred['email'], 'password' => $cred['password'], 'is_active' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Email atau password salah. Coba lagi atau pilih “Lupa password?”.']);
        }
        $request->session()->regenerate();

        return $this->welcome($request->user());
    }

    public function demo(Request $request, string $role): RedirectResponse
    {
        abort_unless(config('nihonbridge.demo_login') && isset(self::DEMO[$role]), 404);
        $user = User::where('email', self::DEMO[$role])->firstOrFail();
        Auth::login($user);
        $request->session()->regenerate();

        return $this->welcome($user);
    }

    private function welcome(User $user): RedirectResponse
    {
        return redirect()->intended(route(Catalog::HOME[$user->role]))->with('toast', "Selamat datang, {$user->name}");
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('toast', 'Anda telah keluar');
    }
}
