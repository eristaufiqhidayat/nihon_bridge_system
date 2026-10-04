@extends('layouts.app')
@section('title', 'Monitoring Peserta')
@section('crumb')<b>Monitoring Peserta</b>@endsection

@php use App\Support\Catalog; @endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Monitoring Peserta · Kelas {{ $class?->kode ?? '–' }}</h2><p>{{ $all->count() }} peserta.{{ $editable ? '' : ' Mode baca saja.' }}</p></div>
    @if ($classes->count() > 1)
      <form method="GET" class="filt" style="margin:0"><select name="kelas" aria-label="Kelas" data-autosubmit>@foreach ($classes as $c)<option value="{{ $c->id }}" @selected($c->id === $class?->id)>Kelas: {{ $c->kode }}</option>@endforeach</select></form>
    @endif
  </div>
  <div class="chips"><div class="chip-stat"><b class="tnum">{{ $avg }}</b>Rata-rata nilai tryout</div><div class="chip-stat"><b class="tnum" style="color:var(--red)">{{ $attn }}</b>Perlu perhatian (nilai &lt; 60)</div><div class="chip-stat"><b class="tnum">{{ $interview }}</b>Lolos ke tahap interview</div></div>
  <div class="card mb16" style="padding:0">
    <div class="pad" style="padding-bottom:0"><form method="GET" class="filter-bar"><input type="hidden" name="kelas" value="{{ $class?->id }}"><input name="q" type="search" placeholder="🔍 Cari nama peserta…" value="{{ $q }}"></form></div>
    <div class="tbl-wrap"><table><thead><tr><th>Peserta</th><th>Nilai tryout</th><th>Progres belajar</th><th>Tahap program</th><th>Status</th></tr></thead><tbody>
      @forelse ($list as $s)
        <?php $nv = $s->nilai; ?>
        <tr class="clickable {{ $sel && $s->id === $sel->id ? 'sel' : '' }}" onclick="location.href='{{ route('monitoring.index', ['kelas' => $class->id, 'q' => $q, 'siswa' => $s->id]) }}#monDetail'">
          <td><div style="display:flex;gap:10px;align-items:center"><div class="av sm">{{ $s->initials }}</div><div><b>{{ $s->name }}</b><br><span class="small muted">{{ $s->nis }}</span></div></div></td>
          <td class="tnum"><b style="color:{{ $nv < 60 ? 'var(--red)' : 'inherit' }}">{{ $nv }}</b></td>
          <td><div class="minibar"><div class="progress-track"><div class="progress-fill" style="width:{{ $s->progres_belajar }}%"></div></div><span class="small tnum">{{ $s->progres_belajar }}%</span></div></td>
          <td>{{ $s->stage + 1 }}. {{ $s->stageName() }}</td>
          <td>@if ($nv < 60)<span class="badge b-red">Perlu perhatian</span>@else<span class="badge b-green">Sesuai jadwal</span>@endif</td></tr>
      @empty
        <tr><td colspan="5" class="empty">{{ $q !== '' ? "Tidak ada peserta dengan nama “{$q}”." : 'Belum ada peserta di kelas ini.' }}</td></tr>
      @endforelse
    </tbody></table></div>
  </div>
  <div id="monDetail"><h4 class="ct">Detail peserta</h4>
    @if ($sel)@include('partials.program-detail', ['student' => $sel, 'editable' => $editable])@endif
  </div>
</div>
@endsection
