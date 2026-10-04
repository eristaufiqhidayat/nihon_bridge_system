@extends('layouts.app')
@section('title', $attempt->package->judul)
@section('crumb')Ujian <span class="muted">›</span> <b>{{ $attempt->package->judul }}</b>@endsection

@php
  use App\Support\Catalog;
  use App\Support\Fmt;
  $secOrder = array_keys(Catalog::SECTIONS);
  $secTotals = collect($questions)->countBy('section');
  $secSeen = [];
@endphp

@section('content')
<div class="ujian-shell" id="cbt"
  data-save="{{ route('ujian.save', $attempt) }}"
  data-left="{{ $attempt->secondsLeft() }}"
  data-total="{{ count($questions) }}"
  data-current="{{ $attempt->current }}"
  data-answers='@json((object) ($attempt->answers ?? []))'
  data-flags='@json((object) ($attempt->flags ?? []))'
  data-store="nb-cbt-{{ $attempt->id }}">
  <div>
    <div class="ujian-head"><div><h4>{{ $attempt->package->judul }}</h4><span id="qSection"></span></div>
      <div class="timer-chip" id="timerChip">⏱ {{ Fmt::duration($attempt->secondsLeft()) }}</div></div>
    <div class="q-area">
      <div class="offline-banner" id="offlineBanner" role="alert" hidden>📡 Koneksi terputus. <b id="pendingCount">0</b> jawaban disimpan di perangkat ini dan akan dikirim otomatis saat tersambung. Timer tetap berjalan di server.</div>
      @foreach ($questions as $i => $q)
        @php
          $sec = Catalog::SECTIONS[$q['section']];
          $secSeen[$q['section']] = ($secSeen[$q['section']] ?? 0) + 1;
        @endphp
        <div class="q-pane" data-i="{{ $i }}" data-section="Bagian {{ array_search($q['section'], $secOrder) + 1 }} · {{ $sec['name'] }} ({{ $sec['jp'] }})" hidden>
          <div class="q-num">Soal no. {{ $i + 1 }} dari {{ count($questions) }} <span class="badge {{ $sec['badge'] }}">{{ $sec['name'] }} {{ $secSeen[$q['section']] }}/{{ $secTotals[$q['section']] }}</span></div>
          @if (! empty($q['passage']))<div class="passage"><b>Bacalah teks berikut.</b>{{ $q['passage'] }}</div>@endif
          @if (! empty($q['audio']))
            <div class="audio-box"><button class="bt solid" type="button" data-audio="{{ $q['audio'] }}">🔊 Putar audio</button><span>Dengarkan percakapan, lalu pilih jawaban yang paling tepat. Audio dibacakan oleh suara Jepang di browser.</span></div>
          @endif
          <p class="q-text">{{ $q['question'] }}</p>
          <div role="radiogroup" aria-label="Pilihan jawaban">
            @foreach ($q['options'] as $j => $o)
              <button type="button" class="opt" role="radio" aria-checked="false" data-opt="{{ $j }}"><span class="letter">{{ 'ABCD'[$j] }}</span><span>{{ $o }}</span></button>
            @endforeach
          </div>
          <div class="q-nav-btns">
            <button type="button" class="nbtn prev" data-jump="{{ $i - 1 }}" @disabled($i === 0)>‹ Sebelumnya</button>
            <button type="button" class="nbtn flag" data-flag>⚑ Ragu-ragu</button>
            @if ($i < count($questions) - 1)
              <button type="button" class="nbtn next" data-jump="{{ $i + 1 }}">Selanjutnya ›</button>
            @else
              <button type="button" class="nbtn next" style="background:var(--sakura-deep)" data-finish>Selesai ✓</button>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  </div>
  <aside class="q-side">
    <h5>Navigasi soal</h5>
    <div class="q-grid" id="qGrid">
      @foreach ($questions as $i => $q)<button type="button" data-jump="{{ $i }}" aria-label="Soal {{ $i + 1 }}">{{ $i + 1 }}</button>@endforeach
    </div>
    <div class="legend"><div><span class="sw" style="background:var(--navy-700)"></span>Sedang dikerjakan</div><div><span class="sw" style="background:var(--tint-green);border:1px solid var(--green)"></span>Sudah dijawab</div><div><span class="sw" style="background:var(--tint-orange);border:1px solid var(--orange)"></span>Ragu-ragu</div><div><span class="sw" style="background:#fff;border:1px solid var(--line)"></span>Belum dijawab</div></div>
    <div class="q-summary" id="qSummary"></div>
    <button class="btn-primary" style="margin-top:14px;background:var(--sakura-deep)" type="button" data-finish>Selesaikan ujian</button>
    <form method="POST" action="{{ route('ujian.submit', $attempt) }}" id="submitForm">@csrf<input type="hidden" name="payload" id="payload"><input type="hidden" name="auto" id="autoFlag" value="0"></form>
  </aside>
</div>
@endsection

@push('modals')
<x-modal id="finishModal" title="Selesaikan ujian?">
  <p class="desc" id="finishText"></p>
  <div class="modal-actions"><button type="button" class="cancel" data-modal-close>Batal</button><button type="button" class="ok" id="finishOk">Kirim jawaban</button></div>
</x-modal>
@endpush

@push('scripts')
<script src="{{ asset('js/cbt.js') }}?v={{ filemtime(public_path('js/cbt.js')) }}"></script>
@endpush
