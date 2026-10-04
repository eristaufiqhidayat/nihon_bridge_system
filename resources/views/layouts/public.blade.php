@extends('layouts.base')

@section('stage')
<section id="screen-public">
  <div class="pub-wrap">
    <header class="pub-head">
      <div class="logo-badge"><img alt="Logo LPK Nihon Bridge" src="{{ asset('images/logo.png') }}"></div>
      <div><b>LPK NIHON BRIDGE</b><span>{{ config('nihonbridge.org.tagline') }}</span></div>
      @auth
        <a class="bt outline" style="margin-left:auto" href="{{ url('/') }}">Kembali ke aplikasi</a>
      @else
        <a class="bt outline" style="margin-left:auto" href="{{ route('login') }}">Masuk</a>
      @endauth
    </header>
    <main class="pub-main">@yield('content')</main>
    <footer class="pub-foot">
      <div><b>{{ config('nihonbridge.org.name') }}</b><br>{!! implode('<br>', array_map('e', config('nihonbridge.org.address'))) !!}</div>
      <div>JAPAN · ISME<br>{{ config('nihonbridge.org.tagline') }}</div>
    </footer>
  </div>
</section>
@endsection
