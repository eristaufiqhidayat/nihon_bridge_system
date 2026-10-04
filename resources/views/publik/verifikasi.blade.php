@extends('layouts.public')
@section('title', 'Verifikasi sertifikat')

@section('content')
<div class="card pad pub-card">
  <h2 style="margin:0 0 4px">Verifikasi sertifikat</h2><p class="muted" style="margin:0 0 16px">Masukkan nomor sertifikat atau pindai kode QR pada sertifikat.</p>
  <form method="GET" action="{{ route('verifikasi') }}" class="filter-bar"><input name="no" value="{{ $no }}" placeholder="mis. NB-JLPT-2026-0061" aria-label="Nomor sertifikat"><button class="bt solid" type="submit">Periksa</button></form>
  @if ($checked)
    @if ($cert)
      <div class="verify-ok"><div class="done-mark sm">✓</div><div><b>Sertifikat asli dan berlaku</b>
        <div class="review-grid" style="margin-top:10px">
          @foreach (['Nomor' => $cert->number, 'Nama' => $cert->user->name, 'Jenis' => $cert->title, 'Tanggal terbit' => \App\Support\Fmt::dateLong($cert->issued_at), 'Nilai' => $cert->score . ' / 100', 'Diterbitkan oleh' => config('nihonbridge.org.name')] as $k => $v)
            <div><span>{{ $k }}</span><b>{{ $v }}</b></div>
          @endforeach
        </div></div></div>
    @else
      <div class="verify-bad"><b>Nomor {{ $no }} tidak ditemukan.</b><br>Periksa kembali penulisan nomor. Jika sertifikat tampak asli tetapi tidak terdaftar, hubungi kantor {{ config('nihonbridge.org.name') }}.</div>
    @endif
  @endif
</div>
@endsection
