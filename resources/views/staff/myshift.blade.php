<!DOCTYPE html>
<html lang="ja" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $staff->name }} さんのタイムテーブル - まつり運行システム</title>
    <x-head-meta />
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

            <!-- 到着チェックイン／閲覧専用ステータス & カレンダー保存 -->
            <div class="flex items-center gap-2 shrink-0 flex-wrap justify-end">
                <a href="{{ route('staff.myshift.ics', ['token' => $staff->access_token, 'timetable_id' => $showAllTimetables ? 'all' : $activeTimetable?->id], false) }}"
                    class="inline-flex items-center space-x-1 px-2.5 py-1.5 rounded-xl text-[11px] font-bold bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-700 transition"
                    title="担当シフトをカレンダー (.ics) に保存">
                    <span>📅</span>
                    <span class="hidden sm:inline">カレンダー保存 (.ics)</span>
                    <span class="sm:hidden">.ics</span>
                </a>
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
            <div class="p-3.5 bg-red-950/80 border border-red-500/50 rounded-2xl text-red-300 text-xs flex items-center space-x-2">
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
                                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-red-500 text-white">
                                    {{ $pendingLeaderRequestCount }}
                                </span>
                            @endif
                        </button>
                    </div>
                @endif

                @include('staff.partials.myshift-timeline-panel')

                @include('staff.partials.myshift-leader-panel')
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
