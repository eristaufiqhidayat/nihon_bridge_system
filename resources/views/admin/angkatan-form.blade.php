@extends('layouts.app')
@section('title', $batch->exists ? 'Edit Angkatan' : 'Tambah Angkatan')
@section('crumb')<a href="{{ route('angkatan.index') }}">Data Angkatan</a> › <b>{{ $batch->exists ? $batch->nama : 'Tambah angkatan' }}</b>@endsection

@php use App\Support\Fmt; @endphp

@section('content')
<?php $b = $batch; ?>
<div class="page">
  <div class="page-head"><div><h2>{{ $b->exists ? 'Edit angkatan · ' . $b->nama : 'Tambah angkatan' }}</h2>
    <p>Atur periode, total biaya, dan tahapan pembayaran beserta tanggal jatuh temponya.</p></div>
    <a class="bt outline" href="{{ route('angkatan.index') }}">‹ Kembali ke daftar</a></div>

  <form method="POST" action="{{ $b->exists ? route('angkatan.update', $b) : route('angkatan.store') }}" novalidate class="grid" style="gap:16px">
    @csrf @if ($b->exists) @method('PUT') @endif
    @if ($errors->any() && ! $errors->has('hapus'))<div class="form-error" role="alert">{{ $errors->first() }}</div>@endif

    <div class="card pad"><h4 class="ct">Angkatan</h4>
      <div class="opt-grid">
        <div class="field"><label for="aProgram">Program kursus</label><select id="aProgram" name="program_id">@foreach ($programs as $p)<option value="{{ $p->id }}" @selected((string) old('program_id', $b->program_id) === (string) $p->id)>{{ $p->nama }}{{ $p->biaya ? ' · ' . Fmt::rupiah($p->biaya) : '' }}</option>@endforeach</select></div>
        <div class="field"><label for="aKode">Kode</label><input id="aKode" name="kode" value="{{ old('kode', $b->kode) }}" placeholder="mis. 2026-09"></div>
        <div class="field"><label for="aNama">Nama angkatan</label><input id="aNama" name="nama" value="{{ old('nama', $b->nama) }}" placeholder="mis. Angkatan 5"></div>
        <div class="field"><label for="aKuota">Kuota peserta</label><input id="aKuota" name="kuota" type="number" min="1" value="{{ old('kuota', $b->kuota) }}" placeholder="Opsional"></div>
        <div class="field"><label for="aMulai">Mulai</label><input id="aMulai" name="mulai" type="date" value="{{ old('mulai', $b->mulai?->toDateString()) }}"></div>
        <div class="field"><label for="aSelesai">Selesai</label><input id="aSelesai" name="selesai" type="date" value="{{ old('selesai', $b->selesai?->toDateString()) }}"></div>
        <div class="field"><label for="aBiaya">Total biaya (Rp)</label><input id="aBiaya" name="biaya" type="number" min="0" step="1000" value="{{ old('biaya', $b->biaya) }}" placeholder="Kosongkan untuk biaya program"><div class="hint">Angka tanpa titik. Peserta yang punya total biaya sendiri tetap memakai biayanya.</div></div>
      </div></div>

    <div class="card pad"><h4 class="ct">Tahapan pembayaran · <span data-repeat-count="tahapList">{{ count($dues) }}</span> tahap</h4>
      <p class="small muted" style="margin-top:0">Nominal tiap tahap = total biaya ÷ jumlah tahap (sisa pembulatan masuk ke tahap terakhir). Tahap diurutkan otomatis menurut tanggal jatuh tempo.</p>
      <div id="tahapList" data-template="tahapTpl" class="grid" style="gap:8px;max-width:420px">
        @foreach ($dues as $i => $d)
          <div class="field" data-repeat-row style="display:flex;gap:8px;align-items:center;margin:0">
            <label style="min-width:70px;margin:0">Tahap <span data-repeat-no>{{ $i + 1 }}</span></label>
            <input name="jatuh_tempo[]" type="date" value="{{ $d }}" aria-label="Jatuh tempo tahap {{ $i + 1 }}">
            <button class="act-ic" type="button" data-repeat-remove aria-label="Hapus tahap">🗑️</button>
          </div>
        @endforeach
      </div>
      <template id="tahapTpl">
        <div class="field" data-repeat-row style="display:flex;gap:8px;align-items:center;margin:0">
          <label style="min-width:70px;margin:0">Tahap <span data-repeat-no></span></label>
          <input name="jatuh_tempo[]" type="date" aria-label="Jatuh tempo tahap">
          <button class="act-ic" type="button" data-repeat-remove aria-label="Hapus tahap">🗑️</button>
        </div>
      </template>
      <button class="bt outline" type="button" data-repeat-add="tahapList" style="margin-top:12px">+ Tambah tahap</button>
    </div>

    <div><button class="bt solid" type="submit">{{ $b->exists ? 'Simpan perubahan' : 'Simpan angkatan' }}</button></div>
  </form>

  @if ($b->exists)
  <div class="card pad" style="margin-top:16px"><h4 class="ct">Hapus angkatan</h4>
    @error('hapus')<div class="form-error" role="alert">{{ $message }}</div>@enderror
    @if ($blockers)
      <p class="small">Angkatan ini belum bisa dihapus karena masih punya <b>{{ collect($blockers)->map(fn ($n, $l) => "{$n} {$l}")->join(', ', ' dan ') }}</b>. Pindahkan kelas dan peserta ke angkatan lain lebih dulu.</p>
    @else
      <p class="small muted">Belum ada kelas atau peserta. Angkatan dan tahapan pembayarannya akan dihapus permanen.</p>
    @endif
    <form method="POST" action="{{ route('angkatan.destroy', $b) }}" class="inline" data-confirm-title="Hapus {{ $b->nama }}?" data-confirm="Angkatan dan tahapan pembayarannya akan dihapus permanen." data-confirm-ok="Hapus angkatan" data-danger>@csrf @method('DELETE')
      <button class="bt danger" type="submit" @disabled($blockers)>Hapus angkatan</button></form>
  </div>
  @endif
</div>
@endsection
