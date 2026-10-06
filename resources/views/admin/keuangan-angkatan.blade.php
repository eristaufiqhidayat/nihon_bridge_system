@extends('layouts.app')
@section('title', 'Keuangan · ' . ($batch?->nama ?? 'Tanpa angkatan'))
@section('crumb')<a href="{{ route('keuangan.index') }}">Keuangan</a> › <b>{{ $batch?->nama ?? 'Tanpa angkatan' }}</b>@endsection

@php
  use App\Support\Fmt;
@endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>{{ $batch?->nama ?? 'Peserta tanpa angkatan' }}</h2>
    <p>{{ $rows->count() }} peserta.
      @if ($batch && $batch->installments->isNotEmpty())
        {{ $batch->installments->count() }} tahap pembayaran, jatuh tempo {{ $batch->installments->map(fn ($t) => Fmt::date($t->jatuh_tempo))->implode(', ') }}.
      @else
        Memakai jadwal pembayaran bawaan karena tahapan angkatan belum diatur.
      @endif</p></div>
    <a class="bt outline" href="{{ route('keuangan.index') }}">‹ Kembali ke rekap</a></div>
  <x-form-errors />
  @include('admin.partials.keuangan-stats')
  <div class="card" style="padding:0"><div class="tbl-wrap"><table><thead><tr><th>Peserta</th><th>Kelas</th><th>Tahap dibayar</th><th>Total tagihan</th><th>Sudah dibayar</th><th>Sisa</th><th>Status</th>@unless ($readonly)<th></th>@endunless</tr></thead><tbody>
    @forelse ($rows as $r)
      <?php $s = $r['student']; ?>
      <tr><td><b>{{ $s->name }}</b><br><span class="small muted">{{ $s->nis }}</span></td><td>{{ $s->classroom?->kode ?? '–' }}</td>
        <td><div class="minibar"><div class="progress-track"><div class="progress-fill" style="width:{{ Fmt::pct(min($r['paid'], $r['stages']), $r['stages']) }}%"></div></div><span class="small tnum">{{ $r['paid'] }}/{{ $r['stages'] }}</span></div></td>
        <td class="tnum">{{ Fmt::rupiah($r['total']) }}</td>
        <td class="tnum">{{ Fmt::rupiah($r['paidAmount']) }}</td>
        <td class="tnum">{{ Fmt::rupiah($r['remaining']) }}</td>
        <td>@if ($r['paid'] >= $r['stages'])<span class="badge b-green">Lunas</span>@elseif ($r['pending'])<span class="badge b-orange">Menunggu verifikasi</span>@elseif ($r['overdue'])<span class="badge b-red">Menunggak {{ $r['overdue'] }} tahap</span>@else<span class="badge b-blue">Lancar</span>@endif</td>
        @unless ($readonly)
          <td>@if ($r['paid'] < $r['stages'] && $r['overdue'])<form method="POST" action="{{ route('keuangan.remind', $s) }}" class="inline">@csrf<button class="mini-btn" type="submit">Kirim pengingat</button></form>@endif</td>
        @endunless
      </tr>
    @empty
      <tr><td colspan="8" class="empty">Belum ada peserta di angkatan ini.</td></tr>
    @endforelse
  </tbody></table></div></div>
</div>
@endsection
