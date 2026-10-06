@extends('layouts.app')
@section('title', 'Data Peserta')
@section('crumb')<b>Data Peserta</b>@endsection

@php
  use App\Support\Catalog;
  use App\Support\Fmt;
@endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Data Peserta</h2><p>{{ $total }} peserta terdaftar. Program → Angkatan → Kelas → Peserta.</p></div>
    <a class="bt solid" href="{{ route('peserta-admin.create') }}">+ Tambah peserta</a></div>
  @error('hapus')<div class="form-error mb16" role="alert">{{ $message }}</div>@enderror
  <form method="GET" class="filter-bar">
    <select name="angkatan" aria-label="Filter angkatan" data-autosubmit><option value="">Angkatan: Semua</option>@foreach ($batches as $b)<option value="{{ $b->id }}" @selected($f['angkatan'] === $b->id)>Angkatan: {{ $b->nama }}</option>@endforeach</select>
    <select name="kelas" aria-label="Filter kelas" data-autosubmit><option value="">Kelas: Semua</option>@foreach ($classes as $c)<option value="{{ $c->id }}" @selected($f['kelas'] === $c->id)>Kelas: {{ $c->kode }}</option>@endforeach</select>
    <select name="status" aria-label="Filter status" data-autosubmit><option value="">Status: Semua</option>@foreach (Catalog::ENROLLMENT as $k => $l)<option value="{{ $k }}" @selected($f['status'] === $k)>Status: {{ $l }}</option>@endforeach</select>
    <input name="q" type="search" placeholder="🔍 Cari nama, email, atau NIS…" value="{{ $f['q'] }}">
  </form>
  <div class="card" style="padding:0"><div class="tbl-wrap"><table>
    <thead><tr><th>Peserta</th><th>Angkatan</th><th>Kelas</th><th>Mode</th><th>Status</th><th>Total biaya</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse ($list as $s)
        <tr><td><div style="display:flex;gap:10px;align-items:center"><div class="av sm">{{ $s->initials }}</div><div><b>{{ $s->user->name }}</b>@unless ($s->user->is_active) <span class="badge b-grey">Akun nonaktif</span>@endunless<br><span class="small muted">{{ $s->nis }} · {{ $s->user->email }}</span></div></div></td>
          <td class="small">{{ $s->batch?->nama ?? '–' }}</td>
          <td>@if ($s->classroom)<span class="badge {{ Catalog::LV_BADGE[$s->classroom->level] ?? 'b-grey' }}">{{ $s->classroom->kode }}</span>@else – @endif</td>
          <td class="small">{{ Catalog::CLASS_MODES[$s->class_mode] ?? '–' }}</td>
          <td><span class="badge {{ ['aktif' => 'b-green', 'lulus' => 'b-blue', 'keluar' => 'b-grey'][$s->enrollment_status] ?? 'b-grey' }}">{{ Catalog::ENROLLMENT[$s->enrollment_status] ?? $s->enrollment_status }}</span></td>
          <td class="tnum small">{{ $s->total_fee !== null ? Fmt::rupiah($s->total_fee) : '–' }}</td>
          <td style="white-space:nowrap">
            <a class="act-ic" href="{{ route('peserta-admin.edit', $s) }}" aria-label="Edit {{ $s->user->name }}">✏️</a>
            <form method="POST" action="{{ route('peserta-admin.destroy', $s) }}" class="inline" data-confirm-title="Hapus {{ $s->user->name }}?" data-confirm="Akun login dan biodata peserta ini akan dihapus permanen. Penghapusan ditolak bila peserta masih punya data terkait (pembayaran, ujian, kehadiran, dan lainnya)." data-confirm-ok="Hapus peserta" data-danger>@csrf @method('DELETE')<button class="act-ic" aria-label="Hapus {{ $s->user->name }}">🗑️</button></form>
          </td></tr>
      @empty
        <tr><td colspan="7" class="empty">Tidak ada peserta yang cocok dengan filter. <a class="linkbtn" href="{{ route('peserta-admin.index') }}">Reset filter</a></td></tr>
      @endforelse
    </tbody></table></div></div>
  <div class="pager"><span>Menampilkan {{ $list->firstItem() ?? 0 }}–{{ $list->lastItem() ?? 0 }} dari {{ $list->total() }} peserta</span>
    @if ($list->lastPage() > 1)
    <div class="pg-btns">
      @if ($list->onFirstPage())<span class="pg">‹</span>@else<a href="{{ $list->previousPageUrl() }}" aria-label="Sebelumnya">‹</a>@endif
      @foreach (range(1, $list->lastPage()) as $p)<a class="{{ $p === $list->currentPage() ? 'cur' : '' }}" href="{{ $list->url($p) }}">{{ $p }}</a>@endforeach
      @if ($list->hasMorePages())<a href="{{ $list->nextPageUrl() }}" aria-label="Berikutnya">›</a>@else<span class="pg">›</span>@endif
    </div>
    @endif
  </div>
</div>
@endsection
