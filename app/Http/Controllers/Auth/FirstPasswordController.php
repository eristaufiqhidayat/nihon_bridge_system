<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Ganti password wajib saat login pertama. */
class FirstPasswordController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route($request->user()->homeRoute());
        }

        return view('auth.first-password', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->validate([
            'password' => ['required', 'min:8', 'regex:/[A-Za-z]/', 'regex:/\d/', 'confirmed'],
        ], [
            'password.required' => 'Isi password baru.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.regex' => 'Gunakan campuran huruf dan angka.',
            'password.confirmed' => 'Kedua password belum sama.',
        ]);
        if (Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['password' => 'Password baru tidak boleh sama dengan password awal.']);
        }
        $user->update(['password' => Hash::make($request->input('password')), 'must_change_password' => false]);
        $request->session()->regenerate();

        return redirect()->route($user->homeRoute())->with('toast', 'Password baru disimpan. Selamat datang, ' . $user->name);
    }
}
