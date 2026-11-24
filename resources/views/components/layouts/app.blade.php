@props(['title' => 'まつり総合管理システム 2026'])
<!DOCTYPE html>
<html lang="ja" class="h-full bg-slate-50 text-slate-800">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - まつり総合管理システム</title>
    <x-head-meta />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-full flex flex-col font-sans antialiased bg-slate-100/70">

    <!-- 本部ナビゲーションヘッダー -->
    <header class="bg-slate-900 border-b border-slate-800 text-white sticky top-0 z-50 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">

                <!-- 左側: システムロゴ & ナビゲーションメニュー -->
                <div class="flex items-center space-x-2 lg:space-x-3 min-w-0">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 group shrink-0">
                        <span class="text-xl transition group-hover:scale-110 shrink-0">🏮</span>
                        <div>
                            <div class="flex items-center space-x-1 whitespace-nowrap">
                                <span class="font-bold text-sm tracking-tight text-white group-hover:text-indigo-300 transition whitespace-nowrap">
                                    まつり総合管理システム
                                </span>
                            </div>
                        </div>
                    </a>

                    <!-- 主要リンク（4大別ドロップダウン: 準備/計画・当日運行・マスター管理・システム/ガイド） -->
                    <nav class="hidden md:flex items-center space-x-1 pl-2.5 border-l border-slate-800 shrink-0">
                        @include('components.layouts.partials.nav-plan')
                        @include('components.layouts.partials.nav-day')
                        @include('components.layouts.partials.nav-master')
                        @include('components.layouts.partials.nav-system')
                    </nav>
                </div>

                @include('components.layouts.partials.header-utilities')

            </div>
        </div>

        @include('components.layouts.partials.mobile-nav')
        @include('components.layouts.partials.registration-setting-modal')
    </header>

    @include('components.layouts.partials.flash-messages')

    <!-- メインコンテンツスロット -->
    <main class="flex-1">
        {{ $slot }}
    </main>

    <!-- フッター (スリム統一) -->
    <footer class="bg-white border-t border-slate-200 mt-8 py-4 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p>© {{ now()->year }} まつり実行委員会 運行本部</p>
        </div>
    </footer>

    @include('components.layouts.partials.global-modals')

</body>

</html>
