<x-layouts.app title="運行タイムテーブル全体マトリクス">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- 1. ヘッダー & 運行日セレクター -->
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 rounded-2xl p-6 text-white shadow-lg border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="flex items-center space-x-2 mb-2">
                <span class="px-2.5 py-0.5 rounded text-xs font-mono font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                    ⏱️ 本部運行司令マトリクス
                </span>
                <span class="text-xs text-slate-300">
                    現在時刻: <strong class="font-mono text-emerald-400">{{ $nowTime }}</strong>
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">
                運行タイムテーブル全体マトリクス
            </h1>
            <p class="text-xs text-slate-300 mt-1 max-w-2xl">
                全担当班（レーン）の時系列作業動態と現場スタッフのアテンド充足状況を一元的に俯瞰します。
            </p>
        </div>

        <!-- KPIサマリー -->
        <div class="flex flex-wrap items-center gap-3 shrink-0">
            <div class="bg-slate-800/80 backdrop-blur px-4 py-2.5 rounded-xl border border-slate-700 text-center min-w-[110px]">
                <div class="text-[10px] text-slate-400 font-bold uppercase">全タスク</div>
                <div class="text-xl font-bold font-mono text-white">{{ $tasksCount }} <span class="text-xs font-normal text-slate-400">件</span></div>
            </div>

            <div class="bg-slate-800/80 backdrop-blur px-4 py-2.5 rounded-xl border border-slate-700 text-center min-w-[130px]">
                <div class="text-[10px] text-slate-400 font-bold uppercase">人員充足タスク</div>
                <div class="text-xl font-bold font-mono {{ $satisfiedTasksCount === $tasksCount && $tasksCount > 0 ? 'text-emerald-400' : 'text-amber-400' }}">
                    {{ $satisfiedTasksCount }} / {{ $tasksCount }}
                </div>
            </div>

            <div class="bg-slate-800/80 backdrop-blur px-4 py-2.5 rounded-xl border border-slate-700 text-center min-w-[130px]">
                <div class="text-[10px] text-slate-400 font-bold uppercase">人員配置総数</div>
                <div class="text-xl font-bold font-mono {{ $totalAssigned >= $totalRequired && $totalRequired > 0 ? 'text-emerald-400' : 'text-indigo-300' }}">
                    {{ $totalAssigned }} / {{ $totalRequired }} <span class="text-xs font-normal text-slate-400">名</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. 運行日タブ選択 ＆ クイック操作 -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-3">
        <!-- 運行日タブ -->
        <div class="flex flex-wrap items-center gap-2">
            @forelse($timetables as $tt)
            <a href="{{ route('timetable.index', ['timetable_id' => $tt->id]) }}"
                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 {{ $selectedTimetable?->id === $tt->id ? 'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-600/30' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' }}">
                <span>📅 {{ $tt->target_date?->format('m/d') }}</span>
                <span>{{ $tt->name }}</span>
                @if($tt->status === 'active')
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                @endif
            </a>
            @empty
            <span class="text-xs text-slate-400">運行日がまだ登録されていません</span>
            @endforelse
        </div>

        <!-- リンク操作 -->
        @if($selectedTimetable)
        <div class="flex items-center space-x-2">
            @can('create', App\Models\TimetableTask::class)
            <a href="{{ route('timetable-tasks.create', ['timetable_id' => $selectedTimetable->id]) }}"
                class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition flex items-center space-x-1">
                <span>➕</span>
                <span>タスク追加</span>
            </a>
            @endcan

            <a href="{{ route('timetables.show', $selectedTimetable) }}"
                class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1">
                <span>📋</span>
                <span>運行日詳細一覧</span>
            </a>
        </div>
        @endif
    </div>

    @if(!$selectedTimetable)
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-500 shadow-sm">
        <span class="text-4xl block mb-3">📅</span>
        <h3 class="text-base font-bold text-slate-800">表示できるタイムテーブルがありません</h3>
        <p class="text-xs text-slate-500 mt-1">「運行タイムテーブル管理」から運行日を登録してください。</p>
        <div class="mt-4">
            <a href="{{ route('timetables.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold shadow transition">
                新規運行日を登録
            </a>
        </div>
    </div>
    @else

    <!-- 3. マトリクス表示（レーン別 時系列カード表示） -->
    <div class="space-y-6">
        @foreach($lanes as $laneKey => $laneLabel)
        @php
            $laneTasks = $tasksByLane[$laneKey] ?? collect();
            $laneColorClasses = match($laneKey) {
                'カラーコーン班' => ['border' => 'border-amber-300', 'badge' => 'bg-amber-100 text-amber-800 border-amber-300', 'icon' => '🔺'],
                '給水設備班' => ['border' => 'border-sky-300', 'badge' => 'bg-sky-100 text-sky-800 border-sky-300', 'icon' => '🚰'],
                '衛生班' => ['border' => 'border-emerald-300', 'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-300', 'icon' => '🧹'],
                '誘導・警備班' => ['border' => 'border-purple-300', 'badge' => 'bg-purple-100 text-purple-800 border-purple-300', 'icon' => '🚨'],
                '救護班' => ['border' => 'border-rose-300', 'badge' => 'bg-rose-100 text-rose-800 border-rose-300', 'icon' => '🏥'],
                default => ['border' => 'border-slate-300', 'badge' => 'bg-slate-100 text-slate-800 border-slate-300', 'icon' => '📋'],
            };
        @endphp

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- レーンヘッダー -->
            <div class="p-4 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center space-x-2.5">
                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold border shadow-2xs {{ $laneColorClasses['badge'] }}">
                        <span class="mr-1.5">{{ $laneColorClasses['icon'] }}</span>
                        <span>{{ $laneLabel }}</span>
                    </span>
                    <span class="text-xs text-slate-500 font-medium">
                        全 <strong class="text-slate-800 font-mono">{{ $laneTasks->count() }}</strong> 件のタスク
                    </span>
                </div>

                @can('create', App\Models\TimetableTask::class)
                <a href="{{ route('timetable-tasks.create', ['timetable_id' => $selectedTimetable->id, 'lane' => $laneKey]) }}"
                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition">
                    + この班にタスクを追加
                </a>
                @endcan
            </div>

            <!-- レーン内のタスクカード群（時系列順） -->
            <div class="p-4">
                @if($laneTasks->isEmpty())
                <div class="py-6 text-center text-xs text-slate-400">
                    この班の作業タスクはまだ登録されていません。
                </div>
                @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($laneTasks as $task)
                    <div class="relative bg-white p-4 rounded-xl border border-slate-200 hover:border-indigo-400 hover:shadow-md transition group flex flex-col justify-between space-y-3">
                        <div class="space-y-2">
                            <!-- 時間帯 & 最優先バッジ -->
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-xs font-bold text-slate-800 bg-slate-100 px-2.5 py-1 rounded-md">
                                    ⏱️ {{ substr($task->start_time, 0, 5) }} 〜 {{ substr($task->end_time, 0, 5) }}
                                </span>

                                <div class="flex items-center space-x-1">
                                    @if($task->is_critical)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">
                                        🚨 最優先
                                    </span>
                                    @endif
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ $task->status_label }}
                                    </span>
                                </div>
                            </div>

                            <!-- タスク名 -->
                            <h4 class="font-bold text-sm text-slate-900 group-hover:text-indigo-600 transition leading-snug">
                                <a href="{{ route('timetable-tasks.show', $task) }}" class="focus:outline-none">
                                    {{ $task->title }}
                                </a>
                            </h4>

                            <!-- 場所 -->
                            @if($task->location)
                            <div class="text-[11px] text-slate-500 flex items-center space-x-1">
                                <span>📍</span>
                                <span>{{ $task->location }}</span>
                            </div>
                            @endif

                            <!-- 作業指示概要 -->
                            @if($task->content)
                            <p class="text-[11px] text-slate-600 line-clamp-2 leading-relaxed bg-slate-50 p-2 rounded-lg border border-slate-100">
                                {{ $task->content }}
                            </p>
                            @endif
                        </div>

                        <!-- フッター: 人員充足インジケーター & アサインスタッフ -->
                        <div class="pt-3 border-t border-slate-100 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-slate-500 font-medium">人員充足状況:</span>
                                @if($task->is_satisfied)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    ✔ {{ $task->assigned_count }} / {{ $task->required_people_label }} (充足)
                                </span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-300">
                                    ⚠️ {{ $task->assigned_count }} / {{ $task->required_people_label }} (不足 {{ $task->shortage_count }})
                                </span>
                                @endif
                            </div>

                            <!-- アサインされたスタッフたち -->
                            <div class="flex flex-wrap items-center gap-1">
                                @forelse($task->assignments as $assignment)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] bg-slate-100 text-slate-700 border border-slate-200 font-medium"
                                    title="{{ $assignment->staff?->name }} ({{ $assignment->attendance_status_label }})">
                                    {{ $assignment->staff?->name }}
                                </span>
                                @empty
                                <span class="text-[10px] text-slate-400">スタッフ未配置</span>
                                @endforelse
                            </div>

                            <!-- 詳細・アサイン操作リンク -->
                            <div class="pt-1 text-right">
                                <a href="{{ route('timetable-tasks.show', $task) }}"
                                    class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition inline-flex items-center space-x-1">
                                    <span>👥 人員アサイン・出欠管理</span>
                                    <span>→</span>
                                </a>
                            </div>
                        </div>

                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    @endif

</div>
</x-layouts.app>
