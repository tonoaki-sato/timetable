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

            @can('create', App\Models\TimetableTask::class)
            <a href="{{ route('timetable-tasks.create', ['timetable_id' => $timetable->id]) }}"
                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>➕</span>
                <span>作業タスクを追加</span>
            </a>
            @endcan
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
            class="px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 shadow-2xs cursor-pointer">
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
        @endphp
        <details id="lane-{{ $lane->id }}" class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden group" {{ $hasTasks ? 'open' : '' }}>
            <summary class="px-3 sm:px-4 py-2.5 bg-slate-50/90 border-b border-slate-200 flex items-center justify-between gap-3 cursor-pointer hover:bg-slate-100/80 transition select-none">
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
                    <a href="{{ route('timetable-tasks.create', ['timetable_id' => $timetable->id, 'lane_id' => $lane->id]) }}"
                        class="px-2 py-1 bg-white hover:bg-indigo-50 text-indigo-700 hover:text-indigo-800 border border-indigo-200 rounded text-[11px] font-bold transition flex items-center space-x-1"
                        title="{{ $lane->name }}にタスクを追加">
                        <span>➕</span>
                        <span class="hidden md:inline">タスク追加</span>
                    </a>
                    @endcan
                </div>
            </summary>

            @if($lane->tasks->isEmpty())
            <div class="px-4 py-8 text-center text-slate-400 text-xs">
                <span>📋</span>
                <p class="mt-1">この班に登録されている作業タスクはありません。</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs">
                    <thead class="bg-slate-50/70 border-b border-slate-200 text-slate-500 font-bold text-[11px] select-none">
                        <tr>
                            <th class="px-3 py-2 text-center whitespace-nowrap w-16">最優先</th>
                            <th class="px-3.5 py-2 whitespace-nowrap w-28">時間帯</th>
                            <th class="px-3.5 py-2 min-w-[160px]">作業名</th>
                            <th class="px-3.5 py-2 min-w-[200px]">作業内容</th>
                            <th class="px-3.5 py-2 whitespace-nowrap">場所</th>
                            <th class="px-3.5 py-2 whitespace-nowrap">必要人員 & 充足状況</th>
                            <th class="px-3.5 py-2 whitespace-nowrap">状態</th>
                            <th class="px-3.5 py-2 font-bold text-right whitespace-nowrap">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($lane->tasks as $task)
                        <tr class="hover:bg-slate-50/70 transition">
                            <!-- 最優先 -->
                            <td class="px-3 py-2 text-center whitespace-nowrap">
                                @if($task->is_critical)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 border border-red-200">
                                    最優先
                                </span>
                                @else
                                <span class="text-slate-300 text-xs">-</span>
                                @endif
                            </td>

                            <!-- 時間帯 -->
                            <td class="px-3.5 py-2 font-mono text-slate-800 whitespace-nowrap">
                                <span class="font-bold">{{ substr($task->start_time, 0, 5) }}</span>
                                <span class="text-slate-400">〜</span>
                                <span>{{ substr($task->end_time, 0, 5) }}</span>
                            </td>

                            <!-- 作業名 -->
                            <td class="px-3.5 py-2">
                                <a href="{{ route('timetable-tasks.show', $task) }}"
                                    class="font-bold text-slate-900 hover:text-indigo-600 transition inline-flex items-center space-x-1">
                                    <span>{{ $task->title }}</span>
                                    <span class="text-[10px] text-indigo-500 font-normal">→</span>
                                </a>
                            </td>

                            <!-- 作業内容 -->
                            <td class="px-3.5 py-2 text-slate-600 text-xs">
                                @if($task->content)
                                <div class="line-clamp-2 max-w-md" title="{{ $task->content }}">
                                    {{ $task->content }}
                                </div>
                                @else
                                <span class="text-slate-400 text-xs">-</span>
                                @endif
                            </td>

                            <!-- 場所 -->
                            <td class="px-3.5 py-2 text-slate-600 whitespace-nowrap">
                                {{ $task->location ?: '-' }}
                            </td>

                            <!-- 必要人員 & 充足状況 -->
                            <td class="px-3.5 py-2 whitespace-nowrap">
                                @if($task->is_satisfied)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    ✔ {{ $task->assigned_count }} / {{ $task->required_people_label }} (充足)
                                </span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-300">
                                    ⚠️ {{ $task->assigned_count }} / {{ $task->required_people_label }} (不足 {{ $task->shortage_count }}名)
                                </span>
                                @endif
                            </td>

                            <!-- 状態 -->
                            <td class="px-3.5 py-2 whitespace-nowrap">
                                @php
                                    $taskStatusClass = match($task->status) {
                                        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'in_progress' => 'bg-amber-50 text-amber-800 border-amber-300 font-bold',
                                        'cancelled' => 'bg-slate-100 text-slate-400 border-slate-200 line-through',
                                        default => 'bg-slate-50 text-slate-600 border-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] border {{ $taskStatusClass }}">
                                    {{ $task->status_label }}
                                </span>
                            </td>

                            <!-- 操作 -->
                            <td class="px-3.5 py-2 text-right space-x-1 whitespace-nowrap">
                                @can('update', $task)
                                <a href="{{ route('timetable-tasks.edit', $task) }}"
                                    class="px-2 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-[11px] font-bold transition">
                                    編集
                                </a>
                                @endcan

                                @can('delete', $task)
                                <form action="{{ route('timetable-tasks.destroy', $task) }}" method="POST" class="inline"
                                    data-confirm="作業タスク「{{ $task->title }}」を削除しますか？">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-600 rounded text-[11px] font-medium transition cursor-pointer">
                                        削除
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

</div>
</x-layouts.app>
