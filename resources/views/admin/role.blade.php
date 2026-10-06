@extends('layouts.app')
@section('title', 'Data Role')
@section('crumb')<b>Data Role</b>@endsection

@section('content')
<div class="page">
  <div class="page-head"><div><h2>Data Role</h2><p>{{ $list->count() }} peran. Tiap peran menentukan menu yang bisa dibuka penggunanya. Ganti peran pengguna di Dashboard Admin.</p></div>
    <a class="bt solid" href="{{ route('role-admin.create') }}">+ Tambah role</a></div>
  @error('hapus')<div class="form-error mb16" role="alert">{{ $message }}</div>@enderror
  <div class="card" style="padding:0"><div class="tbl-wrap"><table>
    <thead><tr><th>Peran</th><th>Keterangan</th><th>Menu</th><th>Pengguna</th><th>Aksi</th></tr></thead>
    <tbody>
      @foreach ($list as $r)
        <?php $menu = collect($r->menuItems()); ?>
        <tr><td><span class="badge {{ $r->badge }}">{{ $r->name }}</span>@if ($r->is_system)<br><span class="small muted">Bawaan sistem</span>@endif</td>
          <td class="small">{{ $r->description ?: '–' }}</td>
          <td class="small"><b>{{ $menu->count() }} menu</b><br><span class="muted">{{ $menu->pluck(2)->take(4)->join(', ') }}{{ $menu->count() > 4 ? ', …' : '' }}</span></td>
          <td class="tnum">{{ $r->users_count }}</td>
          <td style="white-space:nowrap">
            <a class="act-ic" href="{{ route('role-admin.edit', $r) }}" aria-label="Edit {{ $r->name }}">✏️</a>
            @unless ($r->is_system)
            <form method="POST" action="{{ route('role-admin.destroy', $r) }}" class="inline" data-confirm-title="Hapus peran {{ $r->name }}?" data-confirm="Peran akan dihapus permanen. Penghapusan ditolak bila peran masih dipakai pengguna." data-confirm-ok="Hapus peran" data-danger>@csrf @method('DELETE')<button class="act-ic" aria-label="Hapus {{ $r->name }}">🗑️</button></form>
            @endunless
          </td></tr>
      @endforeach
    </tbody></table></div></div>
</div>
@endsection
