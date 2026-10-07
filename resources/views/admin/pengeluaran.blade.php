@extends('layouts.app')
@section('title', 'Pengeluaran')
@section('crumb')<b>Pengeluaran</b>@endsection

@php use App\Support\Fmt; @endphp

@section('content')
<?php $filtered = $f['q'] !== '' || $f['bulan'] || $f['akun'] || $f['sumber']; ?>
<div class="page">
  <div class="page-head"><div><h2>Pengeluaran</h2><p>Bukti kas keluar per kode akun beban. Master akun diatur di <a class="linkbtn" href="{{ route('kode-akun.index') }}">Kode Akun</a>.</p></div>
    <a class="bt solid" href="{{ route('pengeluaran.create') }}">+ Catat pengeluaran</a></div>

  <form method="GET" class="filter-bar">
    <input name="q" type="search" placeholder="🔍 Cari nomor, uraian, atau penerima…" value="{{ $f['q'] }}" aria-label="Cari pengeluaran">
    <select name="bulan" aria-label="Bulan"><option value="">Semua bulan</option>@foreach ($months as $m => $label)<option value="{{ $m }}" @selected($f['bulan'] === $m)>{{ $label }}</option>@endforeach</select>
    <select name="akun" aria-label="Kode akun beban"><option value="">Semua akun beban</option>@foreach ($expenseAccounts as $a)<option value="{{ $a->id }}" @selected($f['akun'] === $a->id)>{{ $a->label }}</option>@endforeach</select>
    <select name="sumber" aria-label="Sumber dana"><option value="">Semua kas/bank</option>@foreach ($cashAccounts as $a)<option value="{{ $a->id }}" @selected($f['sumber'] === $a->id)>{{ $a->label }}</option>@endforeach</select>
    <button class="bt outline" type="submit">Terapkan</button>
    @if ($filtered)<a class="linkbtn" href="{{ route('pengeluaran.index') }}">Reset</a>@endif
  </form>

  <div class="grid g3 mb16">
    <div class="card stat-mini"><div class="ic ic-bg-orange">💸</div><div><p>Total pengeluaran{{ $f['bulan'] ? ' · ' . $months[$f['bulan']] : '' }}</p><h4 class="tnum">{{ Fmt::rupiah($total) }}</h4></div></div>
    <div class="card stat-mini"><div class="ic ic-bg-blue">🧾</div><div><p>Jumlah transaksi</p><h4 class="tnum">{{ $count }}</h4></div></div>
    <div class="card stat-mini"><div class="ic ic-bg-green">📒</div><div><p>Per kelompok</p><div class="small" style="line-height:1.6">@forelse ($perKelompok as $label => $sum){{ $label }}: <b class="tnum">{{ Fmt::rupiah($sum) }}</b>@if (! $loop->last)<br>@endif @empty – @endforelse</div></div></div>
  </div>

  <div class="card mb16" style="padding:0"><div class="tbl-wrap"><table>
    <thead><tr><th>Tanggal</th><th>No. bukti</th><th>Akun beban</th><th>Uraian</th><th>Sumber dana</th><th style="text-align:right">Jumlah</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse ($list as $e)
        <tr><td class="small" style="white-space:nowrap">{{ Fmt::date($e->tanggal) }}</td>
          <td class="tnum small" style="white-space:nowrap">{{ $e->nomor }}</td>
          <td class="small"><b class="tnum">{{ $e->account->kode }}</b><br>{{ $e->account->nama }}</td>
          <td>{{ $e->uraian }}@if ($e->penerima)<br><span class="small muted">Kepada: {{ $e->penerima }}</span>@endif</td>
          <td class="small">{{ $e->cashAccount->nama }}</td>
          <td class="tnum" style="text-align:right;white-space:nowrap">{{ Fmt::rupiah($e->jumlah) }}</td>
          <td style="white-space:nowrap">
            <a class="act-ic" href="{{ route('pengeluaran.edit', $e) }}" aria-label="Edit {{ $e->nomor }}">✏️</a>
            <form method="POST" action="{{ route('pengeluaran.destroy', $e) }}" class="inline" data-confirm-title="Hapus {{ $e->nomor }}?" data-confirm="Pengeluaran {{ Fmt::rupiah($e->jumlah) }} untuk {{ $e->uraian }} akan dihapus permanen." data-confirm-ok="Hapus pengeluaran" data-danger>@csrf @method('DELETE')<button class="act-ic" aria-label="Hapus {{ $e->nomor }}">🗑️</button></form>
          </td></tr>
      @empty
        <tr><td colspan="7" class="empty">@if ($filtered)Tidak ada pengeluaran yang cocok. <a class="linkbtn" href="{{ route('pengeluaran.index') }}">Reset filter</a>@else Belum ada pengeluaran. @endif</td></tr>
      @endforelse
    </tbody></table></div>
  <div class="pager"><span>Menampilkan {{ $list->firstItem() ?? 0 }}–{{ $list->lastItem() ?? 0 }} dari {{ $list->total() }} transaksi</span>
    @if ($list->lastPage() > 1)
    <div class="pg-btns">
      @if ($list->onFirstPage())<span class="pg">‹</span>@else<a href="{{ $list->previousPageUrl() }}" aria-label="Sebelumnya">‹</a>@endif
      @foreach (range(1, $list->lastPage()) as $p)<a class="{{ $p === $list->currentPage() ? 'cur' : '' }}" href="{{ $list->url($p) }}">{{ $p }}</a>@endforeach
      @if ($list->hasMorePages())<a href="{{ $list->nextPageUrl() }}" aria-label="Berikutnya">›</a>@else<span class="pg">›</span>@endif
    </div>
    @endif
  </div></div>

  @if ($perAkun->isNotEmpty())
  <div class="card" style="padding:0">
    <div class="pad" style="padding-bottom:0"><h4 class="ct">Rekap per kode akun{{ $f['bulan'] ? ' · ' . $months[$f['bulan']] : '' }}</h4></div>
    <div class="tbl-wrap"><table>
      <thead><tr><th>Kode</th><th>Nama akun</th><th>Kelompok</th><th>Transaksi</th><th style="text-align:right">Total</th></tr></thead>
      <tbody>
        @foreach ($perAkun as $r)
          <tr><td class="tnum"><b>{{ $r['account']->kode }}</b></td><td>{{ $r['account']->nama }}</td><td class="small muted">{{ $r['account']->kelompok_label }}</td>
            <td class="tnum">{{ $r['n'] }}</td><td class="tnum" style="text-align:right">{{ Fmt::rupiah($r['total']) }}</td></tr>
        @endforeach
        <tr><td colspan="4"><b>Total</b></td><td class="tnum" style="text-align:right"><b>{{ Fmt::rupiah($total) }}</b></td></tr>
      </tbody></table></div></div>
  @endif
</div>
@endsection
