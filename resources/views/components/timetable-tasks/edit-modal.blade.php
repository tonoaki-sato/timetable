@props(['statuses' => \App\Models\TimetableTask::STATUSES])

<!-- タスク編集モーダル (再利用可能独立コンポーネント) -->
<div id="task-edit-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" data-action="close-modal"></div>

    <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
        <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden transform transition-all max-h-[calc(100vh-2.5rem)] flex flex-col">

            <!-- モーダルヘッダー (固定) -->
            <div class="shrink-0 px-5 py-3.5 bg-white border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center text-sm shadow-2xs shrink-0 font-bold">
                        ✏️
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center space-x-2">
                            <h3 class="text-base font-bold text-slate-900 truncate">
                                作業タスク編集
                            </h3>
                            <span id="edit-modal-lane-badge" class="px-2 py-0.5 rounded text-[11px] font-bold border bg-indigo-50 text-indigo-700 border-indigo-200 shrink-0">
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

            <!-- 時間帯重複警告メッセージ (固定ヘッダー直下) -->
            <div class="shrink-0 px-5 pt-3 empty:hidden">
                <div id="edit-modal-overlap-warning" class="hidden p-2.5 bg-rose-50 border border-rose-200/90 text-rose-800 text-xs rounded-xl flex items-center space-x-2 font-bold shadow-2xs">
                    <span class="text-sm">⚠️</span>
                    <span class="warning-text">同一レーン内の他タスクと時間帯が重複しています。</span>
                </div>
            </div>

            <!-- 入力フォーム (縦スクロール可能ボディ) -->
            <form id="task-edit-form" action="" method="POST" class="flex-1 flex flex-col min-h-0">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit-modal-task-id" name="id" value="">
                <input type="hidden" id="edit-modal-lane-id" name="lane_id" value="">

                <div class="overflow-y-auto flex-1 p-5 space-y-3.5">
                    <!-- 1. 作業タスク名 (1行全幅) -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">
                            作業タスク名 <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                            id="edit-modal-title"
                            name="title"
                            required
                            placeholder="例: 中央通り カラーコーン設置"
                            class="w-full px-3 py-1.5 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    </div>

                    <!-- 2. 時間帯 (1行2列: 開始時刻 / 終了時刻) -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                ⏱️ 開始時刻 <span class="text-rose-500">*</span>
                            </label>
                            <input type="time"
                                id="edit-modal-start-time"
                                name="start_time"
                                required
                                class="w-full px-2.5 py-1.5 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono font-medium transition">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                ⏱️ 終了時刻 <span class="text-rose-500">*</span>
                            </label>
                            <input type="time"
                                id="edit-modal-end-time"
                                name="end_time"
                                required
                                class="w-full px-2.5 py-1.5 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono font-medium transition">
                        </div>
                    </div>

                    <!-- 3. 必要人員 & 最優先フラグ (1行2列) -->
                    <div class="grid grid-cols-2 gap-3 items-end">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                👥 必要人員 <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex items-center space-x-2">
                                <input type="number"
                                    id="edit-modal-people"
                                    name="required_people_count"
                                    min="0" max="999" required
                                    class="w-20 px-2.5 py-1.5 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono font-medium transition">
                                <label class="inline-flex items-center space-x-1 cursor-pointer select-none whitespace-nowrap">
                                    <input type="hidden" name="is_at_least" value="0">
                                    <input type="checkbox"
                                        id="edit-modal-at-least"
                                        name="is_at_least"
                                        value="1"
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5">
                                    <span class="text-xs font-bold text-slate-700">以上</span>
                                </label>
                            </div>
                        </div>

                        <div class="pb-0.5">
                            <label class="inline-flex items-center space-x-2 cursor-pointer select-none px-3 py-1.5 bg-rose-50/60 hover:bg-rose-50 border border-rose-200/80 rounded-lg transition w-full">
                                <input type="hidden" name="is_critical" value="0">
                                <input type="checkbox"
                                    id="edit-modal-critical"
                                    name="is_critical"
                                    value="1"
                                    class="rounded border-rose-300 text-rose-600 focus:ring-rose-500 h-4 w-4">
                                <span class="text-xs font-bold text-rose-700">🚨 最優先タスク</span>
                            </label>
                        </div>
                    </div>

                    <!-- 4. 実施場所 & 進行ステータス (1行2列) -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                📍 実施場所・持ち場
                            </label>
                            <input type="text"
                                id="edit-modal-location"
                                name="location"
                                placeholder="例: 本陣、第1ゲート"
                                class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                📊 進行ステータス <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="edit-modal-status"
                                name="status"
                                required
                                class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                                @foreach($statuses as $key => $label)
                                <option value="{{ $key }}">
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- 5. 作業内容・手順指示 (5行テキストエリア) -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">
                            📝 作業内容・手順指示
                        </label>
                        <textarea
                            id="edit-modal-content"
                            name="content"
                            rows="5"
                            placeholder="作業手順や伝達事項・注意事項を入力（改行可能）"
                            class="w-full px-3 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition leading-relaxed resize-y"></textarea>
                    </div>
                </div>

                <!-- モーダルフッター (固定) -->
                <div class="shrink-0 px-5 py-3 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between">
                    <button type="button" data-action="close-modal"
                        class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                        キャンセル
                    </button>

                    <button type="submit" id="btn-submit-edit-task"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1.5 cursor-pointer">
                        <span>💾</span>
                        <span>作業タスクを保存する</span>
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
