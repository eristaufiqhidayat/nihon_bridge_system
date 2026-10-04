@props(['id', 'title' => '', 'wide' => false])
<div class="modal-bg" id="{{ $id }}" hidden>
  <div class="modal {{ $wide ? 'wide' : '' }}" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
    <h3 id="{{ $id }}-title" data-modal-title>{{ $title }}</h3>
    {{ $slot }}
  </div>
</div>
