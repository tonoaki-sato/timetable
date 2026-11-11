@props(['timetable' => null, 'lanes' => collect(), 'statuses' => \App\Models\TimetableTask::STATUSES])

@php
    $isReopenOnError = $errors->any() && old('_form') === 'common_task';
    $selectedLaneIds = old('_form') === 'common_task'
        ? array_map('intval', (array) old('lane_ids', []))
        : $lanes->pluck('id')->map(fn ($id): int => (int) $id)->all();
@endphp

<!-- 複数班・全班 共通タスク一括登録モーダル -->
<div id="task-common-create-modal"
    class="hidden fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    data-reopen-on-error="{{ $isReopenOnError ? '1' : '0' }}">
    <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" data-action="close-modal"></div>

    <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden transform transition-all max-h-[calc(100vh-2.5rem)] flex flex-col">

            <!-- モーダルヘッダー (固定) -->
            <div class="shrink-0 px-5 py-3.5 bg-white border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center text-sm shadow-2xs shrink-0 font-bold">
                        🌐
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center space-x-2">
                            <h3 class="text-base font-bold text-slate-900 truncate">
                                共通タスク一括登録
                            </h3>
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold border bg-indigo-50 text-indigo-700 border-indigo-200 shrink-0">
                                複数班・全班対応
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5 truncate">
                            全体ミーティングや朝礼など、複数の担当班に同一タスクをまとめて登録します。
                        </p>
                    </div>
                </div>

                <button type="button" data-action="close-modal"
                    class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition text-sm leading-none cursor-pointer shrink-0"
                    title="閉じる">
                    ✕
                </button>
            </div>

            <!-- 警告・エラーメッセージエリア (固定ヘッダー直下) -->
            <div class="shrink-0 px-5 pt-3 space-y-2 empty:hidden">
                @if($isReopenOnError)
                <div id="common-modal-server-errors" class="p-2.5 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-xl shadow-2xs">
                    <div class="font-bold flex items-center space-x-1.5 mb-1">
                        <span>⚠️</span>
                        <span>登録できませんでした。入力内容を確認してください。</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-[11px]">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div id="common-modal-overlap-warning" class="hidden p-2.5 bg-rose-50 border border-rose-200/90 text-rose-800 text-xs rounded-xl shadow-2xs">
                    <div class="flex items-center space-x-2 font-bold">
                        <span class="text-sm">⚠️</span>
                        <span>以下の担当班で既存タスクと時間帯が重複しているため登録できません。</span>
                    </div>
                    <ul id="common-modal-overlap-list" class="mt-1.5 pl-6 list-disc space-y-0.5 text-[11px] font-medium text-rose-700"></ul>
                </div>

                <div id="common-modal-no-lane-warning" class="hidden p-2.5 bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-xl flex items-center space-x-2 font-bold shadow-2xs">
                    <span class="text-sm">ℹ️</span>
                    <span>登録対象の担当班を1つ以上選択してください。</span>
                </div>
            </div>

            <!-- 入力フォーム (縦スクロール可能ボディ) -->
            <form id="task-common-create-form" action="{{ route('timetable-tasks.common.store') }}" method="POST" class="flex-1 flex flex-col min-h-0">
                @csrf
                <input type="hidden" name="_form" value="common_task">
                <input type="hidden" name="timetable_id" value="{{ $timetable?->id }}">

                <div class="overflow-y-auto flex-1 p-5 space-y-4">
                    <!-- 1. 対象担当班の選択 -->
                    <div class="bg-slate-50/80 border border-slate-200/90 rounded-xl p-3.5 space-y-2.5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center space-x-2">
                                <label class="text-xs font-bold text-slate-800">
                                    🚩 登録対象の担当班 <span class="text-rose-500">*</span>
                                </label>
                                <span id="common-modal-selected-count" class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-indigo-100 text-indigo-700">
                                    {{ count($selectedLaneIds) }} / {{ $lanes->count() }} 班選択中
                                </span>
                            </div>

                            <div class="flex items-center space-x-1.5">
                                <button type="button" data-action="common-select-all-lanes"
                                    class="px-2 py-1 bg-white hover:bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg text-[11px] font-bold transition cursor-pointer">
                                    すべて選択
                                </button>
                                <button type="button" data-action="common-clear-all-lanes"
                                    class="px-2 py-1 bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 rounded-lg text-[11px] font-bold transition cursor-pointer">
                                    すべて解除
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-44 overflow-y-auto pr-1">
                            @foreach($lanes as $lane)
                            <label class="flex items-center justify-between gap-2 px-2.5 py-2 bg-white hover:bg-indigo-50/40 border border-slate-200 rounded-lg cursor-pointer transition select-none">
                                <span class="flex items-center space-x-2 min-w-0">
                                    <input type="checkbox"
                                        name="lane_ids[]"
                                        value="{{ $lane->id }}"
                                        data-lane-name="{{ $lane->name }}"
                                        {{ in_array((int) $lane->id, $selectedLaneIds, true) ? 'checked' : '' }}
                                        class="common-lane-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 shrink-0">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-bold border truncate {{ $lane->color_classes['badge'] }}">
                                        <span class="mr-1 shrink-0">{{ $lane->icon_label }}</span>
                                        <span class="truncate">{{ $lane->name }}</span>
                                    </span>
                                </span>
                                <span class="text-[10px] font-mono text-slate-400 shrink-0">
                                    {{ $lane->tasks->count() }}件
                                </span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- 2. 作業タスク名 -->
                    <div>
                        <label for="common-modal-title" class="block text-[11px] font-bold text-slate-700 mb-1">
                            作業タスク名 <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                            id="common-modal-title"
                            name="title"
                            value="{{ old('_form') === 'common_task' ? old('title') : '' }}"
                            required
                            placeholder="例: 全体ミーティング・朝礼"
                            class="w-full px-3 py-1.5 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    </div>

                    <!-- 3. 時間帯 (開始時刻 / 終了時刻) -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="common-modal-start-time" class="block text-[11px] font-bold text-slate-700 mb-1">
                                ⏱️ 開始時刻 <span class="text-rose-500">*</span>
                            </label>
                            <input type="time"
                                id="common-modal-start-time"
                                name="start_time"
                                value="{{ old('_form') === 'common_task' ? old('start_time', '08:00') : '08:00' }}"
                                required
                                class="w-full px-2.5 py-1.5 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono font-medium transition">
                        </div>

                        <div>
                            <label for="common-modal-end-time" class="block text-[11px] font-bold text-slate-700 mb-1">
                                ⏱️ 終了時刻 <span class="text-rose-500">*</span>
                            </label>
                            <input type="time"
                                id="common-modal-end-time"
                                name="end_time"
                                value="{{ old('_form') === 'common_task' ? old('end_time', '08:30') : '08:30' }}"
                                required
                                class="w-full px-2.5 py-1.5 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono font-medium transition">
                        </div>
                    </div>

                    <!-- 4. 必要人員 & 最優先フラグ -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                        <div>
                            <label for="common-modal-people" class="block text-[11px] font-bold text-slate-700 mb-1">
                                👥 必要人員（1班あたり） <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex items-center space-x-2">
                                <input type="number"
                                    id="common-modal-people"
                                    name="required_people_count"
                                    value="{{ old('_form') === 'common_task' ? old('required_people_count', '0') : '0' }}"
                                    min="0" max="999" required
                                    class="w-20 px-2.5 py-1.5 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono font-medium transition">
                                <label class="inline-flex items-center space-x-1 cursor-pointer select-none whitespace-nowrap">
                                    <input type="checkbox"
                                        id="common-modal-at-least"
                                        name="is_at_least"
                                        value="1"
                                        {{ old('_form') === 'common_task' && old('is_at_least') ? 'checked' : '' }}
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5">
                                    <span class="text-xs font-bold text-slate-700">以上</span>
                                </label>
                            </div>
                        </div>

                        <div class="pb-0.5">
                            <label class="inline-flex items-center space-x-2 cursor-pointer select-none px-3 py-1.5 bg-rose-50/60 hover:bg-rose-50 border border-rose-200/80 rounded-lg transition w-full">
                                <input type="checkbox"
                                    id="common-modal-critical"
                                    name="is_critical"
                                    value="1"
                                    {{ old('_form') === 'common_task' && old('is_critical') ? 'checked' : '' }}
                                    class="rounded border-rose-300 text-rose-600 focus:ring-rose-500 h-4 w-4">
                                <span class="text-xs font-bold text-rose-700">🚨 最優先タスク</span>
                            </label>
                        </div>
                    </div>

                    <!-- 5. 実施場所 & 進行ステータス -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="common-modal-location" class="block text-[11px] font-bold text-slate-700 mb-1">
                                📍 実施場所・集合場所
                            </label>
                            <input type="text"
                                id="common-modal-location"
                                name="location"
                                value="{{ old('_form') === 'common_task' ? old('location') : '' }}"
                                placeholder="例: 本部テント前"
                                class="w-full px-2.5 py-1.5 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>

                        <div>
                            <label for="common-modal-status" class="block text-[11px] font-bold text-slate-700 mb-1">
                                📊 進行ステータス <span class="text-rose-500">*</span>
                            </label>
                            @php
                                $selectedStatus = old('_form') === 'common_task' ? old('status', 'not_started') : 'not_started';
                            @endphp
                            <select
                                id="common-modal-status"
                                name="status"
                                required
                                class="w-full px-2.5 py-1.5 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                                @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" {{ $selectedStatus === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- 6. 班長・固定スタッフ自動アサイン設定 -->
                    <div class="p-3 bg-indigo-50/50 border border-indigo-100 rounded-xl">
                        <input type="hidden" name="auto_assign_lead" value="0">
                        <label class="inline-flex items-start space-x-2 cursor-pointer select-none">
                            <input type="checkbox"
                                id="common-modal-auto-assign-lead"
                                name="auto_assign_lead"
                                value="1"
                                {{ old('_form') === 'common_task' ? (old('auto_assign_lead') ? 'checked' : '') : 'checked' }}
                                class="mt-0.5 rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 shrink-0">
                            <span>
                                <span class="block text-xs font-bold text-indigo-900">各班の班長・固定スタッフを自動アサインする</span>
                                <span class="block text-[11px] text-indigo-700/80">チェックを外すと、スタッフ未割当のまま各班へ共通タスクのみ作成します。</span>
                            </span>
                        </label>
                    </div>

                    <!-- 7. 作業内容・手順指示 -->
                    <div>
                        <label for="common-modal-content" class="block text-[11px] font-bold text-slate-700 mb-1">
                            📝 作業内容・伝達事項
                        </label>
                        <textarea
                            id="common-modal-content"
                            name="content"
                            rows="4"
                            placeholder="共有事項、持ち物、連絡手順などを入力（改行可能）"
                            class="w-full px-3 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition leading-relaxed resize-y">{{ old('_form') === 'common_task' ? old('content') : '' }}</textarea>
                    </div>
                </div>

                <!-- モーダルフッター (固定) -->
                <div class="shrink-0 px-5 py-3 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between">
                    <button type="button" data-action="close-modal"
                        class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                        キャンセル
                    </button>

                    <button type="submit" id="btn-submit-common-task"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.99] text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1.5 cursor-pointer">
                        <span>🌐</span>
                        <span>選択した班へ一括登録する</span>
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

