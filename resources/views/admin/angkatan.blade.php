@extends('layouts.app')
@section('title', 'Data Angkatan')
@section('crumb')<b>Data Angkatan</b>@endsection

@php use App\Support\Fmt; @endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Data Angkatan</h2><p>{{ $list->count() }} angkatan. Tahapan pembayaran tiap angkatan dipakai di Pembayaran Peserta dan Keuangan.</p></div>
    <a class="bt solid" href="{{ route('angkatan.create') }}">+ Tambah angkatan</a></div>
  @error('hapus')<div class="form-error mb16" role="alert">{{ $message }}</div>@enderror
  <form method="GET" class="filter-bar">
    <input name="q" type="search" placeholder="🔍 Cari nama atau kode angkatan…" value="{{ $q }}" aria-label="Cari angkatan">
    <button class="bt outline" type="submit">Cari</button>
    @if ($q !== '')<a class="linkbtn" href="{{ route('angkatan.index') }}">Reset</a>@endif
  </form>
  <div class="card" style="padding:0"><div class="tbl-wrap"><table>
    <thead><tr><th>Angkatan</th><th>Program</th><th>Periode</th><th>Kelas</th><th>Peserta</th><th>Total biaya</th><th>Tahapan pembayaran</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse ($list as $b)
        <?php $n = $b->installments->count(); $total = $b->totalBiaya(); ?>
        <tr><td><b>{{ $b->nama }}</b><br><span class="small muted">{{ $b->kode }}</span></td>
          <td class="small">{{ $b->program?->nama ?? '–' }}</td>
          <td class="small">{{ $b->mulai ? $b->periode : '–' }}</td>
          <td class="tnum">{{ $b->classrooms_count }}</td>
          <td class="tnum">{{ $b->students_count }}{{ $b->kuota ? ' / ' . $b->kuota : '' }}</td>
          <td class="tnum small">{{ $total !== null ? Fmt::rupiah($total) : '–' }}@if ($b->biaya === null && $total !== null)<br><span class="muted">ikut program</span>@endif</td>
          <td class="small">@if ($n)<b>{{ $n }} tahap</b>@if ($total !== null) × ± {{ Fmt::rupiah(intdiv($total, $n)) }}@endif<br><span class="muted">{{ Fmt::date($b->installments->first()->jatuh_tempo) }}{{ $n > 1 ? ' s.d. ' . Fmt::date($b->installments->last()->jatuh_tempo) : '' }}</span>@else<span class="badge b-orange">Belum diatur</span>@endif</td>
          <td style="white-space:nowrap">
            <a class="act-ic" href="{{ route('angkatan.edit', $b) }}" aria-label="Edit {{ $b->nama }}">✏️</a>
            <form method="POST" action="{{ route('angkatan.destroy', $b) }}" class="inline" data-confirm-title="Hapus {{ $b->nama }}?" data-confirm="Angkatan dan tahapan pembayarannya akan dihapus permanen. Penghapusan ditolak bila angkatan masih punya kelas atau peserta." data-confirm-ok="Hapus angkatan" data-danger>@csrf @method('DELETE')<button class="act-ic" aria-label="Hapus {{ $b->nama }}">🗑️</button></form>
          </td></tr>
      @empty
        <tr><td colspan="8" class="empty">@if ($q !== '')Tidak ada angkatan yang cocok dengan "{{ $q }}". <a class="linkbtn" href="{{ route('angkatan.index') }}">Reset pencarian</a>@else Belum ada angkatan. @endif</td></tr>
      @endforelse
    </tbody></table></div></div>
</div>
@endsection
