<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tahapan pembayaran per angkatan: jumlah tahap dan tanggal jatuh tempo tiap tahap.
 * Nominal per tahap = total biaya ÷ jumlah tahap (sisa pembulatan masuk ke tahap terakhir).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->unsignedBigInteger('biaya')->nullable()->after('kuota'); // null = biaya program
        });

        Schema::create('batch_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('tahap');
            $table->date('jatuh_tempo');
            $table->timestamps();
            $table->unique(['batch_id', 'tahap']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_installments');
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('biaya');
        });
    }
};
