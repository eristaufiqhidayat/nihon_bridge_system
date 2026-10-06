<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menu Pembayaran Peserta (admin): entri per peserta, cari nama, edit dan hapus.
 */
class PembayaranPesertaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function admin(): User
    {
        $u = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($u);

        return $u;
    }

    /** Peserta dengan tiga cicilan berurutan: 1 & 2 lunas, 3 menunggu verifikasi. */
    private function studentWithPayments(): Student
    {
        $s = Student::first();
        $s->payments()->delete();
        foreach ([1 => 'lunas', 2 => 'lunas', 3 => 'menunggu'] as $no => $status) {
            Payment::create(['student_id' => $s->id, 'installment_no' => $no, 'amount' => 2500000, 'method' => 'Transfer VA',
                'status' => $status, 'paid_at' => "2026-0{$no}-10"]);
        }

        return $s;
    }

    public function test_menu_hanya_admin_dan_bisa_cari_nama(): void
    {
        $s = $this->studentWithPayments();
        $p = $s->payments()->first();
        $other = Student::where('id', '!=', $s->id)->firstOrFail();

        $this->actingAs(User::where('role', 'direktur')->first());
        $this->get(route('pembayaran-admin.index'))->assertForbidden();
        $this->get(route('keuangan.index'))->assertOk()->assertDontSee(route('pembayaran-admin.destroy', $p));
        $this->put(route('pembayaran-admin.update', $p), ['method' => 'Tunai di kantor', 'paid_at' => '2026-01-11'])->assertForbidden();
        $this->delete(route('pembayaran-admin.destroy', $p))->assertForbidden();

        $this->admin();
        // Edit & hapus tidak lagi ada di menu Keuangan.
        $this->get(route('keuangan.index'))->assertOk()->assertDontSee(route('pembayaran-admin.destroy', $p))->assertSee(route('pembayaran-admin.index'));
        $this->get(route('pembayaran-admin.index'))->assertOk()->assertSee('Pembayaran Peserta')->assertSee(route('pembayaran-admin.destroy', $p));
        $this->get(route('pembayaran-admin.index', ['q' => $s->user->name]))->assertOk()
            ->assertSee($s->nis)->assertSee(route('pembayaran-admin.destroy', $p))->assertDontSee($other->nis . ' · ', false);
    }

    public function test_edit_pembayaran(): void
    {
        $this->admin();
        $p = $this->studentWithPayments()->payments()->where('installment_no', 2)->first();

        $this->from(route('pembayaran-admin.index'))
            ->put(route('pembayaran-admin.update', $p), ['method' => 'Tunai di kantor', 'paid_at' => '2026-02-15'])
            ->assertRedirect(route('pembayaran-admin.index'));
        $p->refresh();
        $this->assertSame('Tunai di kantor', $p->method);
        $this->assertSame('2026-02-15', $p->paid_at->toDateString());

        $this->from(route('pembayaran-admin.index'))
            ->put(route('pembayaran-admin.update', $p), ['method' => 'Bitcoin', 'paid_at' => 'bukan-tanggal'])
            ->assertSessionHasErrors(['method', 'paid_at']);
    }

    public function test_hapus_pembayaran_menomori_ulang_cicilan(): void
    {
        $this->admin();
        $s = $this->studentWithPayments();
        $first = $s->payments()->where('installment_no', 1)->first();

        $this->from(route('pembayaran-admin.index'))->delete(route('pembayaran-admin.destroy', $first))->assertRedirect(route('pembayaran-admin.index'));

        $this->assertModelMissing($first);
        $left = $s->payments()->orderBy('installment_no')->get();
        $this->assertSame([1, 2], $left->pluck('installment_no')->all());
        $this->assertSame(['lunas', 'menunggu'], $left->pluck('status')->all());

        // Cicilan berikutnya tercatat di nomor yang benar tanpa menimpa cicilan lunas.
        $this->post(route('pembayaran-admin.store'), ['student_id' => $s->id, 'method' => 'Transfer bank', 'paid_at' => '2026-04-01']);
        $this->assertSame(2, $s->payments()->where('status', 'lunas')->count());
    }
}
