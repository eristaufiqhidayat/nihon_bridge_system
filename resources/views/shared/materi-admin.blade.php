@extends('layouts.app')
@section('title', 'Kelola Materi')
@section('crumb')<b>Kelola Materi</b>@endsection

@php use App\Support\Catalog; @endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Kelola Materi</h2><p>Level {{ $level }} · perubahan langsung terlihat oleh peserta</p></div>
    <div class="btnrow">
      <form method="GET" class="filt" style="margin:0"><select name="level" aria-label="Level" data-autosubmit>@foreach (Catalog::LEVELS as $l)<option @selected($l === $level)>{{ $l }}</option>@endforeach</select></form>
      @if ($chapter)<button class="bt solid" type="button" data-modal-open="lessonModal">+ Tambah bagian</button>@endif
    </div>
  </div>
  <x-form-errors />
  <div class="materi-layout materi-admin">
    <div class="card pad"><h4 class="ct">Bab</h4>
      @foreach ($chapters as $c)
        <?php $pub = $c->lessons->where('status', 'Terbit')->count(); ?>
        <a class="list-row clickable {{ $chapter && $c->id === $chapter->id ? 'sel' : '' }}" href="{{ route('materi-admin.index', ['level' => $level, 'bab' => $c->id]) }}"><div class="ic ic-bg-blue">{{ $c->no }}</div><div class="info"><p>Bab {{ $c->no }} · {{ $c->judul }}</p><span>{{ $pub }} terbit · {{ $c->lessons->count() - $pub }} draf</span></div></a>
      @endforeach
      <form method="POST" action="{{ route('materi-admin.chapter.store') }}">@csrf<input type="hidden" name="level" value="{{ $level }}"><button class="bt outline" style="width:100%;margin-top:10px" type="submit">+ Bab baru</button></form>
    </div>
    <div class="card pad">
      @if (! $chapter)
        <div class="empty-state"><div class="ic">📂</div><b>Belum ada bab di level {{ $level }}</b><p>Buat bab pertama lewat tombol “+ Bab baru”.</p></div>
      @else
        <div class="sh"><h4>Bab {{ $chapter->no }} ·
            <button class="linkbtn" type="button" style="font-size:14.5px" data-modal-open="chapterModal" title="Ubah judul bab">{{ $chapter->judul }} ✏️</button></h4>
          <form method="POST" action="{{ route('materi-admin.chapter.update', $chapter) }}" class="inline">@csrf @method('PATCH')
            <input type="hidden" name="is_locked" value="0">
            <label class="check small"><input type="checkbox" name="is_locked" value="1" @checked($chapter->is_locked) data-autosubmit> Kunci sampai bab sebelumnya selesai</label>
          </form></div>
        @if ($chapter->lessons->isNotEmpty())
          <div class="tbl-wrap"><table><thead><tr><th>#</th><th>Bagian</th><th>Jenis</th><th>Durasi</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            @foreach ($chapter->lessons as $i => $l)
              <tr><td class="tnum">{{ $i + 1 }}</td>
                <td><b>{{ $l->judul }}</b>@if ($l->file_path)<br><a class="small" href="{{ asset('storage/' . $l->file_path) }}" target="_blank">📎 berkas</a>@endif</td>
                <td><span class="badge {{ Catalog::LESSON_TYPES[$l->jenis] ?? 'b-grey' }}">{{ $l->jenis }}</span></td><td class="tnum small">{{ $l->durasi }}</td>
                <td><form method="POST" action="{{ route('materi-admin.lesson.toggle', $l) }}" class="inline">@csrf @method('PATCH')<button class="badge {{ $l->status === 'Terbit' ? 'b-green' : 'b-grey' }}" style="border:none;cursor:pointer" title="Klik untuk ubah">{{ $l->status }}</button></form></td>
                <td style="white-space:nowrap">
                  <form method="POST" action="{{ route('materi-admin.lesson.move', $l) }}" class="inline">@csrf @method('PATCH')<input type="hidden" name="arah" value="naik"><button class="act-ic" aria-label="Naikkan" @disabled($i === 0)>↑</button></form>
                  <form method="POST" action="{{ route('materi-admin.lesson.move', $l) }}" class="inline">@csrf @method('PATCH')<input type="hidden" name="arah" value="turun"><button class="act-ic" aria-label="Turunkan" @disabled($i === $chapter->lessons->count() - 1)>↓</button></form>
                  <form method="POST" action="{{ route('materi-admin.lesson.destroy', $l) }}" class="inline" data-confirm-title="Hapus “{{ $l->judul }}”?" data-confirm="{{ $l->progress()->exists() ? 'Beberapa peserta sudah menyelesaikan bagian ini; progres mereka dihitung ulang. ' : '' }}Berkas di penyimpanan ikut dihapus." data-confirm-ok="Hapus bagian" data-danger>@csrf @method('DELETE')<button class="act-ic" aria-label="Hapus">🗑️</button></form>
                </td></tr>
            @endforeach
          </tbody></table></div>
        @else
          <div class="empty-state"><div class="ic">📂</div><b>Bab ini belum punya bagian</b><p>Tambahkan video, PDF, atau PPT pertama. Bab tetap tersembunyi dari peserta sampai ada bagian yang terbit.</p><button class="bt solid" type="button" data-modal-open="lessonModal">+ Tambah bagian pertama</button></div>
        @endif
      @endif
    </div>
  </div>
</div>
@endsection

@if ($chapter)
@push('modals')
<x-modal id="lessonModal" title="Tambah bagian · Bab {{ $chapter->no }}">
  <form method="POST" action="{{ route('materi-admin.lesson.store', $chapter) }}" enctype="multipart/form-data" novalidate>
    @csrf <input type="hidden" name="_modal" value="lessonModal">
    <x-form-errors modal="lessonModal" />
    <div class="field"><label for="lsT">Judul bagian</label><input id="lsT" name="judul" value="{{ old('judul') }}" placeholder="mis. Latihan mendengar Bab {{ $chapter->no }}"></div>
    <div class="opt-grid"><div class="field"><label for="lsJ">Jenis</label><select id="lsJ" name="jenis">@foreach (array_keys(Catalog::LESSON_TYPES) as $j)<option @selected(old('jenis') === $j)>{{ $j }}</option>@endforeach</select></div>
      <div class="field"><label for="lsD">Durasi / halaman</label><input id="lsD" name="durasi" value="{{ old('durasi') }}" placeholder="mis. 12:30 atau 8 hlm"></div></div>
    <div class="field"><label for="lsF">Berkas</label><input id="lsF" name="berkas" type="file" accept="video/*,audio/*,.pdf,.ppt,.pptx"><div class="hint">Video, audio, PDF, atau PPT. Boleh dikosongkan dan diunggah nanti.</div></div>
    <div class="field"><label for="lsS">Status</label><select id="lsS" name="status"><option>Terbit</option><option @selected(old('status') === 'Draf')>Draf</option></select></div>
    <div class="modal-actions"><button type="button" class="cancel" data-modal-close>Batal</button><button class="ok" type="submit">Unggah &amp; simpan</button></div>
  </form>
</x-modal>
<x-modal id="chapterModal" title="Judul Bab {{ $chapter->no }}">
  <form method="POST" action="{{ route('materi-admin.chapter.update', $chapter) }}">
    @csrf @method('PATCH') <input type="hidden" name="_modal" value="chapterModal">
    <div class="field"><label for="chJ">Judul</label><input id="chJ" name="judul" value="{{ $chapter->judul }}"></div>
    <div class="modal-actions"><button type="button" class="cancel" data-modal-close>Batal</button><button class="ok" type="submit">Simpan</button></div>
  </form>
</x-modal>
@endpush
@endif
