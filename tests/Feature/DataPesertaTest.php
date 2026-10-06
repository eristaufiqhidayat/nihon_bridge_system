<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Classroom;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menu Data Peserta (admin): daftar, tambah, edit, dan hapus dengan cek data terkait.
 */
class DataPesertaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function admin(): User
    {
        $u = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($u);

        return $u;
    }

    private function payload(array $extra = []): array
    {
        $class = Classroom::whereNotNull('batch_id')->firstOrFail();

        return array_merge([
            'name' => 'Rina Ayuningtyas', 'email' => 'rina.baru@contoh.id', 'phone' => '081234567890', 'is_active' => '1',
            'program' => 'Magang · Manufaktur', 'batch_id' => $class->batch_id, 'classroom_id' => $class->id,
            'class_mode' => 'online', 'enrollment_status' => 'aktif', 'total_fee' => '12500000',
            'birth_place' => 'Bandung', 'birth_date' => '2003-02-11', 'gender' => 'P', 'height_cm' => '158',
            'religion' => 'Islam', 'marital_status' => 'belum',
            'address_ktp' => 'Jl. Merdeka 1, Bandung', 'address_domicile' => 'Jl. Sudirman 9, Jakarta',
        ], $extra);
    }

    public function test_halaman_hanya_untuk_admin(): void
    {
        $this->actingAs(User::where('role', 'instruktur')->first());
        $this->get(route('peserta-admin.index'))->assertForbidden();

        $this->admin();
        $s = Student::first();
        $this->get(route('peserta-admin.index'))->assertOk()->assertSee('Data Peserta')->assertSee($s->nis);
        $this->get(route('peserta-admin.index', ['q' => $s->user->name]))->assertOk()->assertSee($s->user->email);
        $this->get(route('peserta-admin.create'))->assertOk();
        $this->get(route('peserta-admin.edit', $s))->assertOk()->assertSee($s->user->name);
    }

    public function test_tambah_dan_edit_peserta(): void
    {
        $this->admin();
        $this->post(route('peserta-admin.store'), $this->payload())->assertRedirect(route('peserta-admin.index'));

        $user = User::where('email', 'rina.baru@contoh.id')->firstOrFail();
        $s = $user->student;
        $this->assertSame('peserta', $user->role);
        $this->assertSame('Jl. Sudirman 9, Jakarta', $s->address_domicile);
        $this->assertSame(12500000, $s->total_fee);
        $this->assertSame('online', $s->class_mode);
        $this->assertMatchesRegularExpression('/^NB-\d{2}-\d{4}$/', $s->nis);

        $this->put(route('peserta-admin.update', $s), $this->payload([
            'name' => 'Rina A.', 'enrollment_status' => 'keluar', 'address_ktp' => 'Jl. Baru 2', 'is_active' => '0',
        ]))->assertRedirect(route('peserta-admin.edit', $s));
        $s->refresh();
        $this->assertSame('Rina A.', $s->user->name);
        $this->assertFalse($s->user->is_active);
        $this->assertSame('keluar', $s->enrollment_status);
        $this->assertSame('Jl. Baru 2', $s->address_ktp);
    }

    public function test_kelas_harus_sesuai_angkatan(): void
    {
        $this->admin();
        $class = Classroom::whereNotNull('batch_id')->firstOrFail();
        $other = Batch::where('id', '!=', $class->batch_id)->first()
            ?? Batch::create(['program_id' => $class->batch->program_id, 'kode' => 'X-99', 'nama' => 'Angkatan Uji']);

        $this->post(route('peserta-admin.store'), $this->payload(['batch_id' => $other->id]))
            ->assertSessionHasErrors('classroom_id');
        $this->assertDatabaseMissing('users', ['email' => 'rina.baru@contoh.id']);
    }

    public function test_hapus_ditolak_bila_ada_data_terkait(): void
    {
        $this->admin();
        $s = Student::has('payments')->firstOrFail();

        $this->from(route('peserta-admin.index'))->delete(route('peserta-admin.destroy', $s))
            ->assertRedirect(route('peserta-admin.index'))
            ->assertSessionHasErrors('hapus');
        $this->assertStringContainsString('pembayaran', session('errors')->first('hapus'));
        $this->assertDatabaseHas('students', ['id' => $s->id]);
        $this->assertDatabaseHas('payments', ['student_id' => $s->id]);
    }

    public function test_hapus_peserta_tanpa_data_terkait(): void
    {
        $this->admin();
        $this->post(route('peserta-admin.store'), $this->payload());
        $s = User::where('email', 'rina.baru@contoh.id')->firstOrFail()->student;
        $this->assertSame([], $s->deleteBlockers());

        $this->delete(route('peserta-admin.destroy', $s))->assertRedirect(route('peserta-admin.index'));
        $this->assertDatabaseMissing('students', ['id' => $s->id]);
        $this->assertDatabaseMissing('users', ['email' => 'rina.baru@contoh.id']);

        // Begitu ada pembayaran, peserta baru pun tidak bisa dihapus.
        $this->post(route('peserta-admin.store'), $this->payload(['email' => 'lain@contoh.id']));
        $s2 = User::where('email', 'lain@contoh.id')->firstOrFail()->student;
        Payment::create(['student_id' => $s2->id, 'installment_no' => 1, 'amount' => 1000000, 'paid_at' => now()]);
        $this->delete(route('peserta-admin.destroy', $s2))->assertSessionHasErrors('hapus');
        $this->assertSame(['pembayaran' => 1], $s2->deleteBlockers());
    }
}
