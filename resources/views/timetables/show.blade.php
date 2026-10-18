<x-layouts.app :title="$timetable->name . ' - 運行タイムテーブル詳細'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ナビゲーション & 操作 -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <a href="{{ route('timetables.index') }}"
            class="inline-flex items-center space-x-1 text-xs font-medium text-slate-500 hover:text-slate-800 transition">
            <span>←</span>
            <span>運行タイムテーブル一覧に戻る</span>
        </a>

        <div class="flex items-center space-x-2">
            @can('create', App\Models\TimetableTask::class)
            <a href="{{ route('timetable-tasks.create', ['timetable_id' => $timetable->id]) }}"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-1">
                <span>➕</span>
                <span>作業タスクを追加</span>
            </a>
            @endcan

            @can('update', $timetable)
            <a href="{{ route('timetables.edit', $timetable) }}"
                class="px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1">
                <span>✏️</span>
                <span>運行枠編集</span>
            </a>
            @endcan

            @can('delete', $timetable)
            <form action="{{ route('timetables.destroy', $timetable) }}" method="POST" class="inline"
                onsubmit="return confirm('タイムテーブル「{{ $timetable->name }}」を削除しますか？\n（関連するすべての作業タスクも削除されます）');">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="px-3 py-2 bg-white hover:bg-red-50 text-red-600 border border-slate-200 hover:border-red-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 cursor-pointer">
                    <span>🗑️</span>
                    <span>削除</span>
                </button>
            </form>
            @endcan
        </div>
    </div>

    <!-- 運行枠ヘッダーカード -->
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 rounded-2xl p-6 text-white shadow-lg border border-slate-800">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="px-2.5 py-0.5 rounded text-xs font-mono font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                        📅 {{ $timetable->target_date?->format('Y年m月d日') }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded text-xs font-mono text-slate-300 bg-slate-800 border border-slate-700">
                        ⏱️ {{ substr($timetable->start_time, 0, 5) }} 〜 {{ substr($timetable->end_time, 0, 5) }}
                    </span>
                    @php
                        $statusBadge = match($timetable->status) {
                            'active' => 'bg-emerald-500/20 text-emerald-300 border-emerald-400/30 font-bold',
                            'completed' => 'bg-slate-700 text-slate-300 border-slate-600',
                            default => 'bg-blue-500/20 text-blue-300 border-blue-400/30',
                        };
                    @endphp
                    <span class="px-2.5 py-0.5 rounded text-xs font-medium border {{ $statusBadge }}">
                        {{ $timetable->status_label }}
                    </span>
                </div>

                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">
                    {{ $timetable->name }}
                </h1>
                @if($timetable->description)
                <p class="text-xs sm:text-sm text-slate-300 mt-2 max-w-3xl leading-relaxed">
                    {{ $timetable->description }}
                </p>
                @endif
            </div>

            <!-- サマリー統計 -->
            <div class="grid grid-cols-2 gap-3 shrink-0">
                <div class="bg-slate-800/80 backdrop-blur p-3.5 rounded-xl border border-slate-700/80 text-center min-w-[110px]">
                    <span class="text-[10px] text-slate-400 block font-bold">登録タスク</span>
                    <span class="text-xl font-bold font-mono text-white">{{ $timetable->tasks->count() }}</span>
                    <span class="text-[10px] text-slate-400"> 件</span>
                </div>
                <div class="bg-slate-800/80 backdrop-blur p-3.5 rounded-xl border border-slate-700/80 text-center min-w-[110px]">
                    <span class="text-[10px] text-slate-400 block font-bold">人員充足状況</span>
                    @php
                        $satisfiedCount = $timetable->tasks->filter(fn($t) => $t->is_satisfied)->count();
                        $totalTasks = $timetable->tasks->count();
                    @endphp
                    <span class="text-xl font-bold font-mono {{ $satisfiedCount === $totalTasks && $totalTasks > 0 ? 'text-emerald-400' : 'text-amber-400' }}">
                        {{ $satisfiedCount }} / {{ $totalTasks }}
                    </span>
                    <span class="text-[10px] text-slate-400 block">充足タスク</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 作業タスク一覧セクション -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-base text-slate-900 flex items-center space-x-2">
                <span>📋</span>
                <span>運行作業タスク一覧 (時系列)</span>
            </h2>

            <div class="text-xs text-slate-500">
                全 <strong class="text-slate-800 font-mono">{{ $timetable->tasks->count() }}</strong> 件
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 select-none">
                    <tr>
                        <th class="px-4 py-3 font-bold">時間帯</th>
                        <th class="px-4 py-3 font-bold">担当班</th>
                        <th class="px-4 py-3 font-bold">作業名・作業内容</th>
                        <th class="px-4 py-3 font-bold">場所</th>
                        <th class="px-4 py-3 font-bold">必要人員 & 充足状況</th>
                        <th class="px-4 py-3 font-bold">アサイン中スタッフ</th>
                        <th class="px-4 py-3 font-bold">状態</th>
                        <th class="px-4 py-3 font-bold text-right">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($timetable->tasks as $task)
                    <tr class="hover:bg-slate-50/70 transition">
                        <!-- 時間帯 -->
                        <td class="px-4 py-3 font-mono text-slate-800 whitespace-nowrap">
                            <span class="font-bold">{{ substr($task->start_time, 0, 5) }}</span>
                            <span class="text-slate-400">〜</span>
                            <span>{{ substr($task->end_time, 0, 5) }}</span>
                            @if($task->is_critical)
                            <span class="ml-1 text-[9px] px-1 py-0.5 rounded font-bold bg-red-100 text-red-700 block text-center mt-0.5">
                                最優先
                            </span>
                            @endif
                        </td>

                        <!-- 担当班 -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            @php
                                $laneColor = match($task->lane) {
                                    'カラーコーン班' => 'bg-amber-50 text-amber-800 border-amber-200',
                                    '給水設備班' => 'bg-sky-50 text-sky-800 border-sky-200',
                                    '衛生班' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    '誘導・警備班' => 'bg-purple-50 text-purple-800 border-purple-200',
                                    '救護班' => 'bg-rose-50 text-rose-800 border-rose-200',
                                    default => 'bg-slate-100 text-slate-800 border-slate-200',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $laneColor }}">
                                {{ $task->lane }}
                            </span>
                        </td>

                        <!-- 作業名・作業内容 -->
                        <td class="px-4 py-3 max-w-xs">
                            <a href="{{ route('timetable-tasks.show', $task) }}"
                                class="font-bold text-slate-900 hover:text-indigo-600 transition inline-flex items-center space-x-1">
                                <span>{{ $task->title }}</span>
                                <span class="text-[10px] text-indigo-500 font-normal">→</span>
                            </a>
                            @if($task->content)
                            <p class="text-[11px] text-slate-600 line-clamp-2 mt-0.5 leading-snug">
                                {{ $task->content }}
                            </p>
                            @endif
                        </td>

                        <!-- 場所 -->
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                            {{ $task->location ?: '-' }}
                        </td>

                        <!-- 必要人員 & 充足状況 -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($task->is_satisfied)
                            <span class="inline-flex items-center px-2 py-1 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                ✔ {{ $task->assigned_count }} / {{ $task->required_people_label }} (充足)
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-1 rounded text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-300">
                                ⚠️ {{ $task->assigned_count }} / {{ $task->required_people_label }} (不足 {{ $task->shortage_count }}名)
                            </span>
                            @endif
                        </td>

                        <!-- アサイン中スタッフ -->
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1 max-w-xs">
                                @forelse($task->assignments as $assignment)
                                <a href="{{ route('staff.show', $assignment->staff_id) }}"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[10px] bg-slate-100 hover:bg-indigo-100 text-slate-700 hover:text-indigo-700 border border-slate-200 transition"
                                    title="{{ $assignment->staff?->name }} ({{ $assignment->role_description ?: '担当' }})">
                                    {{ $assignment->staff?->name }}
                                </a>
                                @empty
                                <span class="text-slate-400 text-[10px]">未アサイン</span>
                                @endforelse
                            </div>
                        </td>

                        <!-- 状態 -->
                        <td class="px-4 py-3 whitespace-nowrap">
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
                        <td class="px-4 py-3 text-right space-x-1 whitespace-nowrap">
                            <a href="{{ route('timetable-tasks.show', $task) }}"
                                class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-bold transition">
                                👥 人員管理
                            </a>

                            @can('update', $task)
                            <a href="{{ route('timetable-tasks.edit', $task) }}"
                                class="px-2 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-[11px] font-bold transition">
                                編集
                            </a>
                            @endcan

                            @can('delete', $task)
                            <form action="{{ route('timetable-tasks.destroy', $task) }}" method="POST" class="inline"
                                onsubmit="return confirm('作業タスク「{{ $task->title }}」を削除しますか？');">
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
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-slate-400">
                            <span class="text-3xl block mb-2">📋</span>
                            この運行日にはまだ作業タスクが登録されていません。<br>
                            右上の「作業タスクを追加」ボタンから登録してください。
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
</x-layouts.app>
