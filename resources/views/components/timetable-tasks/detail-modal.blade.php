<!-- タスク詳細モーダル (再利用可能独立コンポーネント) -->
<div id="task-detail-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <!-- 背景オーバーレイ -->
    <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" data-action="close-modal"></div>

    <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
        <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden transform transition-all max-h-[calc(100vh-2.5rem)] flex flex-col">

            <!-- モーダルヘッダー (固定) -->
            <div class="shrink-0 px-5 py-3.5 bg-white border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center text-sm shadow-2xs shrink-0 font-bold">
                        📋
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center space-x-2">
                            <h3 class="text-base font-bold text-slate-900 truncate">
                                作業タスク詳細
                            </h3>
                            <span id="detail-task-lane" class="px-2 py-0.5 rounded text-[11px] font-bold border bg-indigo-50 text-indigo-700 border-indigo-200 shrink-0">
                                担当班
                            </span>
                        </div>
                    </div>
                </div>

                <button type="button" data-action="close-modal"
                    class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition text-sm leading-none cursor-pointer shrink-0"
                    title="閉じる">
                    ✕
                </button>
            </div>

            <!-- モーダルボディ (追加・編集モーダルと対比・スクロール可能領域) -->
            <div class="overflow-y-auto flex-1 p-5 space-y-3.5">
                <!-- 1. 作業タスク名 (1行全幅) -->
                <div>
                    <span class="block text-[11px] font-bold text-slate-500 mb-1">
                        🏷️ 作業タスク名
                    </span>
                    <div id="detail-task-title" class="text-sm sm:text-base font-bold text-slate-900 bg-slate-50/60 px-3 py-2 rounded-lg border border-slate-200/80">
                        作業タスク名
                    </div>
                </div>

                <!-- 2. 時間帯 (1行全幅カード) -->
                <div>
                    <span class="block text-[11px] font-bold text-slate-500 mb-1">
                        ⏱️ 時間帯
                    </span>
                    <div id="detail-task-time" class="text-xs sm:text-sm font-mono font-bold text-slate-800 bg-slate-50/60 px-3 py-2 rounded-lg border border-slate-200/80">
                        08:00 〜 09:30
                    </div>
                </div>

                <!-- 3. 必要人員 & 最優先フラグ (1行2列) -->
                <div class="grid grid-cols-2 gap-3 items-end">
                    <div>
                        <span class="block text-[11px] font-bold text-slate-500 mb-1">
                            👥 必要人員
                        </span>
                        <div id="detail-task-people" class="text-xs sm:text-sm font-bold text-slate-800 bg-slate-50/60 px-3 py-2 rounded-lg border border-slate-200/80 min-h-[38px] flex items-center">
                            2名
                        </div>
                    </div>

                    <div>
                        <span class="block text-[11px] font-bold text-slate-500 mb-1">
                            🚨 優先度
                        </span>
                        <div class="bg-slate-50/60 px-3 py-1.5 rounded-lg border border-slate-200/80 flex items-center min-h-[38px]">
                            <span id="detail-task-critical-badge" class="hidden px-2.5 py-0.5 rounded text-xs font-bold bg-rose-100 text-rose-700 border border-rose-200">
                                🚨 最優先
                            </span>
                            <span class="text-xs text-slate-400 font-medium default-notice">
                                通常
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 4. 実施場所 & 進行ステータス (1行2列) -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <span class="block text-[11px] font-bold text-slate-500 mb-1">
                            📍 実施場所・持ち場
                        </span>
                        <div id="detail-task-location" class="text-xs sm:text-sm font-bold text-slate-800 bg-slate-50/60 px-3 py-2 rounded-lg border border-slate-200/80 min-h-[38px] flex items-center truncate">
                            本陣
                        </div>
                    </div>

                    <div>
                        <span class="block text-[11px] font-bold text-slate-500 mb-1">
                            📊 進行ステータス
                        </span>
                        <div class="bg-slate-50/60 px-3 py-1.5 rounded-lg border border-slate-200/80 flex items-center min-h-[38px]">
                            <span id="detail-task-status-badge" class="px-2.5 py-0.5 rounded text-xs border font-medium">
                                未着手
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 5. 作業内容・手順指示 (5行表示エリア) -->
                <div>
                    <span class="block text-[11px] font-bold text-slate-500 mb-1">
                        📝 作業内容・手順指示
                    </span>
                    <div id="detail-task-content"
                        class="min-h-[120px] p-3.5 bg-slate-50/60 rounded-xl border border-slate-200/80 text-xs sm:text-sm text-slate-800 whitespace-pre-wrap leading-relaxed">
                    </div>
                </div>
            </div>

            <!-- モーダルフッター (固定・閉じるボタンのみ) -->
            <div class="shrink-0 px-5 py-3 bg-slate-50/80 border-t border-slate-100 flex items-center justify-end">
                <button type="button" data-action="close-modal"
                    class="px-5 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                    閉じる
                </button>
            </div>

        </div>
    </div>
</div>
