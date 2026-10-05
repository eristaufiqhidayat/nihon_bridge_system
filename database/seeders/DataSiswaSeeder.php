<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Support\Fmt;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Data peserta asli dari "DATA SISWA LPK NIHON BRIDGE.xlsx" (sheet BATCH 1–4 + sheet pembayaran),
 * sesuai pratinjau-seeder-data-siswa.xlsx.
 *
 * Data pribadi disimpan di database/seeders/data/data_siswa.json yang TIDAK ikut di-commit.
 * Salin file itu ke server lalu jalankan: php artisan db:seed --class=DataSiswaSeeder
 * Aman dijalankan ulang (updateOrCreate per NIS / email / tahap pembayaran).
 */
class DataSiswaSeeder extends Seeder
{
    public const FILE = __DIR__ . '/data/data_siswa.json';

    public const INITIAL_PASSWORD = 'password';

    /** Angkatan: [nomor => [kode, tanggal mulai]]. */
    private const BATCHES = [1 => ['2025-09', '2025-09-15'], 2 => ['2026-01', '2026-01-05'], 3 => ['2026-05', '2026-05-18'], 4 => ['2026-09', '2026-09-14']];

    public function run(): void
    {
        if (! is_file(self::FILE)) {
            $this->command?->warn('Lewati DataSiswaSeeder: ' . self::FILE . ' tidak ada.');

            return;
        }
        $rows = json_decode(file_get_contents(self::FILE), true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($rows) {
            $program = Program::firstOrCreate(['kode' => 'BJ-KERJA'], [
                'nama' => 'Pelatihan Bahasa Jepang & Persiapan Kerja', 'biaya' => 8000000, 'durasi_bulan' => 6,
                'deskripsi' => 'Bahasa Jepang N5–N4 dan pembekalan budaya kerja sebelum penempatan ke Jepang',
            ]);
            $batches = [];
            foreach (self::BATCHES as $no => [$kode, $mulai]) {
                $batches[$no] = Batch::firstOrCreate(['kode' => $kode], [
                    'program_id' => $program->id, 'nama' => "Angkatan $no",
                    'mulai' => $mulai, 'selesai' => \Illuminate\Support\Carbon::parse($mulai)->addMonths(6)->subDay(),
                ]);
            }
            $password = Hash::make(self::INITIAL_PASSWORD);

            foreach ($rows as $r) {
                $batch = $batches[$r['angkatan']];
                $student = Student::where('nis', $r['nis'])->first();
                $user = $student?->user ?? User::firstOrNew(['email' => $r['email']]);
                $user->fill(['name' => $r['nama'], 'email' => $r['email'], 'role' => 'peserta', 'phone' => $r['hp'], 'is_active' => $r['login_aktif']]);
                if (! $user->exists) {
                    $user->fill(['password' => $password, 'must_change_password' => true]);
                }
                $user->save();

                [$start, $end] = $this->classPeriod($r['mulai_kelas'], $batch);
                $student = Student::updateOrCreate(['nis' => $r['nis']], [
                    'user_id' => $user->id, 'batch_id' => $batch->id, 'program' => 'Belum ditentukan', 'stage' => 1,
                    'birth_place' => $r['tempat_lahir'], 'birth_date' => $r['tgl_lahir'], 'gender' => $r['jk'], 'height_cm' => $r['tinggi_cm'],
                    'religion' => $r['agama'], 'marital_status' => $r['status_nikah'], 'passport_no' => $r['paspor'],
                    'address_ktp' => $r['alamat_ktp'], 'address_domicile' => $r['alamat_domisili'], 'guardian_contact' => $r['hp_ortu'],
                    'class_mode' => $r['mode_kelas'], 'class_start' => $start, 'class_end' => $end,
                    'enrollment_status' => $r['status'], 'total_fee' => $r['total_biaya'], 'note' => $r['catatan'],
                ]);

                foreach ($r['pembayaran'] as $p) {
                    Payment::updateOrCreate(['student_id' => $student->id, 'installment_no' => $p['tahap']], [
                        'amount' => $p['nominal'], 'paid_at' => $p['tanggal'], 'status' => 'lunas', 'method' => 'Impor Excel',
                    ]);
                }
            }
        });
        $this->command?->info('DataSiswaSeeder: ' . count($rows) . ' peserta diimpor.');
    }

    /** "5 JANUARI 2026 - 5 APRIL 2026" → [mulai, selesai]; teks tanpa tahun ("14 September - Sekarang") → mulai angkatan. */
    private function classPeriod(?string $text, Batch $batch): array
    {
        [$a, $b] = array_pad(array_map('trim', explode('-', (string) $text, 2)), 2, null);

        return [Fmt::parseDate($a) ?? $batch->mulai?->toDateString(), Fmt::parseDate($b)];
    }
}
