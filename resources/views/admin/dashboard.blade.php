@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('crumb')<b>Dashboard Admin</b>@endsection

@php
  use App\Support\Catalog;
  use App\Support\Fmt;
@endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Dashboard Admin</h2><p>{{ Fmt::dayDateLong(now()) }}</p></div><button class="bt solid" type="button" data-modal-open="userModal">+ Tambah pengguna</button></div>
  <div class="grid g4 mb16">
    <div class="card stat-mini"><div class="ic ic-bg-blue">👥</div><div><p>Peserta aktif</p><h4 class="tnum">{{ $stats['peserta'] }}</h4></div></div>
    <div class="card stat-mini"><div class="ic ic-bg-purple">🏫</div><div><p>Kelas aktif</p><h4 class="tnum">{{ $stats['kelas'] }} <small>· {{ $stats['instruktur'] }} instruktur</small></h4></div></div>
    <div class="card stat-mini"><div class="ic ic-bg-green">🗂️</div><div><p>Soal di bank</p><h4 class="tnum">{{ $stats['soal'] }}</h4></div></div>
    <div class="card stat-mini"><div class="ic ic-bg-orange">📄</div><div><p>Dokumen dalam proses</p><h4 class="tnum">{{ $stats['dokumen'] }}</h4></div></div>
  </div>
  @include('admin.partials.laporan-keuangan', ['fin' => $fin])
  <div class="grid g-admin">
    <div class="card" style="padding:0">
      <div class="pad" style="padding-bottom:6px"><div class="sh"><h4>Pengguna</h4><span class="small muted">{{ $users->where('is_active', true)->count() }} aktif dari {{ $users->count() }} · <a class="linkbtn" href="{{ route('pengguna-admin.index') }}">Kelola di Data Pengguna</a></span></div></div>
      <div class="tbl-wrap" style="max-height:560px;overflow:auto"><table><thead><tr><th>Nama</th><th>Peran</th><th>Kelas</th><th>Status</th></tr></thead><tbody>
        @foreach ($users as $u)
          <tr><td><b>{{ $u->name }}</b><br><span class="small muted">{{ $u->email }}</span></td>
            <td>@if ($u->role === 'peserta' || $u->id === auth()->id())<span class="badge {{ $u->role_badge }}">{{ $u->role_label }}</span>
              @else<form method="POST" action="{{ route('admin.users.role', $u) }}" class="inline">@csrf @method('PATCH')
                <select name="role" aria-label="Peran {{ $u->name }}" data-autosubmit>@foreach ($roles->where('key', '!=', 'peserta') as $r)<option value="{{ $r->key }}" @selected($u->role === $r->key)>{{ $r->name }}</option>@endforeach</select></form>@endif</td>
            <td>{{ $u->student?->classroom?->kode ?? $u->waliClasses->first()?->kode ?? '–' }}</td>
            <td>
              <form method="POST" action="{{ route('admin.users.toggle', $u) }}" class="inline">@csrf @method('PATCH')
                <input type="hidden" name="active" value="0">
                <label class="switch" title="{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}"><input type="checkbox" name="active" value="1" @checked($u->is_active) @disabled($u->id === auth()->id()) data-autosubmit><span></span></label>
              </form></td></tr>
        @endforeach
      </tbody></table></div>
    </div>
    <div class="grid" style="gap:16px;align-content:start">
      <div class="card pad"><h4 class="ct">Aktivitas terbaru</h4>
        @forelse ($activities as $a)
          <div class="list-row"><div class="ic {{ $a->color }}">{{ $a->icon }}</div><div class="info"><p>{{ $a->message }}</p><span>{{ Fmt::chatTime($a->created_at) }}</span></div></div>
        @empty
          <div class="empty">Belum ada aktivitas.</div>
        @endforelse
      </div>
      <div class="card pad"><h4 class="ct">Perlu tindakan</h4>
        <a class="list-row clickable" href="{{ route('pendaftaran.index', ['status' => 'baru']) }}"><div class="ic ic-bg-sakura">📝</div><div class="info"><p>{{ $todo['baru'] }} pendaftar baru</p><span>Periksa berkas dan seleksi</span></div><span class="lnk-arrow">›</span></a>
        <a class="list-row clickable" href="{{ route('keuangan.index') }}"><div class="ic ic-bg-orange">💳</div><div class="info"><p>{{ $todo['telat'] }} peserta menunggak cicilan</p><span>Lewat tanggal jatuh tempo</span></div><span class="lnk-arrow">›</span></a>
        <a class="list-row clickable" href="{{ route('paket.index') }}"><div class="ic ic-bg-blue">🧾</div><div class="info"><p>{{ $todo['draf'] }} paket ujian masih draf</p><span>Lengkapi komposisi lalu terbitkan</span></div><span class="lnk-arrow">›</span></a>
        <a class="list-row clickable" href="{{ route('perusahaan.index') }}"><div class="ic ic-bg-green">🏢</div><div class="info"><p>Job order &amp; interview</p><span>Pantau kandidat yang menunggu hasil</span></div><span class="lnk-arrow">›</span></a>
      </div>
    </div>
  </div>
</div>
@endsection

@push('modals')
<x-modal id="userModal" title="Tambah pengguna">
  <form method="POST" action="{{ route('admin.users.store') }}" novalidate>
    @csrf <input type="hidden" name="_modal" value="userModal">
    <x-form-errors modal="userModal" />
    <div class="field"><label for="uNama">Nama lengkap</label><input id="uNama" name="name" value="{{ old('name') }}" placeholder="mis. Intan Kusuma"></div>
    <div class="field"><label for="uEmail">Email</label><input id="uEmail" name="email" type="email" value="{{ old('email') }}" placeholder="nama@nihonbridge.id"></div>
    <div class="opt-grid"><div class="field"><label for="uRole">Peran</label><select id="uRole" name="role">@foreach ($roles as $r)<option value="{{ $r->key }}" @selected(old('role') === $r->key)>{{ $r->name }}</option>@endforeach</select></div>
      <div class="field"><label for="uKelas">Kelas (untuk peserta)</label><select id="uKelas" name="classroom_id"><option value="">–</option>@foreach ($classes as $c)<option value="{{ $c->id }}" @selected((string) old('classroom_id', $classes->firstWhere('kode', 'N4-A')?->id) === (string) $c->id)>{{ $c->kode }}</option>@endforeach</select></div></div>
    <p class="rule-note">Tautan aktivasi akun (buat password) dikirim ke email pengguna.</p>
    <div class="modal-actions"><button type="button" class="cancel" data-modal-close>Batal</button><button class="ok" type="submit">Simpan</button></div>
  </form>
</x-modal>
@endpush
