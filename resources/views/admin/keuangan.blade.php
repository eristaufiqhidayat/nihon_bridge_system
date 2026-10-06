@extends('layouts.app')
@section('title', 'Keuangan')
@section('crumb')<b>Keuangan</b>@endsection

@php
  use App\Support\Fmt;
@endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Keuangan</h2><p>{{ $rows->count() }} peserta. Tahapan pembayaran dan jatuh tempo mengikuti master angkatan masing-masing peserta.</p></div>
    @unless ($readonly)<a class="bt solid" href="{{ route('pembayaran-admin.index') }}">🧾 Entri pembayaran</a>@endunless</div>
  <x-form-errors />
  <div class="grid g3 mb16">
    <div class="card stat-mini"><div class="ic ic-bg-blue">🧾</div><div><p>Total tagihan</p><h4 class="tnum">{{ Fmt::rupiah($total) }}</h4></div></div>
    <div class="card stat-mini"><div class="ic ic-bg-green">✅</div><div><p>Sudah dibayar</p><h4 class="tnum">{{ Fmt::rupiah($paid) }} <small>{{ Fmt::pct($paid, $total) }}%</small></h4></div></div>
    <div class="card stat-mini"><div class="ic ic-bg-orange">⏰</div><div><p>Tunggakan (lewat jatuh tempo)</p><h4 class="tnum" style="color:{{ $tunggakan ? 'var(--red)' : 'inherit' }}">{{ Fmt::rupiah($tunggakan) }}</h4></div></div>
  </div>
  @if ($pendingRows->isNotEmpty() && ! $readonly)
    <?php $first = $pendingRows->first(); ?>
    <div class="running-banner"><span>📎 {{ $pendingRows->count() }} bukti transfer menunggu verifikasi: {{ $pendingRows->map(fn ($r) => $r['student']->name)->implode(', ') }}</span>
      <span class="btnrow">
        @if ($first['pending']->proof_path)<a class="bt outline" href="{{ route('keuangan.proof', $first['pending']) }}">Lihat bukti</a>@endif
        <form method="POST" action="{{ route('keuangan.verify', $first['pending']) }}" class="inline" data-confirm-title="Verifikasi bukti transfer {{ $first['student']->name }}?" data-confirm="Tahap ke-{{ $first['pending']->installment_no }} sebesar {{ Fmt::rupiah($first['pending']->amount) }}. Pastikan dana sudah masuk ke rekening LPK." data-confirm-ok="Dana sudah masuk">@csrf<button class="bt solid" type="submit">Verifikasi {{ explode(' ', $first['student']->name)[0] }}</button></form>
      </span></div>
  @endif
  <div class="card" style="padding:0"><div class="tbl-wrap"><table><thead><tr><th>Peserta</th><th>Kelas</th><th>Tahap dibayar</th><th>Sisa</th><th>Status</th>@unless ($readonly)<th></th>@endunless</tr></thead><tbody>
    @foreach ($rows as $r)
      <?php $s = $r['student']; ?>
      <tr><td><b>{{ $s->name }}</b><br><span class="small muted">{{ $s->nis }} · {{ $s->batch?->nama ?? 'Tanpa angkatan' }}</span></td><td>{{ $s->classroom?->kode ?? '–' }}</td>
        <td><div class="minibar"><div class="progress-track"><div class="progress-fill" style="width:{{ Fmt::pct(min($r['paid'], $r['stages']), $r['stages']) }}%"></div></div><span class="small tnum">{{ $r['paid'] }}/{{ $r['stages'] }}</span></div></td>
        <td class="tnum">{{ Fmt::rupiah($r['remaining']) }}</td>
        <td>@if ($r['paid'] >= $r['stages'])<span class="badge b-green">Lunas</span>@elseif ($r['pending'])<span class="badge b-orange">Menunggu verifikasi</span>@elseif ($r['overdue'])<span class="badge b-red">Menunggak {{ $r['overdue'] }} tahap</span>@else<span class="badge b-blue">Lancar</span>@endif</td>
        @unless ($readonly)
          <td>@if ($r['paid'] < $r['stages'] && $r['overdue'])<form method="POST" action="{{ route('keuangan.remind', $s) }}" class="inline">@csrf<button class="mini-btn" type="submit">Kirim pengingat</button></form>@endif</td>
        @endunless
      </tr>
    @endforeach
  </tbody></table></div></div>
</div>
@endsection
