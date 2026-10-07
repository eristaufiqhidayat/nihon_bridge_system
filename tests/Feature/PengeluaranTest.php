<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Modul Pengeluaran (admin): master Kode Akun dan transaksi pengeluaran.
 */
class PengeluaranTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function admin(): User
    {
        $u = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($u);

        return $u;
    }

    private function akun(string $kode): Account
    {
        return Account::where('kode', $kode)->firstOrFail();
    }

    public function test_hanya_admin_dan_halaman_tampil_dengan_sample_data(): void
    {
        $this->actingAs(User::where('role', 'direktur')->first());
        $this->get(route('pengeluaran.index'))->assertForbidden();
        $this->get(route('kode-akun.index'))->assertForbidden();

        $this->admin();
        $this->get(route('kode-akun.index'))->assertOk()
            ->assertSee('6-1301')->assertSee('Beban Listrik')->assertSee('Kas &amp; Bank', false)->assertSee('Beban Lain-lain');
        $this->get(route('kode-akun.create'))->assertOk();
        $this->get(route('kode-akun.edit', $this->akun('6-1301')))->assertOk()->assertSee('belum bisa dihapus');
        $this->get(route('pengeluaran.index'))->assertOk()->assertSee('BKK/2026/10/0001')->assertSee('Rekap per kode akun');
        $this->get(route('pengeluaran.index', ['bulan' => '2026-09', 'akun' => $this->akun('6-1301')->id]))->assertOk()
            ->assertSee('Tagihan listrik Agustus 2026')->assertDontSee('Tagihan listrik September 2026')->assertSee('Rp2.480.000');
        $this->get(route('pengeluaran.create'))->assertOk()->assertSee('Beban Operasional');
        $this->get(route('pengeluaran.edit', Expense::first()))->assertOk();

        $this->assertGreaterThan(20, Account::count());
        $this->assertGreaterThan(20, Expense::count());
    }

    public function test_crud_kode_akun_dan_validasi(): void
    {
        $this->admin();
        $this->post(route('kode-akun.store'), ['kode' => '6-2001', 'nama' => 'Beban Asuransi', 'is_active' => 1])
            ->assertRedirect(route('kode-akun.index'));
        $a = $this->akun('6-2001');
        $this->assertSame('beban_operasional', $a->kelompok);

        $this->put(route('kode-akun.update', $a), ['kode' => '8-2001', 'nama' => 'Beban Asuransi Kendaraan'])
            ->assertRedirect(route('kode-akun.edit', $a));
        $a->refresh();
        $this->assertSame('Beban Lain-lain', $a->kelompok_label);
        $this->assertFalse($a->is_active);

        $this->post(route('kode-akun.store'), ['kode' => '4-1101', 'nama' => 'Pendapatan'])->assertSessionHasErrors('kode');
        $this->post(route('kode-akun.store'), ['kode' => '1-1301', 'nama' => 'Piutang'])->assertSessionHasErrors('kode');
        $this->post(route('kode-akun.store'), ['kode' => '6-1301', 'nama' => 'Dobel'])->assertSessionHasErrors('kode');
        $this->post(route('kode-akun.store'), ['kode' => '6-9999', 'nama' => ''])->assertSessionHasErrors('nama');

        $this->delete(route('kode-akun.destroy', $a))->assertRedirect(route('kode-akun.index'));
        $this->assertModelMissing($a);
    }

    public function test_akun_terpakai_tidak_bisa_dihapus_atau_pindah_kelompok(): void
    {
        $this->admin();
        $listrik = $this->akun('6-1301');
        $this->from(route('kode-akun.index'))->delete(route('kode-akun.destroy', $listrik))
            ->assertRedirect(route('kode-akun.index'))
            ->assertSessionHasErrors(['hapus' => 'Akun 6-1301 · Beban Listrik tidak bisa dihapus karena masih dipakai 3 transaksi pengeluaran (akun beban). Nonaktifkan akunnya agar tidak bisa dipilih lagi, atau pindahkan transaksinya ke akun lain lebih dulu.']);
        $this->assertModelExists($listrik);

        $bca = $this->akun('1-1201');
        $this->delete(route('kode-akun.destroy', $bca))->assertSessionHasErrors('hapus');
        $this->assertStringContainsString('sumber dana', session('errors')->first('hapus'));

        $this->put(route('kode-akun.update', $listrik), ['kode' => '8-1301', 'nama' => 'Beban Listrik', 'is_active' => 1])->assertSessionHasErrors('kode');
        $this->put(route('kode-akun.update', $listrik), ['kode' => '6-1311', 'nama' => 'Beban Listrik PLN', 'is_active' => 1])->assertSessionHasNoErrors();
    }

    public function test_crud_pengeluaran_dengan_nomor_otomatis(): void
    {
        $admin = $this->admin();
        $payload = [
            'tanggal' => '2026-10-07', 'account_id' => $this->akun('6-1401')->id, 'cash_account_id' => $this->akun('1-1101')->id,
            'penerima' => 'Toko ATK Sinar', 'uraian' => 'Map dan amplop', 'jumlah' => 150000,
        ];
        $this->post(route('pengeluaran.store'), $payload)->assertRedirect(route('pengeluaran.index'));
        $e = Expense::where('uraian', 'Map dan amplop')->firstOrFail();
        $this->assertSame('BKK/2026/10/0006', $e->nomor);
        $this->assertSame($admin->id, $e->user_id);

        $this->post(route('pengeluaran.store'), [...$payload, 'tanggal' => '2026-11-02'])->assertRedirect();
        $this->assertTrue(Expense::where('nomor', 'BKK/2026/11/0001')->exists());

        $this->put(route('pengeluaran.update', $e), [...$payload, 'jumlah' => 175000])->assertRedirect(route('pengeluaran.edit', $e));
        $this->assertSame(175000, $e->fresh()->jumlah);
        $this->assertSame('BKK/2026/10/0006', $e->fresh()->nomor);

        $this->delete(route('pengeluaran.destroy', $e))->assertRedirect(route('pengeluaran.index'));
        $this->assertModelMissing($e);
    }

    public function test_validasi_pengeluaran_akun_beban_kas_dan_nonaktif(): void
    {
        $this->admin();
        $base = ['tanggal' => '2026-10-07', 'uraian' => 'Uji', 'jumlah' => 1000];
        $beban = $this->akun('6-1401')->id;
        $kas = $this->akun('1-1101')->id;

        // Akun beban tidak boleh kas, sumber dana tidak boleh akun beban.
        $this->post(route('pengeluaran.store'), [...$base, 'account_id' => $kas, 'cash_account_id' => $kas])->assertSessionHasErrors('account_id');
        $this->post(route('pengeluaran.store'), [...$base, 'account_id' => $beban, 'cash_account_id' => $beban])->assertSessionHasErrors('cash_account_id');
        $this->post(route('pengeluaran.store'), [...$base, 'account_id' => $beban, 'cash_account_id' => $kas, 'jumlah' => 0])->assertSessionHasErrors('jumlah');
        $this->post(route('pengeluaran.store'), ['account_id' => $beban, 'cash_account_id' => $kas])->assertSessionHasErrors(['tanggal', 'uraian', 'jumlah']);

        // Akun nonaktif tidak bisa dipakai transaksi baru, tetapi transaksi lama tetap bisa diedit.
        $listrik = $this->akun('6-1301');
        $listrik->update(['is_active' => false]);
        $this->post(route('pengeluaran.store'), [...$base, 'account_id' => $listrik->id, 'cash_account_id' => $kas])->assertSessionHasErrors('account_id');
        $lama = $listrik->expenses()->first();
        $this->put(route('pengeluaran.update', $lama), [...$base, 'account_id' => $listrik->id, 'cash_account_id' => $lama->cash_account_id])->assertSessionHasNoErrors();
    }

    public function test_menu_baru_bisa_dipilih_untuk_role_tambahan(): void
    {
        $this->admin();
        $this->get(route('pengeluaran.index'))->assertSee(route('kode-akun.index'), false);

        $role = Role::create(['key' => 'staf-keuangan', 'name' => 'Staf Keuangan', 'menus' => ['pengeluaran.index']]);
        $staf = User::factory()->create(['role' => $role->key]);
        $this->actingAs($staf);
        $this->get(route('pengeluaran.index'))->assertOk();
        $this->get(route('pengeluaran.create'))->assertOk();
        $this->get(route('kode-akun.index'))->assertForbidden();
    }
}
