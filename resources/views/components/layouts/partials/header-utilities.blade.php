<!-- 右側: グローバル検索・通知ベル・ユーザー情報 & ログアウト -->
<div class="flex items-center space-x-2 lg:space-x-3 shrink-0">
    @auth
    <div class="flex items-center space-x-1.5 sm:space-x-2 shrink-0">
        <!-- グローバルクイック検索トリガー (Ctrl+K) -->
        <button type="button"
            id="btn-open-quick-search"
            data-action="open-quick-search"
            aria-label="クイック検索 (Ctrl+K)"
            title="システム横断検索 (Ctrl+K / Cmd+K)"
            class="inline-flex items-center space-x-1.5 px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg text-xs font-medium transition cursor-pointer shrink-0">
            <span>🔍</span>
            <span class="hidden xl:inline">検索</span>
            <kbd class="hidden xl:inline-block px-1 py-0.2 bg-slate-900 border border-slate-700 rounded text-[10px] font-mono text-slate-400">Ctrl K</kbd>
        </button>

        @include('components.layouts.partials.notification-bell')

        <div class="text-right hidden sm:block shrink-0">
            <div class="text-xs font-bold text-white flex items-center justify-end space-x-1.5 whitespace-nowrap">
                <span class="text-[11px] text-slate-300 truncate max-w-[100px]">{{ Auth::user()->name }}</span>
                @if(Auth::user()->isAdmin())
                <span class="bg-purple-900/80 text-purple-200 border border-purple-500/40 text-[9px] px-1.5 py-0.2 rounded font-bold whitespace-nowrap shrink-0">
                    管理者
                </span>
                @else
                <span class="bg-slate-800 text-slate-300 border border-slate-700 text-[9px] px-1.5 py-0.2 rounded font-medium whitespace-nowrap shrink-0">
                    オペレーター
                </span>
                @endif
            </div>
        </div>

        <!-- プロフィールショートカット -->
        <a href="{{ route('profile') }}"
            class="p-1.5 text-slate-300 hover:text-white hover:bg-slate-800 rounded-lg transition shrink-0"
            title="プロフィール確認">
            <span class="text-xs">👤</span>
        </a>

        <!-- ログアウトフォーム -->
        <form action="{{ route('logout') }}" method="POST" class="inline shrink-0">
            @csrf
            <button type="submit"
                data-confirm="ログアウトしますか？"
                class="px-2 py-1 bg-slate-800 hover:bg-red-600 hover:text-white text-slate-300 text-xs font-medium rounded-lg border border-slate-700 hover:border-red-600 transition flex items-center space-x-1 cursor-pointer whitespace-nowrap shrink-0">
                <span>🚪</span>
                <span class="hidden lg:inline whitespace-nowrap">ログアウト</span>
            </button>
        </form>
    </div>
    @endauth
</div>
