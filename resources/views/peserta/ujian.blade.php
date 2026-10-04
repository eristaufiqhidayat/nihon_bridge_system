@extends('layouts.app')
@section('title', 'Ujian')
@section('crumb')<b>Ujian</b>@endsection

@php
  use App\Support\Catalog;
  use App\Support\Fmt;
@endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Ujian</h2><p>Ujian yang tersedia untuk Kelas {{ $class?->kode ?? '–' }}</p></div></div>
  <x-form-errors />
  @if ($running)
    <div class="running-banner"><span>⏱ {{ $running->package->judul }} sedang berlangsung. Sisa waktu <b class="tnum">{{ Fmt::duration($running->secondsLeft()) }}</b>.</span><a class="bt solid" href="{{ route('ujian.cbt', $running) }}">Lanjutkan ujian</a></div>
  @endif
  <div class="grid" style="gap:12px">
    @forelse ($schedules as $s)
      @php
        $pkg = $s->package;
        $open = $s->isOpen();
        $n = $pkg ? ($attemptCounts[$pkg->id] ?? 0) : 0;
        $isRunning = $running && $running->exam_schedule_id === $s->id;
        $past = $s->closes_at && $s->closes_at->endOfDay()->isPast();
        $icon = $s->mode === 'onsite' ? ['🗓️', 'ic-bg-grey'] : (str_contains($s->display_title, 'JFT') ? ['🧭', 'ic-bg-green'] : (str_contains($s->display_title, 'Kuis') ? ['✏️', 'ic-bg-blue'] : ['📝', 'ic-bg-sakura']));
      @endphp
      <div class="card exam-card"><div class="ic {{ $icon[1] }}">{{ $icon[0] }}</div>
        <div class="info"><h4>{{ $s->display_title }}</h4><span class="small muted">{{ $s->description }}</span>
          <div class="meta">
            @if ($s->mode === 'onsite')
              <span class="badge b-grey">{{ Fmt::dayDate($s->opens_at) }}{{ $s->start_time ? ' · ' . $s->start_time : '' }}</span><span class="badge b-grey">{{ $s->duration }} menit</span>
            @else
              <span class="badge b-grey">{{ $pkg->question_count }} soal</span><span class="badge b-grey">{{ $s->duration }} menit</span>
              @foreach ($pkg->sectionCounts() as $sec => $cnt)<span class="badge {{ Catalog::SECTIONS[$sec]['badge'] }}">{{ Catalog::SECTIONS[$sec]['name'] }} {{ $cnt }}</span>@endforeach
              @if ($open)<span class="badge b-sakura">Percobaan ke-{{ $n + ($isRunning ? 0 : 1) }}</span>@endif
              @if (! $open)<span class="badge b-grey">Dibuka {{ Fmt::date($s->opens_at) }}{{ $s->closes_at ? ' – ' . Fmt::date($s->closes_at) : '' }}</span>@endif
            @endif
          </div></div>
        @if ($s->mode === 'onsite')
          <button class="bt outline" disabled>Terjadwal</button>
        @elseif ($isRunning)
          <a class="bt solid" href="{{ route('ujian.cbt', $running) }}">Lanjutkan</a>
        @elseif ($open && ! $running)
          <button class="bt solid" type="button" data-modal-open="startExam"
            data-fill="{{ json_encode(['@action' => route('ujian.start', $s), '@title' => "Mulai {$pkg->judul}?", 'dur' => $s->duration . ' menit', 'n' => $pkg->question_count . ' soal'], JSON_UNESCAPED_UNICODE) }}">Mulai ujian</button>
        @elseif ($past)
          <button class="bt outline" disabled>Ditutup</button>
        @elseif (! $open)
          <button class="bt outline" disabled>Dibuka {{ Fmt::date($s->opens_at) }}</button>
        @else
          <button class="bt outline" disabled title="Selesaikan ujian yang sedang berjalan dulu">Mulai ujian</button>
        @endif
      </div>
    @empty
      <div class="card"><div class="empty">Belum ada ujian untuk kelas Anda.</div></div>
    @endforelse
  </div>
</div>
@endsection

@push('modals')
<x-modal id="startExam" title="Mulai ujian?">
  <form method="POST" action="#">
    @csrf
    <ul class="rules">
      <li><input name="n" readonly style="border:none;background:none;font:inherit;font-weight:700;width:5.5em;padding:0"> pilihan ganda.</li>
      <li>Waktu <input name="dur" readonly style="border:none;background:none;font:inherit;font-weight:700;width:6em;padding:0">. Waktu tetap berjalan meski Anda membuka halaman lain.</li>
      <li>Soal Mendengar memakai audio; tekan tombol putar untuk mendengarkan.</li>
      <li>Tandai soal <b>Ragu-ragu</b> untuk dicek lagi sebelum selesai.</li>
      <li>Jawaban tersimpan otomatis. Bila koneksi putus, jawaban disimpan di perangkat dan dikirim saat tersambung.</li>
      <li>Saat waktu habis, jawaban dikirim otomatis.</li>
      <li>Lulus jika nilai total ≥ {{ config('nihonbridge.exam.pass_total') }} dan setiap bagian ≥ {{ config('nihonbridge.exam.pass_section') }}.</li>
    </ul>
    <div class="modal-actions"><button type="button" class="cancel" data-modal-close>Batal</button><button class="ok" type="submit">Mulai sekarang</button></div>
  </form>
</x-modal>
@endpush
