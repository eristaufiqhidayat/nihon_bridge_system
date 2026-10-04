@extends('layouts.app')
@section('title', 'Input Kehadiran')
@section('crumb')<b>Input Kehadiran</b>@endsection

@php use App\Support\Catalog; @endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Input Kehadiran{{ $cur ? ' · ' . $cur['classroom']->kode : '' }}</h2><p>{{ $cur ? $students->count() . ' peserta' : 'Sesi mengajar 7 hari terakhir' }}</p></div></div>
  @if (! $cur)
    <div class="card"><div class="empty-state"><div class="ic">📅</div><b>Tidak ada sesi mengajar dalam 7 hari terakhir</b><p>Sesi muncul di sini sesuai jadwal kelas yang Anda ajar.</p></div></div>
  @else
    <div class="filter-bar">
      <form method="GET" style="display:contents"><select name="sesi" aria-label="Sesi" data-autosubmit>@foreach ($sessions as $s)<option value="{{ $s['key'] }}" @selected($s['key'] === $cur['key'])>{{ $s['label'] }}</option>@endforeach</select></form>
      @if ($saved)<span class="badge b-green">Tersimpan</span>@else<span class="badge b-grey">Belum disimpan</span>@endif
      <button class="bt outline" type="button" style="margin-left:auto" id="markAllPresent">Tandai semua hadir</button>
    </div>
    <form method="POST" action="{{ route('kehadiran.store') }}">
      @csrf
      <input type="hidden" name="sesi" value="{{ $cur['key'] }}">
      <div class="card" style="padding:0"><div class="tbl-wrap"><table><thead><tr><th>Peserta</th><th>Status</th></tr></thead><tbody>
        @foreach ($students as $s)
          <tr><td><b>{{ $s->name }}</b><br><span class="small muted">{{ $s->nis }}</span></td><td><div class="seg" role="radiogroup" aria-label="Kehadiran {{ $s->name }}">
            @foreach (Catalog::ATTENDANCE as $k => [$l])
              <label class="{{ $values[$s->id] === $k ? 'on on-' . $k : '' }}"><input type="radio" name="status[{{ $s->id }}]" value="{{ $k }}" @checked($values[$s->id] === $k)><span>{{ $l }}</span></label>
            @endforeach
          </div></td></tr>
        @endforeach
      </tbody></table></div></div>
      <?php $sum = collect($values)->countBy(); ?>
      <div class="sticky-save"><span id="attSummary"><b class="tnum">{{ $sum['H'] ?? 0 }}</b> hadir · <b class="tnum">{{ $sum['I'] ?? 0 }}</b> izin · <b class="tnum">{{ $sum['S'] ?? 0 }}</b> sakit · <b class="tnum">{{ $sum['A'] ?? 0 }}</b> alpa</span><button class="bt solid" type="submit">Simpan kehadiran</button></div>
    </form>
  @endif
</div>
@endsection
