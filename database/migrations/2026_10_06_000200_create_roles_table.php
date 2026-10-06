<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master peran (role). users.role menyimpan roles.key.
 * Empat peran bawaan (is_system) tidak bisa dihapus dan menunya tetap di kode (Navigation::MENU);
 * peran tambahan memilih menu yang boleh diakses (kolom menus).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->string('name', 60);
            $table->string('description')->nullable();
            $table->json('menus')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $now = now();
        DB::table('roles')->insert([
            ['key' => 'peserta', 'name' => 'Peserta', 'description' => 'Siswa LPK: materi, ujian, nilai, sertifikat, dan pembayaran.', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'instruktur', 'name' => 'Instruktur', 'description' => 'Pengajar: kelas, kehadiran, materi, dan bank soal.', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'admin', 'name' => 'Admin', 'description' => 'Pengelola seluruh data LPK.', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'direktur', 'name' => 'Direktur', 'description' => 'Pimpinan: laporan, keuangan, dan perusahaan (baca saja).', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
