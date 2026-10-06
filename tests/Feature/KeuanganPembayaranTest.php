<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Keuangan (admin): edit dan hapus catatan pembayaran peserta.
 */
class KeuanganPembayaranTest extends TestCase
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

    public function test_riwayat_tampil_dan_hanya_admin_yang_bisa_mengubah(): void
    {
        $s = $this->studentWithPayments();
        $p = $s->payments()->first();

        $this->actingAs(User::where('role', 'direktur')->first());
        $this->get(route('keuangan.index'))->assertOk()->assertSee('Riwayat pembayaran')->assertDontSee(route('keuangan.destroy', $p));
        $this->put(route('keuangan.update', $p), ['method' => 'Tunai di kantor', 'paid_at' => '2026-01-11'])->assertForbidden();
        $this->delete(route('keuangan.destroy', $p))->assertForbidden();

        $this->admin();
        $this->get(route('keuangan.index'))->assertOk()->assertSee(route('keuangan.destroy', $p));
    }

    public function test_edit_pembayaran(): void
    {
        $this->admin();
        $p = $this->studentWithPayments()->payments()->where('installment_no', 2)->first();

        $this->from(route('keuangan.index'))
            ->put(route('keuangan.update', $p), ['method' => 'Tunai di kantor', 'paid_at' => '2026-02-15'])
            ->assertRedirect(route('keuangan.index'));
        $p->refresh();
        $this->assertSame('Tunai di kantor', $p->method);
        $this->assertSame('2026-02-15', $p->paid_at->toDateString());

        $this->from(route('keuangan.index'))
            ->put(route('keuangan.update', $p), ['method' => 'Bitcoin', 'paid_at' => 'bukan-tanggal'])
            ->assertSessionHasErrors(['method', 'paid_at']);
    }

    public function test_hapus_pembayaran_menomori_ulang_cicilan(): void
    {
        $this->admin();
        $s = $this->studentWithPayments();
        $first = $s->payments()->where('installment_no', 1)->first();

        $this->from(route('keuangan.index'))->delete(route('keuangan.destroy', $first))->assertRedirect(route('keuangan.index'));

        $this->assertModelMissing($first);
        $left = $s->payments()->orderBy('installment_no')->get();
        $this->assertSame([1, 2], $left->pluck('installment_no')->all());
        $this->assertSame(['lunas', 'menunggu'], $left->pluck('status')->all());

        // Cicilan berikutnya tercatat di nomor yang benar tanpa menimpa cicilan lunas.
        $this->post(route('keuangan.record'), ['student_id' => $s->id, 'method' => 'Transfer bank', 'paid_at' => '2026-04-01']);
        $this->assertSame(2, $s->payments()->where('status', 'lunas')->count());
    }
}
