@extends('layouts.app')
@section('title', 'Profil')
@section('crumb')<b>Profil</b>@endsection

@php
  $s = $user->student;
  $role = match ($user->role) { 'instruktur' => 'Instruktur (' . $user->sensei_name . ')', 'admin' => 'Administrator', default => $user->role_label };
@endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Profil</h2><p>Kelola data diri, keamanan akun, dan notifikasi.</p></div></div>
  <div class="profile-layout">
    <div class="card pad" style="text-align:center">
      <div class="av lg" style="margin:0 auto 10px;width:76px;height:76px;font-size:24px;overflow:hidden">@if ($user->avatar_path)<img class="sb-user-photo" src="{{ asset('storage/' . $user->avatar_path) }}" alt="">@else{{ $user->initials }}@endif</div>
      <h4 style="margin:0 0 2px;font-size:16px">{{ $user->name }}</h4><p class="small muted" style="margin:0 0 12px">{{ $role }}</p>
      @if ($s)
        <div class="list-row" style="text-align:left"><div class="ic ic-bg-blue">🆔</div><div class="info"><p>{{ $s->nis }}</p><span>Nomor induk siswa</span></div></div>
        <div class="list-row" style="text-align:left"><div class="ic ic-bg-purple">🎓</div><div class="info"><p>{{ $s->batch?->nama ?? 'Angkatan –' }}</p><span>{{ $s->batch?->program?->nama ?? 'Angkatan' }}</span></div></div>
        <div class="list-row" style="text-align:left"><div class="ic ic-bg-sakura">🏫</div><div class="info"><p>Kelas {{ $s->classroom?->kode ?? '–' }}</p><span>Wali kelas {{ $s->classroom?->wali?->sensei_name ?? '–' }}</span></div></div>
        <div class="list-row" style="text-align:left"><div class="ic ic-bg-green">✈️</div><div class="info"><p>{{ $s->program }}</p><span>{{ $s->target_departure ? 'Rencana berangkat ' . $s->target_departure : 'Tahap: ' . $s->stageName() }}</span></div></div>
      @endif
      <form method="POST" action="{{ route('profil.photo') }}" enctype="multipart/form-data">@csrf
        <label class="bt outline file-btn" style="margin-top:12px;width:100%;justify-content:center">Ganti foto<input type="file" name="foto" accept="image/*" data-autosubmit></label>
      </form>
      @error('foto')<p class="form-error" style="margin-top:8px">{{ $message }}</p>@enderror
    </div>
    <div class="grid" style="gap:16px">
      <form class="card pad" method="POST" action="{{ route('profil.update') }}" novalidate>@csrf @method('PUT')
        <h4 class="ct">Data diri</h4>
        @if ($errors->any() && ! $errors->has('foto'))<div class="form-error">{{ $errors->first() }}</div>@endif
        <div class="opt-grid">
          <div class="field"><label for="pNama">Nama lengkap</label><input id="pNama" name="name" value="{{ old('name', $user->name) }}"></div>
          <div class="field"><label for="pEmail">Email</label><input id="pEmail" name="email" type="email" value="{{ old('email', $user->email) }}"></div>
          <div class="field"><label for="pHp">No. HP / WhatsApp</label><input id="pHp" name="phone" value="{{ old('phone', $user->phone) }}"></div>
          @if ($s)
            <div class="field"><label for="pTmp">Tempat lahir</label><input id="pTmp" name="birth_place" value="{{ old('birth_place', $s->birth_place) }}"></div>
            <div class="field"><label for="pTgl">Tanggal lahir</label><input id="pTgl" name="birth_date" type="date" value="{{ old('birth_date', $s->birth_date?->toDateString()) }}"></div>
            <div class="field"><label for="pJk">Jenis kelamin</label><select id="pJk" name="gender"><option value="">–</option>@foreach (\App\Support\Catalog::GENDERS as $k => $v)<option value="{{ $k }}" @selected(old('gender', $s->gender) === $k)>{{ $v }}</option>@endforeach</select></div>
            <div class="field"><label for="pTb">Tinggi badan (cm)</label><input id="pTb" name="height_cm" type="number" min="100" max="230" value="{{ old('height_cm', $s->height_cm) }}"></div>
            <div class="field"><label for="pAgama">Agama</label><select id="pAgama" name="religion"><option value="">–</option>@foreach (\App\Support\Catalog::RELIGIONS as $v)<option @selected(old('religion', $s->religion) === $v)>{{ $v }}</option>@endforeach</select></div>
            <div class="field"><label for="pNikah">Status pernikahan</label><select id="pNikah" name="marital_status"><option value="">–</option>@foreach (\App\Support\Catalog::MARITAL as $k => $v)<option value="{{ $k }}" @selected(old('marital_status', $s->marital_status) === $k)>{{ $v }}</option>@endforeach</select></div>
            <div class="field"><label for="pPaspor">No. paspor</label><input id="pPaspor" name="passport_no" value="{{ old('passport_no', $s->passport_no) }}" placeholder="Isi jika sudah ada"></div>
            <div class="field"><label for="pWali">Telepon orang tua / wali</label><input id="pWali" name="guardian_contact" value="{{ old('guardian_contact', $s->guardian_contact) }}"></div>
            <div class="field" style="grid-column:1/-1"><label for="pKtp">Alamat KTP</label><input id="pKtp" name="address_ktp" value="{{ old('address_ktp', $s->address_ktp) }}"></div>
            <div class="field" style="grid-column:1/-1"><label for="pDom">Alamat domisili</label><input id="pDom" name="address_domicile" value="{{ old('address_domicile', $s->address_domicile) }}"></div>
          @endif
        </div>
        <button class="bt solid" type="submit">Simpan perubahan</button>
      </form>
      <form class="card pad" method="POST" action="{{ route('profil.password') }}" novalidate>@csrf @method('PUT')
        <h4 class="ct">Ubah password</h4>
        <x-form-errors bag="pw" />
        <div class="opt-grid" style="grid-template-columns:repeat(3,1fr)">
          <div class="field"><label for="pw0">Password saat ini</label><input id="pw0" name="current_password" type="password" autocomplete="current-password"></div>
          <div class="field"><label for="pw1">Password baru</label><input id="pw1" name="password" type="password" autocomplete="new-password"><div class="hint">Minimal 8 karakter</div></div>
          <div class="field"><label for="pw2">Ulangi password baru</label><input id="pw2" name="password_confirmation" type="password" autocomplete="new-password"></div></div>
        <button class="bt outline" type="submit">Perbarui password</button>
      </form>
      <div class="card pad"><h4 class="ct">Notifikasi</h4>
        @foreach (['jadwal' => 'Pengingat jadwal kelas', 'hasil' => 'Hasil ujian & sertifikat', 'pesan' => 'Pesan baru', 'mingguan' => 'Ringkasan mingguan lewat email'] as $k => $l)
          <div class="toggle-row"><label for="n-{{ $k }}">{{ $l }}</label><label class="switch"><input type="checkbox" id="n-{{ $k }}" data-pref="{{ $k }}" data-label="{{ $l }}" data-url="{{ route('profil.prefs') }}" @checked($user->pref($k))><span></span></label></div>
        @endforeach
      </div>
    </div>
  </div>
</div>
@endsection
