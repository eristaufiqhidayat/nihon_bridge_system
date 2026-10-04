{{-- Detail program peserta: profil, progres tahapan, timeline, dokumen. Butuh $student, $editable --}}
<?php $np = \App\Support\Fmt::pct($student->stage, count(\App\Support\Catalog::STAGES)); ?>
<div class="card pad mb16">
  <div class="mon-profile"><div class="av lg">{{ $student->initials }}</div>
    <div style="flex:1;min-width:200px"><h4>{{ $student->name }}</h4><p>NIS {{ $student->nis }} · Kelas {{ $student->classroom?->kode ?? '–' }} · {{ $student->program }}</p></div>
    <div style="text-align:right"><span class="small muted">Nilai tryout terakhir</span><br><b style="font-size:20px" class="tnum">{{ $student->nilai }}</b></div></div>
  <p style="font-size:12.5px;font-weight:700;margin:16px 0 6px;display:flex;justify-content:space-between">Progres program <span class="tnum">{{ $student->stage }} dari {{ count(\App\Support\Catalog::STAGES) }} tahap ({{ $np }}%)</span></p>
  <div class="progress-track"><div class="progress-fill" style="width:{{ $np }}%"></div></div>
  @if ($student->note)<p class="form-error" style="margin:12px 0 0">⚠ {{ $student->note }}</p>@endif
</div>
<div class="mon-layout">
  <div class="card pad"><h4 class="ct">Tahapan</h4>
    @include('partials.timeline', ['student' => $student])
    @if (! empty($callout))
      <div class="callout" style="margin-top:14px">{!! $callout !!}</div>
    @endif
  </div>
  <div class="card pad"><h4 class="ct">Dokumen</h4>
    @foreach (\App\Support\Catalog::DOCS as $d)
      <?php $st = $student->docStatus($d); ?>
      <div class="doc-row">{{ $d[1] }}
        @if ($editable)
          <form method="POST" action="{{ route('monitoring.document', $student) }}" class="inline">
            @csrf
            <input type="hidden" name="doc" value="{{ $d[0] }}">
            <select name="status" class="doc-status {{ $st }}" aria-label="Status {{ $d[1] }}" data-autosubmit>
              @foreach (\App\Support\Catalog::DOC_STATUS as $k => $l)<option value="{{ $k }}" @selected($k === $st)>{{ $l }}</option>@endforeach
            </select>
          </form>
        @else
          <span class="doc-status {{ $st }}">{{ \App\Support\Catalog::DOC_STATUS[$st] }}</span>
        @endif
      </div>
    @endforeach
    @if ($editable)<p class="rule-note">Ubah status setelah dokumen fisik diverifikasi.</p>@endif
  </div>
</div>
