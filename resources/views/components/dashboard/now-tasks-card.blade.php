<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
    <!-- ヘッダー -->
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4 pb-3 border-b border-slate-100">
        <div class="flex items-center space-x-2">
            @if ($hasTasks())
                <span class="flex h-2.5 w-2.5 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                </span>
            @else
                <span class="inline-flex rounded-full h-2.5 w-2.5 bg-slate-300"></span>
            @endif
            <h3 class="text-base font-bold text-slate-900">⚡ NOW : 現在進行中のタスク</h3>
            @if ($hasTasks())
                <span class="text-[11px] bg-red-50 text-red-700 border border-red-200 px-2 py-0.5 rounded-full font-mono font-bold">
                    {{ $taskCount() }}件
                </span>
            @endif
        </div>

        <div class="flex items-center space-x-3">
            @if ($isMainEventDay())
                <span class="text-xs text-slate-500 font-mono">
                    {{ $context->currentTimeLabel }} 時点
                </span>
                <a href="{{ $context->actionUrl }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition flex items-center space-x-0.5">
                    <span>全体進行表</span>
                    <span class="text-[10px]">→</span>
                </a>
            @else
                <span class="text-xs text-slate-400 font-mono">本日の運行予定なし</span>
            @endif
        </div>
    </div>

    @if ($hasTasks())
        <!-- 進行中タスク一覧 -->
        <div class="space-y-3">
            @foreach ($context->tasks as $task)
                <div class="p-3.5 {{ $task->is_critical ? 'bg-red-50/60 border-red-200' : 'bg-white border-slate-200' }} rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="space-y-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="px-1.5 py-0.5 bg-slate-100 text-slate-700 font-mono font-bold text-[10px] rounded border border-slate-200">
                                {{ $formatTimeRange($task) }}
                            </span>
                            @if ($task->is_critical)
                                <span class="px-2 py-0.5 bg-red-600 text-white font-bold text-[10px] rounded">🚨 最優先設置</span>
                            @endif
                            @if ($task->requires_review)
                                <span class="px-2 py-0.5 bg-amber-100 text-amber-900 border border-amber-300 font-semibold text-[10px] rounded">🔍 要確認・立会</span>
                            @endif
                            @if ($laneDisplay = $formatLaneDisplay($task))
                                <span class="text-xs font-semibold text-slate-600">{{ $laneDisplay }}</span>
                            @endif
                        </div>

                        <h4 class="font-bold text-slate-900 text-sm">
                            {{ $task->title }}
                        </h4>

                        <p class="text-xs text-slate-500 font-mono">
                            <span>📍 {{ $task->location ?: '現場各所' }}</span>
                            @if ($summary = $formatContentSummary($task))
                                <span class="mx-1">|</span>
                                <span>📦 {{ $summary }}</span>
                            @endif
                        </p>
                    </div>

                    <div class="flex sm:flex-col items-center sm:items-end justify-between gap-2 shrink-0">
                        @if ($task->required_people_count > 0 && ! $task->is_satisfied)
                            <span class="text-xs font-bold px-2 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded">
                                ⚠️ {{ $task->assigned_count }} / {{ $task->required_people_label }} (不足{{ $task->shortage_count }}名)
                            </span>
                        @elseif ($task->required_people_count > 0)
                            <span class="text-xs font-medium px-2 py-0.5 bg-slate-100 rounded text-slate-700">
                                👥 {{ $task->assigned_count }} / {{ $task->required_people_label }}配置済
                            </span>
                        @else
                            <span class="text-xs font-medium px-2 py-0.5 bg-slate-100 rounded text-slate-700">
                                👥 {{ $task->assigned_count }}名配置済
                            </span>
                        @endif

                        <span class="text-xs font-bold px-2.5 py-1 rounded-md {{ $statusBadgeClasses($task) }}">
                            {{ $task->status_label }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @elseif ($isMainEventDay())
        <!-- 空状態A: 本番運行日だが現在時間帯の進行中タスクが0件 -->
        <div class="text-center py-6 px-4 bg-slate-50/60 rounded-xl border border-dashed border-slate-200">
            <span class="text-2xl block mb-1">⏳</span>
            <p class="text-xs font-bold text-slate-700">現在進行中のタスクはありません</p>
            <p class="text-[11px] text-slate-500 mt-0.5">
                現在時刻（{{ $context->currentTimeLabel }}時点）に該当する未完了タスクはありません。
            </p>
            <a href="{{ $context->actionUrl }}" class="inline-flex items-center space-x-1 mt-3 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition shadow-xs">
                <span>運行タイムテーブルマトリクスを確認</span>
                <span>→</span>
            </a>
        </div>
    @else
        <!-- 空状態B: 平時（本日開催の運行タイムテーブルなし） -->
        <div class="text-center py-6 px-4 bg-slate-50/60 rounded-xl border border-dashed border-slate-200">
            <span class="text-2xl block mb-1">🗓️</span>
            <p class="text-xs font-bold text-slate-700">本日は運行予定日が設定されていません</p>
            <p class="text-[11px] text-slate-500 mt-0.5">
                本番運行日当日になると、現在時刻に進行中の現場タスクがリアルタイムに表示されます。
            </p>
            <a href="{{ $context->actionUrl }}" class="inline-flex items-center space-x-1 mt-3 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition shadow-xs">
                <span>タイムテーブル計画台帳へ</span>
                <span>→</span>
            </a>
        </div>
    @endif
</div>

