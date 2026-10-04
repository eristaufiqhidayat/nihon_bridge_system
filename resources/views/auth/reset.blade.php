@extends('layouts.public')
@section('title', 'Buat password baru')

@section('content')
<div class="card pad pub-card narrow">
  <h2 style="margin:0 0 4px">Buat password baru</h2><p class="muted" style="margin:0 0 16px">Untuk akun {{ $email }}</p>
  <form method="POST" action="{{ route('password.update') }}" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="email" value="{{ old('email', $email) }}">
    <x-form-errors />
    <div class="field"><label for="rP1">Password baru</label><input id="rP1" name="password" type="password" autocomplete="new-password"><div class="hint">Minimal 8 karakter, campur huruf dan angka</div></div>
    <div class="field"><label for="rP2">Ulangi password baru</label><input id="rP2" name="password_confirmation" type="password" autocomplete="new-password"></div>
    <button class="btn-primary" type="submit">Simpan password</button>
  </form>
</div>
@endsection
