@php
    $barTitleText = $bar->title;
    $searchSource = mb_strtolower($bar->title . ' ' . $bar->laneName . ' ' . ($bar->location ?? '') . ' ' . implode(' ', $bar->assigneeNames));
    $barMeta = [
        'task_id' => $bar->taskId,
        'assignment_id' => $bar->assignmentId,
        'title' => $bar->title,
        'task_type' => $bar->taskType->value,
        'task_type_icon' => $bar->taskType->icon(),
        'task_type_short' => $bar->taskType->shortLabel(),
        'lane_name' => $bar->laneName,
        'lane_icon' => $bar->laneIcon,
        'start_time' => $bar->startTimeLabel,
        'end_time' => $bar->endTimeLabel,
        'location' => $bar->location ?? '',
        'required_people_count' => $bar->requiredPeopleCount,
        'assigned_count' => $bar->assignedCount,
        'shortage_count' => $bar->shortageCount,
        'is_satisfied' => $bar->isSatisfied,
        'is_critical' => $bar->isCritical,
        'assignee_names' => $bar->assigneeNames,
        'content' => $bar->modalPayload['content'] ?? '',
        'update_url' => $bar->modalPayload['update_url'] ?? '',
        'destroy_url' => $bar->modalPayload['destroy_url'] ?? '',
        'status' => $bar->status,
    ];
@endphp
<div data-gantt-bar
    data-task-id="{{ $bar->taskId }}"
    data-lane-id="{{ $bar->timetableLaneId }}"
    data-task-type="{{ $bar->taskType->value }}"
    data-sub-track="{{ $bar->subTrack }}"
    data-start-min="{{ $bar->startMin }}"
    data-end-min="{{ $bar->endMin }}"
    data-is-satisfied="{{ $bar->isSatisfied ? '1' : '0' }}"
    data-has-overlap="{{ $bar->hasSameTypeOverlap ? '1' : '0' }}"
    data-search-text="{{ $searchSource }}"
    data-bar-meta='@json($barMeta)'
    class="group absolute rounded-lg border {{ $bar->barClass }} px-1.5 py-1 shadow-2xs hover:shadow-md hover:z-20 transition-shadow select-none cursor-grab active:cursor-grabbing flex flex-col justify-between overflow-hidden {{ $bar->status === 'cancelled' ? 'opacity-45 line-through' : '' }}"
    title="{{ $bar->taskType->icon() }} {{ $barTitleText }} ({{ $bar->startTimeLabel }}〜{{ $bar->endTimeLabel }})｜{{ $bar->laneName }}｜配置 {{ $bar->assignedCount }}/{{ $bar->requiredPeopleCount }}名">

    <!-- 左端リサイズハンドル -->
    <div data-resize-edge="start" class="absolute left-0 top-0 bottom-0 w-1.5 cursor-ew-resize hover:bg-indigo-500/40 rounded-l-lg"></div>
    <!-- 右端リサイズハンドル -->
    <div data-resize-edge="end" class="absolute right-0 top-0 bottom-0 w-1.5 cursor-ew-resize hover:bg-indigo-500/40 rounded-r-lg"></div>

    <!-- 上段: アイコン + タイトル + 状態バッジ -->
    <div class="flex items-center justify-between gap-1 min-w-0 leading-tight">
        <div class="flex items-center gap-1 min-w-0">
            <span class="text-[10px] shrink-0">{{ $bar->taskType->icon() }}</span>
            @if($bar->isCritical)
            <span class="text-[9px] shrink-0" title="最優先">🚨</span>
            @endif
            <span class="font-bold text-[11px] truncate">
                @if(($mode ?? 'lane') === 'staff')
                    {{ $bar->laneName }}: {{ $barTitleText }}
                @else
                    {{ $barTitleText }}
                @endif
            </span>
        </div>

        <div class="flex items-center gap-1 shrink-0">
            @if($bar->hasSameTypeOverlap)
            <span class="px-1 rounded text-[9px] font-bold bg-red-600 text-white" title="同種別タスクとの時間重複あり">重複</span>
            @endif
            @if(($mode ?? 'lane') === 'lane')
                <button type="button"
                    data-bar-action="quick-assign"
                    data-action="open-quick-assign"
                    data-task-id="{{ $bar->taskId }}"
                    class="px-1 py-0.2 rounded text-[9px] font-mono font-bold border transition cursor-pointer {{ $bar->isSatisfied ? 'bg-emerald-100/90 text-emerald-800 border-emerald-300 hover:bg-emerald-200' : 'bg-amber-200/90 text-amber-950 border-amber-400 hover:bg-amber-300' }}"
                    title="クリックしてスタッフをクイック配置">
                    {{ $bar->assignedCount }}/{{ $bar->requiredPeopleCount }}
                </button>
            @endif
        </div>
    </div>

    <!-- 下段: 時間帯 & 補足（担当者または班長） -->
    <div class="flex items-center justify-between gap-1 text-[9px] opacity-80 leading-none min-w-0">
        <span data-bar-time-label class="font-mono shrink-0">{{ $bar->startTimeLabel }}〜{{ $bar->endTimeLabel }}</span>
        @if(($mode ?? 'lane') === 'lane')
            <span class="truncate">
                @if(!empty($bar->assigneeNames))
                    {{ implode('・', array_slice($bar->assigneeNames, 0, 2)) }}@if(count($bar->assigneeNames) > 2) +{{ count($bar->assigneeNames) - 2 }}@endif
                @else
                    未配置
                @endif
            </span>
        @elseif(! $bar->isSelfTaskLeader)
            <span class="truncate" title="班長: {{ $bar->taskLeaderName ?: '未定' }}">{{ $bar->taskLeaderName ? '班長: ' . $bar->taskLeaderName : '班長未定' }}</span>
        @endif
    </div>
</div>
