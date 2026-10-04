@extends('layouts.app')
@section('title', 'Analisis Ujian')
@section('crumb')<b>Analisis Ujian</b>@endsection

@php use App\Support\Catalog; @endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Analisis Ujian</h2>
    <p>@if ($package){{ $package->judul }} · Kelas {{ $class?->kode ?? 'semua' }} · {{ $r['participants'] }} dari {{ $classSize }} peserta sudah mengerjakan @else Belum ada ujian yang dikerjakan. @endif</p></div>
    <form method="GET" class="filt" style="margin:0">
      <select name="paket" aria-label="Paket" data-autosubmit>@foreach ($packages as $p)<option value="{{ $p->id }}" @selected($p->id === $package?->id)>{{ $p->judul }}</option>@endforeach</select>
      <select name="kelas" aria-label="Kelas" data-autosubmit>@foreach ($classes as $c)<option value="{{ $c->id }}" @selected($c->id === $class?->id)>Kelas {{ $c->kode }}</option>@endforeach</select>
    </form>
  </div>

  @if (! $r || ! $r['participants'])
    <div class="card"><div class="empty-state"><div class="ic">📊</div><b>Belum ada data</b><p>Analisis muncul setelah peserta kelas ini menyelesaikan paket ujian.</p></div></div>
  @else
    <?php $maxB = max(1, max(array_column($r['bins'], 1))); ?>
    <?php $scale = 104 / $maxB; ?>
    <div class="grid g2 mb16">
      <div class="card chart-wrap"><h4>Sebaran nilai</h4><p class="sub">Jumlah peserta per rentang nilai · {{ $r['below'] }} peserta di bawah 60 (belum lulus)</p>
        <svg viewBox="0 0 320 170" role="img" aria-label="Histogram sebaran nilai">
          @foreach (array_unique([0, (int) ceil($maxB / 3), (int) ceil(2 * $maxB / 3), $maxB]) as $t)
            <line x1="30" x2="316" y1="{{ 140 - $t * $scale }}" y2="{{ 140 - $t * $scale }}" stroke="#ece9e0"/><text x="24" y="{{ 143 - $t * $scale }}" text-anchor="end" font-size="9" fill="#5b6478">{{ $t }}</text>
          @endforeach
          @foreach ($r['bins'] as $i => [$l, $n])
            <rect x="{{ 44 + $i * 68 }}" y="{{ 140 - $n * $scale }}" width="48" height="{{ $n * $scale }}" rx="4" fill="{{ $i < 2 ? 'var(--orange)' : 'var(--green)' }}"/><text x="{{ 68 + $i * 68 }}" y="{{ 134 - $n * $scale }}" text-anchor="middle" font-size="10" font-weight="700" fill="#1a2436">{{ $n }}</text><text x="{{ 68 + $i * 68 }}" y="158" text-anchor="middle" font-size="10" fill="#5b6478">{{ $l }}</text>
          @endforeach
        </svg></div>
      <div class="card pad"><h4 class="ct">Rata-rata benar per bagian</h4>
        @foreach ($r['secAvg'] as $s => $v)
          @continue(! collect($r['items'])->contains(fn ($x) => $x['q']['section'] === $s))
          <div class="kompetensi-row"><div class="lbl">{{ Catalog::SECTIONS[$s]['name'] }}<span>{{ Catalog::SECTIONS[$s]['jp'] }}</span></div><div class="kbar"><span style="width:{{ $v }}%;background:{{ Catalog::SECTIONS[$s]['color'] }}"></span></div><div class="val tnum">{{ $v }}%</div></div>
        @endforeach
        <?php $h = $r['hard'][0]; ?>
        <div class="callout" style="margin-top:10px"><b>Saran:</b> soal no. {{ $h['i'] + 1 }} paling sulit ({{ $h['p'] }}% benar). {{ $h['dist'][$h['top']] }}% peserta memilih “{{ $h['q']['options'][$h['top']] }}”. Bahas di pertemuan {{ Catalog::sectionName($h['q']['section']) }} berikutnya.</div>
      </div>
    </div>
    <div class="grid g-admin">
      <div class="card" style="padding:0"><div class="pad" style="padding-bottom:6px"><h4 style="margin:0">Tingkat benar per soal <span class="small muted">(dari yang tersulit)</span></h4></div>
        <div class="tbl-wrap" style="max-height:420px;overflow:auto"><table><thead><tr><th>No.</th><th>Soal</th><th>Benar</th></tr></thead><tbody>
          @foreach ($r['hard'] as $x)
            <tr class="clickable {{ $x['i'] === $sel['i'] ? 'sel' : '' }}" onclick="location.href='{{ route('analisis.index', ['paket' => $package->id, 'kelas' => $class?->id, 'soal' => $x['i']]) }}'"><td class="tnum">{{ $x['i'] + 1 }}</td><td><div class="soal-text">{{ $x['q']['question'] }}</div><span class="badge {{ Catalog::SECTIONS[$x['q']['section']]['badge'] }}">{{ Catalog::sectionName($x['q']['section']) }}</span></td>
              <td><div class="minibar"><div class="progress-track"><div class="progress-fill" style="width:{{ $x['p'] }}%;background:{{ $x['p'] < 50 ? 'var(--red)' : ($x['p'] < 70 ? 'var(--orange)' : 'var(--green)') }}"></div></div><span class="small tnum">{{ $x['p'] }}%</span></div></td></tr>
          @endforeach
        </tbody></table></div></div>
      <div class="card pad"><h4 class="ct">Pilihan jawaban · soal no. {{ $sel['i'] + 1 }}</h4><p class="soal-text" style="margin:0 0 12px;font-weight:700">{{ $sel['q']['question'] }}</p>
        @foreach ($sel['q']['options'] as $j => $o)
          <div class="kompetensi-row" style="grid-template-columns:1fr 1.2fr 44px"><div class="lbl" style="font-family:'Noto Sans JP',sans-serif">{{ 'ABCD'[$j] }}. {{ $o }}@if ($j === $sel['q']['key']) <span class="badge b-green">kunci</span>@endif</div><div class="kbar"><span style="width:{{ $sel['dist'][$j] }}%;background:{{ $j === $sel['q']['key'] ? 'var(--green)' : ($j === $sel['top'] ? 'var(--red)' : 'var(--ink-soft)') }}"></span></div><div class="val tnum">{{ $sel['dist'][$j] }}%</div></div>
        @endforeach
        <p class="rule-note">Pengecoh yang dipilih banyak peserta menunjukkan salah konsep yang sama, bukan tebakan acak.</p></div>
    </div>
  @endif
</div>
@endsection
