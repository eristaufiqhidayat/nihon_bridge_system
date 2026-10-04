<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kelas, jadwal, peserta, dokumen program, kehadiran, dan pengumuman.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->string('level', 2);
            $table->string('nama')->nullable();
            $table->foreignId('wali_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ruang', 10)->nullable();
            $table->string('periode')->nullable();
            $table->timestamps();
        });

        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day');   // 0 = Senin … 4 = Jumat
            $table->unsignedTinyInteger('slot');  // indeks Catalog::SLOTS
            $table->string('subject', 4);         // kode Catalog::MAPEL
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['classroom_id', 'day', 'slot']);
            $table->index(['instructor_id', 'day', 'slot']);
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nis', 20)->unique();
            $table->string('program');
            $table->unsignedTinyInteger('stage')->default(1);
            $table->json('stage_dates')->nullable();
            $table->string('birth_place_date')->nullable();
            $table->string('address')->nullable();
            $table->string('guardian_contact')->nullable();
            $table->string('note')->nullable();
            $table->unsignedSmallInteger('nilai_tryout')->nullable(); // nilai dari sistem lama, dipakai bila belum ada ujian di sistem
            $table->unsignedSmallInteger('materi_selesai')->default(0);
            $table->unsignedSmallInteger('materi_total')->default(0);
            $table->string('target_departure')->nullable();
            $table->timestamps();
        });

        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('doc_key', 20);
            $table->string('status', 10);
            $table->timestamps();
            $table->unique(['student_id', 'doc_key']);
        });

        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedTinyInteger('slot');
            $table->string('subject', 4);
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['classroom_id', 'date', 'slot']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->char('status', 1); // H / I / S / A
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['attendance_session_id', 'student_id']);
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('icon', 8)->default('📣');
            $table->string('color', 20)->default('ic-bg-sakura');
            $table->string('title');
            $table->string('body')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('attendance_sessions');
        Schema::dropIfExists('student_documents');
        Schema::dropIfExists('students');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('classrooms');
    }
};
