@extends('layouts.base')
@section('title', 'Masuk')

@section('stage')
<section id="screen-login">
  <div class="login-wrap">
    <div class="login-visual">
      <div class="brand-mark">
        <div class="logo-badge"><img alt="Logo LPK Nihon Bridge" src="{{ asset('images/logo.png') }}"></div>
        <div>
          <h1>LPK<br>NIHON BRIDGE</h1>
          <p>{{ config('nihonbridge.org.tagline') }}</p>
        </div>
      </div>
      <div class="login-tagline">
        <p class="jp">日本へ、<br>ともに未来を</p>
        <p class="en">Together to a Brighter Future</p>
      </div>
      <div class="login-quote">"Bersama Menggapai Masa Depan di Jepang"</div>
      <div class="fuji"></div>
    </div>
    <div class="login-form-side">
      <div class="login-card">
        <form method="POST" action="{{ route('login') }}" novalidate>
          @csrf
          <h2>Masuk ke Sistem</h2>
          <p class="sub">Silakan login untuk melanjutkan</p>
          <x-form-errors />
          <div class="field">
            <label for="lgEmail">Email / Username</label>
            <input id="lgEmail" name="email" type="text" autocomplete="username" value="{{ old('email') }}" required>
          </div>
          <div class="field field-pw">
            <label for="lgPass">Password</label>
            <input id="lgPass" name="password" type="password" autocomplete="current-password" required>
            <button type="button" class="eye" data-pw-toggle="lgPass" aria-label="Tampilkan password">Tampilkan</button>
          </div>
          <div class="row-between">
            <label class="check"><input type="checkbox" name="remember" value="1" checked> Ingat saya</label>
            <a class="linkbtn" href="{{ route('password.request') }}" id="forgotLink">Lupa password?</a>
          </div>
          <button class="btn-primary" type="submit">Masuk</button>
        </form>

        @if ($demoRoles)
          <div class="or-sep">atau masuk cepat sebagai (demo)</div>
          <div class="role-btns">
            @foreach (array_intersect_key(['peserta' => ['🎓', 'Peserta'], 'instruktur' => ['🧑‍🏫', 'Instruktur'], 'admin' => ['⚙️', 'Admin'], 'direktur' => ['📈', 'Direktur']], array_flip($demoRoles)) as $role => [$ic, $label])
              <form method="POST" action="{{ route('login.demo', $role) }}">@csrf<button type="submit" class="role-btn" style="width:100%"><span class="ic">{{ $ic }}</span>{{ $label }}</button></form>
            @endforeach
          </div>
          <p class="demo-hint">Akun contoh memakai password <b>sakura2026</b>, mis. <b>ahmad.fauzi@nihonbridge.id</b>, <b>sato.sensei@nihonbridge.id</b>, <b>rina.info@nihonbridge.id</b>, <b>direktur@nihonbridge.id</b>.</p>
        @endif
        <p class="login-links"><a class="linkbtn" href="{{ route('daftar') }}">Belum jadi peserta? Daftar sekarang</a><a class="linkbtn" href="{{ route('verifikasi') }}">Verifikasi sertifikat</a></p>
        <div class="org-contact"><b>{{ config('nihonbridge.org.name') }}</b>@foreach (config('nihonbridge.org.address') as $line)<span>{{ $line }}</span>@endforeach</div>
      </div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
  // Bawa email yang sudah diketik ke halaman lupa password
  document.getElementById('forgotLink').addEventListener('click', function (e) {
    const v = document.getElementById('lgEmail').value.trim();
    if (v) { e.preventDefault(); location.href = this.href + '?email=' + encodeURIComponent(v); }
  });
</script>
@endpush
