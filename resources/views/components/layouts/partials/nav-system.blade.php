<!-- 5. ⚙️ システム・ガイド ▾ -->
<div class="relative nav-dropdown shrink-0">
    <button type="button"
        class="dropdown-trigger px-2 py-1 lg:px-2.5 lg:py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1 whitespace-nowrap cursor-pointer shrink-0 {{ $nav->isGuideActive() ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
        <span>⚙️</span>
        <span>システム・ガイド</span>
        <span class="text-[9px] opacity-75">▼</span>
    </button>
    <div class="dropdown-menu hidden absolute right-0 sm:left-auto lg:left-0 mt-1.5 w-76 max-h-[calc(100vh-4.5rem)] overflow-y-auto bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl p-1.5 z-50">
        <div class="px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-800/80 rounded-lg flex items-center justify-between">
            <span>📖 ガイド・広報資料</span>
            <span class="text-[9px] text-indigo-300 font-mono font-bold">資料</span>
        </div>
        <div class="py-1 space-y-0.5">
            <a href="{{ route('manual.index') }}"
                class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('manual.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                <span class="text-base shrink-0">📖</span>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold whitespace-nowrap truncate">操作マニュアル</div>
                    <p class="text-[10px] text-slate-400 truncate mt-0.5">図解・画面付きシステム利用手順</p>
                </div>
            </a>
            <a href="{{ route('presentation.index') }}"
                class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('presentation.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                <span class="text-base shrink-0">📽️</span>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold whitespace-nowrap truncate">幹事会プレゼン</div>
                    <p class="text-[10px] text-slate-400 truncate mt-0.5">情報一元管理の協力要請スライド</p>
                </div>
            </a>
            @can('viewAny', App\Models\User::class)
            <a href="{{ route('users.index') }}"
                class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                <span class="text-base shrink-0">👥</span>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold whitespace-nowrap truncate">ユーザー管理</div>
                    <p class="text-[10px] text-slate-400 truncate mt-0.5">本部アカウント・管理者権限設定</p>
                </div>
            </a>
            @endcan
            <a href="{{ route('staff.join') }}" target="_blank"
                class="flex items-center space-x-2 p-2 rounded-xl transition text-slate-200 hover:bg-slate-800 hover:text-white">
                <span class="text-base shrink-0">🌐</span>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold flex items-center space-x-1">
                        <span class="whitespace-nowrap truncate">セルフ登録フォーム</span>
                        <span class="text-[10px] text-slate-400">↗</span>
                    </div>
                    <p class="text-[10px] text-slate-400 truncate mt-0.5">ボランティア公開用登録ページ</p>
                </div>
            </a>
        </div>

        <div class="border-t border-slate-800/80 my-1"></div>

        <div class="px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-800/80 rounded-lg flex items-center justify-between">
            <span>⚙️ システム設定</span>
            <span class="text-[9px] text-indigo-300 font-mono font-bold">設定</span>
        </div>
        <div class="py-1 space-y-1">
            <!-- Webセルフ登録機能 ON/OFF・受付管理カード -->
            @include('components.layouts.partials.self-registration-card', [
                'cardTitle' => 'Webセルフ登録受付',
                'wrapperClass' => '',
            ])
        </div>
    </div>
</div>
