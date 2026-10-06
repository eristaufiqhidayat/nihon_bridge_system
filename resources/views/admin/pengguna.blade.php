@extends('layouts.app')
@section('title', 'Data Pengguna')
@section('crumb')<b>Data Pengguna</b>@endsection

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Data Pengguna</h2><p>{{ $total }} akun admin, instruktur, direktur, dan peran tambahan. {{ $peserta }} akun peserta dikelola di <a class="linkbtn" href="{{ route('peserta-admin.index') }}">Data Peserta</a>.</p></div>
    <a class="bt solid" href="{{ route('pengguna-admin.create') }}">+ Tambah pengguna</a></div>
  @error('hapus')<div class="form-error mb16" role="alert">{{ $message }}</div>@enderror
  <form method="GET" class="filter-bar">
    <select name="role" aria-label="Filter peran" data-autosubmit><option value="">Peran: Semua</option>@foreach ($roles as $r)<option value="{{ $r->key }}" @selected($f['role'] === $r->key)>Peran: {{ $r->name }}</option>@endforeach</select>
    <select name="status" aria-label="Filter status" data-autosubmit><option value="">Status: Semua</option><option value="aktif" @selected($f['status'] === 'aktif')>Status: Aktif</option><option value="nonaktif" @selected($f['status'] === 'nonaktif')>Status: Nonaktif</option></select>
    <input name="q" type="search" placeholder="🔍 Cari nama atau email…" value="{{ $f['q'] }}">
  </form>
  <div class="card" style="padding:0"><div class="tbl-wrap"><table>
    <thead><tr><th>Pengguna</th><th>Peran</th><th>No. HP</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse ($list as $u)
        <tr><td><div style="display:flex;gap:10px;align-items:center"><div class="av sm">{{ $u->initials }}</div><div><b>{{ $u->name }}</b>@if ($u->id === auth()->id()) <span class="small muted">(Anda)</span>@endif<br><span class="small muted">{{ $u->email }}</span></div></div></td>
          <td><span class="badge {{ $u->role_badge }}">{{ $u->role_label }}</span>@if ($u->waliClasses->isNotEmpty())<br><span class="small muted">Wali {{ $u->waliClasses->pluck('kode')->join(', ') }}</span>@endif</td>
          <td class="small">{{ $u->phone ?: '–' }}</td>
          <td><span class="badge {{ $u->is_active ? 'b-green' : 'b-grey' }}">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span>@if ($u->must_change_password)<br><span class="small muted">Wajib ganti password</span>@endif</td>
          <td style="white-space:nowrap">
            <a class="act-ic" href="{{ route('pengguna-admin.edit', $u) }}" aria-label="Edit {{ $u->name }}">✏️</a>
            @unless ($u->id === auth()->id())
            <form method="POST" action="{{ route('pengguna-admin.destroy', $u) }}" class="inline" data-confirm-title="Hapus {{ $u->name }}?" data-confirm="Akun login ini akan dihapus permanen. Penghapusan ditolak bila pengguna masih punya data terkait (kelas, jadwal mengajar, soal, pesan, dan lainnya)." data-confirm-ok="Hapus pengguna" data-danger>@csrf @method('DELETE')<button class="act-ic" aria-label="Hapus {{ $u->name }}">🗑️</button></form>
            @endunless
          </td></tr>
      @empty
        <tr><td colspan="5" class="empty">Tidak ada pengguna yang cocok dengan filter. <a class="linkbtn" href="{{ route('pengguna-admin.index') }}">Reset filter</a></td></tr>
      @endforelse
    </tbody></table></div></div>
  <div class="pager"><span>Menampilkan {{ $list->firstItem() ?? 0 }}–{{ $list->lastItem() ?? 0 }} dari {{ $list->total() }} pengguna</span>
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
