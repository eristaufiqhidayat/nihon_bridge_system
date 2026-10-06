@extends('layouts.app')
@section('title', $student->exists ? 'Edit Peserta' : 'Tambah Peserta')
@section('crumb')<a href="{{ route('peserta-admin.index') }}">Data Peserta</a> › <b>{{ $student->exists ? $user->name : 'Tambah peserta' }}</b>@endsection

@php
  use App\Support\Catalog;
  use App\Support\Fmt;
@endphp

@section('content')
<?php $s = $student; ?>
<div class="page">
  <div class="page-head"><div><h2>{{ $s->exists ? 'Edit peserta · ' . $user->name : 'Tambah peserta' }}</h2>
    <p>{{ $s->exists ? 'NIS ' . $s->nis . ' · tahap ' . ($s->stage + 1) . '. ' . $s->stageName() : 'Tautan aktivasi akun (buat password) dikirim ke email peserta setelah disimpan.' }}</p></div>
    <a class="bt outline" href="{{ route('peserta-admin.index') }}">‹ Kembali ke daftar</a></div>

  <form method="POST" action="{{ $s->exists ? route('peserta-admin.update', $s) : route('peserta-admin.store') }}" novalidate class="grid" style="gap:16px">
    @csrf @if ($s->exists) @method('PUT') @endif
    @if ($errors->any() && ! $errors->has('hapus'))<div class="form-error" role="alert">{{ $errors->first() }}</div>@endif

    <div class="card pad"><h4 class="ct">Akun</h4>
      <div class="opt-grid">
        <div class="field"><label for="fNama">Nama lengkap</label><input id="fNama" name="name" value="{{ old('name', $user->name) }}" placeholder="mis. Intan Kusuma"></div>
        <div class="field"><label for="fEmail">Email (untuk login)</label><input id="fEmail" name="email" type="email" value="{{ old('email', $user->email) }}" placeholder="nama@email.com"></div>
        <div class="field"><label for="fHp">No. HP / WhatsApp</label><input id="fHp" name="phone" value="{{ old('phone', $user->phone) }}"></div>
        <div class="field"><label for="fAktif">Akun login</label>
          <input type="hidden" name="is_active" value="0">
          <label class="switch" title="Aktif / nonaktif"><input id="fAktif" type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))><span></span></label></div>
      </div></div>

    <div class="card pad"><h4 class="ct">Keikutsertaan</h4>
      <div class="opt-grid">
        <div class="field"><label for="fNis">NIS</label><input id="fNis" name="nis" value="{{ old('nis', $s->nis) }}" placeholder="Kosongkan untuk dibuat otomatis"></div>
        <div class="field"><label for="fJalur">Jalur ke Jepang</label><select id="fJalur" name="program">@foreach (Catalog::PROGRAMS as $k => $v)<option value="{{ $k }}" @selected(old('program', $s->program) === $k)>{{ $k }}</option>@endforeach</select></div>
        <div class="field"><label for="fAngkatan">Angkatan</label><select id="fAngkatan" name="batch_id"><option value="">– Ikuti kelas –</option>@foreach ($batches as $b)<option value="{{ $b->id }}" @selected((string) old('batch_id', $s->batch_id) === (string) $b->id)>{{ $b->nama }} · {{ $b->program?->nama }}</option>@endforeach</select></div>
        <div class="field"><label for="fKelas">Kelas</label><select id="fKelas" name="classroom_id"><option value="">– Belum ada –</option>@foreach ($classes as $c)<option value="{{ $c->id }}" @selected((string) old('classroom_id', $s->classroom_id) === (string) $c->id)>{{ $c->kode }}{{ $c->batch ? ' · ' . $c->batch->nama : '' }}</option>@endforeach</select></div>
        <div class="field"><label for="fMode">Mode kelas</label><select id="fMode" name="class_mode">@foreach (Catalog::CLASS_MODES as $k => $v)<option value="{{ $k }}" @selected(old('class_mode', $s->class_mode) === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="field"><label for="fStatus">Status peserta</label><select id="fStatus" name="enrollment_status">@foreach (Catalog::ENROLLMENT as $k => $v)<option value="{{ $k }}" @selected(old('enrollment_status', $s->enrollment_status) === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="field"><label for="fMulai">Mulai kelas</label><input id="fMulai" name="class_start" type="date" value="{{ old('class_start', $s->class_start?->toDateString()) }}"></div>
        <div class="field"><label for="fSelesai">Selesai kelas</label><input id="fSelesai" name="class_end" type="date" value="{{ old('class_end', $s->class_end?->toDateString()) }}"></div>
        <div class="field"><label for="fBiaya">Total biaya (Rp)</label><input id="fBiaya" name="total_fee" type="number" min="0" step="1000" value="{{ old('total_fee', $s->total_fee) }}" placeholder="Kosongkan untuk biaya program"><div class="hint">Angka tanpa titik, mis. 12500000</div></div>
      </div></div>

    <div class="card pad"><h4 class="ct">Biodata</h4>
      <div class="opt-grid">
        <div class="field"><label for="fTmp">Tempat lahir</label><input id="fTmp" name="birth_place" value="{{ old('birth_place', $s->birth_place) }}"></div>
        <div class="field"><label for="fTgl">Tanggal lahir</label><input id="fTgl" name="birth_date" type="date" value="{{ old('birth_date', $s->birth_date?->toDateString()) }}"></div>
        <div class="field"><label for="fJk">Jenis kelamin</label><select id="fJk" name="gender"><option value="">–</option>@foreach (Catalog::GENDERS as $k => $v)<option value="{{ $k }}" @selected(old('gender', $s->gender) === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="field"><label for="fTb">Tinggi badan (cm)</label><input id="fTb" name="height_cm" type="number" min="100" max="230" value="{{ old('height_cm', $s->height_cm) }}"></div>
        <div class="field"><label for="fAgama">Agama</label><select id="fAgama" name="religion"><option value="">–</option>@foreach (Catalog::RELIGIONS as $v)<option @selected(old('religion', $s->religion) === $v)>{{ $v }}</option>@endforeach</select></div>
        <div class="field"><label for="fNikah">Status pernikahan</label><select id="fNikah" name="marital_status"><option value="">–</option>@foreach (Catalog::MARITAL as $k => $v)<option value="{{ $k }}" @selected(old('marital_status', $s->marital_status) === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="field"><label for="fPaspor">No. paspor</label><input id="fPaspor" name="passport_no" value="{{ old('passport_no', $s->passport_no) }}" placeholder="Isi jika sudah ada"></div>
        <div class="field"><label for="fWali">Telepon orang tua / wali</label><input id="fWali" name="guardian_contact" value="{{ old('guardian_contact', $s->guardian_contact) }}"></div>
        <div class="field" style="grid-column:1/-1"><label for="fKtp">Alamat KTP</label><input id="fKtp" name="address_ktp" value="{{ old('address_ktp', $s->address_ktp) }}"></div>
        <div class="field" style="grid-column:1/-1"><label for="fDom">Alamat domisili</label><input id="fDom" name="address_domicile" value="{{ old('address_domicile', $s->address_domicile) }}"></div>
        <div class="field" style="grid-column:1/-1"><label for="fCatatan">Catatan</label><input id="fCatatan" name="note" value="{{ old('note', $s->note) }}"></div>
      </div></div>

    <div><button class="bt solid" type="submit">{{ $s->exists ? 'Simpan perubahan' : 'Simpan & kirim aktivasi' }}</button></div>
  </form>

  @if ($s->exists)
  <div class="card pad" style="margin-top:16px"><h4 class="ct">Hapus peserta</h4>
    @error('hapus')<div class="form-error" role="alert">{{ $message }}</div>@enderror
    @if ($blockers)
      <p class="small">Peserta ini belum bisa dihapus karena masih terhubung dengan: <b>{{ collect($blockers)->map(fn ($n, $l) => "{$n} {$l}")->join(', ', ' dan ') }}</b>. Jika peserta berhenti, ubah status menjadi "Keluar" dan nonaktifkan akunnya.</p>
    @else
      <p class="small muted">Belum ada data terkait. Akun login dan biodata akan dihapus permanen.</p>
    @endif
    <form method="POST" action="{{ route('peserta-admin.destroy', $s) }}" class="inline" data-confirm-title="Hapus {{ $user->name }}?" data-confirm="Akun login dan biodata peserta ini akan dihapus permanen." data-confirm-ok="Hapus peserta" data-danger>@csrf @method('DELETE')
      <button class="bt danger" type="submit" @disabled($blockers)>Hapus peserta</button></form>
  </div>
  @endif
</div>
@endsection
