@php
    $matrixPeopleLabel = $task->presenter()->matrixPeopleLabel();
@endphp
<div class="task-card">
    @if($isParallel)
        <div class="task-time-badge">{{ substr($task->start_time, 0, 5) }}〜{{ substr($task->end_time, 0, 5) }}</div>
    @endif

    <!-- タイトル ＋ 人員規模 -->
    <div class="task-header">
        <span class="task-title">{{ $task->title }}</span>@if($matrixPeopleLabel)<span class="task-people">{{ $matrixPeopleLabel }}</span>@endif
    </div>

    <!-- 作業内容（箇条書き等） -->
    @if($task->content)
        <div class="task-content">{{ $task->content }}</div>
    @endif

    <!-- 場所（赤ピン付き） -->
    @if($task->location)
        <div class="task-location-row">
            <span class="location-pin-icon">
                <svg width="7" height="9" viewBox="0 0 24 24" fill="#ef4444" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
            </span>
            <span>{{ $task->location }}</span>
        </div>
    @endif

    <!-- 充足ステータスインジケーター -->
    <div>
        @if($task->is_satisfied)
            <span class="status-badge-satisfied">✔ 充足 ({{ $task->assigned_count }})</span>
        @else
            <span class="status-badge-shortage">⚠️ 不足 {{ $task->shortage_count }}名</span>
        @endif
    </div>

    <!-- アサインされたスタッフピルバッジ -->
    @if($task->assignments->isNotEmpty())
        <div class="staff-badges-container">
            @foreach($task->assignments as $assignment)
                @if($assignment->staff)
                    <span class="staff-pill">{{ $assignment->staff->name }}</span>
                @endif
            @endforeach
        </div>
    @endif
</div>
