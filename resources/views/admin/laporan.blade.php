@extends('layouts.app')
@section('title', 'Laporan & Analitik')
@section('crumb')<b>Laporan &amp; Analitik</b>@endsection

@php
  use App\Services\ReportService;
  use App\Support\Catalog;
  use App\Support\Fmt;
  $chip = function (int $v, ?int $pv) {
      $d = ReportService::trendPct($v, $pv);
      if ($d === null) return '';
      return $d > 0 ? "<span class=\"trend up\">↑ {$d}%</span>" : ($d < 0 ? '<span class="trend down">↓ ' . abs($d) . '%</span>' : '<span class="trend flat">→ 0%</span>');
  };
  $kpis = [['👥', 'ic-bg-blue', 'peserta', 'Peserta aktif'], ['🏫', 'ic-bg-purple', 'kelas', 'Kelas aktif'], ['✅', 'ic-bg-green', 'lulus', 'Lulus JLPT'], ['✈️', 'ic-bg-orange', 'berangkat', 'Siap berangkat']];

  // Grafik batang: peserta per level
  $levels = $cur->levels ?? [];
  $W = 320; $H = 190; $L = 34; $B = 24; $T = 16;
  $max = max(50, (int) ceil(max($levels ?: [0]) / 50) * 50);
  $bw = ($W - $L - 10) / max(1, count($levels));
  $ys = fn ($v) => $T + ($H - $T - $B) * (1 - $v / $max);

  // Grafik garis: tingkat kelulusan
  $tYears = collect($trend)->flatMap(fn ($v) => array_keys($v))->unique()->sort()->values()->all();
  $R = 34; $LT = 12; $n = max(1, count($tYears) - 1);
  $xs = fn ($i) => $L + 10 + $i * (($W - $L - $R - 10) / $n);
  $yl = fn ($v) => $LT + ($H - $LT - $B) * (1 - $v / 100);
  $op = fn ($k) => $level === 'Semua' || $level === $k;
@endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Laporan &amp; Analitik</h2><p>Data per {{ Fmt::date(now()) }}{{ $year === now()->year ? ' (tahun berjalan)' : '' }}</p></div></div>
  <form method="GET" class="filt">
    <select name="tahun" aria-label="Tahun" data-autosubmit>@foreach ($years as $o)<option value="{{ $o }}" @selected($o === $year)>Tahun: {{ $o }}</option>@endforeach</select>
    <select name="level" aria-label="Level" data-autosubmit>@foreach (array_merge(['Semua'], Catalog::LEVELS) as $o)<option value="{{ $o }}" @selected($o === $level)>Sorot level: {{ $o }}</option>@endforeach</select>
    <a class="bt solid" style="margin-left:auto" href="{{ route('laporan.export', ['tahun' => $year]) }}">⬇ Ekspor laporan</a>
  </form>
  <div class="grid g4 mb16">
    @foreach ($kpis as [$ic, $bg, $key, $label])
      <div class="card stat-card2"><div class="top"><div class="ic2 {{ $bg }}">{{ $ic }}</div>{!! $chip($cur->$key, $prev?->$key) !!}</div><h4 class="tnum">{{ $cur->$key }}</h4><p>{{ $label }}@if ($prev) · vs {{ $year - 1 }}: {{ $prev->$key }}@endif</p></div>
    @endforeach
  </div>
  <div class="grid g2">
    <div class="card chart-wrap"><h4>Jumlah peserta per level JLPT</h4><p class="sub">{{ $year }} · total {{ $cur->peserta }} peserta</p>
      <svg viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="Grafik batang peserta per level">
        @foreach (range(0, $max, $max / 3) as $t)
          <line x1="{{ $L }}" x2="{{ $W }}" y1="{{ $ys($t) }}" y2="{{ $ys($t) }}" stroke="#ece9e0"/><text x="{{ $L - 6 }}" y="{{ $ys($t) + 3 }}" text-anchor="end" font-size="9" fill="#5b6478">{{ $t }}</text>
        @endforeach
        @foreach (array_keys($levels) as $i => $k)
          <?php $x = $L + 10 + $i * $bw; ?> <?php $w = $bw * .58; ?> <?php $v = $levels[$k]; ?>
          <g opacity="{{ $op($k) ? 1 : .3 }}"><rect x="{{ $x }}" y="{{ $ys($v) }}" width="{{ $w }}" height="{{ $ys(0) - $ys($v) }}" rx="4" fill="{{ Catalog::LV_COLOR[$k] }}"/><text x="{{ $x + $w / 2 }}" y="{{ $ys($v) - 4 }}" text-anchor="middle" font-size="10" font-weight="700" fill="#1a2436">{{ $v }}</text></g>
          <text x="{{ $x + $w / 2 }}" y="{{ $H - 8 }}" text-anchor="middle" font-size="10" fill="#5b6478">{{ $k }}</text>
        @endforeach
      </svg></div>
    <div class="card chart-wrap"><h4>Tingkat kelulusan JLPT per tahun</h4><p class="sub">Persentase peserta yang lulus ujian JLPT resmi, {{ reset($tYears) }}–{{ end($tYears) }}</p>
      <svg viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="Grafik garis tingkat kelulusan per tahun">
        @foreach ([0, 25, 50, 75, 100] as $t)
          <line x1="{{ $L }}" x2="{{ $W - $R + 10 }}" y1="{{ $yl($t) }}" y2="{{ $yl($t) }}" stroke="#ece9e0"/><text x="{{ $L - 6 }}" y="{{ $yl($t) + 3 }}" text-anchor="end" font-size="9" fill="#5b6478">{{ $t }}%</text>
        @endforeach
        @foreach ($tYears as $i => $yy)
          <text x="{{ $xs($i) }}" y="{{ $H - 8 }}" text-anchor="middle" font-size="10" fill="{{ $yy === $year ? '#1a2436' : '#5b6478' }}" font-weight="{{ $yy === $year ? 700 : 400 }}">{{ $yy }}</text>
        @endforeach
        @foreach ($trend as $k => $vals)
          <?php $pts = collect($tYears)->map(fn ($yy, $i) => isset($vals[$yy]) ? [$xs($i), $yl($vals[$yy]), $vals[$yy], $i] : null)->filter()->values(); ?>
          <?php $last = $pts->last(); ?>
          <g opacity="{{ $op($k) ? 1 : .2 }}"><polyline points="{{ $pts->map(fn ($p) => $p[0] . ',' . $p[1])->implode(' ') }}" fill="none" stroke="{{ Catalog::LV_COLOR[$k] }}" stroke-width="2.5" stroke-linejoin="round"/>
            @foreach ($pts as $p)<circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="{{ $p === $last ? 4 : 2.5 }}" fill="{{ $p === $last ? Catalog::LV_COLOR[$k] : '#fff' }}" stroke="{{ Catalog::LV_COLOR[$k] }}" stroke-width="2"/>@endforeach
            @if ($last)<text x="{{ $last[0] + 8 }}" y="{{ $last[1] + 3 }}" font-size="10" font-weight="700" fill="{{ Catalog::LV_COLOR[$k] }}">{{ $last[2] }}%</text>@endif</g>
        @endforeach
      </svg>
      <div class="legend-row">@foreach (array_keys($trend) as $k)<span style="opacity:{{ $op($k) ? 1 : .35 }}"><span class="sw" style="background:{{ Catalog::LV_COLOR[$k] }}"></span>{{ $k }}</span>@endforeach</div></div>
  </div>
</div>
@endsection
