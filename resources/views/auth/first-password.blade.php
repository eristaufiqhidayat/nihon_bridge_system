@extends('layouts.public')
@section('title', 'Buat password baru')

@section('content')
<div class="card pad pub-card narrow">
  <h2 style="margin:0 0 4px">Buat password baru</h2>
  <p class="muted" style="margin:0 0 16px">Halo {{ $user->name }}, ini login pertama Anda. Ganti password awal dengan password milik Anda sendiri sebelum melanjutkan.</p>
  <form method="POST" action="{{ route('password.first.update') }}" novalidate>
    @csrf @method('PUT')
    <x-form-errors />
    <div class="field"><label for="fP1">Password baru</label><input id="fP1" name="password" type="password" autocomplete="new-password" autofocus><div class="hint">Minimal 8 karakter, campur huruf dan angka</div></div>
    <div class="field"><label for="fP2">Ulangi password baru</label><input id="fP2" name="password_confirmation" type="password" autocomplete="new-password"></div>
    <button class="btn-primary" type="submit">Simpan password</button>
  </form>
  <form method="POST" action="{{ route('logout') }}" style="margin-top:12px;text-align:center">@csrf<button type="submit" class="muted small" style="background:none;border:0;cursor:pointer">Keluar</button></form>
</div>
@endsection
