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
                            {{ $activeTimetable->target_date?->format('Y年n月j日') ?? '日付未設定' }}{{ $activeTimetable->presenter()->weekdayLabel() !== '' ? '（' . $activeTimetable->presenter()->weekdayLabel() . '）' : '' }}
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
                            $timetableLane = $task?->timetableLane;
                            $laneLead = $timetableLane?->leadStaff;
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
                             data-lane-id="{{ $task?->timetable_lane_id ?? 'none' }}"
                             data-completed="{{ $isCompleted ? '1' : '0' }}"
                             class="myshift-task-card rounded-xl border transition overflow-hidden {{ $isCurrent ? 'bg-gradient-to-r from-indigo-950/95 via-slate-900 to-indigo-950/85 border-2 border-indigo-400 shadow-lg shadow-indigo-950/60 ring-1 ring-indigo-400/40' : ($isNext ? 'bg-slate-900/95 border-sky-500/60 shadow-sm' : ($isCompleted ? 'bg-slate-900/50 border-slate-800/70 opacity-85' : 'bg-slate-900 border-slate-800/90 hover:border-slate-700')) }}">
                            <div class="p-3.5 space-y-2">
                                <!-- 上段: 時間帯・状態バッジ・詳細ボタン -->
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0 flex-wrap">
                                        <span class="font-mono text-xs font-bold shrink-0 {{ $isCurrent ? 'text-indigo-200' : ($isNext ? 'text-sky-300' : 'text-slate-300') }}">
                                            {{ substr($task?->start_time ?? '', 0, 5) }}〜{{ $task?->end_time ? substr($task->end_time, 0, 5) : '' }}
                                        </span>
                                        @if($task)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold border {{ $task->isRoutine() ? 'bg-teal-500/20 text-teal-200 border-teal-500/40' : 'bg-amber-500/20 text-amber-200 border-amber-500/40' }}">
                                                {{ $task->presenter()->taskTypeIcon() }} {{ $task->presenter()->taskTypeShortLabel() }}
                                            </span>
                                        @endif
                                        @if($task?->is_critical)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-500/20 text-red-300 border border-red-500/40">
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
                                                {{ $assignment->presenter()->attendanceStatusLabel() }}
                                            </span>
                                        @endif

                                        @if($assignment->presenter()->hasDetails())
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
                                    @if($timetableLane)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-bold bg-slate-800 text-indigo-300 border border-slate-700">
                                            @if($timetableLane?->icon)
                                                <span>{{ $timetableLane->icon }}</span>
                                            @endif
                                            <span>{{ $timetableLane->name }}</span>
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
                                                {{ $assignment->presenter()->attendanceStatusLabel() }}
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
                                @if($assignment->presenter()->hasDetails())
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
