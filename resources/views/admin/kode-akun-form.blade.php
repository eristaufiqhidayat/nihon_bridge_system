@extends('layouts.app')
@section('title', $account->exists ? 'Edit Kode Akun' : 'Tambah Kode Akun')
@section('crumb')<a href="{{ route('kode-akun.index') }}">Kode Akun</a> › <b>{{ $account->exists ? $account->label : 'Tambah akun' }}</b>@endsection

@php use App\Models\Account; @endphp

@section('content')
<?php $a = $account; ?>
<div class="page">
  <div class="page-head"><div><h2>{{ $a->exists ? 'Edit akun · ' . $a->label : 'Tambah kode akun' }}</h2>
    <p>Awalan kode menentukan kelompok akun: 1-1xxx Kas &amp; Bank, 5- Beban Pokok Pendapatan, 6- Beban Operasional, 8- Beban Lain-lain.</p></div>
    <a class="bt outline" href="{{ route('kode-akun.index') }}">‹ Kembali ke daftar</a></div>

  <form method="POST" action="{{ $a->exists ? route('kode-akun.update', $a) : route('kode-akun.store') }}" novalidate class="grid" style="gap:16px">
    @csrf @if ($a->exists) @method('PUT') @endif
    @if ($errors->any() && ! $errors->has('hapus'))<div class="form-error" role="alert">{{ $errors->first() }}</div>@endif

    <div class="card pad"><h4 class="ct">Akun</h4>
      <div class="opt-grid">
        <div class="field"><label for="kKode">Kode akun</label><input id="kKode" name="kode" value="{{ old('kode', $a->kode) }}" placeholder="mis. 6-1301" class="tnum">
          <div class="hint">@foreach (Account::GROUPS as $p => [$k, $label]){{ $p === '1' ? '1-11xx/1-12xx' : $p . '-xxxx' }} {{ $label }}@if (! $loop->last) · @endif @endforeach</div></div>
        <div class="field"><label for="kNama">Nama akun</label><input id="kNama" name="nama" value="{{ old('nama', $a->nama) }}" placeholder="mis. Beban Listrik"></div>
        <div class="field"><label for="kKet">Keterangan</label><input id="kKet" name="keterangan" value="{{ old('keterangan', $a->keterangan) }}" placeholder="Opsional"></div>
        <div class="field"><label for="kAktif">Status</label>
          <input type="hidden" name="is_active" value="0">
          <label class="switch" title="Aktif / nonaktif"><input id="kAktif" type="checkbox" name="is_active" value="1" @checked(old('is_active', $a->is_active ?? true))><span></span></label>
          <div class="hint">Akun nonaktif tidak bisa dipilih untuk transaksi baru.</div></div>
      </div></div>

    <div><button class="bt solid" type="submit">{{ $a->exists ? 'Simpan perubahan' : 'Simpan akun' }}</button></div>
  </form>

  @if ($a->exists)
  <div class="card pad" style="margin-top:16px"><h4 class="ct">Hapus akun</h4>
    @error('hapus')<div class="form-error" role="alert">{{ $message }}</div>@enderror
    @if ($blockers)
      <p class="small">Akun ini belum bisa dihapus karena masih dipakai <b>{{ collect($blockers)->map(fn ($n, $l) => "{$n} {$l}")->join(', ', ' dan ') }}</b>. Nonaktifkan akunnya agar tidak bisa dipilih lagi.</p>
    @else
      <p class="small muted">Belum dipakai transaksi. Akun akan dihapus permanen.</p>
    @endif
    <form method="POST" action="{{ route('kode-akun.destroy', $a) }}" class="inline" data-confirm-title="Hapus akun {{ $a->kode }}?" data-confirm="Akun {{ $a->nama }} akan dihapus permanen." data-confirm-ok="Hapus akun" data-danger>@csrf @method('DELETE')
      <button class="bt danger" type="submit" @disabled($blockers)>Hapus akun</button></form>
  </div>
  @endif
</div>
@endsection
