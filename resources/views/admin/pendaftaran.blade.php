@extends('layouts.app')
@section('title', 'Pendaftaran & Seleksi')
@section('crumb')<b>Pendaftaran &amp; Seleksi</b>@endsection

@php
  use App\Support\Catalog;
  use App\Support\Fmt;
@endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Pendaftaran &amp; Seleksi</h2><p>Pendaftar dari formulir publik di halaman Daftar</p></div><a class="bt outline" href="{{ route('daftar') }}" target="_blank">Buka formulir publik</a></div>
  <x-form-errors />
  <div class="pipeline">
    <a class="{{ $f === 'Semua' ? 'on' : '' }}" href="{{ route('pendaftaran.index') }}"><b class="tnum">{{ $all->count() }}</b>Semua</a>
    @foreach (Catalog::APP_STATUS as $k => [$l])
      <a class="{{ $f === $k ? 'on' : '' }}" href="{{ route('pendaftaran.index', ['status' => $k]) }}"><b class="tnum">{{ $all->where('status', $k)->count() }}</b>{{ $l }}</a>
    @endforeach
  </div>
  <div class="grid g-admin">
    <div class="card" style="padding:0"><div class="tbl-wrap"><table><thead><tr><th>Pendaftar</th><th>Program</th><th>Tanggal</th><th>Status</th></tr></thead><tbody>
      @forelse ($list as $a)
        <tr class="clickable {{ $sel && $a->id === $sel->id ? 'sel' : '' }}" onclick="location.href='{{ route('pendaftaran.index', ['status' => $f === 'Semua' ? null : $f, 'sel' => $a->id]) }}'"><td><b>{{ $a->nama }}</b><br><span class="small muted">{{ $a->reg_no }} · {{ $a->asal }}</span></td><td class="small">{{ $a->program }}</td><td class="small tnum">{{ Fmt::date($a->registered_at) }}</td><td><span class="badge {{ $a->status_badge }}">{{ $a->status_label }}</span></td></tr>
      @empty
        <tr><td colspan="4" class="empty">Tidak ada pendaftar dengan status ini.</td></tr>
      @endforelse
    </tbody></table></div></div>

    @if ($sel)
      <?php $miss = $sel->missingFiles(); ?>
      <div class="card pad"><div class="mon-profile"><div class="av lg">{{ \App\Support\Fmt::initials($sel->nama) }}</div><div><h4>{{ $sel->nama }}</h4><p>{{ $sel->reg_no }} · daftar {{ Fmt::date($sel->registered_at) }}</p></div></div>
        <div class="review-grid" style="margin:14px 0">
          @foreach (['Asal' => $sel->asal, 'Usia' => $sel->usia ?? '–', 'Pendidikan' => $sel->pendidikan, 'WhatsApp' => $sel->hp, 'Program' => $sel->program, 'Status' => $sel->status_label] as $k => $v)<div><span>{{ $k }}</span><b>{{ $v }}</b></div>@endforeach
        </div>
        @if ($sel->tes_jadwal)<div class="callout" style="margin-bottom:12px"><b>Tes &amp; wawancara:</b> {{ $sel->tes_jadwal }} · Kantor {{ config('nihonbridge.org.name') }}</div>@endif
        @if ($sel->status === 'diterima')<div class="callout" style="margin-bottom:12px"><b>Diterima.</b> Akun peserta dibuat dan ditempatkan di kelas {{ $sel->classroom?->kode }}.</div>@endif
        @if ($sel->alasan)<p class="form-error">Alasan ditolak: {{ $sel->alasan }}</p>@endif
        <h4 class="ct" style="font-size:13px">Berkas</h4>
        @foreach ([['ktp', 'KTP'], ['ijazah', 'Ijazah'], ['foto', 'Pas foto'], ['izin', 'Surat izin orang tua'], ['kk', 'Kartu keluarga']] as [$k, $l])
          @continue($k === 'kk' && ! $sel->hasFile('kk'))
          <?php $has = $sel->hasFile($k); ?>
          <div class="doc-row">{{ $l }}
            @if ($has && ! empty($sel->berkas[$k]['path']))<a class="doc-status selesai" href="{{ route('pendaftaran.file', [$sel, $k]) }}" style="text-decoration:none">Ada · unduh</a>
            @else<span class="doc-status {{ $has ? 'selesai' : 'belum' }}">{{ $has ? 'Ada' : 'Belum' }}</span>@endif</div>
        @endforeach
        <div class="btnrow" style="margin-top:14px">
          @if ($sel->status === 'baru')
            <form method="POST" action="{{ route('pendaftaran.pass', $sel) }}" class="inline">@csrf<button class="bt solid" @disabled($miss)>Lolos berkas</button></form>
          @elseif ($sel->status === 'berkas')
            <button class="bt solid" type="button" data-modal-open="testModal">Jadwalkan tes &amp; wawancara</button>
          @elseif ($sel->status === 'tes')
            <button class="bt green" type="button" data-modal-open="acceptModal">Terima &amp; buat akun</button>
          @endif
          @if (in_array($sel->status, ['baru', 'berkas', 'tes'], true))
            <button class="bt outline" type="button" data-modal-open="rejectModal">Tolak</button>
          @endif
        </div>
        @if ($sel->status === 'baru' && $miss)
          <p class="rule-note">Belum bisa diloloskan: {{ implode(', ', $miss) }} belum ada.
            <form method="POST" action="{{ route('pendaftaran.remind', $sel) }}" class="inline">@csrf<button class="linkbtn" type="submit">Kirim pengingat</button></form></p>
        @endif
      </div>
    @endif
  </div>
</div>
@endsection

@if ($sel)
@push('modals')
<x-modal id="testModal" title="Jadwalkan tes &amp; wawancara">
  <form method="POST" action="{{ route('pendaftaran.test', $sel) }}">@csrf <input type="hidden" name="_modal" value="testModal">
    <x-form-errors modal="testModal" />
    <div class="opt-grid"><div class="field"><label for="tTgl">Tanggal</label><input id="tTgl" type="date" name="tanggal" min="{{ now()->toDateString() }}" value="{{ old('tanggal', now()->addWeekday()->toDateString()) }}"></div>
      <div class="field"><label for="tJam">Jam</label><select id="tJam" name="jam"><option>09.00</option><option>13.00</option></select></div></div>
    <p class="rule-note">Materi: tes tertulis dasar, tes fisik ringan, wawancara motivasi. Undangan dikirim lewat WhatsApp {{ $sel->hp }}.</p>
    <div class="modal-actions"><button type="button" class="cancel" data-modal-close>Batal</button><button class="ok" type="submit">Kirim undangan</button></div>
  </form>
</x-modal>
<x-modal id="acceptModal" title="Terima {{ $sel->nama }}">
  <p class="desc">Akun peserta dibuat dan tautan aktivasi dikirim ke email pendaftar.</p>
  <form method="POST" action="{{ route('pendaftaran.accept', $sel) }}">@csrf
    <div class="field"><label for="acKelas">Tempatkan di kelas</label><select id="acKelas" name="classroom_id">@foreach ($n5Classes as $c)<option value="{{ $c->id }}">{{ $c->kode }}</option>@endforeach</select><div class="hint">Peserta baru mulai dari kelas N5.</div></div>
    <div class="modal-actions"><button type="button" class="cancel" data-modal-close>Batal</button><button class="ok" type="submit">Terima peserta</button></div>
  </form>
</x-modal>
<x-modal id="rejectModal" title="Tolak {{ $sel->nama }}?">
  <form method="POST" action="{{ route('pendaftaran.reject', $sel) }}">@csrf
    <div class="field"><label for="rjAlasan">Alasan (dikirim ke pendaftar)</label><select id="rjAlasan" name="alasan">@foreach (Catalog::REJECT_REASONS as $r)<option>{{ $r }}</option>@endforeach</select></div>
    <div class="modal-actions"><button type="button" class="cancel" data-modal-close>Batal</button><button class="ok danger" type="submit">Tolak pendaftar</button></div>
  </form>
</x-modal>
@endpush
@endif
