<!-- タスク詳細モーダル (再利用可能独立コンポーネント) -->
<div id="task-detail-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <!-- 背景オーバーレイ -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" data-action="close-modal"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden transform transition-all space-y-4 p-5 sm:p-6">

            <!-- モーダルヘッダー -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center space-x-2.5 flex-wrap gap-y-1">
                    <span class="text-xl">📋</span>
                    <h3 id="detail-task-title" class="text-base sm:text-lg font-bold text-slate-900 truncate">
                        作業タスク詳細
                    </h3>
                    <span id="detail-task-status-badge" class="px-2 py-0.5 rounded text-xs border font-medium"></span>
                    <span id="detail-task-critical-badge" class="hidden px-2 py-0.5 rounded text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                        🚨 最優先
                    </span>
                </div>

                <button type="button" data-action="close-modal"
                    class="text-slate-400 hover:text-slate-600 rounded-lg p-1 transition text-lg leading-none cursor-pointer">
                    ✕
                </button>
            </div>

            <!-- 属性グリッド (4分割) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-500 block mb-0.5">🏷️ 担当班</span>
                    <span id="detail-task-lane" class="font-bold text-slate-800 text-xs truncate block"></span>
                </div>

                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-500 block mb-0.5">⏱️ 時間帯</span>
                    <span id="detail-task-time" class="font-bold text-slate-800 text-xs font-mono block"></span>
                </div>

                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-500 block mb-0.5">📍 実施場所</span>
                    <span id="detail-task-location" class="font-bold text-slate-800 text-xs truncate block"></span>
                </div>

                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-500 block mb-0.5">👥 必要人員</span>
                    <span id="detail-task-people" class="font-bold text-slate-800 text-xs block"></span>
                </div>
            </div>

            <!-- 作業内容・手順指示 -->
            <div>
                <h4 class="text-xs font-bold text-slate-700 mb-1.5 flex items-center space-x-1">
                    <span>📝</span>
                    <span>作業内容・手順指示</span>
                </h4>
                <div id="detail-task-content"
                    class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-800 whitespace-pre-wrap leading-relaxed min-h-[72px]">
                </div>
            </div>

            <!-- モーダルフッター -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <button type="button" data-action="close-modal"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium transition cursor-pointer">
                    閉じる
                </button>

                @can('update', App\Models\TimetableTask::class)
                <button type="button" id="btn-detail-switch-edit"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1 cursor-pointer">
                    <span>✏️</span>
                    <span>このタスクを編集</span>
                </button>
                @endcan
            </div>

        </div>
    </div>
</div>

