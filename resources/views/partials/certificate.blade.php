<div class="cert">
  <span class="cert-sakura tl">🌸</span><span class="cert-sakura br">🌸</span>
  <div class="logo-badge"><img alt="Logo LPK Nihon Bridge" src="{{ asset('images/logo.png') }}"></div>
  <p class="org">LPK NIHON BRIDGE</p>
  <p class="addr">{{ config('nihonbridge.org.short_address') }}</p>
  <h2>{!! preg_replace('/^SERTIFIKAT /', 'SERTIFIKAT<br>', e($cert->kind)) !!}</h2>
  <p class="sub1">Diberikan kepada</p>
  <p class="name">{{ $name }}</p>
  <p class="desc">{{ $cert->description }}</p>
  <div class="foot"><span style="text-align:left">No. Sertifikat<br><b class="tnum">{{ $cert->number }}</b></span><span class="qr" aria-label="Kode QR verifikasi">{!! \App\Support\Qr::svg($cert->verifyUrl()) !!}</span><span style="text-align:right">Direktur<br><b>{{ config('nihonbridge.org.director') }}</b></span></div>
</div>
