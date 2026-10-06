@extends('layouts.app')
@section('title', 'Pembayaran')
@section('crumb')<b>Pembayaran</b>@endsection

@php use App\Support\Fmt; @endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Pembayaran</h2><p>Biaya pelatihan &amp; proses keberangkatan: {{ Fmt::rupiah($total) }} dalam {{ $schedule->count() }} tahap{{ $student->batch ? ' (' . $student->batch->nama . ')' : '' }}.</p></div></div>
  <x-form-errors />
  <div class="grid g2">
    <div class="card" style="padding:0"><div class="tbl-wrap"><table><thead><tr><th>Tahap</th><th>Jatuh tempo</th><th>Jumlah</th><th>Status</th></tr></thead><tbody>
      @foreach ($schedule as $i => $t)
        <tr><td>Ke-{{ $t['no'] }}</td><td class="tnum">{{ Fmt::date($t['due']) }}</td><td class="tnum">{{ Fmt::rupiah($t['amount']) }}</td>
          <td>@if ($i < $paid)<span class="badge b-green">Lunas</span>@elseif ($i === $paid && $pending)<span class="badge b-orange">Menunggu verifikasi</span>@elseif ($i === $paid)<span class="badge b-blue">Tagihan berikutnya</span>@else<span class="badge b-grey">Belum</span>@endif</td></tr>
      @endforeach
    </tbody></table></div></div>
    <div class="card pad">
      @if (! $next)
        <div class="empty-state"><div class="ic">🎉</div><b>Semua tahap pembayaran lunas</b></div>
      @else
        <h4 class="ct">Tagihan berikutnya: tahap ke-{{ $next['no'] }}</h4>
        <p style="font-size:24px;font-weight:900;margin:0" class="tnum">{{ Fmt::rupiah($next['amount']) }}</p><p class="small muted" style="margin:0 0 14px">Jatuh tempo {{ Fmt::date($next['due']) }}</p>
        <div class="callout"><b>Transfer ke Virtual Account</b><br><span class="tnum" style="font-size:17px;font-weight:700">{{ $va }}</span> <button class="linkbtn" type="button" data-copy="{{ str_replace(' ', '', $va) }}">Salin</button><br><span class="small">{{ $fee['bank'] }} · a.n. {{ config('nihonbridge.org.name') }}</span></div>
        @if ($pending)
          <p class="rule-note">Bukti transfer sudah dikirim {{ Fmt::date($pending->paid_at) }}. Admin memverifikasi dalam 1 hari kerja.</p>
        @else
          <form method="POST" action="{{ route('tagihan.upload') }}" enctype="multipart/form-data">
            @csrf
            <label class="bt outline file-btn" style="margin-top:14px;display:inline-flex">📎 Unggah bukti transfer<input type="file" name="bukti" accept="image/*,.pdf" data-autosubmit></label>
          </form>
        @endif
      @endif
    </div>
  </div>
</div>
@endsection
