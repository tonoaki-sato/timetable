@props([
    'description' => 'まつり実行委員会の運行本部・タイムテーブル・人員機材動態・会場配置を一元管理する総合システム',
    'ogTitle' => 'まつり総合管理システム',
])

<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="alternate icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta name="theme-color" content="#0f172a">
<meta name="description" content="{{ $description }}">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:type" content="website">
