@extends('layouts.app')
@section('title', 'Materi')
@section('crumb')Materi @if ($chapter)<span class="muted">›</span> {{ $chapter->book }} <span class="muted">›</span> <b>Bab {{ $chapter->no }}</b>@endif @endsection

@section('content')
<div class="page materi-layout">
  <div>
    @if (! $lesson)
      <div class="card"><div class="empty-state"><div class="ic">📂</div><b>Belum ada materi terbit untuk kelas Anda</b><p>Materi akan muncul di sini setelah instruktur menerbitkannya.</p></div></div>
    @else
      <?php $done = in_array($lesson->id, $doneIds, true); ?>
      <?php $file = $lesson->file_path ? asset('storage/' . $lesson->file_path) : null; ?>
      <div class="video-frame">
        @if ($file && in_array($lesson->jenis, ['Video', 'Audio'], true))
          <video controls preload="metadata" src="{{ $file }}"></video>
        @else
          <div class="kanji-bg">学</div>
          <div class="video-jp-overlay"><h3>{{ $lesson->jp_title ?: $lesson->judul }}</h3><p>{{ $lesson->jp_sub }}</p></div>
          @if ($file)
            <a class="play-btn" href="{{ $file }}" target="_blank" rel="noopener" aria-label="Buka berkas">⬇</a>
          @else
            <button class="play-btn" type="button" onclick="toast('Berkas untuk bagian ini belum diunggah instruktur.')" aria-label="Putar video">▶</button>
          @endif
          <div class="video-bar"><span class="tnum">{{ $done ? $lesson->durasi : '0:00' }} / {{ $lesson->durasi }}</span><div class="bar"><span style="width:{{ $done ? 100 : 0 }}%"></span></div><span>{{ $lesson->jenis }}</span></div>
        @endif
      </div>
      <div class="resource-btns">
        @foreach (['Video' => '🎬', 'PDF' => '📄', 'PPT' => '📊'] as $j => $ic)
          <?php $match = $chapter->publishedLessons->first(fn ($l) => $l->jenis === $j && $l->file_path); ?>
          @if ($match)
            <a class="bt {{ $j === 'Video' ? 'solid' : 'outline' }}" href="{{ asset('storage/' . $match->file_path) }}" target="_blank" rel="noopener">{{ $ic }} {{ $j }}</a>
          @else
            <button class="bt {{ $j === 'Video' ? 'solid' : 'outline' }}" disabled title="Belum ada berkas {{ $j }} di bab ini">{{ $ic }} {{ $j }}</button>
          @endif
        @endforeach
        <a class="bt outline" href="{{ route('ujian.index') }}">✏️ Latihan soal</a>
        <form method="POST" action="{{ route('materi.complete', $lesson) }}" class="inline">@csrf
          <button class="bt {{ $done ? 'outline' : 'green' }}" type="submit" @disabled($done)>{{ $done ? '✓ Sudah selesai' : '✓ Tandai selesai' }}</button>
        </form>
      </div>
      <div class="card pad">
        <h4 style="margin:0 0 4px">Bab {{ $chapter->no }} · {{ $chapter->judul }}@if ($chapter->deskripsi) ({{ $chapter->deskripsi }})@endif</h4>
        @if ($chapter->contoh_jp)<p style="margin:0 0 14px;font-size:13px;color:var(--ink-soft);font-family:'Noto Sans JP',sans-serif">{{ $chapter->contoh_jp }}</p>@endif
        <h4 style="margin:0 0 8px;font-size:13px">Daftar materi Bab {{ $chapter->no }}</h4>
        @foreach ($chapter->publishedLessons as $i => $x)
          <?php $xd = in_array($x->id, $doneIds, true); ?>
          <a class="materi-item {{ $x->id === $lesson->id ? 'active' : '' }} {{ $xd ? 'done' : '' }}" href="{{ route('materi.show', $x) }}" style="text-decoration:none;color:inherit">
            <div class="num">{{ $xd ? '✓' : $i + 1 }}</div><div class="info"><p>{{ $x->judul }}</p><span>{{ $x->jenis === 'Video' ? 'Video pembelajaran' : $x->jenis }} · {{ $xd ? 'Selesai' : 'Belum selesai' }}</span></div>
            <span class="small muted tnum">{{ $x->durasi }}</span></a>
        @endforeach
      </div>
    @endif
  </div>
  <div class="card pad">
    <div class="sh"><h4>Bab lainnya</h4></div>
    @foreach ($chapters as $c)
      @continue($chapter && $c->id === $chapter->id)
      <?php $open = ($unlocked[$c->id] ?? false) && $c->publishedLessons->isNotEmpty(); ?>
      @if ($open)
        <a class="list-row clickable" href="{{ route('materi.show', $c->publishedLessons->first()) }}"><div class="ic ic-bg-green">📗</div><div class="info"><p>Bab {{ $c->no }} · {{ $c->judul }}</p><span>{{ $c->publishedLessons->count() }} bagian</span></div></a>
      @else
        <div class="list-row"><div class="ic ic-bg-grey">🔒</div><div class="info"><p>Bab {{ $c->no }} · {{ $c->judul }}</p><span>{{ $c->publishedLessons->isEmpty() ? 'Belum ada materi' : 'Dibuka setelah bab sebelumnya selesai' }}</span></div></div>
      @endif
    @endforeach
  </div>
</div>
@endsection
