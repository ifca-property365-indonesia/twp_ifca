<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    {{-- Ikon tab sama dengan portal TWP (layouts/app.blade.php) --}}
    <link rel="shortcut icon" href="{{ url('images/logo/logoweb.png') }}">
    <style>
        html, body { height: 100%; margin: 0; background: #525659; }
        iframe { display: block; width: 100%; height: 100%; border: 0; }
        /* dokumen bertanda tangan berupa gambar: tampil di tengah, dicetak selebar kertas */
        .signed-image { min-height: 100%; display: flex; align-items: flex-start; justify-content: center; padding: 24px; box-sizing: border-box; }
        .signed-image img { max-width: 100%; height: auto; background: #fff; box-shadow: 0 4px 16px rgba(0, 0, 0, .4); }
        @media print {
            html, body { background: #fff; height: auto; }
            .signed-image { padding: 0; display: block; }
            .signed-image img { width: 100%; box-shadow: none; }
        }
    </style>
</head>
<body>
    @if (!empty($is_image))
        <div class="signed-image"><img src="{{ $pdf_url }}" alt="{{ $title }}"></div>
    @else
        <iframe src="{{ $pdf_url }}" title="{{ $title }}"></iframe>
    @endif
</body>
</html>
