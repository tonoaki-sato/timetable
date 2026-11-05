<x-layouts.app :title="'「' . $lane->name . '」作業タスク一括編集 - ' . $timetable->name">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="'「' . $lane->name . '」作業タスク一括編集'"
        icon="✏️"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y/m/d') . '）における「' . $lane->name . '」の作業タスク内容を一括で編集・更新します。'">
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
            <span class="text-slate-700 font-bold truncate">✏️ {{ $lane->name }} タスク一括編集</span>
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
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl space-y-1">
        <div class="font-bold flex items-center space-x-1">
            <span>⚠️</span>
            <span>入力内容を確認してください</span>
        </div>
        @foreach ($errors->all() as $error)
        <div>・{{ $error }}</div>
        @endforeach
    </div>
    @endif

    @php
        $tasksData = old('tasks');
        if (!is_array($tasksData) || empty($tasksData)) {
            $tasksData = $tasks->map(fn($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'location' => $t->location,
                'start_time' => substr($t->start_time, 0, 5),
                'end_time' => substr($t->end_time, 0, 5),
                'required_people_count' => $t->required_people_count,
                'is_at_least' => (bool) $t->is_at_least,
                'content' => $t->content,
                'status' => $t->status,
                'is_critical' => (bool) $t->is_critical,
            ])->values()->all();
        } else {
            $tasksData = array_values($tasksData);
        }

        $badgeClass = $lane->color_classes['badge'] ?? 'bg-slate-100 text-slate-800 border-slate-200';
        $laneIcon = $lane->icon ?: '📋';
        $leadStaff = $lane->leadStaff;
        $fixedMembers = $lane->fixedStaff;
    @endphp

    <!-- 担当班サマリー情報 & 削除に関するご案内 -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
            <div class="flex items-center space-x-2.5 flex-wrap gap-y-1">
                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold border {{ $badgeClass }}">
                    <span class="mr-1.5">{{ $laneIcon }}</span>
                    <span>{{ $lane->name }}</span>
                </span>
                @if($leadStaff)
                <span class="text-xs text-slate-600 font-medium">
                    👑 班長: <strong class="text-slate-800">{{ $leadStaff->name }}</strong>
                    @if($leadStaff->affiliation)
                        <span class="text-slate-500 text-[11px]">({{ $leadStaff->affiliation }})</span>
                    @endif
                </span>
                @endif
                <span class="text-xs text-slate-500">
                    📌 固定スタッフ: <strong class="text-slate-700">{{ $fixedMembers->count() }}名</strong>
                    @if($fixedMembers->isNotEmpty())
                        <span class="text-slate-400">（{{ $fixedMembers->pluck('name')->implode('、') }}）</span>
                    @endif
                </span>
            </div>

            <span class="text-[11px] text-slate-400 shrink-0">
                対象運行日: {{ $timetable->target_date?->format('Y/m/d') }}
            </span>
        </div>

        <div class="p-2.5 bg-amber-50/70 border border-amber-200/80 rounded-xl text-xs text-amber-900 flex items-center space-x-2">
            <span class="shrink-0 text-base">💡</span>
            <span>
                <strong>タスクの削除について:</strong> 既存タスクの削除は誤操作防止のため、タスク一覧画面の各行「🗑️ 削除」ボタンから実行してください。本画面では各タスクの内容一括更新および新規タスク枠の追加が行えます。
            </span>
        </div>
    </div>

    <!-- 入力フォーム -->
    <form id="bulk-task-edit-form" action="{{ route('timetables.lanes.tasks.update', ['timetable' => $timetable, 'lane' => $lane]) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- 作業タスクカードリストヘッダー -->
        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 px-1">
                <div class="flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">📋</span>
                    <h2 class="text-sm font-bold text-slate-900">作業タスク一覧を一括編集</h2>
                    <span id="task-count-badge" class="px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-800 text-xs font-bold font-mono">
                        {{ count($tasksData) }} 件
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <button type="button" data-action="add-task-row"
                        class="px-3.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 cursor-pointer shadow-2xs">
                        <span>➕</span>
                        <span>タスク枠を追加</span>
                    </button>
                </div>
            </div>

            <!-- タスクカードコンテナ -->
            <div id="tasks-container" class="space-y-4">
                @foreach($tasksData as $idx => $taskItem)
                @php
                    $hasId = !empty($taskItem['id']);
                @endphp
                <div class="task-item-card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4 transition hover:border-indigo-200"
                    data-task-index="{{ $idx }}"
                    data-is-existing="{{ $hasId ? '1' : '0' }}">

                    <!-- カードヘッダー: タスク番号・状態バッジ & 複製/取り消しボタン -->
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center space-x-2">
                            <span class="task-index-badge px-2.5 py-1 rounded-lg bg-slate-800 text-white text-xs font-bold font-mono">
                                タスク #{{ $idx + 1 }}
                            </span>
                            @if($hasId)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                登録済みタスク
                            </span>
                            @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                新規追加枠
                            </span>
                            @endif
                        </div>

                        <div class="flex items-center space-x-2">
                            <button type="button" data-action="duplicate-task-row"
                                title="このタスクの内容をコピーして次の時間帯に枠を追加します"
                                class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 border border-slate-200 hover:border-indigo-200 rounded-lg text-[11px] font-bold transition flex items-center space-x-1 cursor-pointer">
                                <span>📋</span>
                                <span>複製して次へ追加</span>
                            </button>

                            @if(!$hasId)
                            <button type="button" data-action="remove-new-task-row"
                                title="この追加枠を取り消す"
                                class="btn-remove-task px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 rounded-lg text-[11px] font-bold transition flex items-center space-x-1 cursor-pointer">
                                <span>✕</span>
                                <span>取り消し</span>
                            </button>
                            @endif
                        </div>
                    </div>

                    <!-- 既存タスクのID保持 hidden フィールド -->
                    @if($hasId)
                    <input type="hidden" data-field="id" name="tasks[{{ $idx }}][id]" value="{{ $taskItem['id'] }}">
                    @endif

                    <!-- 1行目: 作業タスク名 & 実施場所 -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                        <div class="sm:col-span-7">
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                作業タスク名 <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                data-field="title"
                                name="tasks[{{ $idx }}][title]"
                                value="{{ $taskItem['title'] ?? '' }}"
                                required
                                placeholder="例: 中央通り カラーコーン・規制バー設置"
                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div class="sm:col-span-5">
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                実施場所・持ち場
                            </label>
                            <input type="text"
                                data-field="location"
                                name="tasks[{{ $idx }}][location]"
                                value="{{ $taskItem['location'] ?? '' }}"
                                placeholder="例: 第1ゲート前交差点、本陣"
                                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- 2行目: 開始時刻・終了時刻 ＆ 必要人員設定 -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
                        <!-- 開始時刻 -->
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                開始時刻 <span class="text-red-500">*</span>
                            </label>
                            <input type="time"
                                data-field="start_time"
                                name="tasks[{{ $idx }}][start_time]"
                                value="{{ $taskItem['start_time'] ?? '08:00' }}"
                                required
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                        </div>

                        <!-- 終了時刻 -->
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                終了時刻 <span class="text-red-500">*</span>
                            </label>
                            <input type="time"
                                data-field="end_time"
                                name="tasks[{{ $idx }}][end_time]"
                                value="{{ $taskItem['end_time'] ?? '09:30' }}"
                                required
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                        </div>

                        <!-- 必要人数 -->
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                必要人員（名） <span class="text-red-500">*</span>
                            </label>
                            <input type="number"
                                data-field="required_people_count"
                                name="tasks[{{ $idx }}][required_people_count]"
                                value="{{ $taskItem['required_people_count'] ?? 2 }}"
                                min="0" max="999" required
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                        </div>

                        <!-- 〇〇名以上フラグ -->
                        <div class="sm:col-span-3 pt-4 sm:pt-5">
                            <label class="flex items-center space-x-2 cursor-pointer select-none">
                                <input type="hidden" data-field="is_at_least" name="tasks[{{ $idx }}][is_at_least]" value="0">
                                <input type="checkbox"
                                    data-field="is_at_least"
                                    name="tasks[{{ $idx }}][is_at_least]"
                                    value="1"
                                    {{ !empty($taskItem['is_at_least']) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-xs font-bold text-slate-700">「〇〇名以上」可</span>
                            </label>
                        </div>
                    </div>

                    <!-- 3行目: 作業内容・手順指示 (content) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            作業内容・手順指示
                        </label>
                        <textarea
                            data-field="content"
                            name="tasks[{{ $idx }}][content]"
                            rows="2"
                            placeholder="作業手順、持ち物、注意事項などを入力"
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs sm:text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ $taskItem['content'] ?? '' }}</textarea>
                    </div>

                    <!-- 4行目: ステータス ＆ 最優先フラグ -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-slate-100">
                        <div class="flex items-center space-x-3">
                            <label class="text-xs font-bold text-slate-600 shrink-0">進行ステータス:</label>
                            <select
                                data-field="status"
                                name="tasks[{{ $idx }}][status]"
                                class="px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @foreach($statuses as $stKey => $stLabel)
                                <option value="{{ $stKey }}" {{ ($taskItem['status'] ?? 'not_started') === $stKey ? 'selected' : '' }}>
                                    {{ $stLabel }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center">
                            <label class="flex items-center space-x-2 cursor-pointer select-none">
                                <input type="hidden" data-field="is_critical" name="tasks[{{ $idx }}][is_critical]" value="0">
                                <input type="checkbox"
                                    data-field="is_critical"
                                    name="tasks[{{ $idx }}][is_critical]"
                                    value="1"
                                    {{ !empty($taskItem['is_critical']) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                <span class="text-xs font-bold text-rose-700">🚨 最優先タスク（重要）</span>
                            </label>
                        </div>
                    </div>

                </div>
                @endforeach
            </div>
        </div>

        <!-- 送信ボタンエリア -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
            <a href="{{ route('timetables.tasks.index', $timetable) }}"
                class="w-full sm:w-auto px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold text-center transition">
                キャンセルしてタスク一覧に戻る
            </a>

            <button type="submit" id="submit-tasks-btn"
                class="w-full sm:w-auto px-7 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm hover:shadow transition flex items-center justify-center space-x-1.5 cursor-pointer">
                <span>💾</span>
                <span>「{{ $lane->name }}」の作業タスクを一括保存する</span>
            </button>
        </div>
    </form>

</div>

@vite(['resources/js/timetable-task-bulk-edit.js'])
</x-layouts.app>

