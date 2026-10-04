@extends('layouts.app')
@section('title', 'Pesan')
@section('crumb')<b>Pesan</b>@endsection

@php
  use App\Support\Fmt;
  $me = auth()->user();
@endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Pesan</h2><p>Percakapan dengan instruktur, admin, dan kelas.</p></div></div>
  <div class="card chat {{ $active ? 'thread-open' : '' }}">
    <div class="chat-list">
      @forelse ($conversations as $c)
        <?php $last = $c->latestMessage; ?>
        <?php $unread = $active && $active->id === $c->id ? 0 : $c->unreadFor($me); ?>
        <a class="{{ $active && $active->id === $c->id ? 'active' : '' }}" href="{{ route('pesan.index', $c) }}">
          <div class="av sm">{{ $c->displayInitials($me) }}</div>
          <div class="who"><p>{{ $c->displayName($me) }}<small>{{ $last ? explode(',', Fmt::chatTime($last->created_at))[0] : '' }}</small></p>
            <span>{{ $last ? ($last->user_id === $me->id ? 'Anda: ' : '') . $last->body : 'Belum ada pesan' }}</span></div>
          @if ($unread)<span class="unread">{{ $unread }}</span>@endif
        </a>
      @empty
        <div class="empty">Belum ada percakapan.</div>
      @endforelse
    </div>
    <div class="chat-thread">
      @if ($active)
        <div class="chat-head"><a class="back" href="{{ route('pesan.index') }}" aria-label="Kembali">‹</a><div class="av sm">{{ $active->displayInitials($me) }}</div><b>{{ $active->displayName($me) }}</b></div>
        <div class="chat-msgs" id="chatMsgs">
          @foreach ($active->messages as $m)
            <div class="msg {{ $m->user_id === $me->id ? 'me' : '' }}">@if ($active->is_group && $m->user_id !== $me->id)<span class="from">{{ explode(' ', $m->user->name)[0] }}</span>@endif{{ $m->body }}<small>{{ Fmt::chatTime($m->created_at) }}</small></div>
          @endforeach
        </div>
        <form class="chat-input" method="POST" action="{{ route('pesan.send', $active) }}">@csrf<input id="chatInput" name="body" type="text" placeholder="Tulis pesan…" autocomplete="off" required maxlength="2000"><button class="bt solid" type="submit">Kirim</button></form>
      @else
        <div class="empty" style="margin:auto">Pilih percakapan di sebelah kiri.</div>
      @endif
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const m = document.getElementById('chatMsgs'); if (m) m.scrollTop = m.scrollHeight;
  const i = document.getElementById('chatInput'); if (i && window.innerWidth > 640) i.focus();
</script>
@endpush
