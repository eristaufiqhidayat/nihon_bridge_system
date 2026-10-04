@extends('layouts.app')
@section('title', 'Bank Soal')
@section('crumb')<b>Bank Soal</b>@endsection

@php use App\Support\Catalog; @endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Bank Soal</h2><p>Kelola soal untuk semua level JLPT. Paket ujian yang sudah terbit memakai salinan soal, jadi perubahan di sini tidak mengubah ujian yang sedang berjalan.</p></div></div>
  <form method="GET" class="filter-bar">
    <select name="lvl" aria-label="Filter level" data-autosubmit>@foreach (array_merge(['Semua'], Catalog::LEVELS) as $o)<option value="{{ $o }}" @selected($o === $f['lvl'])>Level: {{ $o }}</option>@endforeach</select>
    <select name="kat" aria-label="Filter kategori" data-autosubmit><option value="Semua">Kategori: Semua</option>@foreach (Catalog::SECTIONS as $k => $s)<option value="{{ $k }}" @selected($k === $f['kat'])>Kategori: {{ $s['name'] }}</option>@endforeach</select>
    <input name="q" type="search" placeholder="🔍 Cari soal, ID, atau opsi…" value="{{ $f['q'] }}">
    <button class="bt solid btn-add" type="button" data-modal-open="soalModal"
      data-fill="{{ json_encode(['@action' => route('banksoal.store'), '@method' => 'POST', '@title' => 'Tambah soal baru', '@submit' => 'Simpan soal', 'question' => '', 'options[0]' => '', 'options[1]' => '', 'options[2]' => '', 'options[3]' => '', 'answer_key' => 0, 'section' => 'bunpou', 'level' => 'N4', 'explanation' => ''], JSON_UNESCAPED_UNICODE) }}">+ Tambah soal</button>
  </form>
  <div class="card" style="padding:0"><div class="tbl-wrap"><table>
    <thead><tr><th>ID</th><th>Soal</th><th>Kategori</th><th>Level</th><th>Kunci</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse ($rows as $q)
        <tr><td class="tnum small muted">{{ $q->code }}</td><td><div class="soal-text">{{ $q->question }}</div></td>
          <td><span class="badge {{ $q->section_badge }}">{{ $q->section_name }}</span></td><td><span class="badge {{ Catalog::LV_BADGE[$q->level] }}">{{ $q->level }}</span></td>
          <td style="font-family:'Noto Sans JP',sans-serif;white-space:nowrap"><b>{{ 'ABCD'[$q->answer_key] }}.</b> {{ $q->options[$q->answer_key] }}</td>
          <td style="white-space:nowrap">
            <button class="act-ic" type="button" aria-label="Edit {{ $q->code }}" data-modal-open="soalModal"
              data-fill="{{ json_encode(['@action' => route('banksoal.update', $q), '@method' => 'PUT', '@title' => "Edit soal {$q->code}", '@submit' => 'Simpan perubahan', 'question' => $q->question, 'options[0]' => $q->options[0], 'options[1]' => $q->options[1], 'options[2]' => $q->options[2], 'options[3]' => $q->options[3], 'answer_key' => $q->answer_key, 'section' => $q->section, 'level' => $q->level, 'explanation' => $q->explanation], JSON_UNESCAPED_UNICODE) }}">✏️</button>
            <form method="POST" action="{{ route('banksoal.destroy', $q) }}" class="inline" data-confirm-title="Hapus soal {{ $q->code }}?" data-confirm="<span style=&quot;font-family:'Noto Sans JP',sans-serif&quot;>{{ $q->question }}</span><br><br>Soal akan dihapus dari bank. Paket ujian yang sudah memakai soal ini tidak berubah." data-confirm-ok="Hapus soal" data-danger>@csrf @method('DELETE')<button class="act-ic" aria-label="Hapus {{ $q->code }}">🗑️</button></form>
          </td></tr>
      @empty
        <tr><td colspan="6" class="empty">Tidak ada soal yang cocok dengan filter. <a class="linkbtn" href="{{ route('banksoal.index') }}">Reset filter</a></td></tr>
      @endforelse
    </tbody></table></div></div>
  <div class="pager"><span>Menampilkan {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} dari {{ $rows->total() }} soal{{ $filtered ? " (total bank {$total})" : '' }}</span>
    <div class="pg-btns">
      @if ($rows->onFirstPage())<span class="pg">‹</span>@else<a href="{{ $rows->previousPageUrl() }}" aria-label="Sebelumnya">‹</a>@endif
      @foreach (range(1, $rows->lastPage()) as $p)<a class="{{ $p === $rows->currentPage() ? 'cur' : '' }}" href="{{ $rows->url($p) }}">{{ $p }}</a>@endforeach
      @if ($rows->hasMorePages())<a href="{{ $rows->nextPageUrl() }}" aria-label="Berikutnya">›</a>@else<span class="pg">›</span>@endif
    </div></div>
</div>
@endsection

@push('modals')
<x-modal id="soalModal" title="{{ old('_modal') === 'soalModal' ? (old('_method') === 'PUT' ? 'Edit soal' : 'Tambah soal baru') : 'Tambah soal baru' }}" wide>
  <form method="POST" action="{{ old('_action', route('banksoal.store')) }}" novalidate>
    @csrf <input type="hidden" name="_modal" value="soalModal"><input type="hidden" name="_method" value="{{ old('_method', 'POST') }}">
    <x-form-errors modal="soalModal" />
    <div class="field"><label for="mSoal">Pertanyaan</label><textarea id="mSoal" name="question" placeholder="Tulis pertanyaan. Gunakan （　　） untuk bagian kosong.">{{ old('question') }}</textarea></div>
    <div class="opt-grid">@foreach ([0, 1, 2, 3] as $j)<div class="field"><label for="mO{{ $j }}">Opsi {{ 'ABCD'[$j] }}</label><input id="mO{{ $j }}" name="options[{{ $j }}]" type="text" value="{{ old("options.$j") }}"></div>@endforeach</div>
    <div class="opt-grid" style="grid-template-columns:repeat(3,1fr)">
      <div class="field"><label for="mKey">Kunci</label><select id="mKey" name="answer_key">@foreach ([0, 1, 2, 3] as $j)<option value="{{ $j }}" @selected((string) old('answer_key') === (string) $j)>{{ 'ABCD'[$j] }}</option>@endforeach</select></div>
      <div class="field"><label for="mKat">Kategori</label><select id="mKat" name="section">@foreach (Catalog::SECTIONS as $k => $s)<option value="{{ $k }}" @selected(old('section', 'bunpou') === $k)>{{ $s['name'] }}</option>@endforeach</select></div>
      <div class="field"><label for="mLvl">Level</label><select id="mLvl" name="level">@foreach (Catalog::LEVELS as $l)<option @selected(old('level', 'N4') === $l)>{{ $l }}</option>@endforeach</select></div></div>
    <div class="field"><label for="mExp">Pembahasan</label><input id="mExp" name="explanation" type="text" value="{{ old('explanation') }}" placeholder="Penjelasan singkat jawaban benar"></div>
    <div class="modal-actions"><button type="button" class="cancel" data-modal-close>Batal</button><button class="ok" type="submit">Simpan soal</button></div>
  </form>
</x-modal>
@endpush

@push('scripts')
<script>
  // Simpan URL aksi agar form bisa dibuka ulang dengan benar setelah validasi gagal
  document.querySelector('#soalModal form').addEventListener('submit', function () {
    let i = this.querySelector('input[name=_action]');
    if (!i) { i = document.createElement('input'); i.type = 'hidden'; i.name = '_action'; this.appendChild(i); }
    i.value = this.action;
  });
</script>
@endpush
