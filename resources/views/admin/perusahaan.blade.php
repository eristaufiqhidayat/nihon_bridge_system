@extends('layouts.app')
@section('title', 'Perusahaan & Job Order')
@section('crumb')<b>Perusahaan &amp; Job Order</b>@endsection

@php use App\Support\Catalog; @endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Perusahaan &amp; Job Order</h2><p>Mitra penerima peserta di Jepang.{{ $readonly ? ' Mode baca saja.' : '' }}</p></div>
    @unless ($readonly)<button class="bt solid" type="button" data-modal-open="jobModal">+ Job order</button>@endunless</div>
  <div class="grid g3 mb16">
    @foreach ($summaries as $s)
      <div class="card pad"><div class="sh"><h4>{{ $s['company']->nama }}</h4><span class="badge b-grey">{{ $s['company']->kota }}</span></div><p class="small muted" style="margin:0">{{ $s['company']->bidang }} · mitra sejak {{ $s['company']->sejak }}</p>
        <p class="small" style="margin:8px 0 0">{{ $s['jobs'] }} job order · {{ $s['kuota'] }} kuota · {{ $s['lulus'] }} lulus interview</p></div>
    @endforeach
  </div>
  <div class="grid g-admin">
    <div class="card" style="padding:0"><div class="tbl-wrap"><table><thead><tr><th>Job order</th><th>Kuota</th><th>Interview</th><th>Status</th></tr></thead><tbody>
      @foreach ($jobs as $x)
        <tr class="clickable {{ $job && $x->id === $job->id ? 'sel' : '' }}" onclick="location.href='{{ route('perusahaan.index', ['job' => $x->id]) }}'"><td><b>{{ $x->posisi }}</b><br><span class="small muted">{{ $x->code }} · {{ $x->company->nama }} · {{ $x->jalur }}</span></td>
          <td class="tnum">{{ $x->candidates->where('hasil', 'lulus')->count() }}/{{ $x->kuota }}</td><td class="small">{{ $x->interview }}</td><td><span class="badge {{ $x->status === 'Interview' ? 'b-purple' : 'b-blue' }}">{{ $x->status }}</span></td></tr>
      @endforeach
    </tbody></table></div></div>
    @if ($job)
      <div class="card pad"><h4 class="ct">{{ $job->posisi }} · {{ $job->company->nama }}</h4>
        <p class="small" style="margin:0 0 4px"><b>Syarat:</b> {{ $job->syarat }}</p><p class="small" style="margin:0 0 12px"><b>Interview:</b> {{ $job->interview }}</p>
        <h4 class="ct" style="font-size:13px">Kandidat ({{ $job->candidates->count() }})</h4>
        @forelse ($job->candidates as $k)
          <?php $s = $k->student; ?>
          <div class="doc-row"><span><b>{{ $s->name }}</b><br><span class="small muted">Nilai {{ $s->nilai }} · tahap {{ $s->stageName() }}</span></span>
            @if ($readonly)
              <span class="badge {{ Catalog::INTERVIEW[$k->hasil][1] }}">{{ Catalog::INTERVIEW[$k->hasil][0] }}</span>
            @else
              <form method="POST" action="{{ route('perusahaan.candidate.update', $k) }}" class="inline">@csrf @method('PATCH')
                <select name="hasil" class="doc-status {{ $k->hasil === 'lulus' ? 'selesai' : ($k->hasil === 'tidak' ? 'belum' : 'proses') }}" aria-label="Hasil interview {{ $s->name }}" data-autosubmit>
                  @foreach (Catalog::INTERVIEW as $v => [$l])<option value="{{ $v }}" @selected($v === $k->hasil)>{{ $l }}</option>@endforeach
                </select></form>
            @endif
          </div>
        @empty
          <p class="small muted">Belum ada kandidat.</p>
        @endforelse
        @unless ($readonly)
          <h4 class="ct" style="font-size:13px;margin-top:14px">Peserta yang cocok</h4>
          @forelse ($eligible as $s)
            <div class="doc-row"><span><b>{{ $s->name }}</b><br><span class="small muted">{{ $s->program }} · nilai {{ $s->nilai }}</span></span>
              <form method="POST" action="{{ route('perusahaan.candidate.add', [$job, $s]) }}" class="inline">@csrf<button class="mini-btn" type="submit">+ Ajukan</button></form></div>
          @empty
            <p class="small muted" style="margin:0">Belum ada peserta lain yang memenuhi: bidang sama, lulus tryout (≥ 60), dan sudah di tahap Interview.</p>
          @endforelse
        @endunless
      </div>
    @endif
  </div>
</div>
@endsection

@unless ($readonly)
@push('modals')
<x-modal id="jobModal" title="Job order baru" wide>
  <form method="POST" action="{{ route('perusahaan.job.store') }}" novalidate>@csrf <input type="hidden" name="_modal" value="jobModal">
    <x-form-errors modal="jobModal" />
    <div class="opt-grid">
      <div class="field"><label for="joC">Perusahaan</label><select id="joC" name="company_id">@foreach ($companies as $c)<option value="{{ $c->id }}">{{ $c->nama }} ({{ $c->bidang }})</option>@endforeach</select></div>
      <div class="field"><label for="joP">Posisi</label><input id="joP" name="posisi" value="{{ old('posisi') }}" placeholder="mis. Perawat lansia (Kaigo)"></div>
      <div class="field"><label for="joJ">Jalur</label><select id="joJ" name="jalur"><option>Tokutei Ginou</option><option>Magang (Ginou Jisshu)</option></select></div>
      <div class="field"><label for="joK">Kuota</label><input id="joK" name="kuota" type="number" min="1" value="{{ old('kuota', 3) }}"></div>
    </div>
    <div class="field"><label for="joS">Syarat</label><input id="joS" name="syarat" value="{{ old('syarat') }}" placeholder="mis. JLPT N4 / JFT-Basic, ujian keterampilan"></div>
    <div class="field"><label for="joI">Jadwal interview</label><input id="joI" name="interview" value="{{ old('interview') }}" placeholder="mis. Selasa, 20 Okt 2026 · online"></div>
    <div class="modal-actions"><button type="button" class="cancel" data-modal-close>Batal</button><button class="ok" type="submit">Simpan</button></div>
  </form>
</x-modal>
@endpush
@endunless
