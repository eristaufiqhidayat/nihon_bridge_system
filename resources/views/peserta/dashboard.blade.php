@extends('layouts.app')
@section('title', 'Dashboard')
@section('crumb')<b>Dashboard</b>@endsection

@php
  use App\Support\Catalog;
  use App\Support\Fmt;
  use App\Services\ExamService;
  $p = $student->progres_belajar;
  $level = $student->classroom?->level ?? 'N4';
@endphp

@section('content')
<div class="page">
  <div class="welcome-banner">
    <div><h3>Selamat datang, {{ $student->name }}! 🌸</h3>
      <p>
        @if ($student->stage < count(Catalog::STAGES))
          Tahap berikutnya: {{ Catalog::STAGES[$student->stage] }}@if ($student->stage_dates[$student->stage] ?? null) ({{ str_replace('Jadwal ', '', $student->stage_dates[$student->stage]) }})@endif.
        @endif
        Terus belajar dan raih impianmu bekerja di Jepang.</p>
      <div class="progress-track"><div class="progress-fill" style="width:{{ $p }}%"></div></div>
      <p style="margin:6px 0 0;font-size:11.5px">Progres belajar {{ $level }}: {{ $p }}% ({{ $student->materi_selesai }} dari {{ $student->materi_total }} materi)</p></div>
    @include('partials.welcome-art')
  </div>

  <div class="grid g4 mb16">
    <div class="card stat-mini"><div class="ic ic-bg-blue">📶</div><div><p>Level saat ini</p><h4>{{ $level }}</h4></div></div>
    <div class="card stat-mini"><div class="ic ic-bg-green">✅</div><div><p>Kehadiran</p><h4 class="tnum">{{ $attendance['persen'] }}% <small>{{ $attendance['hadir'] }}/{{ $attendance['total'] }} sesi</small></h4></div></div>
    <div class="card stat-mini"><div class="ic ic-bg-orange">📘</div><div><p>Materi selesai</p><h4 class="tnum">{{ $student->materi_selesai }} <small>/ {{ $student->materi_total }}</small></h4></div></div>
    <div class="card stat-mini"><div class="ic ic-bg-purple">🏅</div><div><p>Sertifikat</p><h4 class="tnum">{{ $certCount }}</h4></div></div>
  </div>

  <div class="card pad mb16">
    <div class="sh"><h4>Tahapan Program Jepang</h4><a class="lnk" href="{{ route('program.index') }}">Lihat detail</a></div>
    @include('partials.timeline', ['student' => $student])
  </div>

  <div class="grid g3">
    <div class="card pad">
      <div class="sh"><h4>Lanjutkan belajar</h4><a class="lnk" href="{{ route('materi.show') }}">Lihat semua</a></div>
      @if ($chapter)
        <div class="list-row"><div class="ic ic-bg-sakura">📕</div><div class="info"><p>{{ $chapter->book }} Bab {{ $chapter->no }}</p><span>{{ $chapter->judul }} · {{ $chapterDone }}/{{ $chapterTotal }} bagian selesai</span></div><a class="mini-btn" href="{{ route('materi.show') }}">Lanjut</a></div>
      @else
        <div class="list-row"><div class="ic ic-bg-green">✓</div><div class="info"><p>Semua materi terbit sudah selesai</p><span>Materi baru akan muncul di sini</span></div></div>
      @endif
      <div class="list-row"><div class="ic ic-bg-blue">✏️</div><div class="info"><p>Latihan soal {{ $level }}</p><span>Ujian simulasi & kuis</span></div><a class="mini-btn" href="{{ route('ujian.index') }}">Mulai</a></div>
    </div>

    <div class="card pad">
      <div class="sh"><h4>{{ $isToday ? 'Jadwal hari ini' : 'Jadwal berikutnya' }}</h4><a class="lnk" href="{{ route('kelas.index') }}">Jadwal minggu ini</a></div>
      <p class="small muted" style="margin:-4px 0 4px">{{ Fmt::dayDateLong($scheduleDay) }} · Kelas {{ $student->classroom?->kode }}</p>
      @forelse ($todaySchedule as $r)
        <div class="list-row"><div class="ic {{ $r->subject_bg }}">🕗</div><div class="info"><p>{{ Catalog::SLOTS[$r->slot] }} · {{ $r->subject_name }}</p><span>{{ $r->instructor->sensei_name }} · Ruang {{ $student->classroom->ruang }}</span></div></div>
      @empty
        <div class="empty">Tidak ada jadwal.</div>
      @endforelse
    </div>

    <div class="card pad">
      <div class="sh"><h4>Ujian</h4><a class="lnk" href="{{ route('hasil.index') }}">Semua nilai</a></div>
      @if ($lastAttempt)
        <?php $ok = ExamService::isPass($lastAttempt); ?>
        <div class="list-row"><div class="ic {{ $ok ? 'ic-bg-green' : 'ic-bg-orange' }}">{{ $ok ? '✓' : '△' }}</div><div class="info"><p>Nilai terakhir: {{ $lastAttempt->total }}</p><span>{{ $lastAttempt->package?->judul ?? $lastAttempt->label }} · {{ Fmt::date($lastAttempt->submitted_at) }}</span></div></div>
      @endif
      @if ($running)
        <div class="list-row"><div class="ic ic-bg-orange">⏱</div><div class="info"><p>{{ $running->package->judul }}</p><span>Sedang berlangsung · sisa {{ intdiv($running->secondsLeft(), 60) }} menit</span></div><a class="mini-btn" href="{{ route('ujian.cbt', $running) }}">Lanjutkan</a></div>
      @elseif ($openExam)
        <div class="list-row"><div class="ic ic-bg-blue">📝</div><div class="info"><p>{{ $openExam->display_title }}</p><span>{{ $openExam->package->question_count }} soal · {{ $openExam->duration }} menit · bisa diulang</span></div><a class="mini-btn" href="{{ route('ujian.index') }}">Mulai</a></div>
      @endif
      @if ($nextOnsite)
        <div class="list-row"><div class="ic ic-bg-grey">🗓️</div><div class="info"><p>{{ $nextOnsite->display_title }}</p><span>{{ Fmt::dayDate($nextOnsite->opens_at) }}{{ $nextOnsite->start_time ? ' · ' . $nextOnsite->start_time : '' }}</span></div></div>
      @endif
    </div>
  </div>
</div>
@endsection
