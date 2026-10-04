@extends('layouts.base')

@section('stage')
<div id="shell">
  <aside class="sidebar" id="sidebar" aria-label="Menu utama">
    <div class="sb-brand"><div class="logo-badge"><img alt="Logo LPK Nihon Bridge" src="{{ asset('images/logo.png') }}"></div>
      <div><p>LPK<br>NIHON BRIDGE</p><span>{{ $me->subtitle }}</span></div></div>
    <nav class="sb-nav">
      @foreach ($menu as [$route, $icon, $label, $pattern])
        <a class="sb-item {{ request()->routeIs($pattern) ? 'active' : '' }}" href="{{ route($route) }}"><span class="ic">{{ $icon }}</span>{{ $label }}@if ($route === 'pesan.index' && $unreadMessages)<span class="cnt">{{ $unreadMessages }}</span>@endif</a>
      @endforeach
    </nav>
    <div class="sb-foot">
      <form method="POST" action="{{ route('logout') }}"
        @if ($me->role === 'peserta' && $me->attempts()->where('status', 'berjalan')->exists())
          data-confirm="Jika keluar sekarang, jawaban tersimpan tetapi waktu terus berjalan. Keluar tetap?" data-confirm-title="Ujian masih berlangsung" data-confirm-ok="Keluar"
        @endif>
        @csrf
        <button class="sb-logout" type="submit"><span>⏻</span> Keluar</button>
      </form>
    </div>
  </aside>
  <div class="scrim" id="scrim"></div>
  <div class="main">
    <header class="topbar">
      <button class="hamburger" id="hamburger" aria-label="Buka menu">☰</button>
      <div class="crumb">@yield('crumb')</div>
      <div class="topbar-right">
        <button class="bell" id="bellBtn" aria-label="Notifikasi">🔔<span class="dot" @if (! $notifUnread) hidden @endif>{{ $notifUnread }}</span></button>
        <a class="user-chip" href="{{ route('profil.show') }}" style="text-decoration:none;color:inherit">
          <div class="av">@if ($me->avatar_path)<img class="sb-user-photo" src="{{ asset('storage/' . $me->avatar_path) }}" alt="">@else{{ $me->initials }}@endif</div>
          <div class="txt"><p>{{ $me->name }}</p><span>{{ $me->subtitle }}</span></div>
        </a>
      </div>
      <div class="notif-panel" id="notifPanel" hidden>
        <h5>Notifikasi
          @if ($notifUnread)
            <form method="POST" action="{{ route('notifikasi.readAll') }}" class="inline">@csrf<button class="linkbtn" type="submit">Tandai semua dibaca</button></form>
          @endif
        </h5>
        @forelse ($notifs as $n)
          <a class="notif-item {{ $n->read_at ? '' : 'unread' }}" href="{{ route('notifikasi.open', $n) }}"><span class="ic">{{ $n->icon }}</span><div>{{ $n->message }}<span>{{ \App\Support\Fmt::ago($n->created_at) }}</span></div></a>
        @empty
          <div class="empty">Belum ada notifikasi.</div>
        @endforelse
      </div>
    </header>
    <main id="views">
      <div class="view fade">
        @yield('content')
      </div>
    </main>
  </div>
</div>
@endsection
