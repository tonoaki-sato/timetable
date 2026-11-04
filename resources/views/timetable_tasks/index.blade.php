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
            <a href="{{ route('timetables.staff-timeline', $timetable) }}"
                class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 shadow-2xs whitespace-nowrap shrink-0">
                <span>👥</span>
                <span>スタッフ稼働タイムライン</span>
            </a>

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

    <!-- 作業タスク一覧セクション -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="p-3 sm:p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <h2 class="font-bold text-sm sm:text-base text-slate-900 flex items-center space-x-2">
                <span>📋</span>
                <span>運行作業タスク一覧 (時系列)</span>
            </h2>

            <div class="flex items-center space-x-4 text-xs">
                @php
                    $satisfiedCount = $tasks->filter(fn($t) => $t->is_satisfied)->count();
                    $totalTasks = $tasks->count();
                @endphp
                <div class="flex items-center space-x-1 text-slate-600">
                    <span>登録タスク:</span>
                    <strong class="text-slate-900 font-mono text-sm">{{ $totalTasks }}</strong>
                    <span>件</span>
                </div>
                <span class="text-slate-300">|</span>
                <div class="flex items-center space-x-1 text-slate-600">
                    <span>人員充足:</span>
                    <strong class="font-mono text-sm {{ $satisfiedCount === $totalTasks && $totalTasks > 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                        {{ $satisfiedCount }} / {{ $totalTasks }}
                    </strong>
                    <span>タスク</span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 font-bold text-[11px] select-none">
                    <tr>
                        <th class="px-3.5 py-2 whitespace-nowrap">時間帯</th>
                        <th class="px-3.5 py-2 whitespace-nowrap">担当班</th>
                        <th class="px-3.5 py-2 min-w-[180px]">作業名・作業内容</th>
                        <th class="px-3.5 py-2 min-w-[120px]">場所</th>
                        <th class="px-3.5 py-2 whitespace-nowrap">必要人員 & 充足状況</th>
                        <th class="px-3.5 py-2 min-w-[140px]">アサイン中スタッフ</th>
                        <th class="px-3.5 py-2 whitespace-nowrap">状態</th>
                        <th class="px-3.5 py-2 font-bold text-right whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tasks as $task)
                    <tr class="hover:bg-slate-50/70 transition">
                        <!-- 時間帯 -->
                        <td class="px-3.5 py-2 font-mono text-slate-800 whitespace-nowrap">
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
                                $laneRel = $task->laneRelation;
                                $badgeClass = $laneRel ? $laneRel->color_classes['badge'] : match($task->lane) {
                                    'カラーコーン', 'カラーコーン班' => 'bg-amber-100 text-amber-800 border-amber-300',
                                    '給水設備', '給水設備班' => 'bg-sky-100 text-sky-800 border-sky-300',
                                    '衛生', '衛生班' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                    '巡回', '誘導・警備班' => 'bg-purple-100 text-purple-800 border-purple-300',
                                    'キッズ村' => 'bg-pink-100 text-pink-800 border-pink-300',
                                    'ちょうちん' => 'bg-rose-100 text-rose-800 border-rose-300',
                                    'ござ市' => 'bg-orange-100 text-orange-800 border-orange-300',
                                    '本陣', '本部連絡班' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                                    '道路清掃' => 'bg-teal-100 text-teal-800 border-teal-300',
                                    'ステカン' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                                    default => 'bg-slate-100 text-slate-800 border-slate-200',
                                };
                                $laneIcon = $laneRel ? ($laneRel->icon ?: '📋') : '📋';
                            @endphp
                            <div class="space-y-0.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $badgeClass }}">
                                    <span class="mr-1">{{ $laneIcon }}</span>
                                    <span>{{ $task->lane }}</span>
                                </span>
                                @if($laneRel && $laneRel->leadStaff)
                                <div class="text-[9px] text-slate-500 font-medium">
                                    👤 {{ $laneRel->leadStaff->name }}
                                </div>
                                @endif
                            </div>
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

