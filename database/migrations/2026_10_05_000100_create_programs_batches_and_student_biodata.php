<?php

use App\Support\Fmt;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Struktur Program Kursus → Batch (Angkatan) → Kelas → Peserta,
 * dan biodata peserta sesuai format "DATA SISWA LPK NIHON BRIDGE.xlsx".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama');
            $table->string('deskripsi')->nullable();
            $table->unsignedBigInteger('biaya')->nullable(); // biaya standar; peserta bisa punya total_fee sendiri
            $table->unsignedTinyInteger('durasi_bulan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Batch = angkatan: kelompok peserta yang mulai program pada periode yang sama. Label UI: "Angkatan".
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->string('kode', 20)->unique(); // mis. 2026-03
            $table->string('nama');               // mis. Angkatan 3
            $table->date('mulai')->nullable();
            $table->date('selesai')->nullable();
            $table->unsignedSmallInteger('kuota')->nullable();
            $table->timestamps();
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->char('gender', 1)->nullable();            // L / P
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->string('religion', 20)->nullable();
            $table->string('marital_status', 20)->nullable();  // belum / menikah / cerai
            $table->string('passport_no', 20)->nullable();
            $table->string('address_ktp')->nullable();
            $table->string('address_domicile')->nullable();
            $table->string('class_mode', 10)->default('tatap_muka'); // tatap_muka / online
            $table->date('class_start')->nullable();
            $table->date('class_end')->nullable();
            $table->string('enrollment_status', 10)->default('aktif')->index(); // aktif / keluar / lulus
            $table->unsignedBigInteger('total_fee')->nullable(); // biaya paket peserta ini; null = biaya program
        });

        // Pindahkan data lama: "Tempat, tanggal lahir" → dua kolom; satu alamat → alamat KTP & domisili.
        foreach (DB::table('students')->get(['id', 'birth_place_date', 'address']) as $s) {
            $place = $date = null;
            if ($s->birth_place_date) {
                [$a, $b] = array_pad(explode(',', $s->birth_place_date, 2), 2, null);
                $date = Fmt::parseDate($b ?? $a);
                $place = $b !== null || ! $date ? trim($a) : null;
            }
            DB::table('students')->where('id', $s->id)->update([
                'birth_place' => $place ?: null,
                'birth_date' => $date,
                'address_ktp' => $s->address,
                'address_domicile' => $s->address,
            ]);
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['birth_place_date', 'address']);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('birth_place_date')->nullable();
            $table->string('address')->nullable();
        });
        foreach (DB::table('students')->get(['id', 'birth_place', 'birth_date', 'address_ktp', 'address_domicile']) as $s) {
            DB::table('students')->where('id', $s->id)->update([
                'birth_place_date' => trim(implode(', ', array_filter([$s->birth_place, $s->birth_date ? Fmt::dateLong($s->birth_date) : null]))) ?: null,
                'address' => $s->address_domicile ?? $s->address_ktp,
            ]);
        }
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
            $table->dropIndex(['enrollment_status']);
            $table->dropColumn([
                'birth_place', 'birth_date', 'gender', 'height_cm', 'religion', 'marital_status', 'passport_no',
                'address_ktp', 'address_domicile', 'class_mode', 'class_start', 'class_end', 'enrollment_status', 'total_fee',
            ]);
        });
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
        });
        Schema::dropIfExists('batches');
        Schema::dropIfExists('programs');
    }
};
