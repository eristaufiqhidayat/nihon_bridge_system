<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function request(Request $request): View
    {
        return view('auth.forgot', ['email' => $request->query('email', '')]);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']], [
            'email.required' => 'Masukkan email yang terdaftar.',
            'email.email' => 'Masukkan email yang terdaftar.',
        ]);

        // Pesan sama untuk email terdaftar maupun tidak, agar tidak membocorkan akun.
        Password::sendResetLink($data);

        return redirect()->route('password.sent')->with('reset_email', $data['email']);
    }

    public function sent(Request $request): View|RedirectResponse
    {
        $email = session('reset_email');
        if (! $email) {
            return redirect()->route('password.request');
        }
        session()->keep('reset_email');

        // Di lingkungan lokal (mail driver "log") tampilkan tautan agar alur bisa dicoba tanpa kotak masuk.
        $devLink = null;
        if (app()->isLocal() && config('mail.default') === 'log' && ($user = User::where('email', $email)->first())) {
            $devLink = route('password.reset', ['token' => Password::createToken($user), 'email' => $email]);
        }

        return view('auth.sent', compact('email', 'devLink'));
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8', 'regex:/[A-Za-z]/', 'regex:/\d/', 'confirmed'],
        ], [
            'password.min' => 'Password minimal 8 karakter.',
            'password.regex' => 'Gunakan campuran huruf dan angka.',
            'password.confirmed' => 'Kedua password belum sama.',
        ]);

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['password' => 'Tautan reset sudah kedaluwarsa atau tidak valid. Minta tautan baru.']);
        }

        return redirect()->route('password.done');
    }

    public function done(): View
    {
        return view('auth.done');
    }
}
