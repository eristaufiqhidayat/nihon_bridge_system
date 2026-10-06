<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Data Pengguna (admin): CRUD akun non-peserta, reset password, dan perlindungan akun sendiri.
 */
class DataPenggunaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function admin(): User
    {
        $u = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($u);

        return $u;
    }

    private function staff(array $attrs = []): User
    {
        return User::create([
            'name' => 'Rina Staf', 'email' => 'rina@nihonbridge.id', 'password' => 'rahasia123', 'role' => 'instruktur', ...$attrs,
        ]);
    }

    public function test_hanya_admin_dan_daftar_tanpa_peserta(): void
    {
        $this->actingAs(User::where('role', 'direktur')->firstOrFail())->get('/data-pengguna')->assertForbidden();

        $this->admin();
        $peserta = User::where('role', 'peserta')->firstOrFail();
        $instruktur = User::where('role', 'instruktur')->firstOrFail();
        $this->get('/data-pengguna')->assertOk()
            ->assertSee('Data Pengguna')->assertSee($instruktur->email)->assertDontSee($peserta->email)
            ->assertSee(route('peserta-admin.index'));
        $this->get('/admin')->assertSee(route('pengguna-admin.index'));

        $this->get('/data-pengguna?role=direktur')->assertOk()->assertDontSee($instruktur->email);
        $this->get('/data-pengguna?q=' . urlencode($instruktur->name))->assertOk()->assertSee($instruktur->email);
    }

    public function test_tambah_edit_dan_hapus_pengguna(): void
    {
        $this->admin();
        $this->get('/data-pengguna/baru')->assertOk();

        $this->post('/data-pengguna', ['name' => 'Rina Staf', 'email' => 'rina@nihonbridge.id', 'role' => 'direktur', 'is_active' => '1'])
            ->assertRedirect(route('pengguna-admin.index'));
        $user = User::where('email', 'rina@nihonbridge.id')->firstOrFail();
        $this->assertSame('direktur', $user->role);
        $this->assertTrue($user->is_active);

        $this->get("/data-pengguna/{$user->id}/edit")->assertOk()->assertSee('Rina Staf');
        $this->put("/data-pengguna/{$user->id}", ['name' => 'Rina Saputri', 'email' => 'rina.s@nihonbridge.id', 'phone' => '0812', 'role' => 'instruktur', 'is_active' => '0'])
            ->assertRedirect(route('pengguna-admin.edit', $user));
        $user->refresh();
        $this->assertSame(['Rina Saputri', 'rina.s@nihonbridge.id', '0812', 'instruktur', false], [$user->name, $user->email, $user->phone, $user->role, $user->is_active]);

        $this->delete("/data-pengguna/{$user->id}")->assertRedirect(route('pengguna-admin.index'));
        $this->assertModelMissing($user);
    }

    public function test_validasi_pengguna(): void
    {
        $this->admin();
        $this->post('/data-pengguna', ['name' => '', 'email' => 'bukan-email', 'role' => 'admin'])->assertSessionHasErrors(['name', 'email']);
        $this->post('/data-pengguna', ['name' => 'X', 'email' => 'x@nihonbridge.id', 'role' => 'peserta'])->assertSessionHasErrors('role');
        $existing = User::where('role', 'instruktur')->firstOrFail();
        $this->post('/data-pengguna', ['name' => 'X', 'email' => $existing->email, 'role' => 'admin'])->assertSessionHasErrors('email');
    }

    public function test_reset_password_wajib_ganti_saat_login(): void
    {
        $this->admin();
        $user = $this->staff();

        $res = $this->post("/data-pengguna/{$user->id}/reset-password")->assertRedirect(route('pengguna-admin.edit', $user));
        $temp = session('temp_password');
        $this->assertNotEmpty($temp);
        $user->refresh();
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check($temp, $user->password));
        $this->followRedirects($res)->assertSee($temp);

        $this->actingAs($user)->get('/kelas')->assertRedirect(route('password.first'));
    }

    public function test_admin_tidak_bisa_hapus_nonaktifkan_atau_ganti_peran_sendiri(): void
    {
        $admin = $this->admin();
        $this->delete("/data-pengguna/{$admin->id}")->assertSessionHasErrors('hapus');
        $this->put("/data-pengguna/{$admin->id}", ['name' => $admin->name, 'email' => $admin->email, 'role' => 'direktur', 'is_active' => '1'])->assertSessionHasErrors('role');
        $this->put("/data-pengguna/{$admin->id}", ['name' => $admin->name, 'email' => $admin->email, 'role' => 'admin', 'is_active' => '0'])->assertSessionHasErrors('role');
        $this->post("/data-pengguna/{$admin->id}/reset-password")->assertStatus(422);
        $this->assertSame(['admin', true], [$admin->fresh()->role, $admin->fresh()->is_active]);

        // Nama dan email sendiri tetap bisa diubah.
        $this->put("/data-pengguna/{$admin->id}", ['name' => 'Admin Baru', 'email' => $admin->email, 'role' => 'admin', 'is_active' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('Admin Baru', $admin->fresh()->name);
    }

    public function test_hapus_ditolak_bila_masih_ada_data_terkait(): void
    {
        $this->admin();
        $user = $this->staff();
        Classroom::query()->firstOrFail()->update(['wali_id' => $user->id]);

        $this->get("/data-pengguna/{$user->id}/edit")->assertSee('kelas sebagai wali');
        $this->delete("/data-pengguna/{$user->id}")->assertSessionHasErrors('hapus');
        $this->assertStringContainsString('1 kelas sebagai wali', session('errors')->first('hapus'));
        $this->assertModelExists($user);
    }

    public function test_akun_peserta_diarahkan_ke_data_peserta(): void
    {
        $this->admin();
        $peserta = User::where('role', 'peserta')->whereHas('student')->firstOrFail();

        $this->get("/data-pengguna/{$peserta->id}/edit")->assertRedirect(route('peserta-admin.edit', $peserta->student));
        $this->delete("/data-pengguna/{$peserta->id}")->assertRedirect(route('peserta-admin.edit', $peserta->student));
        $this->assertModelExists($peserta);
    }
}
