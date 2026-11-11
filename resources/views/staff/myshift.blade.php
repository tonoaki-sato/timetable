<!DOCTYPE html>
<html lang="ja" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $staff->name }} さんのタイムテーブル - まつり運行システム</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/staff-myshift.js'])
</head>
<body class="min-h-full pb-16 font-sans antialiased bg-slate-950 text-slate-100 selection:bg-indigo-500 selection:text-white">

    <!-- モバイルヘッダー -->
    <header class="sticky top-0 z-30 bg-slate-900/95 backdrop-blur-md border-b border-slate-800 shadow-lg">
        <div class="max-w-xl mx-auto px-4 py-3 flex items-center justify-between gap-2">
            <div class="flex items-center space-x-2.5 min-w-0">
                <span class="text-2xl shrink-0">🏮</span>
                <div class="min-w-0">
                    <h1 class="text-sm font-bold text-white tracking-tight leading-tight">マイタイムテーブル</h1>
                    <div class="flex items-center flex-wrap gap-1.5 mt-0.5" id="headerProfileArea">
                        <span class="text-xs font-bold text-indigo-300 truncate">{{ $staff->name }} さん</span>
                        @if($ledLanes->isNotEmpty())
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 shrink-0">
                                👑 班長
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 到着チェックイン／閲覧専用ステータスエリア -->
            <div class="shrink-0">
                @if($canCheckInActiveTimetable && $activeTimetable)
                    @if($activeTimetableCheckIn)
                        <div class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                            <span>✅</span>
                            <span>受付済 ({{ $activeTimetableCheckIn->checked_in_at->format('H:i') }})</span>
                        </div>
                    @else
                        <button type="button" id="checkInBtn"
                                data-endpoint="{{ route('staff.myshift.checkin', ['token' => $staff->access_token], false) }}"
                                data-timetable-id="{{ $activeTimetable->id }}"
                                class="px-3.5 py-1.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-950/60 transition transform active:scale-95 flex items-center space-x-1 cursor-pointer">
                            <span>📍</span>
                            <span>現場到着（受付）</span>
                        </button>
                    @endif
                @elseif($isBeforeOperationDayActiveTimetable || ($showAllTimetables && $allTimetablesReadOnly))
                    <div id="readOnlyHeaderBadge"
                         class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800/90 text-slate-300 border border-slate-700">
                        <span>👀</span>
                        <span>運行前・閲覧専用</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- 運行日スイッチャーバー -->
        @if($timetableOptions->isNotEmpty())
            <div class="border-t border-slate-800/80 bg-slate-950/70">
                <nav aria-label="運行日切り替え" class="max-w-xl mx-auto px-4 py-2 flex items-center gap-1.5 overflow-x-auto">
                    @foreach($timetableOptions as $option)
                        @php
                            $temporalClass = match ($option->temporalStatus) {
                                'today' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
                                'future' => 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40',
                                default => 'bg-slate-800 text-slate-400 border-slate-700',
                            };
                        @endphp
                        <a href="{{ route('staff.myshift', ['token' => $staff->access_token, 'timetable_id' => $option->id], false) }}"
                           class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-bold border transition {{ $option->isSelected ? 'bg-indigo-600 text-white border-indigo-400 shadow-sm shadow-indigo-950/70' : 'bg-slate-900/90 text-slate-300 border-slate-800 hover:border-slate-700 hover:bg-slate-800' }}">
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-black border {{ $option->isSelected ? 'bg-white/15 text-white border-white/25' : $temporalClass }}">
                                {{ $option->temporalLabel }}
                            </span>
                            <span class="font-mono">{{ $option->shortDateLabel }}</span>
                            <span class="max-w-28 truncate {{ $option->isSelected ? 'text-indigo-100' : 'text-slate-400' }}">{{ $option->name }}</span>
                            @if($option->assignmentCount > 0 || $option->ledLaneCount > 0)
                                <span class="text-[10px] font-mono {{ $option->isSelected ? 'text-indigo-200' : 'text-slate-500' }}">
                                    ({{ $option->assignmentCount }})
                                </span>
                            @endif
                        </a>
                    @endforeach

                    @if($timetableOptions->count() > 1)
                        <a href="{{ route('staff.myshift', ['token' => $staff->access_token, 'timetable_id' => 'all'], false) }}"
                           class="shrink-0 inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-bold border transition {{ $showAllTimetables ? 'bg-indigo-600 text-white border-indigo-400 shadow-sm' : 'bg-slate-900/90 text-slate-400 border-slate-800 hover:border-slate-700 hover:text-slate-200' }}">
                            <span>📅</span>
                            <span>すべて</span>
                        </a>
                    @endif
                </nav>
            </div>
        @endif
    </header>

    <main class="max-w-xl mx-auto px-4 py-4 space-y-4">

        <!-- フラッシュメッセージ & 非同期通知トースト -->
        @if(session('success'))
            <div class="p-3.5 bg-emerald-950/80 border border-emerald-500/50 rounded-2xl text-emerald-300 text-xs flex items-center space-x-2">
                <span>✅</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-3.5 bg-rose-950/80 border border-rose-500/50 rounded-2xl text-rose-300 text-xs flex items-center space-x-2">
                <span>⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div id="myshiftToast" class="hidden p-3 bg-emerald-950/90 border border-emerald-500/50 rounded-2xl text-emerald-200 text-xs font-bold flex items-center justify-between gap-2 shadow-lg">
            <span id="myshiftToastText"></span>
        </div>

        <div id="myshiftTimelineRoot">
            <div id="myshiftSyncRoot" class="space-y-4">

                <!-- メインモード切り替えタブ（班長権限あり時のみ表示） -->
                @if($ledLanes->isNotEmpty())
                    <div class="grid grid-cols-2 gap-1 p-1 rounded-2xl bg-slate-900 border border-slate-800 shadow-inner" role="tablist" aria-label="表示モード切り替え">
                        <button type="button"
                                role="tab"
                                aria-selected="true"
                                data-main-tab="shift"
                                class="myshiftMainTabBtn py-2 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer bg-indigo-600 text-white shadow-sm">
                            <span>📋</span>
                            <span>マイシフト</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono bg-white/20 text-white">
                                {{ $totalAssignmentCount }}
                            </span>
                        </button>
                        <button type="button"
                                role="tab"
                                aria-selected="false"
                                data-main-tab="leader"
                                class="myshiftMainTabBtn py-2 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer text-slate-300 hover:text-white hover:bg-slate-800/80">
                            <span>👑</span>
                            <span>班長コンソール</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                {{ $ledLanes->count() }}班
                            </span>
                            @if($pendingLeaderRequestCount > 0)
                                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-rose-500 text-white">
                                    {{ $pendingLeaderRequestCount }}
                                </span>
                            @endif
                        </button>
                    </div>
                @endif

                <!-- ========================================== -->
                <!-- パネル1: マイシフト（個人タイムライン）       -->
                <!-- ========================================== -->
                <section id="myshiftModePanel" data-main-panel="shift" class="space-y-3.5">

                    @if($isBeforeOperationDayActiveTimetable || ($showAllTimetables && $allTimetablesReadOnly))
                        <div id="myshiftReadOnlyBanner"
                             class="p-3.5 rounded-2xl bg-slate-900/95 border border-indigo-500/40 text-xs text-slate-200 flex items-start gap-2.5 shadow-sm">
                            <span class="text-base shrink-0 leading-none mt-0.5">👀</span>
                            <div class="space-y-0.5 leading-relaxed">
                                <div class="font-bold text-indigo-300">運行日当日までは閲覧専用です</div>
                                <p class="text-slate-400">
                                    @if($activeTimetable?->target_date)
                                        運行予定・担当内容・配置メンバーを事前に確認できます。{{ $activeTimetable->target_date->format('n月j日') }}の運行日当日から、現場到着の受付・作業進捗の報告・班長要請が可能になります。
                                    @else
                                        運行予定・担当内容を事前に確認できます。運行日当日から現場到着の受付・作業進捗の報告・班長要請が可能になります。
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif

                    <!-- 運行日サマリー & 進捗バー -->
                    <div class="p-3.5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-2.5 shadow-sm">
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-[11px] font-bold text-indigo-300 flex items-center gap-1.5">
                                    <span>📅</span>
                                    @if($showAllTimetables)
                                        <span>全運行日表示</span>
                                    @elseif($activeTimetable)
                                        <span>
                                            {{ $activeTimetable->target_date?->format('Y年n月j日') ?? '日付未設定' }}{{ $activeTimetable->weekday_label !== '' ? '（' . $activeTimetable->weekday_label . '）' : '' }}
                                        </span>
                                    @else
                                        <span>担当スケジュール</span>
                                    @endif
                                </div>
                                <h2 class="text-sm font-bold text-white truncate mt-0.5">
                                    @if($showAllTimetables)
                                        すべての運行日（{{ $timetableOptions->count() }}日程）
                                    @elseif($activeTimetable)
                                        {{ $activeTimetable->name }}
                                    @else
                                        割り当て済みスケジュール
                                    @endif
                                </h2>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-xs font-mono font-bold text-slate-200">
                                    完了 <span class="text-emerald-400">{{ $completedAssignmentCount }}</span> / {{ $totalAssignmentCount }}
                                </div>
                                <div class="text-[10px] font-mono text-slate-400">
                                    進捗 {{ $progressPercent }}%
                                </div>
                            </div>
                        </div>

                        <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                            <div class="myshift-progress-bar h-full bg-gradient-to-r from-indigo-500 to-emerald-400 rounded-full transition-all duration-500"
                                 data-progress-percent="{{ $progressPercent }}"></div>
                        </div>

                        <div class="flex items-center justify-between pt-0.5 text-[11px] text-slate-400">
                            <span class="font-bold text-slate-300">📋 担当タスク・運行スケジュール</span>
                            <div class="flex items-center space-x-1.5" id="myshiftSyncStatus">
                                <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span id="myshiftSyncText">リアルタイム同期中</span>
                            </div>
                        </div>
                    </div>

                    <!-- 担当班フィルター & 未完了のみトグル -->
                    @if($assignments->isNotEmpty() && ($laneFilters->count() > 1 || $completedAssignmentCount > 0))
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 overflow-x-auto py-0.5 flex-1">
                                <button type="button"
                                        data-lane-filter="all"
                                        class="myshiftLaneFilterBtn shrink-0 px-2.5 py-1 rounded-full text-[11px] font-bold border transition cursor-pointer bg-indigo-600 text-white border-indigo-400">
                                    すべて ({{ $totalAssignmentCount }})
                                </button>
                                @foreach($laneFilters as $laneFilter)
                                    <button type="button"
                                            data-lane-filter="{{ $laneFilter->id }}"
                                            class="myshiftLaneFilterBtn shrink-0 px-2.5 py-1 rounded-full text-[11px] font-bold border transition cursor-pointer bg-slate-900 text-slate-300 border-slate-700 hover:border-slate-600">
                                        {{ $laneFilter->icon }} {{ $laneFilter->name }} ({{ $laneFilter->assignmentCount }})
                                    </button>
                                @endforeach
                            </div>
                            <button type="button"
                                    id="myshiftIncompleteToggle"
                                    aria-pressed="false"
                                    class="shrink-0 px-2.5 py-1 rounded-full text-[11px] font-bold border transition cursor-pointer bg-slate-900 text-slate-300 border-slate-700 hover:border-slate-600">
                                未完了のみ
                            </button>
                        </div>
                    @endif

                    @if($assignments->isEmpty())
                        <div class="p-8 text-center bg-slate-900/60 rounded-2xl border border-slate-800/80 space-y-2">
                            <span class="text-3xl block mb-1">⏳</span>
                            <p class="text-sm font-bold text-slate-300">現在、割り当てられているタスクはありません</p>
                            <p class="text-xs text-slate-500">
                                本部で人員配置を行っております。<br>自動で反映されますので、しばらく経ってから確認するか本部の指示をお待ちください。
                            </p>
                            @if($ledLanes->isNotEmpty())
                                <p class="text-xs text-amber-300 pt-1">
                                    ※ 上部の「👑 班長コンソール」タブから担当班全体の状況を確認できます。
                                </p>
                            @endif
                        </div>
                    @else
                        <div id="myshiftFilteredEmpty" class="hidden p-6 text-center bg-slate-900/60 rounded-2xl border border-slate-800 space-y-2">
                            <p class="text-xs font-bold text-slate-300">選択中の絞り込み条件に該当するタスクはありません</p>
                            <button type="button" id="myshiftResetFiltersBtn"
                                    class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-indigo-300 text-xs font-bold border border-slate-700 cursor-pointer transition">
                                絞り込みを解除
                            </button>
                        </div>

                        <div id="myshiftTimelineContainer" class="space-y-2.5">
                            @foreach($groupedAssignments as $dateLabel => $dateAssignments)
                                <div class="myshift-date-group space-y-2" data-date-group="{{ $loop->index }}">
                                    @if($showAllTimetables || $groupedAssignments->count() > 1)
                                        <div class="pt-1">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-800/90 text-indigo-300 border border-slate-700">
                                                📅 {{ $dateLabel }} {{ $dateAssignments->first()?->task?->timetable?->name ? ' - ' . $dateAssignments->first()->task->timetable->name : '' }}
                                            </span>
                                        </div>
                                    @endif

                                    @foreach($dateAssignments as $assignment)
                                        @php
                                            $task = $assignment->task;
                                            $laneRelation = $task?->laneRelation;
                                            $laneLead = $laneRelation?->leadStaff;
                                            $isCurrent = $assignment->isCurrent($currentTask);
                                            $isNext = $assignment->isNext($nextTask);
                                            $isCompleted = $assignment->is_completed;
                                            $canOperateTask = (bool) $task?->timetable?->canOperateOnMyShift();
                                            $coWorkers = $task
                                                ? $task->assignments->filter(fn ($a) => $a->attendance_status !== 'absent')
                                                : collect();
                                        @endphp
                                        <div data-task-card
                                             data-assignment-id="{{ $assignment->id }}"
                                             data-lane-id="{{ $task?->lane_id ?? 'none' }}"
                                             data-completed="{{ $isCompleted ? '1' : '0' }}"
                                             class="myshift-task-card rounded-xl border transition overflow-hidden {{ $isCurrent ? 'bg-gradient-to-r from-indigo-950/95 via-slate-900 to-indigo-950/85 border-2 border-indigo-400 shadow-lg shadow-indigo-950/60 ring-1 ring-indigo-400/40' : ($isNext ? 'bg-slate-900/95 border-sky-500/60 shadow-sm' : ($isCompleted ? 'bg-slate-900/50 border-slate-800/70 opacity-85' : 'bg-slate-900 border-slate-800/90 hover:border-slate-700')) }}">
                                            <div class="p-3.5 space-y-2">
                                                <!-- 上段: 時間帯・状態バッジ・詳細ボタン -->
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="flex items-center gap-2 min-w-0 flex-wrap">
                                                        <span class="font-mono text-xs font-bold shrink-0 {{ $isCurrent ? 'text-indigo-200' : ($isNext ? 'text-sky-300' : 'text-slate-300') }}">
                                                            {{ substr($task?->start_time ?? '', 0, 5) }}〜{{ $task?->end_time ? substr($task->end_time, 0, 5) : '' }}
                                                        </span>
                                                        @if($task?->is_critical)
                                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40">
                                                                重要
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <div class="flex items-center gap-1.5 shrink-0">
                                                        @if($isCurrent)
                                                            <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-indigo-500 text-white shadow-sm animate-pulse">
                                                                <span>●</span><span>NOW 進行中</span>
                                                            </span>
                                                        @elseif($isNext)
                                                            <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-500/20 text-sky-300 border border-sky-400/50">
                                                                <span>NEXT 次の予定</span>
                                                            </span>
                                                        @elseif($isCompleted)
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-950 text-emerald-400 border border-emerald-800">
                                                                済
                                                            </span>
                                                        @elseif($assignment->attendance_status !== 'assigned')
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                                                {{ $assignment->attendance_status_label }}
                                                            </span>
                                                        @endif

                                                        @if($assignment->has_details)
                                                            <button type="button"
                                                                    class="taskDetailToggleBtn p-1 text-slate-400 hover:text-white hover:bg-slate-800 transition rounded-lg cursor-pointer"
                                                                    aria-expanded="false"
                                                                    aria-label="詳細を開く">
                                                                <svg class="w-4 h-4 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                                </svg>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- 中段: タスク名（折り返しで全文表示） -->
                                                <div class="text-sm font-bold leading-snug break-words {{ $isCurrent ? 'text-white' : ($isNext ? 'text-slate-100' : 'text-slate-200') }}">
                                                    {{ $task?->title }}
                                                </div>

                                                <!-- 下段: 担当班・班長・場所・役割チップ -->
                                                <div class="flex items-center flex-wrap gap-1.5 text-[11px]">
                                                    @if($laneRelation || $task?->lane)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-bold bg-slate-800 text-indigo-300 border border-slate-700">
                                                            @if($laneRelation?->icon)
                                                                <span>{{ $laneRelation->icon }}</span>
                                                            @endif
                                                            <span>{{ $laneRelation?->name ?: $task->lane }}</span>
                                                        </span>
                                                    @endif
                                                    @if($laneLead)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-bold bg-amber-950/70 text-amber-300 border border-amber-800/80">
                                                            <span>👑</span><span>{{ $laneLead->name }}</span>
                                                        </span>
                                                    @endif
                                                    @if($task?->location)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-800/60 text-slate-300 border border-slate-700/70">
                                                            <span>📍</span><span>{{ $task->location }}</span>
                                                        </span>
                                                    @endif
                                                    @if($assignment->role_description)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-950/60 text-indigo-200 border border-indigo-800/70">
                                                            担当: {{ $assignment->role_description }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <!-- 現在進行中タスクのクイック操作ボタン (着手・完了) -->
                                                @if($canOperateTask && $isCurrent && $assignment->attendance_status !== 'completed')
                                                    <div class="pt-2.5 border-t border-indigo-500/30 flex items-center justify-between text-xs">
                                                        <div class="flex items-center space-x-1.5">
                                                            <span class="text-indigo-300 text-[11px]">出欠・進捗:</span>
                                                            <span class="font-bold px-2 py-0.5 rounded text-[10px] {{ $assignment->attendance_status === 'in_progress' ? 'bg-amber-400 text-slate-900' : 'bg-indigo-700 text-indigo-100' }}">
                                                                {{ $assignment->attendance_status_label }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center space-x-1.5">
                                                            @if($assignment->attendance_status !== 'in_progress')
                                                                <button type="button"
                                                                        class="assignmentStatusBtn px-2.5 py-1 bg-amber-500 hover:bg-amber-400 text-slate-900 rounded-lg text-xs font-bold transition active:scale-95 cursor-pointer flex items-center space-x-1"
                                                                        data-endpoint="{{ route('staff.myshift.assignment-status', ['token' => $staff->access_token, 'assignment' => $assignment->id], false) }}"
                                                                        data-status="in_progress">
                                                                    <span>▶</span><span>着手</span>
                                                                </button>
                                                            @endif
                                                            <button type="button"
                                                                    class="assignmentStatusBtn px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition active:scale-95 cursor-pointer flex items-center space-x-1"
                                                                    data-endpoint="{{ route('staff.myshift.assignment-status', ['token' => $staff->access_token, 'assignment' => $assignment->id], false) }}"
                                                                    data-status="completed">
                                                                <span>✅</span><span>完了</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif

                                                <!-- 詳細情報アコーディオン (タップ展開: タスク詳細・備考・連携メンバー) -->
                                                @if($assignment->has_details)
                                                    <div class="taskDetailPanel hidden mt-2 pt-2.5 border-t border-slate-800 text-xs text-slate-300 space-y-2.5">
                                                        @if($task?->content)
                                                            <div class="space-y-1">
                                                                <div class="text-[11px] font-bold text-indigo-300">📝 作業内容</div>
                                                                <p class="text-slate-200 whitespace-pre-line leading-relaxed bg-slate-950/60 p-2.5 rounded-lg border border-slate-800/80">{{ $task->content }}</p>
                                                            </div>
                                                        @endif

                                                        @if($assignment->notes)
                                                            <div class="space-y-1">
                                                                <div class="text-[11px] font-bold text-amber-300">📌 申し送り・備考</div>
                                                                <p class="text-amber-100 whitespace-pre-line leading-relaxed bg-amber-950/30 p-2.5 rounded-lg border border-amber-800/40">{{ $assignment->notes }}</p>
                                                            </div>
                                                        @endif

                                                        @if($coWorkers->isNotEmpty())
                                                            <div class="space-y-1">
                                                                <div class="text-[11px] font-bold text-slate-400">👥 同じタスクの配置メンバー（{{ $coWorkers->count() }}名）</div>
                                                                <div class="flex flex-wrap gap-1.5">
                                                                    @foreach($coWorkers as $coWorker)
                                                                        @php
                                                                            $isSelf = (int) $coWorker->staff_id === (int) $staff->id;
                                                                            $isCoLead = $laneLead && (int) $laneLead->id === (int) $coWorker->staff_id;
                                                                        @endphp
                                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] border {{ $isSelf ? 'bg-indigo-950/80 text-indigo-200 border-indigo-600/60 font-bold' : 'bg-slate-950/80 text-slate-300 border-slate-800' }}">
                                                                            @if($isCoLead)
                                                                                <span>👑</span>
                                                                            @endif
                                                                            <span>{{ $coWorker->staff?->name }}</span>
                                                                            @if($isSelf)
                                                                                <span class="text-[10px] text-indigo-300">(あなた)</span>
                                                                            @endif
                                                                            @if($coWorker->role_description)
                                                                                <span class="text-[10px] text-slate-400">・{{ $coWorker->role_description }}</span>
                                                                            @endif
                                                                        </span>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                <!-- ========================================== -->
                <!-- パネル2: 班長コンソール（班切替タブ対応）      -->
                <!-- ========================================== -->
                @if($ledLanes->isNotEmpty())
                    <section id="leaderModePanel" data-main-panel="leader" class="hidden space-y-3.5">

                        <!-- 同一範囲に複数班長担当がある場合の班切り替えサブタブ -->
                        @if($ledLanes->count() > 1)
                            <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5" role="tablist" aria-label="担当班切り替え">
                                @foreach($ledLanes as $ledLane)
                                    @php
                                        $lanePendingCount = $ledLane->laneRequests->where('status', 'pending')->count();
                                    @endphp
                                    <button type="button"
                                            role="tab"
                                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                            data-leader-lane-tab="{{ $ledLane->id }}"
                                            class="leaderLaneTabBtn shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition cursor-pointer {{ $loop->first ? 'bg-amber-500 text-slate-950 border-amber-400 shadow-sm' : 'bg-slate-900 text-slate-300 border-slate-800 hover:border-amber-500/40' }}">
                                        <span>{{ $ledLane->icon ?: '📋' }}</span>
                                        <span>{{ $ledLane->name }}</span>
                                        @if($showAllTimetables && $ledLane->timetable)
                                            <span class="text-[10px] opacity-80">({{ $ledLane->timetable->target_date?->format('n/j') }})</span>
                                        @endif
                                        @if($lanePendingCount > 0)
                                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black {{ $loop->first ? 'bg-slate-950 text-amber-300' : 'bg-rose-500 text-white' }}">
                                                {{ $lanePendingCount }}
                                            </span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @foreach($ledLanes as $ledLane)
                            @php
                                $canOperateLedLane = (bool) $ledLane->timetable?->canOperateOnMyShift();
                            @endphp
                            <div data-leader-lane-panel="{{ $ledLane->id }}"
                                 class="{{ $loop->first ? '' : 'hidden ' }}p-4 rounded-2xl bg-gradient-to-br from-amber-950/80 via-slate-900 to-slate-900 border border-amber-500/50 shadow-xl space-y-4">
                                <div class="flex items-start justify-between gap-2 border-b border-amber-500/30 pb-3">
                                    <div class="space-y-1 min-w-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-black bg-amber-500 text-slate-950">
                                            👑 班長コンソール
                                        </span>
                                        <h2 class="text-sm font-bold text-amber-100 break-words">
                                            {{ $ledLane->icon ?: '📋' }} {{ $ledLane->name }}
                                            @if($ledLane->timetable)
                                                <span class="text-xs font-normal text-amber-200/80">（{{ $ledLane->timetable->name }}）</span>
                                            @endif
                                        </h2>
                                    </div>
                                    <div class="text-right shrink-0 text-[11px] text-amber-300/90 font-mono space-y-0.5">
                                        <div>固定: {{ $ledLane->fixedStaff->count() }}名</div>
                                        <div>タスク: {{ $ledLane->tasks->count() }}件</div>
                                    </div>
                                </div>

                                @if(! $canOperateLedLane)
                                    <div class="p-3 rounded-xl bg-slate-950/70 border border-amber-500/30 text-[11px] text-amber-200/90 flex items-start gap-2 leading-relaxed">
                                        <span class="shrink-0">👀</span>
                                        <span>
                                            @if($ledLane->timetable?->target_date)
                                                {{ $ledLane->timetable->target_date->format('n月j日') }}の運行日当日までは閲覧専用です。班タスクのステータス変更および人員応援・機材申請は当日から行えます。
                                            @else
                                                運行日当日までは閲覧専用です。班タスクのステータス変更および人員応援・機材申請は当日から行えます。
                                            @endif
                                        </span>
                                    </div>
                                @endif

                                <!-- 1. 班のタスク管理（進捗・人員充足確認） -->
                                <div class="space-y-2">
                                    <h3 class="text-xs font-bold text-amber-200 flex items-center space-x-1">
                                        <span>📋</span>
                                        <span>班のタスク管理（全 {{ $ledLane->tasks->count() }} 件）</span>
                                    </h3>
                                    @if($ledLane->tasks->isEmpty())
                                        <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 text-center text-xs text-slate-400">
                                            この担当班に登録されたタスクはありません。
                                        </div>
                                    @else
                                        <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                                            @foreach($ledLane->tasks as $laneTask)
                                                <div class="p-3 rounded-xl bg-slate-900/95 border border-slate-800 space-y-2 text-xs">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div class="min-w-0">
                                                            <div class="font-mono text-amber-300 font-bold text-[11px]">
                                                                {{ substr($laneTask->start_time, 0, 5) }}〜{{ substr($laneTask->end_time, 0, 5) }}
                                                            </div>
                                                            <div class="font-bold text-white break-words mt-0.5">{{ $laneTask->title }}</div>
                                                        </div>
                                                        <span class="shrink-0 text-[10px] px-2 py-0.5 rounded font-bold {{ $laneTask->is_satisfied ? 'bg-emerald-900/70 text-emerald-300 border border-emerald-700/50' : 'bg-rose-900/80 text-rose-200 border border-rose-700/50' }}">
                                                            配置 {{ $laneTask->assigned_count }} / 必要 {{ $laneTask->required_people_label }}
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-800/80">
                                                        <span class="text-[11px] text-slate-400 leading-relaxed">
                                                            班員: {{ $laneTask->assignments->map(fn($a) => $a->staff?->name . '(' . ($a->role_description ?: '担当') . ')')->implode('、') ?: '未配置' }}
                                                        </span>
                                                        @if($canOperateLedLane)
                                                            <form action="{{ route('staff.myshift.lane-task-status', ['token' => $staff->access_token, 'task' => $laneTask->id], false) }}" method="POST" class="shrink-0">
                                                                @csrf
                                                                <select name="status"
                                                                        data-leader-task-select
                                                                        data-endpoint="{{ route('staff.myshift.lane-task-status', ['token' => $staff->access_token, 'task' => $laneTask->id], false) }}"
                                                                        class="px-2 py-1 rounded-lg bg-slate-800 border border-slate-700 text-[11px] font-bold text-slate-100 cursor-pointer">
                                                                    @foreach(\App\Models\TimetableTask::STATUSES as $stKey => $stLabel)
                                                                        <option value="{{ $stKey }}" {{ $laneTask->status === $stKey ? 'selected' : '' }}>{{ $stLabel }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </form>
                                                        @else
                                                            <span class="shrink-0 px-2 py-1 rounded-lg bg-slate-800/80 border border-slate-700 text-[11px] font-bold text-slate-300">
                                                                {{ $laneTask->status_label }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                @if($canOperateLedLane)
                                    <!-- 2. 管理者への要請・申請（サブタブ切替） -->
                                    <div class="pt-2 border-t border-amber-500/30 space-y-2.5" data-leader-req-group="{{ $ledLane->id }}">
                                        <div class="grid grid-cols-2 gap-1.5">
                                            <button type="button"
                                                    data-leader-req-tab="staffing"
                                                    data-lane-id="{{ $ledLane->id }}"
                                                    class="leaderReqTabBtn py-1.5 px-2.5 rounded-xl text-xs font-bold border transition cursor-pointer bg-amber-500/20 text-amber-200 border-amber-500/50">
                                                🙋 人員応援要請
                                            </button>
                                            <button type="button"
                                                    data-leader-req-tab="equipment"
                                                    data-lane-id="{{ $ledLane->id }}"
                                                    class="leaderReqTabBtn py-1.5 px-2.5 rounded-xl text-xs font-bold border transition cursor-pointer bg-slate-900/90 text-slate-400 border-slate-800 hover:text-sky-200">
                                                🧰 必要機材申請
                                            </button>
                                        </div>

                                        <!-- 人員応援要請フォーム -->
                                        <form action="{{ route('staff.myshift.lane-requests.store', ['token' => $staff->access_token, 'lane' => $ledLane->id], false) }}"
                                              method="POST"
                                              data-async-lane-request
                                              data-leader-req-panel="staffing"
                                              data-lane-id="{{ $ledLane->id }}"
                                              class="p-3.5 rounded-xl bg-slate-900/95 border border-slate-800 space-y-2.5">
                                            @csrf
                                            <input type="hidden" name="request_type" value="staffing">
                                            <div class="text-xs font-bold text-amber-300">🙋 人員不足時の管理者への要請</div>
                                            <div>
                                                <select name="timetable_task_id" class="w-full px-2.5 py-2 rounded-lg bg-slate-950 border border-slate-700 text-xs text-slate-200">
                                                    <option value="">-- 対象タスク（班全体の場合は未選択） --</option>
                                                    @foreach($ledLane->tasks as $laneTask)
                                                        <option value="{{ $laneTask->id }}">{{ substr($laneTask->start_time, 0, 5) }} {{ $laneTask->title }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <input type="number" name="requested_count" value="1" min="1" max="50" required
                                                       class="w-20 px-2.5 py-1.5 rounded-lg bg-slate-950 border border-slate-700 text-xs text-white" placeholder="人数">
                                                <span class="text-xs text-slate-400">名応援要請</span>
                                            </div>
                                            <input type="text" name="reason" placeholder="理由（例: 欠員発生・設営遅れのため）"
                                                   class="w-full px-2.5 py-2 rounded-lg bg-slate-950 border border-slate-700 text-xs text-white">
                                            <button type="submit" class="w-full py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 rounded-xl text-xs font-bold transition cursor-pointer">
                                                管理者へ人員を要請する
                                            </button>
                                        </form>

                                        <!-- 必要機材の申請フォーム -->
                                        <form action="{{ route('staff.myshift.lane-requests.store', ['token' => $staff->access_token, 'lane' => $ledLane->id], false) }}"
                                              method="POST"
                                              data-async-lane-request
                                              data-leader-req-panel="equipment"
                                              data-lane-id="{{ $ledLane->id }}"
                                              class="hidden p-3.5 rounded-xl bg-slate-900/95 border border-slate-800 space-y-2.5">
                                            @csrf
                                            <input type="hidden" name="request_type" value="equipment">
                                            <div class="text-xs font-bold text-sky-300">🧰 必要機材の申請</div>
                                            <input type="text" name="equipment_name" required placeholder="機材名（例: カラーコーン、台車、給水タンク）"
                                                   class="w-full px-2.5 py-2 rounded-lg bg-slate-950 border border-slate-700 text-xs text-white">
                                            <div class="flex items-center gap-2">
                                                <input type="number" name="requested_count" value="1" min="1" max="100" required
                                                       class="w-20 px-2.5 py-1.5 rounded-lg bg-slate-950 border border-slate-700 text-xs text-white" placeholder="数量">
                                                <span class="text-xs text-slate-400">個 / 台</span>
                                            </div>
                                            <input type="text" name="reason" placeholder="受け渡し場所・用途メモ"
                                                   class="w-full px-2.5 py-2 rounded-lg bg-slate-950 border border-slate-700 text-xs text-white">
                                            <button type="submit" class="w-full py-2 bg-sky-500 hover:bg-sky-400 text-slate-950 rounded-xl text-xs font-bold transition cursor-pointer">
                                                管理者へ機材を申請する
                                            </button>
                                        </form>
                                    </div>
                                @endif

                                <!-- 送信済み要請・申請ステータス -->
                                @if($ledLane->laneRequests->isNotEmpty())
                                    <div class="pt-2 border-t border-amber-500/30 space-y-1.5">
                                        <div class="text-[11px] font-bold text-slate-300">📨 管理者への要請・申請状況</div>
                                        @foreach($ledLane->laneRequests->take(8) as $req)
                                            <div class="flex items-center justify-between gap-2 text-[11px] bg-slate-900/80 px-2.5 py-1.5 rounded-lg border border-slate-800">
                                                <div class="min-w-0">
                                                    <span class="font-bold text-amber-300">[{{ $req->type_label }}]</span>
                                                    <span class="text-slate-200">
                                                        {{ $req->request_type === 'equipment' ? $req->equipment_name . ' × ' . $req->requested_count : '応援 +' . $req->requested_count . '名' }}
                                                    </span>
                                                    @if($req->reason)
                                                        <span class="text-slate-400">({{ $req->reason }})</span>
                                                    @endif
                                                </div>
                                                <span class="shrink-0 px-2 py-0.5 rounded font-bold text-[10px] {{ $req->status === 'resolved' ? 'bg-emerald-900 text-emerald-300' : ($req->status === 'approved' ? 'bg-sky-900 text-sky-300' : 'bg-amber-900 text-amber-200') }}">
                                                    {{ $req->status_label }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </section>
                @endif
            </div>
        </div>

        <!-- フッター情報 & URL保存 -->
        <div class="pt-4 border-t border-slate-800/80 text-center space-y-3">
            <button type="button" id="copyUrlBtn"
                    class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700 rounded-xl text-xs font-bold transition flex items-center justify-center space-x-2 cursor-pointer">
                <span>📋</span><span>このページのURLをコピーして保存</span>
            </button>

            <p class="text-[11px] text-slate-500">
                お困りの際は本部テント（または班長）へ直接お声がけください。
            </p>
        </div>
    </main>

</body>
</html>
