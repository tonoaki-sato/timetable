<x-layouts.app :title="'新規作業タスク登録 - ' . $timetable->name">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    @php
        $oldTasks = old('tasks');
        if (!is_array($oldTasks) || empty($oldTasks)) {
            $oldTasks = [
                [
                    'title' => old('title', ''),
                    'location' => old('location', ''),
                    'start_time' => old('start_time', $suggestedStartTime ?? '08:00'),
                    'end_time' => old('end_time', $suggestedEndTime ?? '09:30'),
                    'required_people_count' => old('required_people_count', 2),
                    'is_at_least' => (bool) old('is_at_least', false),
                    'content' => old('content', ''),
                    'status' => old('status', 'not_started'),
                    'is_critical' => (bool) old('is_critical', false),
                ],
            ];
        } else {
            $oldTasks = array_values($oldTasks);
        }

        $currentSelectedLaneId = old('lane_id', $selectedLane?->id);
        $currentLaneObj = isset($timetableLanes)
            ? ($timetableLanes->firstWhere('id', (int) $currentSelectedLaneId) ?? $selectedLane ?? $timetableLanes->first())
            : $selectedLane;
        $initialLeadStaff = $currentLaneObj?->leadStaff;
        $badgeClass = $currentLaneObj?->color_classes['badge'] ?? 'bg-indigo-50 text-indigo-700 border-indigo-200';
    @endphp

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="'新規作業タスク登録' . ($currentLaneObj ? '（' . $currentLaneObj->name . '）' : '')"
        icon="➕"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y/m/d') . '）' . ($currentLaneObj ? '「' . $currentLaneObj->name . '」' : '') . 'の作業タスクを登録します。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>⏱️</span>
                <span>タイムテーブル一覧</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>📅</span>
                <span>{{ $timetable->name }}</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.tasks.index', $timetable) }}" class="hover:text-indigo-600 transition truncate">
                📋 タスク一覧
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">➕ 新規登録</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.tasks.index', $timetable) }}"
                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>←</span>
                <span>タスク一覧に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー (日程切替・サブナビ) -->
    <x-timetable-context-bar :timetable="$timetable" active="tasks" />

    <!-- エラーサマリー -->
    @if ($errors->any())
    <div class="p-3.5 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl space-y-1">
        <div class="font-bold flex items-center space-x-1">
            <span>⚠️</span>
            <span>入力内容を確認してください</span>
        </div>
        @foreach ($errors->all() as $error)
        <div>・{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <!-- 入力フォーム -->
    <form id="bulk-task-create-form" action="{{ route('timetable-tasks.store') }}" method="POST" class="space-y-4">
        @csrf
        <input type="hidden" name="timetable_id" value="{{ $timetable->id }}">
        <input type="hidden" id="lane_id" name="lane_id" value="{{ $currentSelectedLaneId }}">
        <input type="hidden" name="auto_assign_lead" value="1">

        <!-- 作業タスク入力カードリストヘッダー -->
        <div class="space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 px-1">
                <div class="flex items-center space-x-2.5 flex-wrap gap-y-1">
                    @if($currentLaneObj)
                    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold border {{ $badgeClass }}">
                        <span class="mr-1">{{ $currentLaneObj->icon ?: '📋' }}</span>
                        <span>{{ $currentLaneObj->name }}</span>
                    </span>
                    @if($initialLeadStaff)
                    <span class="text-xs text-slate-600 font-medium">
                        👑 班長: <strong class="text-slate-800">{{ $initialLeadStaff->name }}</strong>
                    </span>
                    @endif
                    @endif
                    <h2 class="text-sm font-bold text-slate-900">作業タスクを入力</h2>
                    <span id="task-count-badge" class="px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 text-xs font-bold font-mono">
                        {{ count($oldTasks) }} 件
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <button type="button" data-action="add-task-row"
                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 cursor-pointer shadow-2xs">
                        <span>➕</span>
                        <span>タスク枠を追加</span>
                    </button>
                </div>
            </div>

            <!-- タスクカードコンテナ -->
            <div id="tasks-container" class="space-y-3">
                @foreach($oldTasks as $idx => $taskItem)
                <div class="task-item-card bg-white p-3.5 sm:p-4 rounded-xl border border-slate-200 shadow-2xs space-y-2.5 transition hover:border-indigo-200"
                    data-task-index="{{ $idx }}">

                    <!-- カードヘッダー: タスク番号 ＆ 警告 ＆ 複製/削除ボタン -->
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <div class="flex items-center space-x-2">
                            <span class="task-index-badge px-2 py-0.5 rounded bg-slate-800 text-white text-[11px] font-bold font-mono">
                                タスク #{{ $idx + 1 }}
                            </span>
                            <span class="task-overlap-warning text-xs font-bold text-rose-600 hidden">
                                ⚠️ 他のタスクと時間帯が重複しています
                            </span>
                        </div>

                        <div class="flex items-center space-x-2">
                            <button type="button" data-action="duplicate-task-row"
                                title="このタスクの内容をコピーして次の時間帯に枠を追加します"
                                class="px-2 py-0.5 bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 border border-slate-200 hover:border-indigo-200 rounded text-[11px] font-bold transition flex items-center space-x-1 cursor-pointer">
                                <span>📋</span>
                                <span>複製</span>
                            </button>

                            <button type="button" data-action="remove-task-row"
                                title="このタスク枠を削除"
                                class="{{ count($oldTasks) <= 1 ? 'hidden' : '' }} btn-remove-task px-2 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 rounded text-[11px] font-bold transition flex items-center space-x-1 cursor-pointer">
                                <span>✕</span>
                                <span>削除</span>
                            </button>
                        </div>
                    </div>

                    <!-- 1段目: 作業タスク名 / 開始時刻 / 終了時刻 / 必要人員 / 最優先フラグ -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-end">
                        <div class="sm:col-span-4">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                作業タスク名 <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                data-field="title"
                                name="tasks[{{ $idx }}][title]"
                                value="{{ $taskItem['title'] ?? '' }}"
                                required
                                placeholder="例: 中央通り カラーコーン設置"
                                class="w-full px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                開始時刻 <span class="text-red-500">*</span>
                            </label>
                            <input type="time"
                                data-field="start_time"
                                name="tasks[{{ $idx }}][start_time]"
                                value="{{ substr($taskItem['start_time'] ?? '08:00', 0, 5) }}"
                                required
                                class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono font-medium">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                終了時刻 <span class="text-red-500">*</span>
                            </label>
                            <input type="time"
                                data-field="end_time"
                                name="tasks[{{ $idx }}][end_time]"
                                value="{{ substr($taskItem['end_time'] ?? '09:30', 0, 5) }}"
                                required
                                class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono font-medium">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                必要人員 <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center space-x-1.5">
                                <input type="number"
                                    data-field="required_people_count"
                                    name="tasks[{{ $idx }}][required_people_count]"
                                    value="{{ $taskItem['required_people_count'] ?? 2 }}"
                                    min="0" max="999" required
                                    class="w-16 px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                                <label class="inline-flex items-center space-x-1 cursor-pointer select-none whitespace-nowrap">
                                    <input type="checkbox"
                                        data-field="is_at_least"
                                        name="tasks[{{ $idx }}][is_at_least]"
                                        value="1"
                                        {{ !empty($taskItem['is_at_least']) ? 'checked' : '' }}
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5">
                                    <span class="text-[11px] font-bold text-slate-700">以上</span>
                                </label>
                            </div>
                        </div>

                        <div class="sm:col-span-2 pb-2">
                            <label class="inline-flex items-center space-x-1.5 cursor-pointer select-none">
                                <input type="checkbox"
                                    data-field="is_critical"
                                    name="tasks[{{ $idx }}][is_critical]"
                                    value="1"
                                    {{ !empty($taskItem['is_critical']) ? 'checked' : '' }}
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
                                data-field="location"
                                name="tasks[{{ $idx }}][location]"
                                value="{{ $taskItem['location'] ?? '' }}"
                                placeholder="例: 本陣、第1ゲート"
                                class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                進行ステータス <span class="text-red-500">*</span>
                            </label>
                            <select
                                data-field="status"
                                name="tasks[{{ $idx }}][status]"
                                required
                                class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" {{ ($taskItem['status'] ?? 'not_started') === $key ? 'selected' : '' }}>
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
                                data-field="content"
                                name="tasks[{{ $idx }}][content]"
                                value="{{ $taskItem['content'] ?? '' }}"
                                placeholder="作業手順や注意事項を入力"
                                class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                </div>
                @endforeach
            </div>

            <!-- 下部: タスク枠追加ボタン -->
            <button type="button" data-action="add-task-row"
                class="w-full py-2.5 bg-white hover:bg-indigo-50/60 text-indigo-700 border border-dashed border-indigo-300 hover:border-indigo-400 rounded-xl text-xs font-bold transition flex items-center justify-center space-x-1.5 cursor-pointer shadow-2xs">
                <span>➕</span>
                <span>次の作業タスク枠を追加する（前の終了時刻から連続追加）</span>
            </button>
        </div>

        <!-- 送信ボタンエリア -->
        <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs text-slate-600 flex items-center space-x-1.5">
                <span>💡</span>
                <span>複数枠を追加して、1回の操作でまとめてタイムテーブルへ登録できます。</span>
            </div>
            <div class="flex items-center space-x-3 w-full sm:w-auto justify-end">
                <a href="{{ route('timetables.tasks.index', $timetable) }}"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium transition">
                    キャンセル
                </a>
                <button type="submit" id="submit-tasks-btn"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs transition cursor-pointer">
                    {{ count($oldTasks) > 1 ? count($oldTasks) . '件の作業タスクを一括登録する' : '作業タスクを登録する' }}
                </button>
            </div>
        </div>

    </form>

</div>

@vite(['resources/js/timetable-task-create.js'])
</x-layouts.app>
