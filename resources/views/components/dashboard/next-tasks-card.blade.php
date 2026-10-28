<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
    <!-- ヘッダー -->
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4 pb-3 border-b border-slate-100">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-base">⏳</span>
            <h3 class="text-base font-bold text-slate-900">
                NEXT : 次に控えるタスク
                @if ($hasTasks() && $context->nextStartTimeLabel)
                    <span class="text-sm font-bold text-slate-600">(直近{{ $context->nextStartTimeLabel }}開始)</span>
                @endif
            </h3>
            @if ($hasTasks())
                <span class="text-[11px] bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-0.5 rounded-full font-mono font-bold">
                    @if ($hasMoreTasks())
                        全{{ $context->totalNextCount }}件中 {{ $context->tasks->count() }}件
                    @else
                        {{ $context->totalNextCount }}件
                    @endif
                </span>
            @endif
        </div>

        <div class="flex items-center space-x-2">
            @if (! $hasTasks() && $isMainEventDay())
                <span class="text-xs text-slate-400 font-mono">以降の予定タスクなし</span>
            @elseif (! $hasTasks())
                <span class="text-xs text-slate-400 font-mono">本日の運行予定なし</span>
            @endif
        </div>
    </div>

    @if ($hasTasks())
        <!-- 次期タスク一覧（2カラムグリッド・最大6件） -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach ($context->tasks as $task)
                <div data-task-id="{{ $task->id }}" class="p-3.5 {{ $task->is_critical ? 'bg-red-50/50 border-red-200' : 'bg-slate-50 border-slate-200' }} rounded-xl border flex flex-col justify-between gap-2">
                    <div class="space-y-1.5">
                        <!-- 上段: 時間帯・残り時間 & 担当班 -->
                        <div class="flex items-center justify-between gap-2 text-xs text-slate-500">
                            <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                                <span class="font-mono font-bold text-slate-700 bg-white px-1.5 py-0.5 rounded border border-slate-200">
                                    {{ $formatTimeRange($task) }}
                                </span>
                                <span class="text-[10px] text-indigo-600 font-bold bg-indigo-50/90 px-2 py-0.5 rounded-full border border-indigo-200/80">
                                    {{ $formatTaskRemainingTime($task) }}
                                </span>
                            </div>
                            @if ($laneDisplay = $formatLaneDisplay($task))
                                <span class="font-bold text-slate-700 truncate">{{ $laneDisplay }}</span>
                            @endif
                        </div>

                        <!-- 中段: バッジ & タスク名 -->
                        <div class="space-y-1">
                            @if ($task->is_critical || $task->requires_review)
                                <div class="flex flex-wrap items-center gap-1">
                                    @if ($task->is_critical)
                                        <span class="px-1.5 py-0.5 bg-red-600 text-white font-bold text-[10px] rounded">🚨 最優先</span>
                                    @endif
                                    @if ($task->requires_review)
                                        <span class="px-1.5 py-0.5 bg-amber-100 text-amber-900 border border-amber-300 font-semibold text-[10px] rounded">🔍 要確認・立会</span>
                                    @endif
                                </div>
                            @endif
                            <h5 class="font-bold text-slate-900 text-xs leading-snug">
                                {{ $task->title }}
                            </h5>
                        </div>
                    </div>

                    <!-- 下段: 場所 & 人員配置/不足アラート -->
                    <div class="flex items-center justify-between gap-2 pt-1.5 border-t border-slate-200/70 text-[11px]">
                        <span class="text-slate-500 truncate">
                            📍 {{ $task->location ?: '現場各所' }}
                        </span>

                        @if ($task->required_people_count > 0 && ! $task->is_satisfied)
                            <span class="shrink-0 font-bold px-1.5 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded text-[10px]">
                                ⚠️ {{ $task->assigned_count }}/{{ $task->required_people_label }} (不足{{ $task->shortage_count }}名)
                            </span>
                        @elseif ($task->required_people_count > 0)
                            <span class="shrink-0 font-medium px-1.5 py-0.5 bg-white text-slate-600 border border-slate-200 rounded text-[10px]">
                                👥 {{ $task->assigned_count }}/{{ $task->required_people_label }}配置済
                            </span>
                        @else
                            <span class="shrink-0 font-medium px-1.5 py-0.5 bg-white text-slate-600 border border-slate-200 rounded text-[10px]">
                                👥 {{ $task->assigned_count }}名配置済
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @elseif ($isMainEventDay())
        <!-- 空状態A: 本番運行日だが以降に開始予定の未着手タスクが0件 -->
        <div class="text-center py-6 px-4 bg-slate-50/60 rounded-xl border border-dashed border-slate-200">
            <span class="text-2xl block mb-1">✅</span>
            <p class="text-xs font-bold text-slate-700">本日これ以降に開始予定のタスクはありません</p>
            <p class="text-[11px] text-slate-500 mt-0.5">
                予定されているすべてのタスクが開始済み、または完了しています。
            </p>
        </div>
    @else
        <!-- 空状態B: 平時（本日開催の運行タイムテーブルなし） -->
        <div class="text-center py-6 px-4 bg-slate-50/60 rounded-xl border border-dashed border-slate-200">
            <span class="text-2xl block mb-1">🗓️</span>
            <p class="text-xs font-bold text-slate-700">本日は運行予定日が設定されていません</p>
            <p class="text-[11px] text-slate-500 mt-0.5">
                本番運行日当日になると、次に控えるタスクと開始までの残り時間が表示されます。
            </p>
        </div>
    @endif
</div>

