<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pendaftaran calon peserta, perusahaan & job order, pembayaran, dan laporan tahunan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->string('reg_no', 20)->unique();
            $table->string('nama');
            $table->string('nik', 16)->nullable();
            $table->string('ttl')->nullable();
            $table->string('asal')->nullable();
            $table->unsignedTinyInteger('usia')->nullable();
            $table->string('pendidikan', 20);
            $table->string('program');
            $table->string('level_bahasa')->nullable();
            $table->string('referensi')->nullable();
            $table->string('hp', 30);
            $table->string('email')->nullable();
            $table->string('status', 10)->default('baru')->index();
            $table->string('tes_jadwal')->nullable();
            $table->string('alasan')->nullable();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('berkas')->nullable(); // {ktp: {name, size, path}, …}
            $table->date('registered_at');
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kota');
            $table->string('bidang');
            $table->string('sejak', 4);
            $table->timestamps();
        });

        Schema::create('job_orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('posisi');
            $table->string('jalur');
            $table->unsignedSmallInteger('kuota');
            $table->text('syarat');
            $table->string('interview');
            $table->string('status', 30)->default('Seleksi kandidat');
            $table->timestamps();
        });

        Schema::create('job_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('hasil', 10)->default('menunggu');
            $table->timestamps();
            $table->unique(['job_order_id', 'student_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('installment_no');
            $table->unsignedBigInteger('amount');
            $table->string('method', 30)->nullable();
            $table->string('status', 10)->default('lunas'); // lunas / menunggu
            $table->date('paid_at')->nullable();
            $table->string('proof_path')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'installment_no']);
        });

        Schema::create('yearly_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->unsignedInteger('peserta');
            $table->unsignedSmallInteger('kelas');
            $table->unsignedInteger('lulus');
            $table->unsignedInteger('berangkat');
            $table->json('levels')->nullable();
            $table->timestamps();
        });

        Schema::create('pass_rates', function (Blueprint $table) {
            $table->id();
            $table->string('level', 2);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('rate');
            $table->unique(['level', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pass_rates');
        Schema::dropIfExists('yearly_stats');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('job_candidates');
        Schema::dropIfExists('job_orders');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('applicants');
    }
};
