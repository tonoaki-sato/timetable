@props(['statuses' => \App\Models\TimetableTask::STATUSES])

<!-- タスク編集モーダル (再利用可能独立コンポーネント) -->
<div id="task-edit-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" data-action="close-modal"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden transform transition-all p-5 sm:p-6 space-y-4">

            <!-- モーダルヘッダー -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center space-x-2.5">
                    <span class="text-xl">✏️</span>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900">
                        作業タスク編集
                    </h3>
                    <span id="edit-modal-lane-badge" class="px-2.5 py-0.5 rounded text-xs font-bold border bg-indigo-50 text-indigo-700 border-indigo-200">
                        担当班
                    </span>
                </div>

                <button type="button" data-action="close-modal"
                    class="text-slate-400 hover:text-slate-600 rounded-lg p-1 transition text-lg leading-none cursor-pointer">
                    ✕
                </button>
            </div>

            <!-- 時間帯重複警告メッセージ -->
            <div id="edit-modal-overlap-warning" class="hidden p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl flex items-center space-x-2 font-bold">
                <span class="text-base">⚠️</span>
                <span class="warning-text">同一レーン内の他タスクと時間帯が重複しています。</span>
            </div>

            <!-- 入力フォーム -->
            <form id="task-edit-form" action="" method="POST" class="space-y-3.5">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit-modal-task-id" name="id" value="">
                <input type="hidden" id="edit-modal-lane-id" name="lane_id" value="">

                <!-- 1段目: 作業タスク名 / 開始時刻 / 終了時刻 / 必要人員 / 最優先フラグ -->
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-end">
                    <div class="sm:col-span-4">
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">
                            作業タスク名 <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                            id="edit-modal-title"
                            name="title"
                            required
                            placeholder="例: 中央通り カラーコーン設置"
                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">
                            開始時刻 <span class="text-red-500">*</span>
                        </label>
                        <input type="time"
                            id="edit-modal-start-time"
                            name="start_time"
                            required
                            class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono font-medium">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">
                            終了時刻 <span class="text-red-500">*</span>
                        </label>
                        <input type="time"
                            id="edit-modal-end-time"
                            name="end_time"
                            required
                            class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono font-medium">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">
                            必要人員 <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center space-x-1.5">
                            <input type="number"
                                id="edit-modal-people"
                                name="required_people_count"
                                min="0" max="999" required
                                class="w-16 px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                            <label class="inline-flex items-center space-x-1 cursor-pointer select-none whitespace-nowrap">
                                <input type="hidden" name="is_at_least" value="0">
                                <input type="checkbox"
                                    id="edit-modal-at-least"
                                    name="is_at_least"
                                    value="1"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5">
                                <span class="text-[11px] font-bold text-slate-700">以上</span>
                            </label>
                        </div>
                    </div>

                    <div class="sm:col-span-2 pb-2">
                        <label class="inline-flex items-center space-x-1.5 cursor-pointer select-none">
                            <input type="hidden" name="is_critical" value="0">
                            <input type="checkbox"
                                id="edit-modal-critical"
                                name="is_critical"
                                value="1"
                                class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 h-3.5 w-3.5">
                            <span class="text-[11px] font-bold text-rose-700 whitespace-nowrap">🚨 最優先</span>
                        </label>
                    </div>
                </div>

                <!-- 2段目: 実施場所 / 進行ステータス / 作業内容・手順指示 -->
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-end bg-slate-50/70 p-2.5 rounded-lg border border-slate-200/70">
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">
                            実施場所・持ち場
                        </label>
                        <input type="text"
                            id="edit-modal-location"
                            name="location"
                            placeholder="例: 本陣、第1ゲート"
                            class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">
                            進行ステータス <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="edit-modal-status"
                            name="status"
                            required
                            class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach($statuses as $key => $label)
                            <option value="{{ $key }}">
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-8">
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">
                            作業内容・手順指示
                        </label>
                        <input type="text"
                            id="edit-modal-content"
                            name="content"
                            placeholder="作業手順や注意事項を入力"
                            class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- モーダルフッター -->
                <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                    <button type="button" data-action="close-modal"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium transition cursor-pointer">
                        キャンセル
                    </button>

                    <button type="submit" id="btn-submit-edit-task"
                        class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1 cursor-pointer">
                        <span>💾</span>
                        <span>作業タスクを保存する</span>
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

