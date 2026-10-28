<x-layouts.app :title="'新規作業タスク登録 - ' . $timetable->name">
<div class="max-w-4xl mx-auto px-4 py-6 space-y-6">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        title="新規作業タスク登録（一括登録対応）"
        icon="➕"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y/m/d') . '）の担当班（レーン）を選択し、1件または複数の作業タスクをまとめて登録できます。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1">
                <span>⏱️</span>
                <span>タイムテーブル一覧</span>
            </a>
            <span>/</span>
            <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition">
                <span>📅 {{ $timetable->name }}</span>
            </a>
            <span>/</span>
            <span class="text-slate-700 font-bold">タスク追加</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.show', $timetable) }}" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                <span>←</span>
                <span>「{{ $timetable->name }}」に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

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
                    'requires_review' => (bool) old('requires_review', false),
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
    @endphp

    <!-- 入力フォーム -->
    <form id="bulk-task-create-form" action="{{ route('timetable-tasks.store') }}" method="POST" class="space-y-6">
        @csrf
        <input type="hidden" name="timetable_id" value="{{ $timetable->id }}">

        <!-- ステップ1: 担当班（レーン）＆ 主担当設定 -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">1</span>
                    <h2 class="text-sm font-bold text-slate-900">登録先の担当班（レーン）を選択</h2>
                </div>
                <span class="text-[11px] text-slate-500">選択した班に対して下のタスクリストが一括登録されます</span>
            </div>

            <div class="space-y-3">
                <div>
                    <label for="lane_id" class="block text-xs font-bold text-slate-700 mb-1">
                        担当班（レーン） <span class="text-red-500">*</span>
                    </label>
                    <select id="lane_id" name="lane_id" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium">
                        @if(isset($timetableLanes) && $timetableLanes->isNotEmpty())
                            @foreach($timetableLanes as $l)
                            @php
                                $laneStart = $laneSuggestedStartTimes[$l->id] ?? '08:00';
                            @endphp
                            <option value="{{ $l->id }}"
                                data-lead-name="{{ $l->leadStaff?->name ?? '' }}"
                                data-lead-aff="{{ $l->leadStaff?->affiliation ?? '' }}"
                                data-suggested-start="{{ $laneStart }}"
                                {{ ($currentLaneObj?->id == $l->id || old('lane') == $l->name) ? 'selected' : '' }}>
                                {{ $l->icon ?: '📋' }} {{ $l->name }}
                                @if($l->leadStaff)
                                    (主担当: {{ $l->leadStaff->name }})
                                @endif
                            </option>
                            @endforeach
                        @else
                            @foreach($lanes as $key => $label)
                            <option value="" {{ old('lane') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- 主担当スタッフの自動アサイン案内（主担当あり時） -->
                <div id="lead-staff-banner"
                    class="{{ $initialLeadStaff ? '' : 'hidden' }} p-3 bg-indigo-50/70 border border-indigo-200/80 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-indigo-900">
                    <div class="flex items-center space-x-2">
                        <span>👤</span>
                        <span>
                            レーン主担当: <strong id="lead-staff-name">{{ $initialLeadStaff?->name }}</strong>
                            <span id="lead-staff-affiliation" class="text-indigo-600 text-[11px] {{ $initialLeadStaff?->affiliation ? '' : 'hidden' }}">
                                ({{ $initialLeadStaff?->affiliation }})
                            </span>
                        </span>
                    </div>
                    <label class="inline-flex items-center space-x-2 cursor-pointer font-bold text-indigo-700 hover:text-indigo-900">
                        <input type="checkbox" name="auto_assign_lead" value="1"
                            {{ old('auto_assign_lead', '1') ? 'checked' : '' }}
                            class="w-4 h-4 text-indigo-600 rounded border-indigo-300 focus:ring-indigo-500">
                        <span>登録する全タスクに主担当を班リーダーとして自動アサインする</span>
                    </label>
                </div>

                <!-- 主担当未設定時の案内 -->
                <div id="no-lead-staff-banner"
                    class="{{ !$initialLeadStaff && $currentLaneObj ? '' : 'hidden' }} p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-500 flex items-center justify-between">
                    <span>👤 このレーンには現在主担当が設定されていません。</span>
                    <a href="{{ route('timetables.lanes.index', $timetable) }}" class="text-indigo-600 hover:underline font-bold text-[11px]">
                        主担当を設定する →
                    </a>
                </div>
            </div>
        </div>

        <!-- ステップ2: 作業タスク入力カードリスト -->
        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 px-1">
                <div class="flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">2</span>
                    <h2 class="text-sm font-bold text-slate-900">登録する作業タスクを入力</h2>
                    <span id="task-count-badge" class="px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-800 text-xs font-bold font-mono">
                        {{ count($oldTasks) }} 件
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
                @foreach($oldTasks as $idx => $taskItem)
                <div class="task-item-card bg-white p-5 sm:p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4 transition hover:border-indigo-200"
                    data-task-index="{{ $idx }}">

                    <!-- カードヘッダー: タスク番号 & 複製/削除ボタン -->
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center space-x-2">
                            <span class="task-index-badge px-2.5 py-1 rounded-lg bg-slate-800 text-white text-xs font-bold font-mono">
                                タスク #{{ $idx + 1 }}
                            </span>
                            <span class="text-[11px] text-slate-400">時系列に沿って作業内容と必要人数を設定してください</span>
                        </div>

                        <div class="flex items-center space-x-2">
                            <button type="button" data-action="duplicate-task-row"
                                title="このタスクの内容をコピーして次の時間帯に枠を追加します"
                                class="px-2.5 py-1 bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 border border-slate-200 hover:border-indigo-200 rounded-lg text-[11px] font-bold transition flex items-center space-x-1 cursor-pointer">
                                <span>📋</span>
                                <span>複製して次へ追加</span>
                            </button>

                            <button type="button" data-action="remove-task-row"
                                title="このタスク枠を削除"
                                class="{{ count($oldTasks) <= 1 ? 'hidden' : '' }} btn-remove-task px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 rounded-lg text-[11px] font-bold transition flex items-center space-x-1 cursor-pointer">
                                <span>✕</span>
                                <span>枠を削除</span>
                            </button>
                        </div>
                    </div>

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
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-start bg-slate-50/80 p-3.5 rounded-xl border border-slate-200/80">
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                開始時刻 <span class="text-red-500">*</span>
                            </label>
                            <input type="time"
                                data-field="start_time"
                                name="tasks[{{ $idx }}][start_time]"
                                value="{{ substr($taskItem['start_time'] ?? '08:00', 0, 5) }}"
                                required
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                終了時刻 <span class="text-red-500">*</span>
                            </label>
                            <input type="time"
                                data-field="end_time"
                                name="tasks[{{ $idx }}][end_time]"
                                value="{{ substr($taskItem['end_time'] ?? '09:30', 0, 5) }}"
                                required
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                必要人数（名） <span class="text-red-500">*</span>
                            </label>
                            <input type="number"
                                data-field="required_people_count"
                                name="tasks[{{ $idx }}][required_people_count]"
                                value="{{ $taskItem['required_people_count'] ?? 2 }}"
                                min="0" max="999" required
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                        </div>

                        <div class="sm:col-span-3 pt-1 sm:pt-6">
                            <label class="flex items-center space-x-2 cursor-pointer select-none">
                                <input type="checkbox"
                                    data-field="is_at_least"
                                    name="tasks[{{ $idx }}][is_at_least]"
                                    value="1"
                                    {{ !empty($taskItem['is_at_least']) ? 'checked' : '' }}
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                <div>
                                    <span class="text-xs font-bold text-slate-800 block">「〇〇名以上」扱い</span>
                                    <span class="text-[10px] text-slate-500 block">基準人数以上で充足</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- 3行目: 作業内容・手順指示 -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            作業内容・手順指示
                        </label>
                        <textarea
                            data-field="content"
                            name="tasks[{{ $idx }}][content]"
                            rows="2"
                            placeholder="現場スタッフへの具体的な手順指示や注意事項（例: コーンバーは3m間隔で設置、一般車両の誤進入を防ぐため声掛けを徹底）"
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ $taskItem['content'] ?? '' }}</textarea>
                    </div>

                    <!-- 4行目: ステータス ＆ 重要フラグ設定 -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                初期進行ステータス <span class="text-red-500">*</span>
                            </label>
                            <select
                                data-field="status"
                                name="tasks[{{ $idx }}][status]"
                                required
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" {{ ($taskItem['status'] ?? 'not_started') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:pt-6 flex items-center">
                            <label class="flex items-center space-x-2 cursor-pointer select-none">
                                <input type="checkbox"
                                    data-field="is_critical"
                                    name="tasks[{{ $idx }}][is_critical]"
                                    value="1"
                                    {{ !empty($taskItem['is_critical']) ? 'checked' : '' }}
                                    class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 h-4 w-4">
                                <span class="text-xs font-bold text-rose-700">🚨 最優先タスク（重要）</span>
                            </label>
                        </div>

                        <div class="sm:pt-6 flex items-center">
                            <label class="flex items-center space-x-2 cursor-pointer select-none">
                                <input type="checkbox"
                                    data-field="requires_review"
                                    name="tasks[{{ $idx }}][requires_review]"
                                    value="1"
                                    {{ !empty($taskItem['requires_review']) ? 'checked' : '' }}
                                    class="rounded border-slate-300 text-amber-600 focus:ring-amber-500 h-4 w-4">
                                <span class="text-xs font-bold text-amber-800">📋 本部完了確認を要する</span>
                            </label>
                        </div>
                    </div>

                </div>
                @endforeach
            </div>

            <!-- 下部: タスク枠追加ボタン -->
            <button type="button" data-action="add-task-row"
                class="w-full py-3 bg-white hover:bg-indigo-50/60 text-indigo-700 border-2 border-dashed border-indigo-300 hover:border-indigo-400 rounded-2xl text-xs font-bold transition flex items-center justify-center space-x-2 cursor-pointer shadow-2xs">
                <span class="text-base">➕</span>
                <span>次の作業タスク枠を追加する（前の終了時刻から連続追加）</span>
            </button>
        </div>

        <!-- 送信ボタンエリア -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs text-slate-600 flex items-center space-x-1.5">
                <span>💡</span>
                <span>複数のタスク枠を追加して、1回の操作でまとめてタイムテーブルへ反映できます。</span>
            </div>
            <div class="flex items-center space-x-3 w-full sm:w-auto justify-end">
                <a href="{{ route('timetables.show', $timetable) }}"
                    class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-medium transition">
                    キャンセル
                </a>
                <button type="submit" id="submit-tasks-btn"
                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition cursor-pointer">
                    {{ count($oldTasks) > 1 ? count($oldTasks) . '件の作業タスクを一括登録する' : '作業タスクを登録する' }}
                </button>
            </div>
        </div>

    </form>

</div>

@vite(['resources/js/timetable-task-create.js'])
</x-layouts.app>
