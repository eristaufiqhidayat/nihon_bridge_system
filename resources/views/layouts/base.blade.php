<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@hasSection('title')@yield('title') · @endif{{ config('nihonbridge.org.name') }}</title>
<link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&family=Zen+Kaku+Gothic+New:wght@400;500;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
@stack('head')
</head>
<body data-toast="{{ session('toast') }}" data-open-modal="{{ $errors->any() ? old('_modal') : '' }}">
<div id="app">
  <div id="stage">
    @yield('stage')
  </div>
  <div id="toast" role="status" aria-live="polite"></div>
  <div class="modal-bg" id="confirmModal" hidden>
    <div class="modal" role="dialog" aria-modal="true">
      <h3 data-modal-title>Lanjutkan?</h3>
      <p class="desc" data-modal-text></p>
      <div class="modal-actions"><button type="button" class="cancel" data-modal-close>Batal</button><button type="button" class="ok" id="confirmOk">Ya, lanjutkan</button></div>
    </div>
  </div>
  @stack('modals')
</div>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
@stack('scripts')
</body>
</html>
