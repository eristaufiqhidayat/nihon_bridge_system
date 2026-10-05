@extends('layouts.app')
@section('title', 'Kelas Saya')
@section('crumb')<b>Kelas Saya</b>@endsection

@php
  use App\Support\Catalog;
  use App\Support\Fmt;
@endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Kelas {{ $class->kode }}@if ($class->nama) · {{ $class->nama }}@endif</h2>
    <p>{{ $isInstructor && $class->wali_id === auth()->id() ? 'Anda wali kelas ini' : 'Wali kelas: ' . ($class->wali?->sensei_name ?? '–') }} · {{ $class->students->count() }} peserta · {{ $class->batch?->nama ?? 'Angkatan –' }} · Ruang {{ $class->ruang }} · Periode {{ $class->periode }}</p></div>
    @if ($isInstructor)<a class="bt solid" href="{{ route('monitoring.index', ['kelas' => $class->id]) }}">👥 Monitoring peserta kelas</a>@endif
  </div>

  <div class="card mb16" style="padding:0">
    <div class="pad" style="padding-bottom:6px"><div class="sh"><h4>Jadwal minggu ini</h4><span class="small muted">{{ Fmt::date($dates->first()) }} – {{ Fmt::date($dates->last()) }}</span></div></div>
    <div class="tbl-wrap"><table class="sched"><thead><tr><th>Waktu</th>@foreach ($dates as $d)<th>{{ Fmt::dayShort($d) }}</th>@endforeach</tr></thead>
    <tbody>
      @foreach (Catalog::SLOTS as $si => $w)
        <tr><td class="tnum"><b>{{ $w }}</b></td>
          @foreach (Catalog::DAYS as $di => $_)
            <?php $x = $grid[$si][$di]; ?>
            <td>@if ($x)<span class="slot {{ $x->subject_bg }}">{{ $x->subject_name }}<br><span style="font-weight:500">{{ $x->instructor->sensei_name }}</span></span>@else<span class="small muted">–</span>@endif</td>
          @endforeach
        </tr>
      @endforeach
    </tbody></table></div>
  </div>

  <div class="grid g3">
    <div class="card pad">
      <h4 class="ct">{{ $isInstructor ? 'Kehadiran kelas' : 'Kehadiran saya' }}</h4>
      @if ($isInstructor)
        <p style="font-size:26px;font-weight:900;margin:0" class="tnum">{{ $attendance['avg'] }}%</p><p class="small muted" style="margin:0 0 12px">Rata-rata kehadiran · {{ $class->students->count() }} peserta</p>
        @forelse ($attendance['low'] as $l)
          <div class="list-row"><div class="ic ic-bg-orange">!</div><div class="info"><p>{{ $l['name'] }} · {{ $l['pct'] }}%</p><span>Di bawah batas {{ config('nihonbridge.attendance_min') }}%</span></div></div>
        @empty
          <p class="small muted">Semua peserta di atas batas {{ config('nihonbridge.attendance_min') }}%.</p>
        @endforelse
      @else
        <p style="font-size:26px;font-weight:900;margin:0" class="tnum">{{ $attendance['persen'] }}%</p><p class="small muted" style="margin:0 0 12px">{{ $attendance['hadir'] }} dari {{ $attendance['total'] }} sesi</p>
        @foreach (['I' => ['📝', 'Izin'], 'S' => ['🤒', 'Sakit'], 'A' => ['⚠️', 'Alpa']] as $k => [$ic, $label])
          @if (isset($attendance['detail'][$k]))
            <div class="list-row"><div class="ic ic-bg-grey">{{ $ic }}</div><div class="info"><p>{{ $label }} · {{ $attendance['detail'][$k]->count() }} sesi</p><span>{{ $attendance['detail'][$k]->map(fn ($a) => Fmt::date($a->session->date))->map(fn ($d) => preg_replace('/ \d{4}$/', '', $d))->implode(', ') }}</span></div></div>
          @endif
        @endforeach
      @endif
    </div>
    <div class="card pad">
      <h4 class="ct">Pengumuman</h4>
      @forelse ($announcements as $a)
        <div class="list-row"><div class="ic {{ $a->color }}">{{ $a->icon }}</div><div class="info"><p>{{ $a->title }}</p><span>{{ $a->body }}</span></div></div>
      @empty
        <div class="empty">Belum ada pengumuman.</div>
      @endforelse
    </div>
    <div class="card pad">
      <h4 class="ct">Anggota kelas</h4>
      <div class="avatars">
        @foreach ($class->students->take(8) as $s)<div class="av sm" title="{{ $s->name }}">{{ $s->initials }}</div>@endforeach
        @if ($class->students->count() > 8)<div class="av sm" style="background:var(--tint-grey);color:var(--ink-soft)">+{{ $class->students->count() - 8 }}</div>@endif
      </div>
      @if ($instructors->isNotEmpty())<p class="small muted" style="margin:12px 0 0">Instruktur: {{ $instructors->implode(', ') }}.</p>@endif
    </div>
  </div>
</div>
@endsection
