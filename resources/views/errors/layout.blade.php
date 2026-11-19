@props([
    'code',
    'title',
    'icon' => '⚠️',
    'message',
    'hint' => null,
    'showReload' => false,
])
<!DOCTYPE html>
<html lang="ja" class="h-full bg-slate-100 text-slate-800">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $code }} {{ $title }} - まつり総合管理システム</title>
    <x-head-meta />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-full flex flex-col justify-between bg-slate-100 font-sans antialiased">
    <header class="bg-slate-900 border-b border-slate-800 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center space-x-2 text-white hover:text-indigo-300 transition">
                <span class="text-xl">🏮</span>
                <span class="font-bold text-sm tracking-tight">まつり総合管理システム</span>
            </a>
        </div>
    </header>

    <main class="flex-1 flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-lg bg-white rounded-2xl border border-slate-200 shadow-lg p-6 sm:p-8 text-center space-y-5">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-red-50 border border-red-200 text-3xl mx-auto">
                {{ $icon }}
            </div>

            <div class="space-y-2">
                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-slate-100 text-slate-600 border border-slate-200">
                    HTTP {{ $code }}
                </span>
                <h1 class="text-xl font-bold text-slate-900">{{ $title }}</h1>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">{{ $message }}</p>
                @if($hint)
                    <p class="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-xl p-3">
                        {{ $hint }}
                    </p>
                @endif
            </div>

            <div class="flex flex-wrap items-center justify-center gap-2 pt-2 border-t border-slate-100">
                @if($showReload)
                    <x-ui.button variant="primary" size="md" data-action="reload">
                        <span>🔄</span>
                        <span>ページを再読み込み</span>
                    </x-ui.button>
                @endif

                @auth
                    <x-ui.button variant="header-action" size="md" :href="route('dashboard')">
                        <span>📊</span>
                        <span>ダッシュボードへ戻る</span>
                    </x-ui.button>
                @endauth

                <x-ui.button variant="secondary" size="md" :href="route('home')">
                    <span>🏠</span>
                    <span>トップページへ戻る</span>
                </x-ui.button>
            </div>
        </div>
    </main>

    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4">
            <p>© {{ now()->year }} まつり実行委員会 運行本部</p>
        </div>
    </footer>
</body>

</html>
