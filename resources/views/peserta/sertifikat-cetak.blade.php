<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sertifikat {{ $cert->number }}</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&family=Zen+Kaku+Gothic+New:wght@400;500;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<style>
  @page{size:A4 landscape;margin:12mm}
  html,body{height:auto;background:#fff}
  .wrap{max-width:900px;margin:24px auto;padding:0 16px}
  .cert{max-width:none}
</style>
</head>
<body>
<div class="wrap">
  <p class="no-print small muted" style="text-align:center">Pilih “Simpan sebagai PDF” di dialog cetak untuk mengunduh. <button class="bt solid" onclick="window.print()">Cetak / Simpan PDF</button></p>
  @include('partials.certificate', ['cert' => $cert, 'name' => $cert->user->name])
</div>
<script>window.addEventListener('load', () => setTimeout(() => window.print(), 400));</script>
</body>
</html>
