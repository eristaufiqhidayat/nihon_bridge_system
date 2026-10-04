@extends('layouts.app')
@section('title', 'Paket & Jadwal Ujian')
@section('crumb')<b>Paket &amp; Jadwal Ujian</b>@endsection

@php
  use App\Support\Catalog;
  use App\Support\Fmt;
  $pickIds = $package?->picks->pluck('id')->all() ?? [];
@endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Paket &amp; Jadwal Ujian</h2><p>Paket menyimpan salinan soal saat diterbitkan, lalu dijadwalkan untuk kelas.</p></div>
    <form method="POST" action="{{ route('paket.store') }}">@csrf<button class="bt solid" type="submit">+ Paket baru</button></form></div>
  <x-form-errors />
  <div class="grid g-admin mb16">
    <div class="card" style="padding:0"><div class="tbl-wrap"><table><thead><tr><th>Paket</th><th>Soal</th><th>Status</th><th></th></tr></thead><tbody>
      @foreach ($packages as $x)
        <tr class="clickable {{ $package && $x->id === $package->id ? 'sel' : '' }}" onclick="location.href='{{ route('paket.index', ['paket' => $x->id]) }}'">
          <td><b>{{ $x->judul }}</b><br><span class="small muted">{{ $x->isPublished() ? 'Terbit ' . Fmt::date($x->published_at) : 'Draf, belum bisa dijadwalkan' }}</span></td>
          <td class="tnum">{{ $x->isPublished() ? $x->question_count : $x->picks_count }}/{{ array_sum($x->composition ?? Catalog::COMPOSE) }}</td>
          <td><span class="badge {{ $x->isPublished() ? 'b-green' : 'b-grey' }}">{{ $x->status }}</span></td>
          <td>@if ($x->isPublished())<button class="mini-btn" type="button" onclick="event.stopPropagation()" data-modal-open="scheduleModal" data-fill="{{ json_encode(['@action' => route('paket.schedule', $x), '@title' => "Jadwalkan {$x->judul}"], JSON_UNESCAPED_UNICODE) }}">Jadwalkan</button>@endif</td></tr>
      @endforeach
    </tbody></table></div></div>
    <div class="card pad"><h4 class="ct">Ujian terjadwal</h4>
      @forelse ($schedules as $s)
        <div class="list-row"><div class="ic ic-bg-sakura">🗓️</div><div class="info"><p>{{ $s->package->judul }} · {{ $s->classroom->kode }}</p><span>{{ Fmt::date($s->opens_at) }} – {{ Fmt::date($s->closes_at) }} · {{ $s->duration }} menit</span></div></div>
      @empty
        <div class="empty">Belum ada jadwal.</div>
      @endforelse
    </div>
  </div>

  @if ($package && ! $package->isPublished())
    <div class="card pad">
      <div class="sh"><h4>Susun {{ $package->judul }}</h4><div class="btnrow">
        <form method="POST" action="{{ route('paket.autopick', $package) }}" class="inline">@csrf<button class="bt outline" type="submit">Pilih otomatis sesuai komposisi</button></form>
        <form method="POST" action="{{ route('paket.publish', $package) }}" class="inline" data-confirm-title="Terbitkan {{ $package->judul }}?" data-confirm="Setelah terbit, isi paket terkunci dan disimpan sebagai salinan. Paket lalu bisa dijadwalkan untuk kelas." data-confirm-ok="Terbitkan">@csrf<button class="bt green" type="submit" @disabled(! $ok)>Terbitkan paket</button></form>
      </div></div>
      <div class="compose">
        @foreach ($package->composition ?? Catalog::COMPOSE as $s => $n)
          <?php $c = $counts[$s] ?? 0; ?>
          <div class="{{ $c === $n ? 'ok' : ($c > $n ? 'over' : '') }}"><span>{{ Catalog::sectionName($s) }}</span><b class="tnum">{{ $c }}/{{ $n }}</b></div>
        @endforeach
      </div>
      @unless ($ok)<p class="rule-note" style="margin-top:0">Tombol terbitkan aktif setelah jumlah soal tiap bagian tepat sesuai komposisi JLPT {{ $package->level }} simulasi.</p>@endunless
      <form method="GET" class="filter-bar"><input type="hidden" name="paket" value="{{ $package->id }}">
        <select name="bagian" aria-label="Filter bagian" data-autosubmit><option value="Semua">Semua bagian</option>@foreach (Catalog::SECTIONS as $k => $s)<option value="{{ $k }}" @selected($filter === $k)>{{ $s['name'] }}</option>@endforeach</select>
        <span class="small muted">{{ $pool->count() }} soal {{ $package->level }} di bank</span></form>
      <div class="tbl-wrap" style="max-height:360px;overflow:auto;border:1px solid var(--line);border-radius:10px"><table><thead><tr><th></th><th>ID</th><th>Soal</th><th>Bagian</th></tr></thead><tbody>
        @foreach ($pool as $q)
          <tr><td><form method="POST" action="{{ route('paket.pick', $package) }}" class="inline">@csrf<input type="hidden" name="question_id" value="{{ $q->id }}"><input type="hidden" name="on" value="0">
            <input type="checkbox" name="on" value="1" aria-label="Pilih {{ $q->code }}" @checked(in_array($q->id, $pickIds, true)) data-autosubmit></form></td>
            <td class="small muted tnum">{{ $q->code }}</td><td class="soal-text">{{ $q->question }}</td><td><span class="badge {{ $q->section_badge }}">{{ $q->section_name }}</span></td></tr>
        @endforeach
      </tbody></table></div>
    </div>
  @elseif ($package)
    <div class="card pad"><h4 class="ct">{{ $package->judul }}</h4><p class="muted small" style="margin:0">Terbit {{ Fmt::date($package->published_at) }} · {{ $package->question_count }} soal. Isi paket terkunci; perubahan di bank soal tidak memengaruhi paket ini. Untuk memperbaiki soal, buat paket baru.</p></div>
  @endif
</div>
@endsection

@push('modals')
<x-modal id="scheduleModal" title="Jadwalkan paket">
  <form method="POST" action="{{ old('_action', $package ? route('paket.schedule', $package) : '#') }}">@csrf <input type="hidden" name="_modal" value="scheduleModal">
    <x-form-errors modal="scheduleModal" />
    <div class="opt-grid">
      <div class="field"><label for="scK">Kelas</label><select id="scK" name="classroom_id">@foreach ($classes as $c)<option value="{{ $c->id }}" @selected($c->kode === 'N4-A')>{{ $c->kode }}</option>@endforeach</select></div>
      <div class="field"><label for="scD">Durasi (menit)</label><input id="scD" name="duration" type="number" min="15" max="180" value="{{ old('duration', 60) }}"></div>
      <div class="field"><label for="scB">Dibuka</label><input id="scB" name="opens_at" type="date" value="{{ old('opens_at', now()->addDay()->toDateString()) }}"></div>
      <div class="field"><label for="scT">Ditutup</label><input id="scT" name="closes_at" type="date" value="{{ old('closes_at', now()->addMonth()->toDateString()) }}"></div></div>
    <div class="modal-actions"><button type="button" class="cancel" data-modal-close>Nanti saja</button><button class="ok" type="submit">Jadwalkan</button></div>
  </form>
</x-modal>
@endpush

@if (request('jadwalkan') && $package?->isPublished())
@push('scripts')
<script>openModal('scheduleModal', { '@action': @json(route('paket.schedule', $package)), '@title': @json('Jadwalkan ' . $package->judul) });</script>
@endpush
@endif
