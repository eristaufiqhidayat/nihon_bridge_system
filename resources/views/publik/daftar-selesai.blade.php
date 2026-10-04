@extends('layouts.public')
@section('title', 'Pendaftaran terkirim')

@section('content')
<div class="card pad pub-card" style="text-align:center">
  <div class="done-mark">✓</div><h2>Pendaftaran terkirim</h2>
  <p class="muted">Nomor pendaftaran Anda</p><p class="reg-no tnum">{{ $a->reg_no }}</p>
  <p class="muted" style="max-width:420px;margin:0 auto 18px">Tim LPK memeriksa berkas dalam 3 hari kerja dan menghubungi Anda lewat WhatsApp {{ $a->hp }} untuk jadwal tes dan wawancara.</p>
  <div class="btnrow" style="justify-content:center">
    <button class="bt solid" type="button" data-copy="{{ $a->reg_no }}" data-copy-msg="Nomor disalin: ">Salin nomor</button>
    <form method="POST" action="{{ route('daftar.reset') }}" class="inline">@csrf<button class="bt outline" type="submit">Daftarkan orang lain</button></form>
  </div>
</div>
@endsection
