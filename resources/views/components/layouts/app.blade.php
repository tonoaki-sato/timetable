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
                <div class="flex items-center space-x-3 lg:space-x-4 min-w-0">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-2.5 group shrink-0">
                        <span class="text-2xl transition group-hover:scale-110 shrink-0">🏮</span>
                        <div>
                            <div class="flex items-center space-x-1.5 whitespace-nowrap">
                                <span class="font-bold text-sm lg:text-base tracking-tight text-white group-hover:text-indigo-300 transition whitespace-nowrap">
                                    まつり総合管理システム
                                </span>
                                <span class="bg-indigo-600 text-indigo-100 text-[10px] px-1.5 py-0.5 rounded font-mono font-bold whitespace-nowrap">2026年度</span>
                            </div>
                            <p class="text-[10px] text-slate-400 hidden 2xl:block whitespace-nowrap">運行本部・タイムテーブル・人員機材動態</p>
                        </div>
                    </a>

                    <!-- 主要リンク -->
                    <nav class="hidden md:flex items-center space-x-0.5 lg:space-x-1 pl-3 lg:pl-4 border-l border-slate-800 shrink-0">
                        <a href="{{ route('dashboard') }}"
                            class="px-2 py-1.5 lg:px-2.5 xl:px-3 rounded-lg text-xs font-bold transition flex items-center space-x-1 lg:space-x-1.5 whitespace-nowrap shrink-0 {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <span>📊</span>
                            <span><span class="hidden xl:inline">本部</span>ダッシュボード</span>
                        </a>

                        <a href="{{ route('annual-schedules.index') }}"
                            class="px-2 py-1.5 lg:px-2.5 xl:px-3 rounded-lg text-xs font-bold transition flex items-center space-x-1 lg:space-x-1.5 whitespace-nowrap shrink-0 {{ request()->routeIs('annual-schedules.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <span>📅</span>
                            <span>年間<span class="hidden xl:inline">スケジュール</span><span class="xl:hidden">予定</span></span>
                        </a>

                        <a href="{{ route('timetables.index') }}"
                            class="px-2 py-1.5 lg:px-2.5 xl:px-3 rounded-lg text-xs font-bold transition flex items-center space-x-1 lg:space-x-1.5 whitespace-nowrap shrink-0 {{ request()->routeIs('timetables.*') || request()->routeIs('timetable-tasks.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <span>📋</span>
                            <span><span class="hidden xl:inline">運行日・</span>タスク管理</span>
                        </a>

                        <a href="{{ route('timetable.index') }}"
                            class="px-2 py-1.5 lg:px-2.5 xl:px-3 rounded-lg text-xs font-bold transition flex items-center space-x-1 lg:space-x-1.5 whitespace-nowrap shrink-0 {{ request()->routeIs('timetable.index') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <span>⏱️</span>
                            <span><span class="hidden xl:inline">全体</span>マトリクス</span>
                        </a>

                        <a href="{{ route('staff.index') }}"
                            class="px-2 py-1.5 lg:px-2.5 xl:px-3 rounded-lg text-xs font-bold transition flex items-center space-x-1 lg:space-x-1.5 whitespace-nowrap shrink-0 {{ request()->routeIs('staff.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <span>👷</span>
                            <span>スタッフ</span>
                        </a>

                        <a href="{{ route('users.index') }}"
                            class="px-2 py-1.5 lg:px-2.5 xl:px-3 rounded-lg text-xs font-bold transition flex items-center space-x-1 lg:space-x-1.5 whitespace-nowrap shrink-0 {{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <span>👥</span>
                            <span>ユーザー<span class="hidden xl:inline">管理</span></span>
                        </a>
                    </nav>
                </div>

                <!-- 右側: ログイン中ユーザー情報 & ログアウト -->
                <div class="flex items-center space-x-2 lg:space-x-3 shrink-0">
                    @auth
                    <div class="flex items-center space-x-2 lg:space-x-3 shrink-0">
                        <!-- ユーザーアバター / 権限バッジ -->
                        <div class="text-right hidden sm:block shrink-0">
                            <div class="text-xs font-bold text-white flex items-center justify-end space-x-1.5 whitespace-nowrap">
                                <span class="whitespace-nowrap max-w-[120px] lg:max-w-none truncate">{{ Auth::user()->name }}</span>
                                @if(Auth::user()->isAdmin())
                                <span class="bg-purple-900/80 text-purple-200 border border-purple-500/40 text-[10px] px-1.5 py-0.5 rounded font-bold whitespace-nowrap shrink-0">
                                    管理者
                                </span>
                                @else
                                <span class="bg-slate-800 text-slate-300 border border-slate-700 text-[10px] px-1.5 py-0.5 rounded font-medium whitespace-nowrap shrink-0">
                                    オペレーター
                                </span>
                                @endif
                            </div>
                            <div class="text-[10px] text-slate-400 font-mono hidden xl:block whitespace-nowrap">{{ Auth::user()->email }}</div>
                        </div>

                        <!-- プロフィールショートカット -->
                        <a href="{{ route('profile') }}"
                            class="p-1.5 lg:p-2 text-slate-300 hover:text-white hover:bg-slate-800 rounded-lg transition shrink-0"
                            title="プロフィール確認">
                            <span class="text-sm">👤</span>
                        </a>

                        <!-- ログアウトフォーム -->
                        <form action="{{ route('logout') }}" method="POST" class="inline shrink-0">
                            @csrf
                            <button type="submit"
                                onclick="return confirm('ログアウトしますか？');"
                                class="px-2.5 py-1.5 lg:px-3 lg:py-1.5 bg-slate-800 hover:bg-red-600 hover:text-white text-slate-300 text-xs font-medium rounded-lg border border-slate-700 hover:border-red-600 transition flex items-center space-x-1 shadow-xs cursor-pointer whitespace-nowrap shrink-0">
                                <span>🚪</span>
                                <span class="hidden lg:inline whitespace-nowrap">ログアウト</span>
                            </button>
                        </form>
                    </div>
                    @endauth
                </div>

            </div>
        </div>

        <!-- モバイル用ナビゲーションタブバー -->
        <div class="md:hidden border-t border-slate-800 bg-slate-900/95 px-1 py-1.5 flex items-center justify-around text-xs">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center py-1 px-1 whitespace-nowrap {{ request()->routeIs('dashboard') ? 'text-indigo-400 font-bold' : 'text-slate-400' }}">
                <span>📊</span>
                <span class="text-[10px] mt-0.5 whitespace-nowrap">本部</span>
            </a>
            <a href="{{ route('annual-schedules.index') }}" class="flex flex-col items-center py-1 px-1 whitespace-nowrap {{ request()->routeIs('annual-schedules.*') ? 'text-indigo-400 font-bold' : 'text-slate-400' }}">
                <span>📅</span>
                <span class="text-[10px] mt-0.5 whitespace-nowrap">年間予定</span>
            </a>
            <a href="{{ route('timetables.index') }}" class="flex flex-col items-center py-1 px-1 whitespace-nowrap {{ request()->routeIs('timetables.*') || request()->routeIs('timetable-tasks.*') ? 'text-indigo-400 font-bold' : 'text-slate-400' }}">
                <span>📋</span>
                <span class="text-[10px] mt-0.5 whitespace-nowrap">運行日</span>
            </a>
            <a href="{{ route('timetable.index') }}" class="flex flex-col items-center py-1 px-1 whitespace-nowrap {{ request()->routeIs('timetable.index') ? 'text-indigo-400 font-bold' : 'text-slate-400' }}">
                <span>⏱️</span>
                <span class="text-[10px] mt-0.5 whitespace-nowrap">マトリクス</span>
            </a>
            <a href="{{ route('staff.index') }}" class="flex flex-col items-center py-1 px-1 whitespace-nowrap {{ request()->routeIs('staff.*') ? 'text-indigo-400 font-bold' : 'text-slate-400' }}">
                <span>👷</span>
                <span class="text-[10px] mt-0.5 whitespace-nowrap">スタッフ</span>
            </a>
            <a href="{{ route('users.index') }}" class="flex flex-col items-center py-1 px-1 whitespace-nowrap {{ request()->routeIs('users.*') ? 'text-indigo-400 font-bold' : 'text-slate-400' }}">
                <span>👥</span>
                <span class="text-[10px] mt-0.5 whitespace-nowrap">ユーザー</span>
            </a>
            <a href="{{ route('profile') }}" class="flex flex-col items-center py-1 px-1 whitespace-nowrap {{ request()->routeIs('profile') ? 'text-indigo-400 font-bold' : 'text-slate-400' }}">
                <span>👤</span>
                <span class="text-[10px] mt-0.5 whitespace-nowrap">設定</span>
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