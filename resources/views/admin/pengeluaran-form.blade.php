@extends('layouts.app')
@section('title', $expense->exists ? 'Edit Pengeluaran' : 'Catat Pengeluaran')
@section('crumb')<a href="{{ route('pengeluaran.index') }}">Pengeluaran</a> › <b>{{ $expense->exists ? $expense->nomor : 'Catat pengeluaran' }}</b>@endsection

@section('content')
<?php $e = $expense; ?>
<div class="page">
  <div class="page-head"><div><h2>{{ $e->exists ? 'Edit pengeluaran · ' . $e->nomor : 'Catat pengeluaran' }}</h2>
    <p>{{ $e->exists ? 'Dicatat oleh ' . ($e->user?->name ?? '–') . '. Nomor bukti tidak berubah saat diedit.' : 'Nomor bukti kas keluar (BKK) dibuat otomatis saat disimpan.' }}</p></div>
    <a class="bt outline" href="{{ route('pengeluaran.index') }}">‹ Kembali ke daftar</a></div>

  <form method="POST" action="{{ $e->exists ? route('pengeluaran.update', $e) : route('pengeluaran.store') }}" novalidate class="grid" style="gap:16px">
    @csrf @if ($e->exists) @method('PUT') @endif
    @if ($errors->any())<div class="form-error" role="alert">{{ $errors->first() }}</div>@endif

    <div class="card pad"><h4 class="ct">Transaksi</h4>
      <div class="opt-grid">
        <div class="field"><label for="pTgl">Tanggal</label><input id="pTgl" name="tanggal" type="date" value="{{ old('tanggal', $e->tanggal?->toDateString()) }}"></div>
        <div class="field"><label for="pAkun">Kode akun beban (debit)</label><select id="pAkun" name="account_id"><option value="">Pilih akun…</option>
          @foreach ($expenseAccounts->groupBy('kelompok_label') as $label => $rows)<optgroup label="{{ $label }}">@foreach ($rows as $a)<option value="{{ $a->id }}" @selected((string) old('account_id', $e->account_id) === (string) $a->id)>{{ $a->label }}{{ $a->is_active ? '' : ' (nonaktif)' }}</option>@endforeach</optgroup>@endforeach
          </select><div class="hint">Tambah akun baru di <a class="linkbtn" href="{{ route('kode-akun.create') }}">Kode Akun</a>.</div></div>
        <div class="field"><label for="pSumber">Dibayar dari (kredit)</label><select id="pSumber" name="cash_account_id"><option value="">Pilih kas/bank…</option>
          @foreach ($cashAccounts as $a)<option value="{{ $a->id }}" @selected((string) old('cash_account_id', $e->cash_account_id) === (string) $a->id)>{{ $a->label }}{{ $a->is_active ? '' : ' (nonaktif)' }}</option>@endforeach
          </select></div>
        <div class="field"><label for="pJumlah">Jumlah (Rp)</label><input id="pJumlah" name="jumlah" type="number" min="1" step="1" value="{{ old('jumlah', $e->jumlah) }}" class="tnum"><div class="hint">Angka tanpa titik.</div></div>
        <div class="field"><label for="pPenerima">Dibayarkan kepada</label><input id="pPenerima" name="penerima" value="{{ old('penerima', $e->penerima) }}" placeholder="Opsional, mis. PLN"></div>
        <div class="field" style="grid-column:1/-1"><label for="pUraian">Uraian</label><input id="pUraian" name="uraian" value="{{ old('uraian', $e->uraian) }}" placeholder="mis. Tagihan listrik September 2026"></div>
      </div></div>

    <div><button class="bt solid" type="submit">{{ $e->exists ? 'Simpan perubahan' : 'Simpan pengeluaran' }}</button></div>
  </form>

  @if ($e->exists)
  <div class="card pad" style="margin-top:16px"><h4 class="ct">Hapus pengeluaran</h4>
    <p class="small muted">Transaksi akan dihapus permanen dari catatan pengeluaran.</p>
    <form method="POST" action="{{ route('pengeluaran.destroy', $e) }}" class="inline" data-confirm-title="Hapus {{ $e->nomor }}?" data-confirm="Transaksi akan dihapus permanen." data-confirm-ok="Hapus pengeluaran" data-danger>@csrf @method('DELETE')
      <button class="bt danger" type="submit">Hapus pengeluaran</button></form>
  </div>
  @endif
</div>
@endsection
