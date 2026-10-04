@extends('layouts.public')
@section('title', 'Pendaftaran calon peserta')

@php
  use App\Support\Catalog;
  $steps = ['Data diri', 'Program', 'Berkas', 'Tinjau'];
@endphp

@section('content')
<div class="card pad pub-card">
  <h2 style="margin:0 0 4px">Pendaftaran calon peserta</h2><p class="muted" style="margin:0 0 16px">Program kerja ke Jepang · gelombang {{ \App\Support\Fmt::monthYear(now()) }}</p>
  <ol class="stepper">@foreach ($steps as $i => $s)<li class="{{ $i + 1 < $step ? 'done' : ($i + 1 === $step ? 'cur' : '') }}"><span>{{ $i + 1 < $step ? '✓' : $i + 1 }}</span>{{ $s }}</li>@endforeach</ol>
  <form method="POST" action="{{ route('daftar.store') }}" enctype="multipart/form-data" novalidate>@csrf
    @if ($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif

    @if ($step === 1)
      <div class="opt-grid">
        <div class="field"><label for="dNama">Nama lengkap (sesuai KTP)</label><input id="dNama" name="nama" value="{{ old('nama', $d['nama']) }}" placeholder="mis. Intan Kusuma"></div>
        <div class="field"><label for="dNik">NIK</label><input id="dNik" name="nik" inputmode="numeric" maxlength="16" value="{{ old('nik', $d['nik']) }}" placeholder="16 digit"></div>
        <div class="field"><label for="dTtl">Tempat, tanggal lahir</label><input id="dTtl" name="ttl" value="{{ old('ttl', $d['ttl']) }}" placeholder="mis. Cirebon, 3 Maret 2005"></div>
        <div class="field"><label for="dHp">No. WhatsApp</label><input id="dHp" name="hp" inputmode="tel" value="{{ old('hp', $d['hp']) }}" placeholder="08xx-xxxx-xxxx"></div>
        <div class="field"><label for="dEmail">Email</label><input id="dEmail" name="email" type="email" value="{{ old('email', $d['email']) }}" placeholder="nama@email.com"></div>
        <div class="field"><label for="dPend">Pendidikan terakhir</label><select id="dPend" name="pend">@foreach (Catalog::EDUCATION as $o)<option @selected($o === old('pend', $d['pend']))>{{ $o }}</option>@endforeach</select></div>
      </div>
    @elseif ($step === 2)
      <div class="field"><label>Pilih program</label><div class="choice-grid">
        @foreach (Catalog::PROGRAMS as $k => $s)
          <label class="choice {{ old('prog', $d['prog']) === $k ? 'on' : '' }}"><input type="radio" name="prog" value="{{ $k }}" @checked(old('prog', $d['prog']) === $k) onchange="document.querySelectorAll('.choice').forEach(c=>c.classList.toggle('on', c.contains(this)))"><b>{{ $k }}</b><span>{{ $s }}</span></label>
        @endforeach
      </div></div>
      <div class="opt-grid">
        <div class="field"><label for="dLevel">Kemampuan bahasa Jepang saat ini</label><select id="dLevel" name="level">@foreach (Catalog::JP_LEVEL as $o)<option @selected($o === old('level', $d['level']))>{{ $o }}</option>@endforeach</select></div>
        <div class="field"><label for="dRef">Tahu LPK dari</label><select id="dRef" name="ref">@foreach (Catalog::REFERRAL as $o)<option @selected($o === old('ref', $d['ref']))>{{ $o }}</option>@endforeach</select></div>
      </div>
    @elseif ($step === 3)
      <p class="small muted" style="margin-top:0">Unggah foto atau PDF, maksimal 2 MB per berkas. Berkas langsung tersimpan begitu dipilih.</p>
      @foreach (Catalog::APP_FILES as [$k, $l, $req])
        <?php $f = $files[$k] ?? null; ?>
        <div class="upload-row"><div><b>{{ $l }}</b>@unless ($req) <span class="small muted">(opsional)</span>@endunless<br><span class="small {{ $f ? '' : 'muted' }}">{{ $f ? "✓ {$f['name']} · {$f['size']}" : 'Belum diunggah' }}</span></div>
          <label class="bt {{ $f ? 'outline' : 'solid' }} file-btn">{{ $f ? 'Ganti' : 'Pilih berkas' }}<input type="file" name="berkas[{{ $k }}]" accept="image/*,.pdf" onchange="if(this.files[0]&&this.files[0].size>2097152){toast(this.files[0].name+' lebih dari 2 MB. Kecilkan ukurannya lalu unggah lagi.');this.value='';return}const a=this.form.querySelector('[name=aksi_upl]');a.name='aksi';a.value='unggah';this.form.submit()"></label></div>
      @endforeach
      <input type="hidden" name="aksi_upl" value="">
    @elseif ($step === 4)
      <div class="review-grid">
        @foreach (['Nama' => 'nama', 'NIK' => 'nik', 'Tempat, tanggal lahir' => 'ttl', 'WhatsApp' => 'hp', 'Email' => 'email', 'Pendidikan' => 'pend', 'Program' => 'prog', 'Bahasa Jepang' => 'level'] as $label => $key)
          <div><span>{{ $label }}</span><b>{{ $d[$key] ?: '–' }}</b></div>
        @endforeach
      </div>
      <p class="small"><b>Berkas:</b> {{ count($files) }} diunggah ({{ collect($files)->pluck('name')->implode(', ') }})</p>
      <label class="check small" style="margin:12px 0"><input type="checkbox" name="setuju" value="1"> Data yang saya isi benar, dan saya setuju data ini dipakai LPK untuk proses seleksi.</label>
    @endif

    <div class="q-nav-btns"><button class="nbtn prev" type="submit" name="aksi" value="kembali" formnovalidate @disabled($step === 1)>‹ Kembali</button>
      <button class="nbtn next" type="submit" name="aksi" value="lanjut">{{ $step === 4 ? 'Kirim pendaftaran' : 'Lanjut ›' }}</button></div>
  </form>
</div>
@endsection
