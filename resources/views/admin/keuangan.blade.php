@extends('layouts.app')
@section('title', 'Keuangan')
@section('crumb')<b>Keuangan</b>@endsection

@php
  use App\Support\Catalog;
  use App\Support\Fmt;
@endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Keuangan</h2><p>Biaya pelatihan &amp; proses: {{ Fmt::rupiah($fee['total']) }}, {{ $fee['installments'] }} cicilan {{ Fmt::rupiah($fee['per']) }}. {{ $rows->count() }} peserta.</p></div>
    @unless ($readonly)<button class="bt solid" type="button" data-modal-open="payModal">+ Catat pembayaran</button>@endunless</div>
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
        <form method="POST" action="{{ route('keuangan.verify', $first['pending']) }}" class="inline" data-confirm-title="Verifikasi bukti transfer {{ $first['student']->name }}?" data-confirm="Cicilan ke-{{ $first['pending']->installment_no }} sebesar {{ Fmt::rupiah($first['pending']->amount) }}. Pastikan dana sudah masuk ke rekening LPK." data-confirm-ok="Dana sudah masuk">@csrf<button class="bt solid" type="submit">Verifikasi {{ explode(' ', $first['student']->name)[0] }}</button></form>
      </span></div>
  @endif
  <div class="card" style="padding:0"><div class="tbl-wrap"><table><thead><tr><th>Peserta</th><th>Kelas</th><th>Cicilan dibayar</th><th>Sisa</th><th>Status</th>@unless ($readonly)<th></th>@endunless</tr></thead><tbody>
    @foreach ($rows as $r)
      <?php $s = $r['student']; ?>
      <tr><td><b>{{ $s->name }}</b><br><span class="small muted">{{ $s->nis }}</span></td><td>{{ $s->classroom?->kode ?? '–' }}</td>
        <td><div class="minibar"><div class="progress-track"><div class="progress-fill" style="width:{{ Fmt::pct($r['paid'], $fee['installments']) }}%"></div></div><span class="small tnum">{{ $r['paid'] }}/{{ $fee['installments'] }}</span></div></td>
        <td class="tnum">{{ Fmt::rupiah(($fee['installments'] - $r['paid']) * $fee['per']) }}</td>
        <td>@if ($r['paid'] >= $fee['installments'])<span class="badge b-green">Lunas</span>@elseif ($r['pending'])<span class="badge b-orange">Menunggu verifikasi</span>@elseif ($r['overdue'])<span class="badge b-red">Menunggak {{ $r['overdue'] }} cicilan</span>@else<span class="badge b-blue">Lancar</span>@endif</td>
        @unless ($readonly)
          <td>@if ($r['paid'] < $fee['installments'] && $r['overdue'])<form method="POST" action="{{ route('keuangan.remind', $s) }}" class="inline">@csrf<button class="mini-btn" type="submit">Kirim pengingat</button></form>@endif</td>
        @endunless
      </tr>
    @endforeach
  </tbody></table></div></div>
</div>
@endsection

@unless ($readonly)
@push('modals')
<x-modal id="payModal" title="Catat pembayaran">
  <form method="POST" action="{{ route('keuangan.record') }}">@csrf <input type="hidden" name="_modal" value="payModal">
    <x-form-errors modal="payModal" />
    <div class="opt-grid">
      <div class="field"><label for="pyS">Peserta</label><select id="pyS" name="student_id">@foreach ($rows->filter(fn ($r) => $r['paid'] < $fee['installments']) as $r)<option value="{{ $r['student']->id }}">{{ $r['student']->name }} (cicilan {{ $r['paid'] + 1 }})</option>@endforeach</select></div>
      <div class="field"><label for="pyM">Metode</label><select id="pyM" name="method">@foreach (Catalog::PAYMENT_METHODS as $m)<option>{{ $m }}</option>@endforeach</select></div>
      <div class="field"><label for="pyJ">Jumlah</label><input id="pyJ" value="{{ Fmt::rupiah($fee['per']) }}" readonly></div>
      <div class="field"><label for="pyT">Tanggal</label><input id="pyT" name="paid_at" type="date" value="{{ now()->toDateString() }}"></div></div>
    <div class="modal-actions"><button type="button" class="cancel" data-modal-close>Batal</button><button class="ok" type="submit">Simpan &amp; kirim kuitansi</button></div>
  </form>
</x-modal>
@endpush
@endunless
