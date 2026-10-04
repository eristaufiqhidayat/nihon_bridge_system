@extends('layouts.app')
@section('title', 'Sertifikat')
@section('crumb')<b>Sertifikat</b>@endsection

@php use App\Support\Fmt; @endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Sertifikat Digital</h2><p>Sertifikat terbit otomatis untuk ujian yang lulus dan dapat diverifikasi lewat kode QR.</p></div></div>
  @if (! $cert)
    <div class="card"><div class="empty-state"><div class="ic">🏅</div><b>Belum ada sertifikat</b><p>Sertifikat terbit otomatis saat Anda lulus ujian simulasi.</p></div></div>
  @else
  <div class="cert-layout">
    <div class="card pad"><h4 class="ct">Sertifikat saya ({{ $certs->count() }})</h4>
      @foreach ($certs as $x)
        <a class="list-row clickable {{ $x->id === $cert->id ? 'sel' : '' }}" href="{{ route('sertifikat.index', $x) }}"><div class="ic ic-bg-purple">🏅</div><div class="info"><p>{{ $x->title }}</p><span>{{ Fmt::date($x->issued_at) }} · Nilai {{ $x->score }}</span></div></a>
      @endforeach
    </div>
    <div>
      @include('partials.certificate', ['cert' => $cert, 'name' => auth()->user()->name])
      <div class="cert-actions">
        <a class="bt outline" href="{{ route('sertifikat.print', $cert) }}" target="_blank">⬇ Unduh PDF</a>
        <form method="POST" action="{{ route('sertifikat.email', $cert) }}" class="inline">@csrf<button class="bt solid" type="submit">✉ Kirim ke email</button></form>
        <button class="bt gold" type="button" data-copy="{{ $cert->verifyUrl() }}" data-copy-msg="Tautan verifikasi disalin: ">🔗 Salin tautan verifikasi</button>
        <a class="bt outline" href="{{ $cert->verifyUrl() }}" target="_blank">Lihat halaman verifikasi</a>
      </div>
    </div>
  </div>
  @endif
</div>
@endsection
