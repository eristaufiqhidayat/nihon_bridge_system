@extends('layouts.app')
@section('title', 'Kelas & Jadwal')
@section('crumb')<b>Kelas &amp; Jadwal</b>@endsection

@php use App\Support\Catalog; @endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Kelas &amp; Jadwal</h2><p>{{ $classes->count() }} kelas aktif · klik sel jadwal untuk mengubah</p></div><button class="bt solid" type="button" data-modal-open="classModal">+ Kelas baru</button></div>
  <div class="card mb16" style="padding:0"><div class="tbl-wrap"><table><thead><tr><th>Kelas</th><th>Angkatan</th><th>Wali kelas</th><th>Peserta</th><th>Ruang</th><th>Periode</th></tr></thead><tbody>
    @foreach ($classes as $k)
      <tr class="clickable {{ $class && $k->id === $class->id ? 'sel' : '' }}" onclick="location.href='{{ route('kelas-admin.index', ['kelas' => $k->kode]) }}'"><td><span class="badge {{ Catalog::LV_BADGE[$k->level] }}">{{ $k->kode }}</span></td><td class="small">{{ $k->batch?->nama ?? '–' }}</td><td>{{ $k->wali?->name ?? '–' }}</td><td class="tnum">{{ $k->students_count }}</td><td>{{ $k->ruang }}</td><td class="small">{{ $k->periode }}</td></tr>
    @endforeach
  </tbody></table></div></div>
  @if ($class)
  <div class="card" style="padding:0"><div class="pad" style="padding-bottom:6px"><div class="sh"><h4>Jadwal mingguan {{ $class->kode }}</h4><span class="small muted">Ruang {{ $class->ruang }}</span></div></div>
    <div class="tbl-wrap"><table class="sched"><thead><tr><th>Waktu</th>@foreach (Catalog::DAYS as $d)<th>{{ $d }}</th>@endforeach</tr></thead><tbody>
      @foreach (Catalog::SLOTS as $si => $w)
        <tr><td class="tnum"><b>{{ $w }}</b></td>
          @foreach (Catalog::DAYS as $di => $dn)
            <?php $x = $grid[$si][$di]; ?>
            <td><button type="button" class="slot slot-btn {{ $x ? $x->subject_bg : 'slot-empty' }}" data-modal-open="slotModal"
              data-fill="{{ json_encode(['@title' => "{$class->kode} · {$dn} {$w}", 'slot' => $si, 'day' => $di, 'subject' => $x?->subject ?? '', 'instructor_id' => $x?->instructor_id ?? $class->wali_id], JSON_UNESCAPED_UNICODE) }}">
              @if ($x){{ $x->subject_name }}<br><span style="font-weight:500">{{ $x->instructor->sensei_name }}</span>@else + Isi @endif</button></td>
          @endforeach
        </tr>
      @endforeach
    </tbody></table></div></div>
  @endif
</div>
@endsection

@push('modals')
@if ($class)
<x-modal id="slotModal" title="Ubah jadwal">
  <form method="POST" action="{{ route('kelas-admin.slot', $class) }}">@csrf <input type="hidden" name="_modal" value="slotModal">
    <input type="hidden" name="slot" value="{{ old('slot') }}"><input type="hidden" name="day" value="{{ old('day') }}">
    <x-form-errors modal="slotModal" />
    <div class="opt-grid"><div class="field"><label for="slM">Mata pelajaran</label><select id="slM" name="subject"><option value="">– Kosong –</option>@foreach (Catalog::MAPEL as $k => $v)<option value="{{ $k }}" @selected(old('subject') === $k)>{{ $v }}</option>@endforeach</select></div>
      <div class="field"><label for="slI">Instruktur</label><select id="slI" name="instructor_id">@foreach ($instructors as $i)<option value="{{ $i->id }}" @selected((string) old('instructor_id') === (string) $i->id)>{{ $i->name }}</option>@endforeach</select></div></div>
    <p class="rule-note">Sistem menolak jadwal bila instruktur sudah mengajar kelas lain di jam yang sama.</p>
    <div class="modal-actions"><button type="button" class="cancel" data-modal-close>Batal</button><button class="ok" type="submit">Simpan</button></div>
  </form>
</x-modal>
@endif
<x-modal id="classModal" title="Kelas baru">
  <form method="POST" action="{{ route('kelas-admin.store') }}" novalidate>@csrf <input type="hidden" name="_modal" value="classModal">
    <x-form-errors modal="classModal" />
    <div class="opt-grid">
      <div class="field"><label for="ncKode">Kode kelas</label><input id="ncKode" name="kode" value="{{ old('kode') }}" placeholder="mis. N5-D"></div>
      <div class="field"><label for="ncLvl">Level</label><select id="ncLvl" name="level">@foreach (['N5', 'N4', 'N3', 'N2', 'N1'] as $l)<option @selected(old('level') === $l)>{{ $l }}</option>@endforeach</select></div>
      <div class="field"><label for="ncBatch">Angkatan</label><select id="ncBatch" name="batch_id">@foreach ($batches as $b)<option value="{{ $b->id }}" @selected((string) old('batch_id', $batches->last()?->id) === (string) $b->id)>{{ $b->nama }} · {{ $b->periode }}</option>@endforeach</select></div>
      <div class="field"><label for="ncWali">Wali kelas</label><select id="ncWali" name="wali_id">@foreach ($instructors as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach</select></div>
      <div class="field"><label for="ncRuang">Ruang</label><input id="ncRuang" name="ruang" value="{{ old('ruang') }}" placeholder="mis. 2A"></div></div>
    <div class="modal-actions"><button type="button" class="cancel" data-modal-close>Batal</button><button class="ok" type="submit">Buat kelas</button></div>
  </form>
</x-modal>
@endpush
