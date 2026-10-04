@extends('layouts.public')
@section('title', 'Lupa password')

@section('content')
<div class="card pad pub-card narrow">
  <h2 style="margin:0 0 4px">Lupa password</h2><p class="muted" style="margin:0 0 16px">Kami kirim tautan untuk membuat password baru. Tautan berlaku {{ config('auth.passwords.users.expire') }} menit.</p>
  <form method="POST" action="{{ route('password.email') }}" novalidate>
    @csrf
    <x-form-errors />
    <div class="field"><label for="rEmail">Email akun</label><input id="rEmail" name="email" type="email" value="{{ old('email', $email) }}" required></div>
    <button class="btn-primary" type="submit">Kirim tautan reset</button>
  </form>
</div>
@endsection
