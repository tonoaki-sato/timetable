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

        <!-- 👑 班長専用コンソール（班のタスク管理・人員不足時の管理者要請・必要機材の申請） -->
        @if(isset($ledLanes) && $ledLanes->isNotEmpty())
        <div class="space-y-4">
            @foreach($ledLanes as $ledLane)
            <div class="p-4 rounded-2xl bg-gradient-to-br from-amber-950/90 to-slate-900 border-2 border-amber-500/60 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-amber-500/30 pb-2.5">
                    <div class="flex items-center space-x-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-amber-500 text-slate-950">
                            👑 班長コンソール
                        </span>
                        <h2 class="text-sm font-bold text-amber-100">
                            {{ $ledLane->icon ?: '📋' }} {{ $ledLane->name }}（{{ $ledLane->timetable?->name }}）
                        </h2>
                    </div>
                    <span class="text-[11px] text-amber-300/80 font-mono">
                        固定スタッフ: {{ $ledLane->fixedStaff->count() }}名
                    </span>
                </div>

                <!-- 1. 班のタスク管理（進捗・人員充足確認） -->
                <div class="space-y-2">
                    <h3 class="text-xs font-bold text-amber-200 flex items-center space-x-1">
                        <span>📋</span>
                        <span>班のタスク管理（全 {{ $ledLane->tasks->count() }} 件）</span>
                    </h3>
                    <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                        @foreach($ledLane->tasks as $laneTask)
                        <div class="p-2.5 rounded-xl bg-slate-900/90 border border-slate-800 flex flex-col gap-2 text-xs">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <span class="font-mono text-amber-300 font-bold">
                                        {{ substr($laneTask->start_time, 0, 5) }}〜{{ substr($laneTask->end_time, 0, 5) }}
                                    </span>
                                    <span class="font-bold text-white ml-1">{{ $laneTask->title }}</span>
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded font-bold {{ $laneTask->is_satisfied ? 'bg-emerald-900/70 text-emerald-300' : 'bg-rose-900/80 text-rose-200' }}">
                                    配置 {{ $laneTask->assigned_count }} / 必要 {{ $laneTask->required_people_label }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[11px] text-slate-400">
                                    班員: {{ $laneTask->assignments->map(fn($a) => $a->staff?->name . '(' . ($a->role_description ?: '担当') . ')')->implode('、') ?: '未配置' }}
                                </span>
                                <form action="{{ route('staff.myshift.lane-task-status', ['token' => $staff->access_token, 'task' => $laneTask->id]) }}" method="POST" class="flex items-center gap-1 shrink-0">
                                    @csrf
                                    <select name="status" onchange="this.form.submit()" class="px-2 py-1 rounded bg-slate-800 border border-slate-700 text-[11px] text-slate-100">
                                        @foreach(\App\Models\TimetableTask::STATUSES as $stKey => $stLabel)
                                        <option value="{{ $stKey }}" {{ $laneTask->status === $stKey ? 'selected' : '' }}>{{ $stLabel }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- 2. 人員不足時の管理者への要請 & 3. 必要機材の申請フォーム -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-amber-500/30">
                    <!-- 人員応援要請フォーム -->
                    <form action="{{ route('staff.myshift.lane-requests.store', ['token' => $staff->access_token, 'lane' => $ledLane->id]) }}" method="POST" class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 space-y-2">
                        @csrf
                        <input type="hidden" name="request_type" value="staffing">
                        <div class="text-xs font-bold text-amber-300">🙋 人員不足時の管理者への要請</div>
                        <div>
                            <select name="timetable_task_id" class="w-full px-2 py-1.5 rounded bg-slate-950 border border-slate-700 text-xs text-slate-200">
                                <option value="">-- 対象タスク（班全体の場合は未選択） --</option>
                                @foreach($ledLane->tasks as $laneTask)
                                <option value="{{ $laneTask->id }}">{{ substr($laneTask->start_time, 0, 5) }} {{ $laneTask->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="number" name="requested_count" value="1" min="1" max="50" required
                                class="w-20 px-2 py-1.5 rounded bg-slate-950 border border-slate-700 text-xs text-white" placeholder="人数">
                            <span class="text-xs text-slate-400">名応援要請</span>
                        </div>
                        <input type="text" name="reason" placeholder="理由（例: 欠員発生・設営遅れのため）"
                            class="w-full px-2 py-1.5 rounded bg-slate-950 border border-slate-700 text-xs text-white">
                        <button type="submit" class="w-full py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 rounded-lg text-xs font-bold transition cursor-pointer">
                            管理者へ人員を要請する
                        </button>
                    </form>

                    <!-- 必要機材の申請フォーム -->
                    <form action="{{ route('staff.myshift.lane-requests.store', ['token' => $staff->access_token, 'lane' => $ledLane->id]) }}" method="POST" class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 space-y-2">
                        @csrf
                        <input type="hidden" name="request_type" value="equipment">
                        <div class="text-xs font-bold text-sky-300">🧰 必要機材の申請</div>
                        <input type="text" name="equipment_name" required placeholder="機材名（例: カラーコーン、台車、給水タンク）"
                            class="w-full px-2 py-1.5 rounded bg-slate-950 border border-slate-700 text-xs text-white">
                        <div class="flex items-center gap-2">
                            <input type="number" name="requested_count" value="1" min="1" max="100" required
                                class="w-20 px-2 py-1.5 rounded bg-slate-950 border border-slate-700 text-xs text-white" placeholder="数量">
                            <span class="text-xs text-slate-400">個 / 台</span>
                        </div>
                        <input type="text" name="reason" placeholder="受け渡し場所・用途メモ"
                            class="w-full px-2 py-1.5 rounded bg-slate-950 border border-slate-700 text-xs text-white">
                        <button type="submit" class="w-full py-1.5 bg-sky-500 hover:bg-sky-400 text-slate-950 rounded-lg text-xs font-bold transition cursor-pointer">
                            管理者へ機材を申請する
                        </button>
                    </form>
                </div>

                <!-- 送信済み要請・申請ステータス -->
                @if($ledLane->laneRequests->isNotEmpty())
                <div class="pt-2 border-t border-amber-500/30 space-y-1.5">
                    <div class="text-[11px] font-bold text-slate-300">📨 管理者への要請・申請状況</div>
                    @foreach($ledLane->laneRequests->take(5) as $req)
                    <div class="flex items-center justify-between text-[11px] bg-slate-900/70 px-2.5 py-1.5 rounded-lg border border-slate-800">
                        <div>
                            <span class="font-bold text-amber-300">[{{ $req->type_label }}]</span>
                            <span class="text-slate-200">
                                {{ $req->request_type === 'equipment' ? $req->equipment_name . ' × ' . $req->requested_count : '応援 +' . $req->requested_count . '名' }}
                            </span>
                            @if($req->reason)
                            <span class="text-slate-400">({{ $req->reason }})</span>
                            @endif
                        </div>
                        <span class="px-2 py-0.5 rounded font-bold text-[10px] {{ $req->status === 'resolved' ? 'bg-emerald-900 text-emerald-300' : ($req->status === 'approved' ? 'bg-sky-900 text-sky-300' : 'bg-amber-900 text-amber-200') }}">
                            {{ $req->status_label }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
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
                お困りの際は本部テント（または班長）へ直接お声がけください。
            </p>
        </div>
    </main>

    <!-- 外部JS（CSP準拠） -->
    <script src="{{ asset('js/staff-myshift.js') }}" defer></script>
</body>
</html>
