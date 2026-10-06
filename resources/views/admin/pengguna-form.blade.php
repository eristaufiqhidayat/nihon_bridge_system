@extends('layouts.app')
@section('title', $user->exists ? 'Edit Pengguna' : 'Tambah Pengguna')
@section('crumb')<a href="{{ route('pengguna-admin.index') }}">Data Pengguna</a> › <b>{{ $user->exists ? $user->name : 'Tambah pengguna' }}</b>@endsection

@section('content')
<?php $u = $user; ?>
<div class="page">
  <div class="page-head"><div><h2>{{ $u->exists ? 'Edit pengguna · ' . $u->name : 'Tambah pengguna' }}</h2>
    <p>{{ $u->exists ? ($self ? 'Ini akun Anda sendiri: peran dan status tidak bisa diubah dari sini.' : 'Ubah data akun, peran, status, atau reset password.') : 'Tautan aktivasi akun (buat password) dikirim ke email pengguna setelah disimpan. Peserta ditambahkan lewat Data Peserta.' }}</p></div>
    <a class="bt outline" href="{{ route('pengguna-admin.index') }}">‹ Kembali ke daftar</a></div>

  @if (session('temp_password'))
  <div class="card pad mb16" role="status"><h4 class="ct">Password sementara</h4>
    <p>Berikan password ini kepada {{ $u->name }}: <b class="tnum" style="font-size:1.15em;letter-spacing:.05em">{{ session('temp_password') }}</b></p>
    <p class="small muted">Password hanya ditampilkan sekali. Pengguna wajib menggantinya dengan password baru saat login berikutnya.</p></div>
  @endif

  <form method="POST" action="{{ $u->exists ? route('pengguna-admin.update', $u) : route('pengguna-admin.store') }}" novalidate class="grid" style="gap:16px">
    @csrf @if ($u->exists) @method('PUT') @endif
    @if ($errors->any() && ! $errors->has('hapus'))<div class="form-error" role="alert">{{ $errors->first() }}</div>@endif

    <div class="card pad"><h4 class="ct">Akun</h4>
      <div class="opt-grid">
        <div class="field"><label for="uNama">Nama lengkap</label><input id="uNama" name="name" value="{{ old('name', $u->name) }}" placeholder="mis. Sato Kenji"></div>
        <div class="field"><label for="uEmail">Email (untuk login)</label><input id="uEmail" name="email" type="email" value="{{ old('email', $u->email) }}" placeholder="nama@nihonbridge.id"></div>
        <div class="field"><label for="uHp">No. HP / WhatsApp</label><input id="uHp" name="phone" value="{{ old('phone', $u->phone) }}"></div>
        <div class="field"><label for="uRole">Peran</label>
          @if ($self)
            <input type="hidden" name="role" value="{{ $u->role }}"><select id="uRole" disabled><option>{{ $u->role_label }}</option></select>
          @else
            <select id="uRole" name="role">@foreach ($roles as $r)<option value="{{ $r->key }}" @selected(old('role', $u->role) === $r->key)>{{ $r->name }}</option>@endforeach</select>
          @endif
          <div class="hint">Menu tiap peran diatur di <a class="linkbtn" href="{{ route('role-admin.index') }}">Data Role</a>.</div></div>
        <div class="field"><label for="uAktif">Akun login</label>
          <input type="hidden" name="is_active" value="{{ $self ? 1 : 0 }}">
          <label class="switch" title="Aktif / nonaktif"><input id="uAktif" type="checkbox" name="is_active" value="1" @checked(old('is_active', $u->is_active ?? true)) @disabled($self)><span></span></label></div>
      </div></div>

    <div><button class="bt solid" type="submit">{{ $u->exists ? 'Simpan perubahan' : 'Simpan & kirim aktivasi' }}</button></div>
  </form>

  @if ($u->exists && ! $self)
  <div class="card pad" style="margin-top:16px"><h4 class="ct">Reset password</h4>
    <p class="small muted">Membuat password sementara yang ditampilkan sekali di halaman ini. Pengguna wajib menggantinya saat login berikutnya.</p>
    <form method="POST" action="{{ route('pengguna-admin.reset', $u) }}" class="inline" data-confirm-title="Reset password {{ $u->name }}?" data-confirm="Password lama tidak bisa dipakai lagi. Password sementara akan ditampilkan sekali." data-confirm-ok="Reset password">@csrf
      <button class="bt outline" type="submit">Reset password</button></form>
  </div>

  <div class="card pad" style="margin-top:16px"><h4 class="ct">Hapus pengguna</h4>
    @error('hapus')<div class="form-error" role="alert">{{ $message }}</div>@enderror
    @if ($blockers)
      <p class="small">Pengguna ini belum bisa dihapus karena masih terhubung dengan: <b>{{ collect($blockers)->map(fn ($n, $l) => "{$n} {$l}")->join(', ', ' dan ') }}</b>. Jika pengguna berhenti, nonaktifkan akunnya.</p>
    @else
      <p class="small muted">Belum ada data terkait. Akun login akan dihapus permanen.</p>
    @endif
    <form method="POST" action="{{ route('pengguna-admin.destroy', $u) }}" class="inline" data-confirm-title="Hapus {{ $u->name }}?" data-confirm="Akun login ini akan dihapus permanen." data-confirm-ok="Hapus pengguna" data-danger>@csrf @method('DELETE')
      <button class="bt danger" type="submit" @disabled($blockers)>Hapus pengguna</button></form>
  </div>
  @endif
</div>
@endsection
