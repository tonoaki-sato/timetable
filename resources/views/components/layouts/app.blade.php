@props(['title' => 'まつり総合管理システム 2026'])
<!DOCTYPE html>
<html lang="ja" class="h-full bg-slate-50 text-slate-800">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - まつり総合管理システム</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-full flex flex-col font-sans antialiased bg-slate-100/70">

    <!-- 本部ナビゲーションヘッダー -->
    <header class="bg-slate-900 border-b border-slate-800 text-white sticky top-0 z-30 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                <!-- 左側: システムロゴ & ナビゲーションメニュー -->
                <div class="flex items-center space-x-6">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                        <span class="text-2xl transition group-hover:scale-110">🏮</span>
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-sm sm:text-base tracking-tight text-white group-hover:text-indigo-300 transition">
                                    まつり総合管理システム
                                </span>
                                <span class="bg-indigo-600 text-indigo-100 text-[10px] px-1.5 py-0.5 rounded font-mono font-bold">2026年度</span>
                            </div>
                            <p class="text-[10px] text-slate-400 hidden sm:block">運行本部・タイムテーブル・人員機材動態</p>
                        </div>
                    </a>

                    <!-- 主要リンク -->
                    <nav class="hidden md:flex items-center space-x-1 pl-4 border-l border-slate-800">
                        <a href="{{ route('dashboard') }}"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <span>📊</span>
                            <span>本部ダッシュボード</span>
                        </a>

                        <a href="{{ route('annual-schedules.index') }}"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 {{ request()->routeIs('annual-schedules.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <span>📅</span>
                            <span>年間スケジュール</span>
                        </a>

                        <a href="{{ route('users.index') }}"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 {{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <span>👥</span>
                            <span>ユーザー管理</span>
                        </a>

                        <a href="{{ route('home') }}" target="_blank"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition flex items-center space-x-1">
                            <span>🌐</span>
                            <span>トップ画面</span>
                        </a>
                    </nav>
                </div>

                <!-- 右側: ログイン中ユーザー情報 & ログアウト -->
                <div class="flex items-center space-x-3">
                    @auth
                    <div class="flex items-center space-x-3">
                        <!-- ユーザーアバター / 権限バッジ -->
                        <div class="text-right hidden sm:block">
                            <div class="text-xs font-bold text-white flex items-center justify-end space-x-1.5">
                                <span>{{ Auth::user()->name }}</span>
                                @if(Auth::user()->isAdmin())
                                <span class="bg-purple-900/80 text-purple-200 border border-purple-500/40 text-[10px] px-2 py-0.5 rounded font-bold">
                                    管理者
                                </span>
                                @else
                                <span class="bg-slate-800 text-slate-300 border border-slate-700 text-[10px] px-2 py-0.5 rounded font-medium">
                                    オペレーター
                                </span>
                                @endif
                            </div>
                            <div class="text-[10px] text-slate-400 font-mono">{{ Auth::user()->email }}</div>
                        </div>

                        <!-- プロフィールショートカット -->
                        <a href="{{ route('profile') }}"
                            class="p-2 text-slate-300 hover:text-white hover:bg-slate-800 rounded-lg transition"
                            title="プロフィール確認">
                            <span class="text-sm">👤</span>
                        </a>

                        <!-- ログアウトフォーム -->
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit"
                                onclick="return confirm('ログアウトしますか？');"
                                class="px-3 py-1.5 bg-slate-800 hover:bg-red-600 hover:text-white text-slate-300 text-xs font-medium rounded-lg border border-slate-700 hover:border-red-600 transition flex items-center space-x-1 shadow-xs cursor-pointer">
                                <span>🚪</span>
                                <span class="hidden sm:inline">ログアウト</span>
                            </button>
                        </form>
                    </div>
                    @endauth
                </div>

            </div>
        </div>

        <!-- モバイル用ナビゲーションタブバー -->
        <div class="md:hidden border-t border-slate-800 bg-slate-900/95 px-4 py-2 flex items-center justify-around text-xs">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center py-1 {{ request()->routeIs('dashboard') ? 'text-indigo-400 font-bold' : 'text-slate-400' }}">
                <span>📊</span>
                <span class="text-[10px] mt-0.5">ダッシュボード</span>
            </a>
            <a href="{{ route('annual-schedules.index') }}" class="flex flex-col items-center py-1 {{ request()->routeIs('annual-schedules.*') ? 'text-indigo-400 font-bold' : 'text-slate-400' }}">
                <span>📅</span>
                <span class="text-[10px] mt-0.5">年間予定</span>
            </a>
            <a href="{{ route('users.index') }}" class="flex flex-col items-center py-1 {{ request()->routeIs('users.*') ? 'text-indigo-400 font-bold' : 'text-slate-400' }}">
                <span>👥</span>
                <span class="text-[10px] mt-0.5">ユーザー管理</span>
            </a>
            <a href="{{ route('profile') }}" class="flex flex-col items-center py-1 {{ request()->routeIs('profile') ? 'text-indigo-400 font-bold' : 'text-slate-400' }}">
                <span>👤</span>
                <span class="text-[10px] mt-0.5">プロフィール</span>
            </a>
        </div>
    </header>

    <!-- グローバルフラッシュメッセージ -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        @if (session('success'))
        <div class="p-3.5 mb-2 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm rounded-xl font-medium flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <span>✔</span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        @endif

        @if (session('status'))
        <div class="p-3.5 mb-2 bg-indigo-50 border border-indigo-200 text-indigo-800 text-xs sm:text-sm rounded-xl font-medium flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <span>ℹ️</span>
                <span>{{ session('status') }}</span>
            </div>
        </div>
        @endif

        @if (session('error'))
        <div class="p-3.5 mb-2 bg-red-50 border border-red-200 text-red-800 text-xs sm:text-sm rounded-xl font-medium flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <span>⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
        </div>
        @endif
    </div>

    <!-- メインコンテンツスロット -->
    <main class="flex-1">
        {{ $slot }}
    </main>

    <!-- フッター -->
    <footer class="bg-white border-t border-slate-200 mt-12 py-6 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p>© 2026 まつり実行委員会 運行本部総合システム</p>
        </div>
    </footer>

</body>

</html>
