<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Catalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfilController extends Controller
{
    private const BIODATA = [
        'birth_place', 'birth_date', 'gender', 'height_cm', 'religion', 'marital_status', 'passport_no',
        'address_ktp', 'address_domicile', 'guardian_contact',
    ];

    public function show(Request $request): View
    {
        return view('shared.profil', ['user' => $request->user()->load('student.classroom.wali', 'student.batch.program')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(array_keys(Catalog::GENDERS))],
            'height_cm' => ['nullable', 'integer', 'between:100,230'],
            'religion' => ['nullable', Rule::in(Catalog::RELIGIONS)],
            'marital_status' => ['nullable', Rule::in(array_keys(Catalog::MARITAL))],
            'passport_no' => ['nullable', 'string', 'max:20'],
            'address_ktp' => ['nullable', 'string', 'max:255'],
            'address_domicile' => ['nullable', 'string', 'max:255'],
            'guardian_contact' => ['nullable', 'string', 'max:120'],
        ], [
            'name.required' => 'Nama tidak boleh kosong',
            'birth_date.before' => 'Tanggal lahir harus sebelum hari ini.',
            'height_cm.between' => 'Tinggi badan diisi dalam cm (100–230).',
        ]);
        $user->update(collect($data)->only('name', 'email', 'phone')->all());
        if ($user->student) {
            $user->student->update(collect($data)->only(self::BIODATA)->all());
        }

        return back()->with('toast', 'Profil disimpan');
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validateWithBag('pw', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Isi password saat ini.',
            'current_password.current_password' => 'Password saat ini salah.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);
        $request->user()->update(['password' => Hash::make($request->input('password'))]);

        return back()->with('toast', 'Password diperbarui');
    }

    public function preferences(Request $request): JsonResponse
    {
        $data = $request->validate(['key' => ['required', Rule::in(array_keys(User::DEFAULT_PREFS))], 'on' => ['required', 'boolean']]);
        $user = $request->user();
        $user->update(['notification_prefs' => array_merge(User::DEFAULT_PREFS, $user->notification_prefs ?? [], [$data['key'] => (bool) $data['on']])]);

        return response()->json(['ok' => true]);
    }

    public function photo(Request $request): RedirectResponse
    {
        $request->validate(['foto' => ['required', 'image', 'max:2048']], ['foto.max' => 'Ukuran foto maksimal 2 MB.']);
        $user = $request->user();
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }
        $user->update(['avatar_path' => $request->file('foto')->store('avatar', 'public')]);

        return back()->with('toast', 'Foto profil diperbarui');
    }
}
