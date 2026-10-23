@props(['title' => 'まつり総合管理システム 2026'])
<!DOCTYPE html>
<html lang="ja" class="h-full bg-slate-50 text-slate-800">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

                    <!-- 主要リンク（グループ化ドロップダウン） -->
                    @php
                        $isScheduleActive = request()->routeIs('calendar.*') || request()->routeIs('annual-schedules.*') || request()->routeIs('meetings.*');
                        $isOperationActive = request()->routeIs('timetables.*') || request()->routeIs('timetable-tasks.*') || request()->routeIs('timetable.index');
                        $isManagementActive = request()->routeIs('staff.*') || request()->routeIs('users.*');
                    @endphp
                    <nav class="hidden md:flex items-center space-x-1 pl-3 lg:pl-4 border-l border-slate-800 shrink-0">
                        <!-- 1. 本部ダッシュボード -->
                        <a href="{{ route('dashboard') }}"
                            class="px-2.5 py-1.5 lg:px-3 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 whitespace-nowrap shrink-0 {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                            <span>📊</span>
                            <span>本部ダッシュボード</span>
                        </a>

                        <!-- 2. スケジュール ▾ -->
                        <div class="relative nav-dropdown shrink-0">
                            <button type="button"
                                class="dropdown-trigger px-2.5 py-1.5 lg:px-3 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 whitespace-nowrap cursor-pointer {{ $isScheduleActive ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                                <span>📅</span>
                                <span>スケジュール</span>
                                <span class="text-[9px] opacity-75">▼</span>
                            </button>
                            <div class="dropdown-menu hidden absolute left-0 mt-1.5 w-64 bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl p-1.5 z-50 divide-y divide-slate-800/80">
                                <div class="py-1 space-y-0.5">
                                    <a href="{{ route('calendar.index') }}"
                                        class="flex items-start space-x-2.5 p-2 rounded-xl transition {{ request()->routeIs('calendar.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">📅</span>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold">総合カレンダー</div>
                                            <p class="text-[10px] {{ request()->routeIs('calendar.*') ? 'text-indigo-100' : 'text-slate-400' }} truncate mt-0.5">月間グリッドで予定・会議・運行日を俯瞰</p>
                                        </div>
                                    </a>
                                    <a href="{{ route('annual-schedules.index') }}"
                                        class="flex items-start space-x-2.5 p-2 rounded-xl transition {{ request()->routeIs('annual-schedules.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">📋</span>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold">年間工程表・タスク</div>
                                            <p class="text-[10px] {{ request()->routeIs('annual-schedules.*') ? 'text-indigo-100' : 'text-slate-400' }} truncate mt-0.5">年次マイルストーン・準備工程管理</p>
                                        </div>
                                    </a>
                                    <a href="{{ route('meetings.index') }}"
                                        class="flex items-start space-x-2.5 p-2 rounded-xl transition {{ request()->routeIs('meetings.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">🤝</span>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold">会議・打合せ日程</div>
                                            <p class="text-[10px] {{ request()->routeIs('meetings.*') ? 'text-indigo-100' : 'text-slate-400' }} truncate mt-0.5">幹事会・総会・LINE告知テキスト生成</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- 3. まつり運行 ▾ -->
                        <div class="relative nav-dropdown shrink-0">
                            <button type="button"
                                class="dropdown-trigger px-2.5 py-1.5 lg:px-3 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 whitespace-nowrap cursor-pointer {{ $isOperationActive ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                                <span>⏱️</span>
                                <span>まつり運行</span>
                                <span class="text-[9px] opacity-75">▼</span>
                            </button>
                            <div class="dropdown-menu hidden absolute left-0 mt-1.5 w-64 bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl p-1.5 z-50 divide-y divide-slate-800/80">
                                <div class="py-1 space-y-0.5">
                                    <a href="{{ route('timetables.index') }}"
                                        class="flex items-start space-x-2.5 p-2 rounded-xl transition {{ request()->routeIs('timetables.*') || request()->routeIs('timetable-tasks.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">📋</span>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold">運行日・タスク管理</div>
                                            <p class="text-[10px] {{ request()->routeIs('timetables.*') ? 'text-indigo-100' : 'text-slate-400' }} truncate mt-0.5">4日間のタイムテーブルと人員配置</p>
                                        </div>
                                    </a>
                                    <a href="{{ route('timetable.index') }}"
                                        class="flex items-start space-x-2.5 p-2 rounded-xl transition {{ request()->routeIs('timetable.index') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">⏱️</span>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold">全体マトリクス</div>
                                            <p class="text-[10px] {{ request()->routeIs('timetable.index') ? 'text-indigo-100' : 'text-slate-400' }} truncate mt-0.5">時間帯×レーンの全体進行状況表</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- 4. 設定・管理 ▾ -->
                        <div class="relative nav-dropdown shrink-0">
                            <button type="button"
                                class="dropdown-trigger px-2.5 py-1.5 lg:px-3 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 whitespace-nowrap cursor-pointer {{ $isManagementActive ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                                <span>👥</span>
                                <span>設定・管理</span>
                                <span class="text-[9px] opacity-75">▼</span>
                            </button>
                            <div class="dropdown-menu hidden absolute right-0 sm:left-0 mt-1.5 w-60 bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl p-1.5 z-50 divide-y divide-slate-800/80">
                                <div class="py-1 space-y-0.5">
                                    <a href="{{ route('staff.index') }}"
                                        class="flex items-start space-x-2.5 p-2 rounded-xl transition {{ request()->routeIs('staff.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">👷</span>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold">スタッフ管理</div>
                                            <p class="text-[10px] {{ request()->routeIs('staff.*') ? 'text-indigo-100' : 'text-slate-400' }} truncate mt-0.5">現場ボランティア・幹事名簿</p>
                                        </div>
                                    </a>
                                    <a href="{{ route('users.index') }}"
                                        class="flex items-start space-x-2.5 p-2 rounded-xl transition {{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">👥</span>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold">ユーザー管理</div>
                                            <p class="text-[10px] {{ request()->routeIs('users.*') ? 'text-indigo-100' : 'text-slate-400' }} truncate mt-0.5">本部アカウント・ログイン権限</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>

                <!-- 右側: ログイン中ユーザー情報 & ログアウト -->
                <div class="flex items-center space-x-2 lg:space-x-3 shrink-0">
                    @auth
                    <div class="flex items-center space-x-2 lg:space-x-3 shrink-0">
                        <!-- ユーザーアバター / 権限バッジ -->
                        <div class="text-right hidden sm:block shrink-0">
                            <div class="text-xs font-bold text-white flex items-center justify-end space-x-1.5 whitespace-nowrap">
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

        <!-- モバイル用ナビゲーションタブバー（5枠最適化） -->
        <div class="md:hidden border-t border-slate-800 bg-slate-900/95 px-2 py-1.5 flex items-center justify-around text-xs">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center py-1 px-2 whitespace-nowrap {{ request()->routeIs('dashboard') ? 'text-indigo-400 font-bold' : 'text-slate-400 hover:text-white' }}">
                <span class="text-base">📊</span>
                <span class="text-[10px] mt-0.5">本部</span>
            </a>
            <a href="{{ route('calendar.index') }}" class="flex flex-col items-center py-1 px-2 whitespace-nowrap {{ request()->routeIs('calendar.*') ? 'text-indigo-400 font-bold' : 'text-slate-400 hover:text-white' }}">
                <span class="text-base">📅</span>
                <span class="text-[10px] mt-0.5">カレンダー</span>
            </a>
            <a href="{{ route('timetable.index') }}" class="flex flex-col items-center py-1 px-2 whitespace-nowrap {{ request()->routeIs('timetable.index') ? 'text-indigo-400 font-bold' : 'text-slate-400 hover:text-white' }}">
                <span class="text-base">⏱️</span>
                <span class="text-[10px] mt-0.5">マトリクス</span>
            </a>
            <a href="{{ route('staff.index') }}" class="flex flex-col items-center py-1 px-2 whitespace-nowrap {{ request()->routeIs('staff.*') ? 'text-indigo-400 font-bold' : 'text-slate-400 hover:text-white' }}">
                <span class="text-base">👷</span>
                <span class="text-[10px] mt-0.5">スタッフ</span>
            </a>
            <button type="button" onclick="toggleMobileMenu()" class="flex flex-col items-center py-1 px-2 whitespace-nowrap text-slate-400 hover:text-white cursor-pointer">
                <span class="text-base">☰</span>
                <span class="text-[10px] mt-0.5">メニュー</span>
            </button>
        </div>

        <!-- モバイル用メニューモーダル -->
        <div id="mobileMenuModal" class="hidden md:hidden fixed inset-0 z-50">
            <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" onclick="toggleMobileMenu()"></div>
            <div class="fixed inset-x-0 bottom-0 bg-slate-900 border-t border-slate-800 rounded-t-3xl p-5 z-10 max-h-[85vh] overflow-y-auto space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <span class="text-sm font-bold text-white flex items-center space-x-2">
                        <span>🏮</span>
                        <span>すべてのメニュー</span>
                    </span>
                    <button type="button" onclick="toggleMobileMenu()" class="text-slate-400 hover:text-white text-lg p-1 cursor-pointer">✕</button>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <a href="{{ route('annual-schedules.index') }}" class="p-3 bg-slate-800/80 hover:bg-slate-800 rounded-xl flex items-center space-x-2 text-slate-200 {{ request()->routeIs('annual-schedules.*') ? 'ring-1 ring-indigo-500 bg-slate-800' : '' }}">
                        <span class="text-lg">📋</span>
                        <div class="min-w-0">
                            <span class="font-bold block truncate">年間スケジュール</span>
                            <span class="text-[10px] text-slate-400 block truncate">工程・タスク表</span>
                        </div>
                    </a>
                    <a href="{{ route('meetings.index') }}" class="p-3 bg-slate-800/80 hover:bg-slate-800 rounded-xl flex items-center space-x-2 text-slate-200 {{ request()->routeIs('meetings.*') ? 'ring-1 ring-indigo-500 bg-slate-800' : '' }}">
                        <span class="text-lg">🤝</span>
                        <div class="min-w-0">
                            <span class="font-bold block truncate">会議・打合せ</span>
                            <span class="text-[10px] text-slate-400 block truncate">幹事会・総会</span>
                        </div>
                    </a>
                    <a href="{{ route('timetables.index') }}" class="p-3 bg-slate-800/80 hover:bg-slate-800 rounded-xl flex items-center space-x-2 text-slate-200 {{ request()->routeIs('timetables.*') ? 'ring-1 ring-indigo-500 bg-slate-800' : '' }}">
                        <span class="text-lg">📋</span>
                        <div class="min-w-0">
                            <span class="font-bold block truncate">運行日管理</span>
                            <span class="text-[10px] text-slate-400 block truncate">タスク詳細・配置</span>
                        </div>
                    </a>
                    <a href="{{ route('users.index') }}" class="p-3 bg-slate-800/80 hover:bg-slate-800 rounded-xl flex items-center space-x-2 text-slate-200 {{ request()->routeIs('users.*') ? 'ring-1 ring-indigo-500 bg-slate-800' : '' }}">
                        <span class="text-lg">👥</span>
                        <div class="min-w-0">
                            <span class="font-bold block truncate">ユーザー管理</span>
                            <span class="text-[10px] text-slate-400 block truncate">本部アカウント</span>
                        </div>
                    </a>
                    <a href="{{ route('profile') }}" class="p-3 bg-slate-800/80 hover:bg-slate-800 rounded-xl flex items-center space-x-2 text-slate-200 {{ request()->routeIs('profile') ? 'ring-1 ring-indigo-500 bg-slate-800' : '' }}">
                        <span class="text-lg">👤</span>
                        <div class="min-w-0">
                            <span class="font-bold block truncate">アカウント設定</span>
                            <span class="text-[10px] text-slate-400 block truncate">パスワード・情報</span>
                        </div>
                    </a>
                    <form action="{{ route('logout') }}" method="POST" class="p-0">
                        @csrf
                        <button type="submit" onclick="return confirm('ログアウトしますか？');" class="w-full h-full p-3 bg-red-950/40 hover:bg-red-900/60 border border-red-800/50 rounded-xl flex items-center space-x-2 text-red-200 text-left cursor-pointer">
                            <span class="text-lg">🚪</span>
                            <div class="min-w-0">
                                <span class="font-bold block truncate">ログアウト</span>
                                <span class="text-[10px] text-red-400 block truncate">セッション終了</span>
                            </div>
                        </button>
                    </form>
                </div>
            </div>
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

    <!-- ナビゲーションメニュー対話スクリプト -->
    <script>
    (function() {
        'use strict';

        // 1. デスクトップ用ドロップダウン制御
        const dropdowns = document.querySelectorAll('.nav-dropdown');

        dropdowns.forEach(dropdown => {
            const trigger = dropdown.querySelector('.dropdown-trigger');
            const menu = dropdown.querySelector('.dropdown-menu');

            if (!trigger || !menu) return;

            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                const isCurrentlyOpen = !menu.classList.contains('hidden');

                // 他のすべてのドロップダウンを閉じる
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));

                if (!isCurrentlyOpen) {
                    menu.classList.remove('hidden');
                }
            });
        });

        // 2. 外側クリックでドロップダウンを閉じる
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.nav-dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        // 3. Escキーで閉じる
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
                const mobileMenu = document.getElementById('mobileMenuModal');
                if (mobileMenu) mobileMenu.classList.add('hidden');
            }
        });

        // 4. モバイル用メニューモーダル開閉
        window.toggleMobileMenu = function() {
            const modal = document.getElementById('mobileMenuModal');
            if (modal) {
                modal.classList.toggle('hidden');
            }
        };
    })();
    </script>

</body>

</html>