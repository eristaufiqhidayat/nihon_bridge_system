@extends('layouts.app')
@section('title', 'Nilai & Hasil Ujian')
@section('crumb')<b>Nilai &amp; Hasil Ujian</b>@endsection

@php
  use App\Support\Catalog;
  use App\Support\Fmt;
  use App\Services\ExamService;
  $cfg = config('nihonbridge.exam');
@endphp

@section('content')
<div class="page">
  @if (! $attempt)
    <div class="page-head"><div><h2>Nilai &amp; Hasil Ujian</h2></div></div>
    <div class="card"><div class="empty-state"><div class="ic">📝</div><b>Belum ada ujian yang selesai</b><p>Kerjakan ujian simulasi untuk melihat nilai per kompetensi dan indeks kesiapan.</p><a class="bt solid" href="{{ route('ujian.index') }}">Ke halaman ujian</a></div></div>
  @else
    @php
      $sections = $attempt->section_scores ?? [];
      asort($sections);
      $weak = array_key_first($sections);
      $ready = $readiness >= $cfg['readiness_ready'];
    @endphp
    <div class="page-head"><div><h2>Hasil {{ $attempt->package?->judul ?? $attempt->label }}@if ($attempt->attempt_no > 1) · percobaan {{ $attempt->attempt_no }}@endif</h2>
      <p>Tanggal ujian: {{ Fmt::date($attempt->submitted_at) }}@if ($attempt->correct !== null) · {{ $attempt->correct }} dari {{ $attempt->question_count }} soal benar @endif @if ($attempt->auto_submitted) · dikirim otomatis saat waktu habis @endif</p></div></div>
    <div class="hasil-hero {{ $pass ? 'pass' : 'fail' }}">
      <div class="l"><div class="mark">{{ $pass ? '✓' : '!' }}</div><div><h3>{{ $pass ? 'Selamat, Anda lulus' : 'Belum lulus' }}</h3>
        <p>@if ($pass)Bagian terlemah: {{ Catalog::sectionName($weak) }} ({{ $sections[$weak] }}). Fokuskan latihan di sana.@else Syarat lulus: total ≥ {{ $cfg['pass_total'] }} dan setiap bagian ≥ {{ $cfg['pass_section'] }}. Ulangi setelah latihan {{ Catalog::sectionName($weak) }}.@endif</p></div></div>
      <div class="r"><span>Nilai total</span><b class="tnum">{{ $attempt->total }}<small>/100</small></b></div>
    </div>
    <div class="grid g2">
      <div class="card pad">
        <h4 class="ct">Rekap nilai per kompetensi</h4>
        @foreach (Catalog::SECTIONS as $k => $s)
          <?php $v = $attempt->section_scores[$k] ?? 0; ?>
          <?php $c = $attempt->section_counts ? ($attempt->section_counts['c'][$k] . '/' . $attempt->section_counts['t'][$k] . ' benar') : ''; ?>
          <div class="kompetensi-row"><div class="lbl">{{ $s['name'] }}<span>{{ $s['jp'] }}</span></div><div class="kbar"><span style="width:{{ $v }}%;background:{{ $v < $cfg['pass_section'] ? 'var(--red)' : $s['color'] }}"></span><i style="left:{{ $cfg['pass_section'] }}%" title="Batas minimum {{ $cfg['pass_section'] }}"></i></div><div class="val tnum">{{ $v }}<small>{{ $c }}</small></div></div>
        @endforeach
        <div class="pred-box">
          <div class="top"><span>Indeks kesiapan JLPT</span><span class="tnum" style="color:{{ $ready ? 'var(--green)' : 'var(--orange)' }}">{{ $readiness }}/100</span></div>
          <div class="progress-track"><div class="progress-fill" style="width:{{ $readiness }}%;background:{{ $ready ? 'var(--green)' : 'var(--orange)' }}"></div></div>
          <p style="margin:10px 0 0;font-size:12px;font-weight:700;color:{{ $ready ? 'var(--green)' : 'var(--orange)' }}">{{ $ready ? '✓ Siap mengikuti Tryout JLPT Nasional' : 'Perlu latihan tambahan sebelum tryout nasional' }}</p>
          <p class="rule-note">Indeks = 70% nilai total + 30% nilai bagian terendah. Garis tipis di tiap bar menandai batas minimum {{ $cfg['pass_section'] }}.</p>
        </div>
        <div class="btnrow" style="margin-top:16px">
          @if ($attempt->hasReview())
            <a class="bt outline" href="{{ route('hasil.index', [$attempt, 'pembahasan' => $showReview ? 0 : 1]) }}#{{ $showReview ? '' : 'review' }}">{{ $showReview ? 'Tutup pembahasan' : 'Lihat pembahasan' }}</a>
          @else
            <button class="bt outline" disabled title="Pembahasan hanya tersedia untuk ujian di sistem baru">Lihat pembahasan</button>
          @endif
          @if ($attempt->certificate)<a class="bt solid" href="{{ route('sertifikat.index', $attempt->certificate) }}">🏅 Lihat sertifikat</a>@endif
          <a class="bt gold" href="{{ route('ujian.index') }}">↻ Ulangi ujian</a>
        </div>
      </div>
      <div class="card pad">
        <div class="sh"><h4>Riwayat ujian</h4><span class="small muted">Klik untuk melihat detail</span></div>
        @foreach ($history as $x)
          <?php $p = ExamService::isPass($x); ?>
          <a class="list-row clickable {{ $x->id === $attempt->id ? 'sel' : '' }}" href="{{ route('hasil.index', $x) }}"><div class="ic {{ $p ? 'ic-bg-green' : 'ic-bg-orange' }}">{{ $p ? '✓' : '△' }}</div><div class="info"><p>{{ $x->package?->judul ?? $x->label }}@if ($x->attempt_no > 1) · percobaan {{ $x->attempt_no }}@endif</p><span>{{ Fmt::date($x->submitted_at) }} · {{ $p ? 'Lulus' : 'Belum lulus' }}</span></div><b class="tnum">{{ $x->total }}</b></a>
        @endforeach
      </div>
    </div>

    @if ($showReview)
      @php
        $items = collect($attempt->questions())->map(fn ($q, $i) => ['q' => $q, 'i' => $i, 'your' => $attempt->answers[$i] ?? null])
          ->filter(fn ($x) => ! $wrongOnly || $x['your'] !== $x['q']['key']);
      @endphp
      <div class="card pad" style="margin-top:16px" id="review">
        <div class="sh"><h4>Pembahasan soal</h4>
          <form method="GET" action="{{ route('hasil.index', $attempt) }}#review" class="inline"><input type="hidden" name="pembahasan" value="1">
            <label class="check small"><input type="checkbox" name="salah" value="1" @checked($wrongOnly) data-autosubmit> Hanya yang salah</label></form></div>
        @forelse ($items as $x)
          <?php $q = $x['q']; ?>
          <?php $ok = $x['your'] !== null && (int) $x['your'] === (int) $q['key']; ?>
          <div class="review-item"><span class="badge {{ Catalog::SECTIONS[$q['section']]['badge'] }}">No. {{ $x['i'] + 1 }} · {{ Catalog::sectionName($q['section']) }}</span>
            @if (! empty($q['audio']))<p class="exp" style="margin-top:6px">🎧 {{ $q['audio'] }}</p>@endif
            <p class="q">{{ $q['question'] }}</p>
            <div class="ans"><span class="badge {{ $ok ? 'b-green' : 'b-red' }}">{{ $ok ? '✓' : '✗' }} Jawaban Anda: {{ $x['your'] === null ? 'tidak dijawab' : 'ABCD'[$x['your']] . '. ' . $q['options'][$x['your']] }}</span>
              @unless ($ok)<span class="badge b-green">Kunci: {{ 'ABCD'[$q['key']] }}. {{ $q['options'][$q['key']] }}</span>@endunless</div>
            <p class="exp">{{ $q['explanation'] }}</p></div>
        @empty
          <div class="empty">Semua jawaban benar. 🎉</div>
        @endforelse
      </div>
    @endif
  @endif
</div>
@endsection
