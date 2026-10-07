<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Pengeluaran.
 * accounts = master kode akun (bagan akun gaya SAK EMKM): 1-1xxx Kas & Bank (sumber dana),
 * 5-xxxx Beban Pokok Pendapatan, 6-xxxx Beban Operasional, 8-xxxx Beban Lain-lain.
 * expenses = transaksi pengeluaran (bukti kas keluar): akun beban didebit, akun kas/bank dikredit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->string('nama', 120);
            $table->string('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 30)->unique();
            $table->date('tanggal');
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('cash_account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('penerima', 120)->nullable();
            $table->string('uraian');
            $table->unsignedBigInteger('jumlah');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('accounts');
    }
};
