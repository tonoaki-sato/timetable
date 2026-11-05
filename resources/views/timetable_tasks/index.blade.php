<x-layouts.app :title="$timetable->name . ' - 作業タスク一覧'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$timetable->name . ' 作業タスク一覧'"
        icon="📋"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）の作業タスク台帳およびスタッフ配置管理です。'">
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
            <span class="text-slate-700 font-bold truncate">📋 タスク一覧</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.print-assignments', $timetable) }}" target="_blank"
                class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 shadow-2xs whitespace-nowrap shrink-0">
                <span>🖨️</span>
                <span>人員配置表印刷</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー (日程切替・サブナビ) -->
    <x-timetable-context-bar :timetable="$timetable" active="tasks" />

    <!-- 作業タスク一覧セクションヘッダーバー -->
    <div class="flex items-center justify-between gap-2.5 px-1">
        <h2 class="font-bold text-sm sm:text-base text-slate-900 flex items-center space-x-2">
            <span>📋</span>
            <span>運行作業タスク一覧 (班別時系列)</span>
        </h2>

        @if($lanes->isNotEmpty())
        <button type="button"
            data-action="toggle-accordions"
            data-target="#lane-task-accordions"
            data-state="open"
            class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer">
            <span data-icon>🔼</span>
            <span data-label>すべて折りたたむ</span>
        </button>
        @endif
    </div>

    <!-- 班別アコーディオン一覧 -->
    <div id="lane-task-accordions" class="space-y-3">
        @forelse($lanes as $lane)
        @php
            $hasTasks = $lane->tasks->isNotEmpty();
            $badgeClass = $lane->color_classes['badge'] ?? 'bg-slate-100 text-slate-800 border-slate-200';
            $laneIcon = $lane->icon ?: '📋';
            $latestTask = $lane->tasks->sortByDesc('end_time')->first();
            $suggestedStart = $latestTask ? substr($latestTask->end_time, 0, 5) : '08:00';
        @endphp
        <details id="lane-{{ $lane->id }}" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden group" {{ $hasTasks ? 'open' : '' }}>
            <summary class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-3 cursor-pointer hover:bg-slate-100/70 transition select-none">
                <div class="flex items-center space-x-2.5 min-w-0 flex-wrap gap-y-1">
                    <span class="text-slate-400 group-open:rotate-90 transition-transform duration-150 inline-block text-xs">▶</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border {{ $badgeClass }}">
                        <span class="mr-1">{{ $laneIcon }}</span>
                        <span>{{ $lane->name }}</span>
                    </span>
                    @if($lane->leadStaff)
                    <span class="text-xs text-slate-500 font-medium hidden sm:inline">
                        👤 班長: <strong class="text-slate-700">{{ $lane->leadStaff->name }}</strong>
                    </span>
                    @endif
                    <span class="text-[11px] px-2 py-0.5 rounded-full font-mono font-bold {{ $hasTasks ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-400' }}">
                        {{ $lane->tasks->count() }} 件
                    </span>
                </div>

                <div class="flex items-center space-x-2 shrink-0">
                    @can('create', App\Models\TimetableTask::class)
                    <button type="button"
                        data-action="open-task-create"
                        data-lane-id="{{ $lane->id }}"
                        data-lane-name="{{ $lane->name }}"
                        data-lane-icon="{{ $laneIcon }}"
                        data-suggested-start="{{ $suggestedStart }}"
                        class="inline-flex items-center space-x-1 px-2.5 py-1 bg-white hover:bg-indigo-50 text-indigo-700 hover:text-indigo-800 border border-indigo-200 rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer"
                        title="{{ $lane->name }}にタスクを追加">
                        <span>➕</span>
                        <span class="hidden md:inline">タスク追加</span>
                    </button>
                    @endcan
                </div>
            </summary>

            @if($lane->tasks->isEmpty())
            <div class="px-4 py-8 text-center text-slate-400 text-xs">
                <span class="text-2xl block mb-1">📋</span>
                <p>この班に登録されている作業タスクはありません。</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold select-none text-xs">
                        <tr>
                            <th class="px-3.5 py-2.5 text-center whitespace-nowrap w-16">最優先</th>
                            <th class="px-3.5 py-2.5 whitespace-nowrap w-28">時間帯</th>
                            <th class="px-3.5 py-2.5 min-w-[160px]">作業名</th>
                            <th class="px-3.5 py-2.5 max-w-[200px]">作業内容</th>
                            <th class="px-3.5 py-2.5 whitespace-nowrap">必要人員</th>
                            <th class="px-3.5 py-2.5 font-bold text-right whitespace-nowrap">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($lane->tasks as $task)
                        @php
                            $taskData = [
                                'id' => $task->id,
                                'timetable_id' => $task->timetable_id,
                                'lane_id' => $task->lane_id,
                                'lane_name' => $lane->name,
                                'lane_icon' => $laneIcon,
                                'lead_staff_name' => $lane->leadStaff?->name,
                                'title' => $task->title,
                                'start_time' => substr($task->start_time, 0, 5),
                                'end_time' => substr($task->end_time, 0, 5),
                                'required_people_count' => $task->required_people_count,
                                'is_at_least' => (bool) $task->is_at_least,
                                'is_critical' => (bool) $task->is_critical,
                                'location' => $task->location ?? '',
                                'status' => $task->status,
                                'status_label' => $task->status_label,
                                'content' => $task->content ?? '',
                                'update_url' => route('timetable-tasks.update', $task),
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition" data-task-row data-task-id="{{ $task->id }}" data-task='@json($taskData)'>
                            <!-- 最優先 -->
                            <td class="px-3.5 py-2.5 text-center whitespace-nowrap">
                                @if($task->is_critical)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 border border-red-200">
                                    最優先
                                </span>
                                @else
                                <span class="text-slate-300 text-xs">-</span>
                                @endif
                            </td>

                            <!-- 時間帯 -->
                            <td class="px-3.5 py-2.5 font-mono text-slate-800 whitespace-nowrap text-xs">
                                <span class="font-bold">{{ substr($task->start_time, 0, 5) }}</span>
                                <span class="text-slate-400">〜</span>
                                <span>{{ substr($task->end_time, 0, 5) }}</span>
                            </td>

                            <!-- 作業名 (クリックで詳細モーダル起動) -->
                            <td class="px-3.5 py-2.5">
                                <button type="button"
                                    data-action="open-task-detail"
                                    data-task-id="{{ $task->id }}"
                                    class="font-bold text-slate-900 hover:text-indigo-600 transition text-left cursor-pointer">
                                    {{ $task->title }}
                                </button>
                            </td>

                            <!-- 作業内容 -->
                            <td class="px-3.5 py-2.5 text-slate-600 text-xs">
                                @if($task->content)
                                <div class="truncate max-w-[200px]" title="{{ $task->content }}">
                                    {{ $task->content }}
                                </div>
                                @else
                                <span class="text-slate-400 text-xs">-</span>
                                @endif
                            </td>

                            <!-- 必要人員 -->
                            <td class="px-3.5 py-2.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    👤 {{ $task->required_people_label }}
                                </span>
                            </td>

                            <!-- 操作 -->
                            <td class="px-3.5 py-2.5 text-right whitespace-nowrap space-x-1">
                                @can('update', $task)
                                <button type="button"
                                    data-action="open-task-edit"
                                    data-task-id="{{ $task->id }}"
                                    class="inline-flex items-center space-x-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                    title="編集">
                                    <span>✏️</span>
                                    <span>編集</span>
                                </button>
                                @endcan

                                @can('delete', $task)
                                <form action="{{ route('timetable-tasks.destroy', $task) }}" method="POST" class="inline"
                                    data-confirm="作業タスク「{{ $task->title }}」を削除しますか？">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center space-x-1 px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                        title="削除">
                                        <span>🗑️</span>
                                        <span>削除</span>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </details>
        @empty
        <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-400">
            <span class="text-3xl block mb-2">📋</span>
            この運行日にはまだ担当班が登録されていません。<br>
            先に「担当班」画面から班を登録してください。
        </div>
        @endforelse
    </div>

    <!-- 各種タスク操作モーダル (独立コンポーネント) -->
    <x-timetable-tasks.detail-modal />
    <x-timetable-tasks.create-modal :timetable="$timetable" :statuses="$statuses" />
    <x-timetable-tasks.edit-modal :statuses="$statuses" />

</div>

@vite(['resources/js/timetable-task-modal.js'])
</x-layouts.app>
