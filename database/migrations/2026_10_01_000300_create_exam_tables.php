<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bank soal, paket ujian, jadwal ujian, percobaan (CBT), dan sertifikat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->string('section', 10)->index();
            $table->string('level', 2)->index();
            $table->text('question');
            $table->json('options');
            $table->unsignedTinyInteger('answer_key');
            $table->text('explanation')->nullable();
            $table->text('passage')->nullable();
            $table->text('audio_script')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('exam_packages', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('level', 2)->default('N4');
            $table->string('status', 10)->default('Draf'); // Draf / Terbit
            $table->date('published_at')->nullable();
            $table->unsignedSmallInteger('question_count')->default(0);
            $table->json('composition')->nullable();
            $table->json('snapshot')->nullable(); // salinan soal saat terbit
            $table->timestamps();
        });

        Schema::create('exam_package_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unique(['exam_package_id', 'question_id']);
        });

        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->string('mode', 10)->default('cbt'); // cbt / onsite
            $table->date('opens_at');
            $table->date('closes_at')->nullable();
            $table->string('start_time', 5)->nullable();
            $table->unsignedSmallInteger('duration')->default(60);
            $table->timestamps();
        });

        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('exam_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label');
            $table->unsignedSmallInteger('attempt_no')->default(1);
            $table->string('status', 10)->default('berjalan'); // berjalan / selesai
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->json('answers')->nullable();
            $table->json('flags')->nullable();
            $table->unsignedSmallInteger('current')->default(0);
            $table->json('section_scores')->nullable();
            $table->json('section_counts')->nullable();
            $table->unsignedSmallInteger('correct')->nullable();
            $table->unsignedSmallInteger('question_count')->nullable();
            $table->unsignedSmallInteger('total')->nullable();
            $table->boolean('auto_submitted')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_attempt_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30)->unique();
            $table->string('title');
            $table->string('kind');
            $table->text('description');
            $table->unsignedSmallInteger('score');
            $table->date('issued_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('exam_attempts');
        Schema::dropIfExists('exam_schedules');
        Schema::dropIfExists('exam_package_question');
        Schema::dropIfExists('exam_packages');
        Schema::dropIfExists('questions');
    }
};
