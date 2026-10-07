{{-- Laporan keuangan bulanan: pemasukan, pengeluaran, laba, cashflow. $fin dari FinanceReportService::report(); $keep = query lain yang dipertahankan filter. --}}
<?php
  use App\Support\Fmt;
  $rp = fn (int $v) => $v < 0 ? '−' . Fmt::rupiah(abs($v)) : Fmt::rupiah($v);
  $neg = fn (int $v) => $v < 0 ? 'color:var(--red)' : '';
  $cur = $fin['cur']; $prev = $fin['prev']; $lr = $fin['labaRugi'];
  $bulan = Fmt::monthName($fin['month']) . ' ' . $fin['year'];
  $chip = function (int $v, ?int $pv) {
      if ($pv === null || $pv === 0) return '';
      $d = (int) round(($v - $pv) / abs($pv) * 100);
      return $d > 0 ? "<span class=\"trend up\">↑ {$d}%</span>" : ($d < 0 ? '<span class="trend down">↓ ' . abs($d) . '%</span>' : '<span class="trend flat">→ 0%</span>');
  };

  // Grafik: batang pemasukan & pengeluaran per bulan, garis saldo kas akhir.
  $W = 640; $H = 220; $L = 58; $B = 24; $T = 14; $R = 10;
  $vals = $fin['months']->flatMap(fn ($r) => [$r['pemasukan'], $r['pengeluaran'], $r['saldo_akhir']]);
  $hi = max(1, $vals->max()); $lo = min(0, $vals->min());
  $step = 10 ** max(0, strlen((string) (int) (($hi - $lo) / 4)) - 1);
  $step = (int) ceil(($hi - $lo) / 4 / $step) * $step ?: 1;
  $hi = (int) ceil($hi / $step) * $step; $lo = (int) floor($lo / $step) * $step;
  $y = fn ($v) => $T + ($H - $T - $B) * ($hi - $v) / max(1, $hi - $lo);
  $cw = ($W - $L - $R) / 12; $bw = $cw * .32;
  $short = fn (int $v) => abs($v) >= 1e9 ? round($v / 1e9, 1) . ' M' : (abs($v) >= 1e6 ? round($v / 1e6, 1) . ' jt' : (abs($v) >= 1e3 ? round($v / 1e3) . ' rb' : $v));
  $line = $fin['months']->map(fn ($r, $i) => round($L + $cw * $i + $cw / 2, 1) . ',' . round($y($r['saldo_akhir']), 1))->implode(' ');
?>
<section class="mb16" id="laporan-keuangan">
  <div class="sh" style="flex-wrap:wrap"><div><h3 style="margin:0">Laporan keuangan bulanan</h3>
    <p class="small muted" style="margin:2px 0 0">Basis kas: pemasukan dari pembayaran peserta yang lunas, pengeluaran dari bukti kas keluar.</p></div>
    <form method="GET" action="#laporan-keuangan" class="filt" style="margin:0">
      @foreach ($keep ?? [] as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
      <select name="kbulan" aria-label="Bulan laporan keuangan" data-autosubmit>@foreach (range(1, 12) as $i)<option value="{{ $i }}" @selected($i === $fin['month'])>{{ Fmt::monthName($i) }}</option>@endforeach</select>
      <select name="ktahun" aria-label="Tahun laporan keuangan" data-autosubmit>@foreach ($fin['years'] as $o)<option value="{{ $o }}" @selected($o === $fin['year'])>{{ $o }}</option>@endforeach</select>
    </form></div>

  <div class="grid g4 mb16">
    <div class="card stat-card2"><div class="top"><div class="ic2 ic-bg-green">⬇</div>{!! $chip($cur['pemasukan'], $prev['pemasukan'] ?? null) !!}</div><h4 class="tnum">{{ $rp($cur['pemasukan']) }}</h4><p>Pemasukan · {{ $bulan }}</p></div>
    <div class="card stat-card2"><div class="top"><div class="ic2 ic-bg-orange">⬆</div>{!! $chip($cur['pengeluaran'], $prev['pengeluaran'] ?? null) !!}</div><h4 class="tnum">{{ $rp($cur['pengeluaran']) }}</h4><p>Pengeluaran · {{ $bulan }}</p></div>
    <div class="card stat-card2"><div class="top"><div class="ic2 ic-bg-blue">📊</div></div><h4 class="tnum" style="{{ $neg($cur['laba']) }}">{{ $rp($cur['laba']) }}</h4><p>{{ $cur['laba'] < 0 ? 'Rugi' : 'Laba' }} bersih · {{ $bulan }}</p></div>
    <div class="card stat-card2"><div class="top"><div class="ic2 ic-bg-purple">💰</div></div><h4 class="tnum" style="{{ $neg($cur['saldo_akhir']) }}">{{ $rp($cur['saldo_akhir']) }}</h4><p>Saldo kas akhir · awal {{ $rp($cur['saldo_awal']) }}</p></div>
  </div>

  <div class="grid g2 mb16" style="align-items:start">
    <div class="card chart-wrap"><h4>Pemasukan, pengeluaran &amp; saldo kas {{ $fin['year'] }}</h4><p class="sub">Batang per bulan, garis = saldo kas akhir bulan</p>
      <svg viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="Grafik pemasukan, pengeluaran, dan saldo kas per bulan {{ $fin['year'] }}">
        @for ($t = $lo; $t <= $hi; $t += $step)
          <line x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $y($t) }}" y2="{{ $y($t) }}" stroke="{{ $t === 0 ? '#c9c5b9' : '#ece9e0' }}"/><text x="{{ $L - 6 }}" y="{{ $y($t) + 3 }}" text-anchor="end" font-size="9" fill="#5b6478">{{ $short($t) }}</text>
        @endfor
        @foreach ($fin['months'] as $i => $r)
          <?php $x = $L + $cw * $i + $cw / 2; $sel = $r['month'] === $fin['month']; ?>
          @if ($sel)<rect x="{{ $x - $cw / 2 }}" y="{{ $T }}" width="{{ $cw }}" height="{{ $H - $T - $B }}" fill="#f6f4ee"/>@endif
          <rect x="{{ $x - $bw - 1 }}" y="{{ $y($r['pemasukan']) }}" width="{{ $bw }}" height="{{ $y(0) - $y($r['pemasukan']) }}" rx="2" fill="#3f9c6d"><title>Pemasukan {{ Fmt::monthName($r['month']) }}: {{ $rp($r['pemasukan']) }}</title></rect>
          <rect x="{{ $x + 1 }}" y="{{ $y($r['pengeluaran']) }}" width="{{ $bw }}" height="{{ $y(0) - $y($r['pengeluaran']) }}" rx="2" fill="#e08a3c"><title>Pengeluaran {{ Fmt::monthName($r['month']) }}: {{ $rp($r['pengeluaran']) }}</title></rect>
          <text x="{{ $x }}" y="{{ $H - 8 }}" text-anchor="middle" font-size="10" fill="{{ $sel ? '#1a2436' : '#5b6478' }}" font-weight="{{ $sel ? 700 : 400 }}">{{ Fmt::monthName($r['month'], true) }}</text>
        @endforeach
        <polyline points="{{ $line }}" fill="none" stroke="#3f7fc0" stroke-width="2" stroke-linejoin="round"/>
        @foreach ($fin['months'] as $i => $r)<circle cx="{{ $L + $cw * $i + $cw / 2 }}" cy="{{ $y($r['saldo_akhir']) }}" r="2.5" fill="#fff" stroke="#3f7fc0" stroke-width="2"><title>Saldo akhir {{ Fmt::monthName($r['month']) }}: {{ $rp($r['saldo_akhir']) }}</title></circle>@endforeach
      </svg>
      <div class="legend-row"><span><span class="sw" style="background:#3f9c6d"></span>Pemasukan</span><span><span class="sw" style="background:#e08a3c"></span>Pengeluaran</span><span><span class="sw" style="background:#3f7fc0"></span>Saldo kas</span></div></div>

    <div class="card pad"><h4 class="ct">Laba rugi · {{ $bulan }}</h4>
      <div class="tbl-wrap"><table><tbody>
        <tr><td><b>Pendapatan jasa pelatihan</b><br><span class="small muted">Pembayaran peserta (lunas)</span></td><td class="tnum" style="text-align:right"><b>{{ $rp($lr['pendapatan']) }}</b></td></tr>
        @foreach ($lr['groups'] as $prefix => $g)
          <tr><td><b>{{ $g['label'] }}</b></td><td class="tnum" style="text-align:right">{{ $g['total'] ? '(' . $rp($g['total']) . ')' : $rp(0) }}</td></tr>
          @foreach ($g['accounts'] as $label => $v)<tr><td class="small muted" style="padding-left:28px">{{ $label }}</td><td class="tnum small muted" style="text-align:right">{{ $rp($v) }}</td></tr>@endforeach
          @if ($prefix === '5')<tr style="background:#faf9f5"><td><b>Laba kotor</b></td><td class="tnum" style="text-align:right;{{ $neg($lr['laba_kotor']) }}"><b>{{ $rp($lr['laba_kotor']) }}</b></td></tr>@endif
          @if ($prefix === '6')<tr style="background:#faf9f5"><td><b>Laba operasional</b></td><td class="tnum" style="text-align:right;{{ $neg($lr['laba_operasional']) }}"><b>{{ $rp($lr['laba_operasional']) }}</b></td></tr>@endif
        @endforeach
        <tr style="background:#f6f4ee"><td><b>{{ $lr['laba_bersih'] < 0 ? 'Rugi' : 'Laba' }} bersih</b></td><td class="tnum" style="text-align:right;{{ $neg($lr['laba_bersih']) }}"><b>{{ $rp($lr['laba_bersih']) }}</b></td></tr>
      </tbody></table></div></div>
  </div>

  <div class="grid g2 mb16" style="align-items:start">
    <div class="card pad"><h4 class="ct">Arus kas · {{ $bulan }}</h4>
      <div class="tbl-wrap"><table><tbody>
        <tr style="background:#faf9f5"><td><b>Saldo kas awal</b></td><td class="tnum" style="text-align:right;{{ $neg($cur['saldo_awal']) }}"><b>{{ $rp($cur['saldo_awal']) }}</b></td></tr>
        <tr><td><b>Kas masuk</b></td><td class="tnum" style="text-align:right;color:var(--green)"><b>+{{ $rp($cur['pemasukan']) }}</b></td></tr>
        @foreach ($fin['incomeByMethod'] as $label => $v)<tr><td class="small muted" style="padding-left:28px">{{ $label }}</td><td class="tnum small muted" style="text-align:right">{{ $rp($v) }}</td></tr>@endforeach
        <tr><td><b>Kas keluar</b></td><td class="tnum" style="text-align:right;color:var(--orange)"><b>−{{ $rp($cur['pengeluaran']) }}</b></td></tr>
        @foreach ($fin['outByCash'] as $label => $v)<tr><td class="small muted" style="padding-left:28px">{{ $label }}</td><td class="tnum small muted" style="text-align:right">{{ $rp($v) }}</td></tr>@endforeach
        <tr><td><b>Arus kas bersih</b></td><td class="tnum" style="text-align:right;{{ $neg($cur['laba']) }}"><b>{{ $rp($cur['laba']) }}</b></td></tr>
        <tr style="background:#f6f4ee"><td><b>Saldo kas akhir</b></td><td class="tnum" style="text-align:right;{{ $neg($cur['saldo_akhir']) }}"><b>{{ $rp($cur['saldo_akhir']) }}</b></td></tr>
      </tbody></table></div>
      <p class="small muted" style="margin:10px 0 0">Saldo awal = akumulasi seluruh pemasukan dikurangi pengeluaran sebelum {{ Fmt::monthName($fin['month']) }} {{ $fin['year'] }}.</p></div>

    <div class="card pad"><h4 class="ct">Pemasukan per angkatan · {{ $bulan }}</h4>
      @if ($fin['incomeByBatch']->isEmpty())<div class="empty">Belum ada pembayaran lunas di bulan ini.</div>
      @else<div class="tbl-wrap"><table><thead><tr><th>Angkatan</th><th style="text-align:right">Cicilan</th><th style="text-align:right">Jumlah</th></tr></thead><tbody>
        @foreach ($fin['incomeByBatch'] as $label => $r)<tr><td>{{ $label }}</td><td class="tnum" style="text-align:right">{{ $r['n'] }}</td><td class="tnum" style="text-align:right">{{ $rp($r['total']) }}</td></tr>@endforeach
      </tbody></table></div>@endif</div>
  </div>

  <div class="card" style="padding:0">
    <div class="pad" style="padding-bottom:6px"><div class="sh"><h4>Rekap bulanan {{ $fin['year'] }}</h4><span class="small muted">Klik bulan untuk melihat rinciannya</span></div></div>
    <div class="tbl-wrap"><table><thead><tr><th>Bulan</th><th style="text-align:right">Pemasukan</th><th style="text-align:right">Pengeluaran</th><th style="text-align:right">Laba / rugi</th><th style="text-align:right">Saldo awal</th><th style="text-align:right">Saldo akhir</th></tr></thead><tbody>
      @foreach ($fin['months'] as $r)
        <tr @if ($r['month'] === $fin['month']) style="background:#faf9f5" @endif><td><a class="linkbtn" href="?{{ http_build_query(($keep ?? []) + ['ktahun' => $fin['year'], 'kbulan' => $r['month']]) }}#laporan-keuangan">{!! $r['month'] === $fin['month'] ? '<b>' . Fmt::monthName($r['month']) . '</b>' : Fmt::monthName($r['month']) !!}</a></td>
          <td class="tnum" style="text-align:right">{{ $rp($r['pemasukan']) }}</td><td class="tnum" style="text-align:right">{{ $rp($r['pengeluaran']) }}</td>
          <td class="tnum" style="text-align:right;{{ $neg($r['laba']) }}">{{ $rp($r['laba']) }}</td><td class="tnum" style="text-align:right;{{ $neg($r['saldo_awal']) }}">{{ $rp($r['saldo_awal']) }}</td><td class="tnum" style="text-align:right;{{ $neg($r['saldo_akhir']) }}">{{ $rp($r['saldo_akhir']) }}</td></tr>
      @endforeach
      <tr style="background:#f6f4ee"><td><b>Total {{ $fin['year'] }}</b></td><td class="tnum" style="text-align:right"><b>{{ $rp($fin['totals']['pemasukan']) }}</b></td><td class="tnum" style="text-align:right"><b>{{ $rp($fin['totals']['pengeluaran']) }}</b></td>
        <td class="tnum" style="text-align:right;{{ $neg($fin['totals']['laba']) }}"><b>{{ $rp($fin['totals']['laba']) }}</b></td><td class="tnum" style="text-align:right"><b>{{ $rp($fin['totals']['saldo_awal']) }}</b></td><td class="tnum" style="text-align:right;{{ $neg($fin['totals']['saldo_akhir']) }}"><b>{{ $rp($fin['totals']['saldo_akhir']) }}</b></td></tr>
    </tbody></table></div>
  </div>
</section>
