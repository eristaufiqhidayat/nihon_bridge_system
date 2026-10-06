@extends('layouts.app')
@section('title', $role->exists ? 'Edit Role' : 'Tambah Role')
@section('crumb')<a href="{{ route('role-admin.index') }}">Data Role</a> › <b>{{ $role->exists ? $role->name : 'Tambah role' }}</b>@endsection

@section('content')
<?php $r = $role; ?>
<div class="page">
  <div class="page-head"><div><h2>{{ $r->exists ? 'Edit role · ' . $r->name : 'Tambah role' }}</h2>
    <p>{{ $r->is_system ? 'Peran bawaan sistem: nama dan keterangan bisa diubah, menunya tetap.' : 'Pilih menu yang boleh dibuka pengguna dengan peran ini. Pesan dan Profil selalu tersedia.' }}</p></div>
    <a class="bt outline" href="{{ route('role-admin.index') }}">‹ Kembali ke daftar</a></div>

  <form method="POST" action="{{ $r->exists ? route('role-admin.update', $r) : route('role-admin.store') }}" novalidate class="grid" style="gap:16px">
    @csrf @if ($r->exists) @method('PUT') @endif
    @if ($errors->any() && ! $errors->has('hapus'))<div class="form-error" role="alert">{{ $errors->first() }}</div>@endif

    <div class="card pad"><h4 class="ct">Peran</h4>
      <div class="opt-grid">
        <div class="field"><label for="rNama">Nama peran</label><input id="rNama" name="name" value="{{ old('name', $r->name) }}" placeholder="mis. Staf Keuangan"></div>
        <div class="field"><label for="rKet">Keterangan</label><input id="rKet" name="description" value="{{ old('description', $r->description) }}" placeholder="Opsional"></div>
      </div></div>

    <div class="card pad"><h4 class="ct">Akses menu</h4>
      @if ($r->is_system)
        <p class="small">{{ collect($r->menuItems())->map(fn ($m) => $m[1] . ' ' . $m[2])->join(' · ') }}</p>
      @else
        <div class="grid g3" style="gap:8px">
          @foreach ($options as [$route, $ic, $label])
            <label class="check"><input type="checkbox" name="menus[]" value="{{ $route }}" @checked(in_array($route, $picked, true))> {{ $ic }} {{ $label }}</label>
          @endforeach
        </div>
        <p class="rule-note">Pengguna dengan peran ini bisa membuka dan mengubah data di menu yang dipilih, sama seperti admin.</p>
      @endif
    </div>

    <div><button class="bt solid" type="submit">{{ $r->exists ? 'Simpan perubahan' : 'Simpan role' }}</button></div>
  </form>

  @if ($r->exists && ! $r->is_system)
  <div class="card pad" style="margin-top:16px"><h4 class="ct">Hapus role</h4>
    @error('hapus')<div class="form-error" role="alert">{{ $message }}</div>@enderror
    @if ($r->users_count)
      <p class="small">Peran ini belum bisa dihapus karena masih dipakai <b>{{ $r->users_count }} pengguna</b>. Ganti peran mereka di Dashboard Admin lebih dulu.</p>
    @else
      <p class="small muted">Belum ada pengguna dengan peran ini. Peran akan dihapus permanen.</p>
    @endif
    <form method="POST" action="{{ route('role-admin.destroy', $r) }}" class="inline" data-confirm-title="Hapus peran {{ $r->name }}?" data-confirm="Peran akan dihapus permanen." data-confirm-ok="Hapus peran" data-danger>@csrf @method('DELETE')
      <button class="bt danger" type="submit" @disabled($r->users_count)>Hapus role</button></form>
  </div>
  @endif
</div>
@endsection
