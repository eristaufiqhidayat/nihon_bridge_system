<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Role;
use App\Support\Navigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Data Role (admin): peran pengguna beserta menu yang boleh diakses.
 * Empat peran bawaan tidak bisa dihapus dan menunya tetap; peran yang masih dipakai pengguna tidak bisa dihapus.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        $list = Role::withCount('users')->orderByDesc('is_system')->orderBy('id')->get();

        return view('admin.role', ['list' => $list]);
    }

    public function create(): View
    {
        return $this->form(new Role(['menus' => []]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $role = Role::create([...$data, 'key' => Role::keyFor($data['name'])]);
        Activity::log('🔐', 'ic-bg-purple', "Peran {$role->name} ditambahkan dengan " . count($role->menus) . ' menu');

        return redirect()->route('role-admin.index')->with('toast', "Peran {$role->name} ditambahkan");
    }

    public function edit(Role $role): View
    {
        return $this->form($role->loadCount('users'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $this->validated($request, $role);
        $role->update($role->is_system ? collect($data)->except('menus')->all() : $data);

        return redirect()->route('role-admin.edit', $role)->with('toast', "Peran {$role->name} disimpan");
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return back()->withErrors(['hapus' => "{$role->name} adalah peran bawaan sistem dan tidak bisa dihapus."]);
        }
        $n = $role->users()->count();
        if ($n) {
            return back()->withErrors([
                'hapus' => "Peran {$role->name} tidak bisa dihapus karena masih dipakai {$n} pengguna. Ganti peran pengguna tersebut di Dashboard Admin lebih dulu.",
            ]);
        }

        $role->delete();
        Activity::log('🗑️', 'ic-bg-grey', "Peran {$role->name} dihapus");

        return redirect()->route('role-admin.index')->with('toast', "Peran {$role->name} dihapus");
    }

    private function form(Role $role): View
    {
        return view('admin.role-form', [
            'role' => $role,
            'options' => Navigation::options(),
            'picked' => old('menus', $role->menus ?? []),
        ]);
    }

    private function validated(Request $request, ?Role $role = null): array
    {
        $menuKeys = array_column(Navigation::options(), 0);

        return $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('roles')->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'menus' => [$role?->is_system ? 'nullable' : 'required', 'array'],
            'menus.*' => [Rule::in($menuKeys)],
        ], [
            'name.required' => 'Nama peran wajib diisi, mis. Staf Keuangan.',
            'name.unique' => 'Nama peran sudah dipakai.',
            'menus.required' => 'Pilih minimal satu menu untuk peran ini.',
            'menus.*.in' => 'Menu yang dipilih tidak tersedia.',
        ]) + ['menus' => []];
    }
}
