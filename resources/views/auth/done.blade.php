@extends('layouts.public')
@section('title', 'Password diperbarui')

@section('content')
<div class="card pad pub-card narrow">
  <div style="text-align:center"><div class="done-mark">✓</div><h2>Password diperbarui</h2><p class="muted">Silakan masuk dengan password baru.</p>
    <a class="bt solid" href="{{ route('login') }}">Ke halaman masuk</a></div>
</div>
@endsection
