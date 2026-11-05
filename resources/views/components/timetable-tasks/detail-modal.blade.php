<!-- タスク詳細モーダル (再利用可能独立コンポーネント) -->
<div id="task-detail-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <!-- 背景オーバーレイ -->
    <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" data-action="close-modal"></div>

    <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden transform transition-all">

            <!-- モーダルヘッダー -->
            <div class="px-5 py-3.5 bg-white border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-100/80 text-indigo-600 flex items-center justify-center text-sm shadow-2xs shrink-0">
                        📋
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center space-x-2">
                            <h3 id="detail-task-title" class="text-base font-bold text-slate-900 truncate">
                                作業タスク詳細
                            </h3>
                            <span id="detail-task-status-badge" class="px-2 py-0.5 rounded text-[11px] border font-medium shrink-0"></span>
                            <span id="detail-task-critical-badge" class="hidden px-2 py-0.5 rounded text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 shrink-0">
                                🚨 最優先
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

            <!-- モーダルボディ (縦長・スクロールフリー設計) -->
            <div class="p-5 space-y-3.5">
                <!-- 属性グリッド (2列 x 2行) -->
                <div class="grid grid-cols-2 gap-2.5">
                    <div class="bg-gradient-to-br from-slate-50 to-slate-100/50 p-2.5 rounded-xl border border-slate-200/70">
                        <span class="text-[10px] font-bold tracking-wider text-slate-400 block mb-0.5">🏷️ 担当班</span>
                        <span id="detail-task-lane" class="font-bold text-slate-800 text-xs sm:text-sm truncate block"></span>
                    </div>

                    <div class="bg-gradient-to-br from-slate-50 to-slate-100/50 p-2.5 rounded-xl border border-slate-200/70">
                        <span class="text-[10px] font-bold tracking-wider text-slate-400 block mb-0.5">⏱️ 時間帯</span>
                        <span id="detail-task-time" class="font-bold text-slate-800 text-xs sm:text-sm font-mono block"></span>
                    </div>

                    <div class="bg-gradient-to-br from-slate-50 to-slate-100/50 p-2.5 rounded-xl border border-slate-200/70">
                        <span class="text-[10px] font-bold tracking-wider text-slate-400 block mb-0.5">📍 実施場所</span>
                        <span id="detail-task-location" class="font-bold text-slate-800 text-xs sm:text-sm truncate block"></span>
                    </div>

                    <div class="bg-gradient-to-br from-slate-50 to-slate-100/50 p-2.5 rounded-xl border border-slate-200/70">
                        <span class="text-[10px] font-bold tracking-wider text-slate-400 block mb-0.5">👥 必要人員</span>
                        <span id="detail-task-people" class="font-bold text-slate-800 text-xs sm:text-sm block"></span>
                    </div>
                </div>

                <!-- 作業内容・手順指示 -->
                <div class="space-y-1">
                    <h4 class="text-[11px] font-bold text-slate-700 flex items-center space-x-1">
                        <span>📝</span>
                        <span>作業内容・手順指示</span>
                    </h4>
                    <div id="detail-task-content"
                        class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80 text-xs text-slate-800 whitespace-pre-wrap leading-relaxed min-h-[70px]">
                    </div>
                </div>
            </div>

            <!-- モーダルフッター (閉じるボタンのみ・右寄せ配置) -->
            <div class="px-5 py-3 bg-slate-50/80 border-t border-slate-100 flex items-center justify-end">
                <button type="button" data-action="close-modal"
                    class="px-5 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                    閉じる
                </button>
            </div>

        </div>
    </div>
</div>
