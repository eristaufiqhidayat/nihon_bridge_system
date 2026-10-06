@extends('layouts.app')
@section('title', 'Keuangan')
@section('crumb')<b>Keuangan</b>@endsection

@php
  use App\Support\Fmt;
@endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Keuangan</h2><p>Rekap per angkatan, {{ $groups->sum('count') }} peserta. Tahapan pembayaran dan jatuh tempo mengikuti master angkatan.</p></div>
    @unless ($readonly)<a class="bt solid" href="{{ route('pembayaran-admin.index') }}">🧾 Entri pembayaran</a>@endunless</div>
  <x-form-errors />
  @include('admin.partials.keuangan-stats')
  @if ($pendingRows->isNotEmpty() && ! $readonly)
    <?php $first = $pendingRows->first(); ?>
    <div class="running-banner"><span>📎 {{ $pendingRows->count() }} bukti transfer menunggu verifikasi: {{ $pendingRows->map(fn ($r) => $r['student']->name)->implode(', ') }}</span>
      <span class="btnrow">
        @if ($first['pending']->proof_path)<a class="bt outline" href="{{ route('keuangan.proof', $first['pending']) }}">Lihat bukti</a>@endif
        <form method="POST" action="{{ route('keuangan.verify', $first['pending']) }}" class="inline" data-confirm-title="Verifikasi bukti transfer {{ $first['student']->name }}?" data-confirm="Tahap ke-{{ $first['pending']->installment_no }} sebesar {{ Fmt::rupiah($first['pending']->amount) }}. Pastikan dana sudah masuk ke rekening LPK." data-confirm-ok="Dana sudah masuk">@csrf<button class="bt solid" type="submit">Verifikasi {{ explode(' ', $first['student']->name)[0] }}</button></form>
      </span></div>
  @endif
  <div class="card" style="padding:0"><div class="tbl-wrap"><table><thead><tr><th>Angkatan</th><th>Peserta</th><th>Total tagihan</th><th>Sudah dibayar</th><th>Sisa</th><th>Tunggakan</th><th>Status</th><th></th></tr></thead><tbody>
    @forelse ($groups as $g)
      <?php $b = $g['batch']; ?>
      <tr><td><b>{{ $b?->nama ?? 'Tanpa angkatan' }}</b>@if ($b)<br><span class="small muted">{{ $b->kode }} · {{ $b->installments->count() ?: 'jadwal bawaan' }}{{ $b->installments->count() ? ' tahap' : '' }}</span>@endif</td>
        <td class="tnum">{{ $g['count'] }}</td>
        <td class="tnum">{{ Fmt::rupiah($g['total']) }}</td>
        <td><div class="minibar"><div class="progress-track"><div class="progress-fill" style="width:{{ Fmt::pct($g['paid'], $g['total']) }}%"></div></div><span class="small tnum">{{ Fmt::rupiah($g['paid']) }}</span></div></td>
        <td class="tnum">{{ Fmt::rupiah($g['sisa']) }}</td>
        <td class="tnum" style="color:{{ $g['tunggakan'] ? 'var(--red)' : 'inherit' }}">{{ Fmt::rupiah($g['tunggakan']) }}</td>
        <td class="small">@if ($g['lunas'])<span class="badge b-green">{{ $g['lunas'] }} lunas</span> @endif @if ($g['menunggak'])<span class="badge b-red">{{ $g['menunggak'] }} menunggak</span>@endif @if (! $g['lunas'] && ! $g['menunggak'])<span class="badge b-blue">Lancar</span>@endif</td>
        <td><a class="mini-btn" href="{{ route('keuangan.batch', $g['key']) }}">Lihat peserta</a></td></tr>
    @empty
      <tr><td colspan="8" class="empty">Belum ada peserta.</td></tr>
    @endforelse
    </tbody>
    @if ($groups->count() > 1)
      <tfoot><tr><th>Total</th><th class="tnum">{{ $groups->sum('count') }}</th><th class="tnum">{{ Fmt::rupiah($total) }}</th><th class="tnum">{{ Fmt::rupiah($paid) }}</th><th class="tnum">{{ Fmt::rupiah($sisa) }}</th><th class="tnum">{{ Fmt::rupiah($tunggakan) }}</th><th colspan="2"></th></tr></tfoot>
    @endif
  </table></div></div>
</div>
@endsection
