<!DOCTYPE html>
<html lang="ja" class="h-full bg-slate-900 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $staff->name }} さんのタイムテーブル - まつり運行システム</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full pb-16 font-sans antialiased bg-slate-950 text-slate-100 selection:bg-indigo-500 selection:text-white">

    <!-- モバイルヘッダー -->
    <header class="sticky top-0 z-30 bg-slate-900/95 backdrop-blur-md border-b border-slate-800 px-4 py-3 shadow-lg">
        <div class="max-w-xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="text-2xl">🏮</span>
                <div>
                    <h1 class="text-sm font-bold text-white tracking-tight leading-tight">マイタイムテーブル</h1>
                    <div class="flex items-center space-x-1.5" id="headerProfileArea">
                        <span class="text-xs font-bold text-indigo-400">{{ $staff->name }} さん</span>
                    </div>
                </div>
            </div>

            <!-- 到着チェックインエリア -->
            <div>
                @if($staff->is_checked_in)
                <div class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">
                    <span>✅</span>
                    <span>受付済 ({{ $staff->checked_in_at->format('H:i') }})</span>
                </div>
                @else
                <button type="button" id="checkInBtn"
                        data-endpoint="{{ route('staff.myshift.checkin', ['token' => $staff->access_token]) }}"
                        class="px-3.5 py-1.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-900/40 transition transform active:scale-95 flex items-center space-x-1 cursor-pointer">
                    <span>📍</span>
                    <span>現場到着（受付）</span>
                </button>
                @endif
            </div>
        </div>
    </header>

    <main class="max-w-xl mx-auto px-4 py-5 space-y-5">

        <!-- フラッシュメッセージ -->
        @if(session('success'))
        <div class="p-3.5 bg-emerald-950/80 border border-emerald-500/50 rounded-2xl text-emerald-300 text-xs flex items-center space-x-2">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
        @endif

        <!-- NOW & NEXT カード -->
        @if($currentTask || $nextTask)
        <div class="grid grid-cols-1 gap-3">
            @if($currentTask)
            <div class="p-4 rounded-2xl bg-gradient-to-br from-indigo-900/90 to-purple-900/80 border-2 border-indigo-400/60 shadow-xl shadow-indigo-950/50 relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 text-7xl opacity-10">⏱️</div>
                <div class="flex items-center justify-between mb-2">
                    <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[11px] font-black bg-indigo-500 text-white shadow-sm animate-pulse">
                        <span>●</span><span>NOW (現在進行中)</span>
                    </span>
                    <span class="text-xs font-mono text-indigo-200">
                        {{ substr($currentTask->task?->start_time, 0, 5) }} 〜 {{ $currentTask->task?->end_time ? substr($currentTask->task?->end_time, 0, 5) : '' }}
                    </span>
                </div>
                <h2 class="text-base font-bold text-white mb-1">
                    {{ $currentTask->task?->title }}
                </h2>
                <div class="flex flex-wrap gap-2 text-xs text-indigo-200 mt-2">
                    @if($currentTask->task?->laneRelation || $currentTask->task?->lane)
                    <span class="px-2 py-0.5 rounded bg-white/10 text-white font-bold">
                        班: {{ $currentTask->task->laneRelation?->name ?: $currentTask->task->lane }}
                    </span>
                    @endif
                    @if($currentTask->role_description)
                    <span class="px-2 py-0.5 rounded bg-white/10 text-white">
                        役割: {{ $currentTask->role_description }}
                    </span>
                    @endif
                    @if($currentTask->task?->location)
                    <span class="px-2 py-0.5 rounded bg-white/10 text-white">
                        📍 {{ $currentTask->task->location }}
                    </span>
                    @endif
                </div>

                <!-- 現場タスク着手・完了報告ボタンエリア (UC-403) -->
                <div class="mt-4 pt-3 border-t border-indigo-500/40 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center space-x-1.5 text-xs">
                        <span class="text-indigo-300">出欠・進捗:</span>
                        <span id="nowAttendanceStatusBadge" class="font-bold px-2 py-0.5 rounded text-[11px] {{ $currentTask->attendance_status === 'completed' ? 'bg-emerald-500 text-white' : ($currentTask->attendance_status === 'in_progress' ? 'bg-amber-400 text-slate-900 animate-pulse' : 'bg-indigo-700 text-indigo-100') }}">
                            {{ $currentTask->attendance_status_label }}
                        </span>
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($currentTask->attendance_status !== 'in_progress' && $currentTask->attendance_status !== 'completed')
                        <button type="button" class="assignmentStatusBtn px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-900 rounded-xl text-xs font-bold shadow transition active:scale-95 cursor-pointer flex items-center space-x-1"
                                data-endpoint="{{ route('staff.myshift.assignment-status', ['token' => $staff->access_token, 'assignment' => $currentTask->id]) }}"
                                data-status="in_progress">
                            <span>▶</span><span>着手する</span>
                        </button>
                        @endif
                        @if($currentTask->attendance_status !== 'completed')
                        <button type="button" class="assignmentStatusBtn px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow transition active:scale-95 cursor-pointer flex items-center space-x-1"
                                data-endpoint="{{ route('staff.myshift.assignment-status', ['token' => $staff->access_token, 'assignment' => $currentTask->id]) }}"
                                data-status="completed">
                            <span>✅</span><span>完了にする</span>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            @if($nextTask)
            <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-md">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                        <span>NEXT (次の予定)</span>
                    </span>
                    <span class="text-xs font-mono text-slate-400">
                        {{ substr($nextTask->task?->start_time, 0, 5) }} 〜
                    </span>
                </div>
                <h3 class="text-sm font-bold text-slate-200">
                    {{ $nextTask->task?->title }}
                </h3>
                <div class="text-xs text-slate-400 mt-1 flex flex-wrap gap-2">
                    @if($nextTask->task?->laneRelation || $nextTask->task?->lane)
                    <span>班: {{ $nextTask->task->laneRelation?->name ?: $nextTask->task->lane }}</span>
                    @endif
                    @if($nextTask->task?->location)
                    <span>📍 {{ $nextTask->task->location }}</span>
                    @endif
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- 担当タスクタイムライン -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-bold text-slate-200 flex items-center space-x-1.5">
                    <span>📋</span>
                    <span>担当タスク・運行スケジュール</span>
                </h2>
                <button type="button" id="refreshBtn" class="text-xs text-indigo-400 hover:text-indigo-300 transition flex items-center space-x-1">
                    <span>🔄</span><span>更新</span>
                </button>
            </div>

            @if($assignments->isEmpty())
            <div class="p-8 text-center bg-slate-900/60 rounded-2xl border border-slate-800/80 space-y-2">
                <span class="text-3xl block mb-1">⏳</span>
                <p class="text-sm font-bold text-slate-300">現在、割り当てられているタスクはありません</p>
                <p class="text-xs text-slate-500">
                    本部で人員配置を行っております。<br>しばらく経ってから「更新」ボタンを押すか、本部の指示をお待ちください。
                </p>
            </div>
            @else
            <div class="space-y-3">
                @php $currentDateHeader = null; @endphp
                @foreach($assignments as $assignment)
                @php
                    $task = $assignment->task;
                    $timetable = $task?->timetable;
                    $dateStr = $timetable?->target_date ? $timetable->target_date->format('Y年n月j日') . ' (' . $timetable->target_date->isoFormat('ddd') . ')' : '日程未定';
                @endphp

                @if($currentDateHeader !== $dateStr)
                    @php $currentDateHeader = $dateStr; @endphp
                    <div class="pt-2 pb-1">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-800 text-indigo-300 border border-slate-700">
                            📅 {{ $dateStr }} {{ $timetable?->name ? ' - ' . $timetable->name : '' }}
                        </span>
                    </div>
                @endif

                <div class="bg-slate-900 rounded-2xl border border-slate-800 p-4 shadow-sm hover:border-slate-700 transition">
                    <!-- 時間 & 班 -->
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center space-x-2">
                            <span class="font-mono text-sm font-bold text-white">
                                {{ substr($task?->start_time, 0, 5) }} 〜 {{ $task?->end_time ? substr($task?->end_time, 0, 5) : '' }}
                            </span>
                        </div>
                        @if($task?->laneRelation || $task?->lane)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold text-indigo-100 bg-indigo-600 shadow-sm">
                            {{ $task->laneRelation?->name ?: $task->lane }}
                        </span>
                        @endif
                    </div>

                    <!-- タスク名 -->
                    <h3 class="text-base font-bold text-slate-100 mb-2">
                        {{ $task?->title }}
                    </h3>

                    <!-- 詳細スペックグリッド -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-400 bg-slate-950/60 p-3 rounded-xl border border-slate-800/80">
                        @if($assignment->role_description)
                        <div class="flex items-start space-x-1.5">
                            <span class="text-indigo-400 font-bold">役割:</span>
                            <span class="text-slate-200">{{ $assignment->role_description }}</span>
                        </div>
                        @endif

                        @if($task?->location)
                        <div class="flex items-start space-x-1.5">
                            <span class="text-indigo-400 font-bold">場所:</span>
                            <span class="text-slate-200">{{ $task->location }}</span>
                        </div>
                        @endif

                        @if($task?->equipment)
                        <div class="flex items-start space-x-1.5 col-span-full">
                            <span class="text-indigo-400 font-bold">使用機材:</span>
                            <span class="text-slate-200">{{ $task->equipment }}</span>
                        </div>
                        @endif

                        @if($task?->description)
                        <div class="flex items-start space-x-1.5 col-span-full border-t border-slate-800 pt-1.5 mt-1">
                            <span class="text-indigo-400 font-bold">手順・指示:</span>
                            <span class="text-slate-300 whitespace-pre-line">{{ $task->description }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <!-- フッター情報 & URL保存 -->
        <div class="pt-4 border-t border-slate-800/80 text-center space-y-3">
            <button type="button" id="copyUrlBtn"
                    class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700 rounded-xl text-xs font-bold transition flex items-center justify-center space-x-2">
                <span>📋</span><span>このページのURLをコピーして保存</span>
            </button>

            <p class="text-[11px] text-slate-500">
                お困りの際は本部テント（または班リーダー）へ直接お声がけください。
            </p>
        </div>
    </main>

    <!-- 外部JS（CSP準拠） -->
    <script src="{{ asset('js/staff-myshift.js') }}" defer></script>
</body>
</html>
