<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Materi pembelajaran: bab, bagian (video/PDF/PPT/audio), dan progres peserta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->string('level', 2);
            $table->string('book')->default('Minna no Nihongo');
            $table->unsignedSmallInteger('no');
            $table->string('judul');
            $table->string('deskripsi')->nullable();
            $table->string('contoh_jp')->nullable();
            $table->boolean('is_locked')->default(true);
            $table->timestamps();
            $table->unique(['level', 'no']);
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->string('judul');
            $table->string('jenis', 10)->default('Video');
            $table->string('durasi', 20)->nullable();
            $table->string('status', 10)->default('Terbit');
            $table->string('jp_title')->nullable();
            $table->string('jp_sub')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->unique(['user_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('chapters');
    }
};
