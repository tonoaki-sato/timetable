@auth
<!-- グローバル横断検索モーダル (Ctrl+K / Cmd+K) -->
<div id="quickSearchModal"
    class="hidden fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="quick-search-title"
    data-search-url="{{ route('search.quick') }}">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" data-close-quick-search></div>
    <div class="flex min-h-full items-start justify-center p-4 sm:pt-16">
        <div class="relative bg-white rounded-2xl shadow-xl border border-slate-200 max-w-2xl w-full overflow-hidden">
            <div class="p-3.5 border-b border-slate-200 flex items-center space-x-2.5">
                <span class="text-base text-slate-400 shrink-0">🔍</span>
                <h2 id="quick-search-title" class="sr-only">システム横断クイック検索</h2>
                <input type="text"
                    id="quick-search-input"
                    autocomplete="off"
                    placeholder="スタッフ名、出展者、機材、スケジュール、タスクを検索..."
                    class="flex-1 bg-transparent border-0 text-sm text-slate-900 placeholder-slate-400 focus:outline-none">
                <button type="button" data-close-quick-search
                    class="px-2 py-1 text-[11px] font-mono text-slate-500 hover:text-slate-800 bg-slate-100 rounded-md cursor-pointer shrink-0">
                    ESC
                </button>
            </div>

            <div id="quick-search-results" class="max-h-[60vh] overflow-y-auto divide-y divide-slate-100 p-2">
                <div class="py-8 text-center text-xs text-slate-400">
                    検索キーワードを入力してください（スタッフ・出展者・機材・予定・タスク）
                </div>
            </div>

            <div class="px-4 py-2 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-[11px] text-slate-500">
                <span id="quick-search-status">↑↓ キーで移動 · Enter で開く</span>
                <span class="font-mono">Ctrl + K / Esc</span>
            </div>
        </div>
    </div>
</div>
@endauth

<!-- アプリ共通カスタム確認モーダル (window.confirm置換) -->
<div id="appConfirmModal"
    class="hidden fixed inset-0 z-[60] overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="app-confirm-title">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" data-confirm-cancel></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-xl border border-slate-200 max-w-md w-full overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center space-x-2">
                <span id="app-confirm-icon" class="text-lg">⚠️</span>
                <h3 id="app-confirm-title" class="text-sm font-bold text-slate-900">操作の確認</h3>
            </div>
            <div class="p-5">
                <p id="app-confirm-message" class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line"></p>
            </div>
            <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-end space-x-2">
                <button type="button"
                    id="app-confirm-cancel-btn"
                    data-confirm-cancel
                    class="px-3.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-bold transition cursor-pointer">
                    キャンセル
                </button>
                <button type="button"
                    id="app-confirm-ok-btn"
                    data-confirm-ok
                    class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-bold transition cursor-pointer shadow-xs">
                    実行する
                </button>
            </div>
        </div>
    </div>
</div>
