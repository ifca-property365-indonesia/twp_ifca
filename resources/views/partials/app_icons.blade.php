{{--
    Ikon tab browser & shortcut "Add to Home screen" (Android / iPhone).
    Manifest: /manifest.webmanifest (nama, ikon, tampilan standalone). Ikon di images/icons, dibuat dari
    images/logo/IFCA_trade.png (512x512, diperkecil ke 192, 512, maskable 512, 180, 32).
--}}
<link rel="icon" type="image/png" sizes="32x32" href="{{ url('images/icons/favicon-32.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ url('images/icons/icon-192.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ url('images/icons/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ url('manifest.webmanifest') }}">
<meta name="theme-color" content="#ffffff">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="TWP">
<meta name="application-name" content="TWP">
