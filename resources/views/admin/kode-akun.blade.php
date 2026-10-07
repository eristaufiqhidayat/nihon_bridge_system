@extends('layouts.app')
@section('title', 'Kode Akun')
@section('crumb')<b>Kode Akun</b>@endsection

@php use App\Models\Account; use App\Support\Fmt; @endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Kode Akun</h2><p>{{ $total }} akun. Bagan akun mengikuti pola SAK EMKM: 1-1xxx Kas &amp; Bank (sumber dana), 5-xxxx Beban Pokok Pendapatan, 6-xxxx Beban Operasional, 8-xxxx Beban Lain-lain.</p></div>
    <a class="bt solid" href="{{ route('kode-akun.create') }}">+ Tambah akun</a></div>
  @error('hapus')<div class="form-error mb16" role="alert">{{ $message }}</div>@enderror
  <form method="GET" class="filter-bar">
    <input name="q" type="search" placeholder="🔍 Cari kode atau nama akun…" value="{{ $f['q'] }}" aria-label="Cari akun">
    <select name="kelompok" aria-label="Kelompok akun"><option value="">Semua kelompok</option>@foreach (Account::GROUPS as $p => [$k, $label])<option value="{{ $p }}" @selected($f['kelompok'] === (string) $p)>{{ $p }}- {{ $label }}</option>@endforeach</select>
    <button class="bt outline" type="submit">Cari</button>
    @if ($f['q'] !== '' || $f['kelompok'])<a class="linkbtn" href="{{ route('kode-akun.index') }}">Reset</a>@endif
  </form>
  @forelse ($groups as $prefix => $rows)
  <div class="card mb16" style="padding:0">
    <div class="pad" style="padding-bottom:0"><h4 class="ct">{{ $prefix }}- {{ Account::GROUPS[$prefix][1] ?? 'Lainnya' }} <span class="small muted">· {{ $rows->count() }} akun</span></h4></div>
    <div class="tbl-wrap"><table>
      <thead><tr><th>Kode</th><th>Nama akun</th><th>Keterangan</th><th>Transaksi</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
        @foreach ($rows as $a)
          <?php $n = $a->expenses_count + $a->cash_expenses_count; $sum = (int) $a->expenses_sum_jumlah + (int) $a->cash_expenses_sum_jumlah; ?>
          <tr><td class="tnum"><b>{{ $a->kode }}</b></td>
            <td>{{ $a->nama }}</td>
            <td class="small muted">{{ $a->keterangan ?: '–' }}</td>
            <td class="tnum">{{ $n }}</td>
            <td class="tnum small">{{ $n ? Fmt::rupiah($sum) : '–' }}@if ($n && $a->is_cash)<br><span class="muted">dibayarkan</span>@endif</td>
            <td>@if ($a->is_active)<span class="badge b-green">Aktif</span>@else<span class="badge b-grey">Nonaktif</span>@endif</td>
            <td style="white-space:nowrap">
              <a class="act-ic" href="{{ route('kode-akun.edit', $a) }}" aria-label="Edit {{ $a->label }}">✏️</a>
              <form method="POST" action="{{ route('kode-akun.destroy', $a) }}" class="inline" data-confirm-title="Hapus akun {{ $a->kode }}?" data-confirm="Akun {{ $a->nama }} akan dihapus permanen. Penghapusan ditolak bila akun sudah dipakai transaksi pengeluaran." data-confirm-ok="Hapus akun" data-danger>@csrf @method('DELETE')<button class="act-ic" aria-label="Hapus {{ $a->label }}">🗑️</button></form>
            </td></tr>
        @endforeach
      </tbody></table></div></div>
  @empty
  <div class="card pad empty">@if ($f['q'] !== '' || $f['kelompok'])Tidak ada akun yang cocok. <a class="linkbtn" href="{{ route('kode-akun.index') }}">Reset pencarian</a>@else Belum ada kode akun. @endif</div>
  @endforelse
</div>
@endsection
