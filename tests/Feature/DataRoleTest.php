<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Data Role (admin): CRUD peran, akses menu peran tambahan, dan ganti peran pengguna.
 */
class DataRoleTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function admin(): User
    {
        $u = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($u);

        return $u;
    }

    private function staff(string $role): User
    {
        return User::create(['name' => 'Rina Staf', 'email' => 'rina@nihonbridge.id', 'password' => 'rahasia123', 'role' => $role]);
    }

    public function test_hanya_admin_dan_daftar_role_tampil(): void
    {
        $this->actingAs(User::where('role', 'direktur')->firstOrFail())->get('/data-role')->assertForbidden();

        $this->admin();
        $this->get('/data-role')->assertOk()
            ->assertSee('Data Role')->assertSee('Peserta')->assertSee('Instruktur')->assertSee('Direktur')->assertSee('Bawaan sistem');
        $this->get('/admin')->assertSee(route('role-admin.index'));
    }

    public function test_tambah_edit_dan_hapus_role(): void
    {
        $this->admin();
        $this->get('/data-role/baru')->assertOk()->assertSee('Keuangan');

        $this->post('/data-role', ['name' => 'Staf Keuangan', 'description' => 'Urus pembayaran', 'menus' => ['keuangan.index', 'pembayaran-admin.index']])
            ->assertRedirect(route('role-admin.index'));
        $role = Role::where('key', 'staf-keuangan')->firstOrFail();
        $this->assertSame(['keuangan.index', 'pembayaran-admin.index'], $role->menus);
        $this->assertFalse($role->is_system);

        $this->put("/data-role/{$role->id}", ['name' => 'Staf Keuangan', 'menus' => ['keuangan.index']])->assertRedirect(route('role-admin.edit', $role));
        $this->assertSame(['keuangan.index'], $role->fresh()->menus);

        $this->delete("/data-role/{$role->id}")->assertRedirect(route('role-admin.index'));
        $this->assertModelMissing($role);
    }

    public function test_validasi_role(): void
    {
        $this->admin();
        $this->post('/data-role', ['name' => '', 'menus' => []])->assertSessionHasErrors(['name', 'menus']);
        $this->post('/data-role', ['name' => 'Admin', 'menus' => ['keuangan.index']])->assertSessionHasErrors('name');
        $this->post('/data-role', ['name' => 'Iseng', 'menus' => ['dashboard']])->assertSessionHasErrors('menus.0');
    }

    public function test_role_bawaan_tidak_bisa_dihapus_tapi_menunya_bisa_diatur(): void
    {
        $this->admin();
        $admin = Role::where('key', 'admin')->firstOrFail();
        $this->delete("/data-role/{$admin->id}")->assertSessionHasErrors('hapus');
        $this->assertModelExists($admin);

        // Form edit role bawaan menampilkan centang menu.
        $instruktur = Role::where('key', 'instruktur')->firstOrFail();
        $this->get("/data-role/{$instruktur->id}/edit")->assertOk()
            ->assertSee('name="menus[]" value="kehadiran.index"', false)
            ->assertSee('name="menus[]" value="keuangan.index"', false);

        // Instruktur: lepas Bank Soal, tambah Keuangan.
        $keep = array_values(array_diff(array_column($instruktur->defaultItems(), 0), ['banksoal.index']));
        $this->put("/data-role/{$instruktur->id}", ['name' => 'Instruktur', 'menus' => [...$keep, 'keuangan.index']])->assertSessionHasNoErrors();

        $this->actingAs(User::where('role', 'instruktur')->firstOrFail());
        $this->get('/keuangan')->assertOk()->assertDontSee(route('banksoal.index'));
        $this->get('/bank-soal')->assertForbidden();
        $this->get('/kehadiran')->assertOk();
    }

    public function test_admin_tidak_bisa_melepas_dashboard_data_pengguna_dan_data_role(): void
    {
        $this->admin();
        $admin = Role::where('key', 'admin')->firstOrFail();
        $this->get("/data-role/{$admin->id}/edit")->assertOk()->assertSee('(wajib)');

        $this->put("/data-role/{$admin->id}", ['name' => 'Administrator', 'menus' => ['keuangan.index']])->assertSessionHasNoErrors();
        $admin->refresh();
        $this->assertSame('Administrator', $admin->name);
        $this->assertEqualsCanonicalizing(['admin.dashboard', 'pengguna-admin.index', 'role-admin.index', 'keuangan.index'], $admin->menus);

        $this->actingAs(User::where('role', 'admin')->firstOrFail()); // muat ulang peran
        $this->get('/admin')->assertOk()->assertDontSee(route('angkatan.index'));
        $this->get('/data-role')->assertOk();
        $this->get('/keuangan')->assertOk();
        $this->get('/data-angkatan')->assertForbidden();
    }

    public function test_role_dipakai_pengguna_tidak_bisa_dihapus(): void
    {
        $this->admin();
        $role = Role::create(['key' => 'staf', 'name' => 'Staf', 'menus' => ['laporan.index']]);
        $this->staff('staf');

        $this->delete("/data-role/{$role->id}")->assertSessionHasErrors(['hapus' => 'Peran Staf tidak bisa dihapus karena masih dipakai 1 pengguna. Ganti peran pengguna tersebut di Data Pengguna lebih dulu.']);
        $this->assertModelExists($role);
    }

    public function test_pengguna_role_tambahan_hanya_bisa_buka_menu_terpilih(): void
    {
        Role::create(['key' => 'staf-keuangan', 'name' => 'Staf Keuangan', 'menus' => ['keuangan.index', 'pembayaran-admin.index']]);
        $u = $this->staff('staf-keuangan');
        $this->actingAs($u);

        $this->get('/')->assertRedirect(route('pembayaran-admin.index'));
        $this->get('/keuangan')->assertOk()->assertSee('Pembayaran Peserta')->assertSee('Pesan')->assertDontSee(route('angkatan.index'));
        $this->get('/data-pembayaran')->assertOk();
        $this->get('/pesan')->assertOk();
        $this->get('/profil')->assertOk()->assertSee('Staf Keuangan');

        $this->get('/admin')->assertForbidden();
        $this->get('/data-peserta')->assertForbidden();
        $this->get('/data-role')->assertForbidden();
        $this->get('/dashboard')->assertForbidden();
    }

    public function test_admin_tambah_dan_ganti_peran_pengguna(): void
    {
        $me = $this->admin();
        Role::create(['key' => 'staf', 'name' => 'Staf', 'menus' => ['laporan.index']]);

        $this->post('/admin/pengguna', ['name' => 'Budi Staf', 'email' => 'budi@nihonbridge.id', 'role' => 'staf'])->assertSessionHasNoErrors();
        $budi = User::where('email', 'budi@nihonbridge.id')->firstOrFail();
        $this->assertSame('staf', $budi->role);
        $this->post('/admin/pengguna', ['name' => 'X', 'email' => 'x@nihonbridge.id', 'role' => 'tidak-ada'])->assertSessionHasErrors('role');

        $this->patch("/admin/pengguna/{$budi->id}/peran", ['role' => 'instruktur'])->assertSessionHasNoErrors();
        $this->assertSame('instruktur', $budi->fresh()->role);

        $this->patch("/admin/pengguna/{$budi->id}/peran", ['role' => 'peserta'])->assertSessionHasErrors('role');
        $peserta = User::where('role', 'peserta')->firstOrFail();
        $this->patch("/admin/pengguna/{$peserta->id}/peran", ['role' => 'admin'])->assertStatus(422);
        $this->patch("/admin/pengguna/{$me->id}/peran", ['role' => 'staf'])->assertStatus(422);
        $this->assertSame('admin', $me->fresh()->role);
    }
}
