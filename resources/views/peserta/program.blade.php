@extends('layouts.app')
@section('title', 'Program Jepang')
@section('crumb')<b>Program Jepang</b>@endsection

@php
  use App\Services\ExamService;
  $interview = $student->candidacies->firstWhere('hasil', 'menunggu');
  $callout = null;
  if ($student->stage === 3 && $interview) {
      $callout = '<b>Tahap berikutnya: Interview ' . e($interview->jobOrder->company->nama) . '</b><br>' . e($interview->jobOrder->posisi) . ' · ' . e($interview->jobOrder->interview) . '. Bawa paspor asli dan 2 lembar pas foto 3×4. Latihan jikoshoukai bersama wali kelas.';
  }
  $pass = $lastAttempt && ExamService::isPass($lastAttempt);
  $kaigo = str_contains(mb_strtolower($student->program), 'kaigo');
@endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Program Jepang</h2><p>Pantau tahapan keberangkatan dan kelengkapan dokumen Anda.</p></div></div>
  @include('partials.program-detail', ['student' => $student, 'editable' => false, 'callout' => $callout])

  <div class="card pad" style="margin-top:16px"><div class="sh"><h4>Syarat ujian program {{ $student->program }}</h4></div>
    <p class="small muted" style="margin:-4px 0 8px">{{ $kaigo ? 'Tiga ujian ini wajib lulus sebelum pengajuan visa Tokutei Ginou (i) bidang perawatan lansia.' : 'Ujian bahasa ini wajib lulus sebelum pengajuan visa.' }}</p>
    <div class="tbl-wrap"><table><thead><tr><th>Syarat</th><th>Ujian</th><th>Status</th></tr></thead><tbody>
      <tr><td><b>Bahasa Jepang umum</b></td><td>JLPT N4 atau JFT-Basic</td>
        <td>@if ($pass)<span class="badge b-green">Simulasi lulus ({{ $lastAttempt->total }})</span>@else<span class="badge b-orange">Simulasi belum lulus</span>@endif</td></tr>
      @if ($kaigo)
        <tr><td><b>Keterampilan Kaigo</b></td><td>Nursing Care Skills Evaluation Test</td><td><span class="badge b-grey">Belum</span></td></tr>
        <tr><td><b>Bahasa Jepang Kaigo</b></td><td>Nursing Care Japanese Language Evaluation Test</td><td><span class="badge b-grey">Belum</span></td></tr>
      @endif
    </tbody></table></div></div>

  @if ($student->candidacies->isNotEmpty())
    <div class="card pad" style="margin-top:16px"><h4 class="ct">Pengajuan ke perusahaan</h4>
      @foreach ($student->candidacies as $c)
        <div class="doc-row"><span><b>{{ $c->jobOrder->company->nama }}</b> · {{ $c->jobOrder->posisi }}<br><span class="small muted">{{ $c->jobOrder->jalur }} · Interview {{ $c->jobOrder->interview }}</span></span>
          <span class="badge {{ \App\Support\Catalog::INTERVIEW[$c->hasil][1] }}">{{ \App\Support\Catalog::INTERVIEW[$c->hasil][0] }}</span></div>
      @endforeach
    </div>
  @endif
</div>
@endsection
