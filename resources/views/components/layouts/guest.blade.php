@props(['title' => '本部ログイン - まつり総合管理システム'])
<!DOCTYPE html>
<html lang="ja" class="h-full bg-slate-900 text-slate-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-full flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8 font-sans antialiased bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950">

    <!-- システムタイトルヘッダー -->
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <a href="{{ route('home') }}" class="inline-flex flex-col items-center group">
            <span class="text-4xl sm:text-5xl transition transform group-hover:scale-110">🏮</span>
            <div class="mt-3 flex items-center space-x-2 justify-center">
                <span class="text-xl sm:text-2xl font-black tracking-tight text-white group-hover:text-indigo-400 transition">
                    まつり総合管理システム
                </span>
            </div>
        </a>
        <p class="mt-2 text-xs text-slate-400">
            運行本部・タイムテーブル・人員機材動態・安全管理
        </p>
    </div>

    <!-- メインコンテンツカード -->
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white text-slate-800 py-8 px-6 shadow-2xl rounded-2xl border border-slate-700/30 sm:px-10">
            {{ $slot }}
        </div>

        <!-- コピーライト -->
        <p class="mt-6 text-center text-xs text-slate-500 font-mono">
            © 2026 まつり実行委員会 運行本部
        </p>
    </div>

</body>

</html>
