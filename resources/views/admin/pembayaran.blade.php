@extends('layouts.app')
@section('title', 'Pembayaran Peserta')
@section('crumb')<b>Pembayaran Peserta</b>@endsection

@php
  use App\Support\Catalog;
  use App\Support\Fmt;
@endphp

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Pembayaran Peserta</h2><p>Entri, edit, dan hapus pembayaran cicilan. {{ $fee['installments'] }} cicilan × {{ Fmt::rupiah($fee['per']) }} per peserta.</p></div>
    <button class="bt solid" type="button" data-modal-open="payModal" data-fill="{{ json_encode(['student_id' => $options->first()?->id ?? ''], JSON_UNESCAPED_UNICODE) }}">+ Entri pembayaran</button></div>
  <x-form-errors />
  <form method="GET" class="filter-bar">
    <input name="q" type="search" placeholder="🔍 Cari nama peserta atau NIS…" value="{{ $q }}" aria-label="Cari nama peserta">
    <button class="bt outline" type="submit">Cari</button>
    @if ($q !== '')<a class="linkbtn" href="{{ route('pembayaran-admin.index') }}">Reset</a>@endif
  </form>

  @forelse ($list as $s)
    <?php $paid = $paidCount($s); ?>
    <div class="card mb16" style="padding:0">
      <div style="display:flex;gap:12px;align-items:center;justify-content:space-between;padding:14px 16px;flex-wrap:wrap">
        <div style="display:flex;gap:10px;align-items:center"><div class="av sm">{{ $s->initials }}</div>
          <div><b>{{ $s->name }}</b><br><span class="small muted">{{ $s->nis }} · {{ $s->classroom?->kode ?? 'Belum ada kelas' }}</span></div></div>
        <div style="display:flex;gap:10px;align-items:center">
          @if ($paid >= $fee['installments'])<span class="badge b-green">Lunas {{ $paid }}/{{ $fee['installments'] }}</span>@else<span class="badge b-blue">Dibayar {{ $paid }}/{{ $fee['installments'] }}</span>
            <button class="mini-btn" type="button" data-modal-open="payModal" data-fill="{{ json_encode(['student_id' => $s->id], JSON_UNESCAPED_UNICODE) }}">+ Catat cicilan ke-{{ $paid + 1 }}</button>@endif
        </div>
      </div>
      @if ($s->payments->isEmpty())
        <p class="small muted" style="padding:0 16px 14px">Belum ada pembayaran.</p>
      @else
        <div class="tbl-wrap"><table><thead><tr><th>Cicilan</th><th>Tanggal</th><th>Jumlah</th><th>Metode</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
          @foreach ($s->payments as $p)
            <tr><td class="tnum">Ke-{{ $p->installment_no }}</td><td class="tnum">{{ $p->paid_at?->format('d/m/Y') ?? '–' }}</td>
              <td class="tnum">{{ Fmt::rupiah($p->amount) }}</td><td>{{ $p->method ?? '–' }}</td>
              <td>@if ($p->status === 'lunas')<span class="badge b-green">Lunas</span>@else<span class="badge b-orange">Menunggu verifikasi</span>@endif</td>
              <td style="white-space:nowrap">
                <button class="act-ic" type="button" aria-label="Edit pembayaran {{ $s->name }} cicilan ke-{{ $p->installment_no }}" data-modal-open="editPayModal"
                  data-fill="{{ json_encode(['@action' => route('pembayaran-admin.update', $p), '@title' => "Edit pembayaran {$s->name} (cicilan ke-{$p->installment_no})", '_payment' => $p->id, 'method' => $p->method ?? Catalog::PAYMENT_METHODS[0], 'paid_at' => $p->paid_at?->toDateString() ?? now()->toDateString()], JSON_UNESCAPED_UNICODE) }}">✏️</button>
                <form method="POST" action="{{ route('pembayaran-admin.destroy', $p) }}" class="inline" data-confirm-title="Hapus pembayaran {{ $s->name }}?" data-confirm="Cicilan ke-{{ $p->installment_no }} sebesar {{ Fmt::rupiah($p->amount) }} akan dihapus permanen beserta bukti transfernya. Nomor cicilan sesudahnya akan disesuaikan." data-confirm-ok="Hapus pembayaran" data-danger>@csrf @method('DELETE')<button class="act-ic" aria-label="Hapus pembayaran {{ $s->name }} cicilan ke-{{ $p->installment_no }}">🗑️</button></form>
              </td></tr>
          @endforeach
        </tbody></table></div>
      @endif
    </div>
  @empty
    <div class="card"><p class="empty">Tidak ada peserta yang cocok dengan "{{ $q }}". <a class="linkbtn" href="{{ route('pembayaran-admin.index') }}">Reset pencarian</a></p></div>
  @endforelse

  <div class="pager"><span>Menampilkan {{ $list->firstItem() ?? 0 }}–{{ $list->lastItem() ?? 0 }} dari {{ $list->total() }} peserta</span>
    @if ($list->lastPage() > 1)
    <div class="pg-btns">
      @if ($list->onFirstPage())<span class="pg">‹</span>@else<a href="{{ $list->previousPageUrl() }}" aria-label="Sebelumnya">‹</a>@endif
      @foreach (range(1, $list->lastPage()) as $pg)<a class="{{ $pg === $list->currentPage() ? 'cur' : '' }}" href="{{ $list->url($pg) }}">{{ $pg }}</a>@endforeach
      @if ($list->hasMorePages())<a href="{{ $list->nextPageUrl() }}" aria-label="Berikutnya">›</a>@else<span class="pg">›</span>@endif
    </div>
    @endif
  </div>
</div>
@endsection

@push('modals')
<x-modal id="payModal" title="Entri pembayaran">
  <form method="POST" action="{{ route('pembayaran-admin.store') }}">@csrf <input type="hidden" name="_modal" value="payModal">
    <x-form-errors modal="payModal" />
    <div class="opt-grid">
      <div class="field"><label for="pyS">Peserta</label><select id="pyS" name="student_id">@foreach ($options as $o)<option value="{{ $o->id }}" @selected((int) old('student_id') === $o->id)>{{ $o->name }} · {{ $o->nis }} (cicilan {{ $paidCount($o) + 1 }})</option>@endforeach</select></div>
      <div class="field"><label for="pyM">Metode</label><select id="pyM" name="method">@foreach (Catalog::PAYMENT_METHODS as $m)<option @selected(old('method') === $m)>{{ $m }}</option>@endforeach</select></div>
      <div class="field"><label for="pyJ">Jumlah</label><input id="pyJ" value="{{ Fmt::rupiah($fee['per']) }}" readonly></div>
      <div class="field"><label for="pyT">Tanggal</label><input id="pyT" name="paid_at" type="date" value="{{ old('paid_at', now()->toDateString()) }}"></div></div>
    <div class="modal-actions"><button type="button" class="cancel" data-modal-close>Batal</button><button class="ok" type="submit">Simpan &amp; kirim kuitansi</button></div>
  </form>
</x-modal>
<x-modal id="editPayModal" title="Edit pembayaran">
  <form method="POST" action="{{ old('_modal') === 'editPayModal' && old('_payment') ? route('pembayaran-admin.update', old('_payment')) : '' }}">@csrf @method('PUT') <input type="hidden" name="_modal" value="editPayModal"> <input type="hidden" name="_payment" value="{{ old('_payment') }}">
    <x-form-errors modal="editPayModal" />
    <div class="opt-grid">
      <div class="field"><label for="epM">Metode</label><select id="epM" name="method">@foreach (Catalog::PAYMENT_METHODS as $m)<option>{{ $m }}</option>@endforeach</select></div>
      <div class="field"><label for="epT">Tanggal</label><input id="epT" name="paid_at" type="date"></div></div>
    <div class="modal-actions"><button type="button" class="cancel" data-modal-close>Batal</button><button class="ok" type="submit">Simpan perubahan</button></div>
  </form>
</x-modal>
@endpush
