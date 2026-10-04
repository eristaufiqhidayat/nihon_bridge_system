@extends('layouts.public')
@section('title', 'Cek email')

@section('content')
<div class="card pad pub-card narrow">
  <div style="text-align:center"><div class="done-mark">✉</div><h2>Cek email Anda</h2>
    <p class="muted">Jika <b>{{ $email }}</b> terdaftar, tautan reset sudah dikirim ke sana. Tidak ada di kotak masuk? Periksa folder spam.</p>
    <div class="btnrow" style="justify-content:center">
      @if ($devLink)
        <a class="bt solid" href="{{ $devLink }}">Buka tautan dari email (mode lokal)</a>
      @endif
      <form method="POST" action="{{ route('password.email') }}" class="inline">@csrf<input type="hidden" name="email" value="{{ $email }}"><button class="bt outline" type="submit">Kirim ulang</button></form>
    </div>
  </div>
</div>
@endsection
