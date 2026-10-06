<div class="grid g3 mb16">
  <div class="card stat-mini"><div class="ic ic-bg-blue">🧾</div><div><p>Total tagihan</p><h4 class="tnum">{{ App\Support\Fmt::rupiah($total) }}</h4></div></div>
  <div class="card stat-mini"><div class="ic ic-bg-green">✅</div><div><p>Sudah dibayar</p><h4 class="tnum">{{ App\Support\Fmt::rupiah($paid) }} <small>{{ App\Support\Fmt::pct($paid, $total) }}%</small></h4></div></div>
  <div class="card stat-mini"><div class="ic ic-bg-orange">⏰</div><div><p>Tunggakan (lewat jatuh tempo)</p><h4 class="tnum" style="color:{{ $tunggakan ? 'var(--red)' : 'inherit' }}">{{ App\Support\Fmt::rupiah($tunggakan) }}</h4></div></div>
</div>
