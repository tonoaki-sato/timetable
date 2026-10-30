<x-layouts.app title="タイムテーブル">
<div class="max-w-[1700px] mx-auto px-2 sm:px-4 lg:px-6 py-4 space-y-4">

    <!-- 印刷用ヘッダー（印刷時のみ表示） -->
    <div class="hidden print:block text-center pb-2 border-b-2 border-black mb-2">
        <h1 class="text-xl font-bold tracking-tight text-black">
            {{ $selectedTimetable?->name ?? 'タイムテーブル' }}
            @if($selectedTimetable?->target_date)
            <span class="text-base font-normal ml-2">({{ $selectedTimetable->target_date->format('Y年m月d日') }})</span>
            @endif
        </h1>
        <div class="flex justify-between text-xs text-slate-700 mt-1">
            <span>運行時間: {{ substr($selectedTimetable?->start_time ?? '07:00', 0, 5) }} 〜 {{ substr($selectedTimetable?->end_time ?? '19:30', 0, 5) }}</span>
            <span>出力日時: {{ now()->format('Y/m/d H:i') }}</span>
        </div>
    </div>

    <!-- 1. ヘッダー（タイトル ＆ アクションを1行に集約） -->
    <div class="print:hidden bg-gradient-to-r from-slate-900 to-indigo-950 rounded-xl px-4 py-3 sm:px-5 sm:py-3 text-white shadow-md border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 min-w-0">
            <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30 shrink-0">
                ⏱️ タイムテーブル
            </span>
            <h1 class="text-base sm:text-lg font-bold tracking-tight truncate">
                {{ $selectedTimetable?->name ?? '運行タイムテーブル' }}
            </h1>
            <span class="h-4 w-px bg-slate-700 hidden sm:inline-block shrink-0"></span>
            <div class="flex items-center space-x-1.5 text-xs text-slate-300 shrink-0">
                <span class="text-slate-400">現在時刻:</span>
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <strong id="matrix-live-clock" class="font-mono text-emerald-400 font-bold text-sm tracking-wider">{{ $nowTime }}</strong>
            </div>
        </div>

        <!-- 右側: コンパクトなアクションボタン（A4横印刷のみ） -->
        @if($selectedTimetable)
        <div class="flex items-center gap-2 shrink-0">
            <!-- スタッフタイムラインボタン -->
            <a href="{{ route('timetables.staff-timeline', $selectedTimetable) }}"
                class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-purple-300 border border-purple-400/30 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 cursor-pointer shadow-xs">
                <span>👥</span>
                <span>スタッフタイムライン</span>
            </a>

            <!-- A4横 印刷 / PDF保存ボタン -->
            <button type="button" data-action="print"
                class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1.5 cursor-pointer">
                <span>🖨️</span>
                <span>A4横 印刷 / PDF保存</span>
            </button>
        </div>
        @endif
    </div>

    @if($selectedTimetable)
    <x-timetable-workflow-steps :current-step="4" :timetable="$selectedTimetable" />

    <!-- 2. 人員充足状況 & 人員不足タスク即時アサインサマリーバー (Step 4) -->
    <div class="print:hidden bg-white border border-slate-200 rounded-2xl p-4 shadow-sm space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center space-x-3">
                <span class="w-8 h-8 rounded-xl {{ $shortageTasks->isNotEmpty() ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }} flex items-center justify-center text-lg font-bold">
                    {{ $shortageTasks->isNotEmpty() ? '⚠️' : '✔' }}
                </span>
                <div>
                    <div class="flex items-center space-x-2">
                        <h2 class="text-xs sm:text-sm font-bold text-slate-900">
                            人員配置状況サマリー (Step 4: 過不足調整)
                        </h2>
                        @if($shortageTasks->isNotEmpty())
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                            未充足 {{ $shortageTasks->count() }} 件
                        </span>
                        @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            全タスク充足
                        </span>
                        @endif
                    </div>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        全タスク必要人数: <strong class="text-slate-800 font-mono">{{ $totalRequired }}</strong> 名 / 現在配置済: <strong class="text-slate-800 font-mono">{{ $totalAssigned }}</strong> 名
                        @if($shortageTasks->isNotEmpty())
                            （※下のボタンをクリックして、空きスタッフや他班の余剰スタッフをワンクリックで割り当てられます）
                        @endif
                    </p>
                </div>
            </div>

            <!-- クイックタスク追加ボタン -->
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('timetable-tasks.create', ['timetable_id' => $selectedTimetable->id]) }}"
                    class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs">
                    <span>➕</span>
                    <span>作業タスクを追加 (Step 3)</span>
                </a>
                <a href="{{ route('timetables.lanes.index', $selectedTimetable) }}"
                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center space-x-1 shadow-2xs">
                    <span>🏷️ 班編成を確認 (Step 2)</span>
                </a>
            </div>
        </div>

        @if($shortageTasks->isNotEmpty())
        <!-- 人員不足タスククイックジャンプリスト -->
        <div class="pt-3 border-t border-slate-100">
            <span class="text-[11px] font-bold text-amber-900 block mb-2">👇 人員不足のタスク（クリックすると即座にアサインモーダルが開きます）:</span>
            <div class="flex flex-wrap gap-2">
                @foreach($shortageTasks as $sTask)
                <button type="button" data-action="open-quick-assign" data-task-id="{{ $sTask->id }}"
                    class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 hover:border-amber-400 rounded-xl text-xs font-bold transition flex items-center space-x-2 cursor-pointer shadow-2xs">
                    <span class="font-mono text-[10px] bg-amber-200/60 px-1 rounded">{{ substr($sTask->start_time, 0, 5) }}</span>
                    <span>[{{ $sTask->lane ?: '班未設定' }}]</span>
                    <span class="truncate max-w-[140px]">{{ $sTask->title }}</span>
                    <span class="px-1.5 py-0.2 rounded text-[10px] bg-rose-100 text-rose-700 font-mono">不足{{ $sTask->shortage_count }}名</span>
                </button>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    @if(!$selectedTimetable)
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-500 shadow-sm">
        <span class="text-4xl block mb-3">📅</span>
        <h3 class="text-base font-bold text-slate-800">表示できるタイムテーブルがありません</h3>
        <p class="text-xs text-slate-500 mt-1">「タイムテーブル管理」から新規タイムテーブルを登録してください。</p>
        <div class="mt-4">
            <a href="{{ route('timetables.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold shadow transition">
                新規タイムテーブルを登録
            </a>
        </div>
    </div>
    @else

    <!-- 3. 担当班（レーン）随時追加対応: 絞り込みフィルターバー & 横スクロールナビゲーション -->
    <div class="print:hidden bg-slate-50 border border-slate-200 rounded-xl p-3 flex flex-col md:flex-row md:items-center justify-between gap-3 shadow-2xs">
        <!-- プリセット切り替え & 班チップ -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-slate-700 flex items-center mr-1">
                <span>🔍 表示班:</span>
            </span>

            <!-- プリセットボタン -->
            <button type="button" data-lane-preset="all"
                id="btn-preset-all"
                class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-600 text-white border border-indigo-600 shadow-2xs transition">
                全表示 ({{ $lanes->count() }})
            </button>
            <button type="button" data-lane-preset="core"
                id="btn-preset-core"
                class="px-2.5 py-1 rounded-lg text-xs font-medium bg-white text-slate-700 border border-slate-300 hover:bg-slate-100 transition">
                主要5班（PDF準拠）
            </button>
            <button type="button" data-lane-preset="mine"
                id="btn-preset-mine"
                class="px-2.5 py-1 rounded-lg text-xs font-medium bg-white text-slate-700 border border-slate-300 hover:bg-slate-100 transition">
                マイ担当班
            </button>

            <span class="h-4 w-px bg-slate-300 mx-1"></span>

            <!-- 各班のチップ（ON/OFF トグル） -->
            <div class="flex flex-wrap items-center gap-1.5" id="lane-filter-chips">
                @foreach($lanes as $lane)
                @php
                    $leadName = $lane->leadStaff ? (explode(' ', $lane->leadStaff->name)[0] ?? $lane->leadStaff->name) : '';
                    $isCoreLane = (bool) $lane->is_core;
                @endphp
                <button type="button"
                    id="chip-lane-{{ $lane->id }}"
                    data-lane-id="{{ $lane->id }}"
                    data-lane-name="{{ $lane->name }}"
                    data-is-core="{{ $isCoreLane ? '1' : '0' }}"
                    data-lead-id="{{ $lane->lead_staff_id ?? '' }}"
                    class="lane-chip inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border transition bg-white text-slate-800 border-slate-300 hover:border-indigo-400">
                    <span class="mr-1 text-[10px] text-emerald-600 font-bold">✔</span>
                    <span>{{ $lane->name }}</span>
                    @if($leadName)
                    <span class="text-slate-400 text-[10px] ml-0.5">({{ $leadName }})</span>
                    @endif
                </button>
                @endforeach
            </div>
        </div>

        <!-- 横スクロール補助ナビ -->
        <div class="flex items-center space-x-2 shrink-0">
            <span class="text-xs text-slate-500 font-mono" id="lane-count-indicator">
                全 {{ $lanes->count() }} 班表示中
            </span>
            <div class="inline-flex rounded-lg border border-slate-300 bg-white p-0.5">
                <button type="button" data-scroll-grid="-250"
                    title="左へスクロール"
                    class="px-2 py-1 text-slate-600 hover:text-indigo-600 hover:bg-slate-100 rounded text-xs font-bold transition">
                    ◀ 前の班
                </button>
                <button type="button" data-scroll-grid="250"
                    title="右へスクロール"
                    class="px-2 py-1 text-slate-600 hover:text-indigo-600 hover:bg-slate-100 rounded text-xs font-bold transition">
                    次の班 ▶
                </button>
            </div>
        </div>
    </div>

    <!-- 4. タイムテーブル帳票グリッド（PDFレイアウト完全再現） -->
    <div id="timetable-grid-container"
        data-target-date="{{ $selectedTimetable?->target_date?->format('Y-m-d') }}"
        class="relative bg-white rounded-xl border-2 border-slate-800 shadow-sm overflow-x-auto print:overflow-visible print:border print:border-black">

        <!-- リアルタイム現在時刻 赤ボーダーライン -->
        <div id="matrix-current-time-line"
            class="hidden absolute left-0 right-0 pointer-events-none z-25 print:hidden transition-[top] duration-1000 ease-linear top-0">
            <!-- 左端の現在時刻ピルバッジ -->
            <div class="absolute left-1 sm:left-2 -top-3 flex items-center space-x-1 px-1.5 py-0.5 rounded-full bg-rose-600 text-white font-mono text-[10px] font-bold shadow-md ring-1 ring-white">
                <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                <span id="matrix-line-time-label">{{ $nowTime }}</span>
            </div>
            <!-- テーブル全幅の赤線 -->
            <div class="w-full border-t-2 border-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.6)]"></div>
        </div>

        <table class="w-full border-collapse text-left border-spacing-0 select-text font-sans">
            <!-- テーブルヘッダー（Sticky Top ＆ 左4列 Sticky Left） -->
            <thead class="sticky top-0 z-30 bg-slate-100 print:static">
                <tr class="border-b-2 border-slate-800 text-slate-900 text-xs">
                    <!-- 1. 日付列（最左端固定） -->
                    <th scope="col"
                        class="sticky left-0 z-40 bg-slate-200 border-r border-b-2 border-slate-800 p-2 text-center font-bold w-[65px] min-w-[65px] print:static print:w-[50px] print:border-black">
                        日付
                    </th>

                    <!-- 2. 時間列（固定） -->
                    <th scope="col"
                        class="sticky left-[65px] z-40 bg-slate-200 border-r border-b-2 border-slate-800 p-2 text-center font-bold w-[65px] min-w-[65px] print:static print:w-[55px] print:border-black">
                        時間
                    </th>

                    <!-- 3. 道路列（固定） -->
                    <th scope="col"
                        class="sticky left-[130px] z-40 bg-slate-200 border-r border-b-2 border-slate-800 p-2 text-center font-bold w-[75px] min-w-[75px] print:static print:w-[65px] print:border-black">
                        道路
                    </th>

                    <!-- 4. まつり列（固定・右側に濃い境界線） -->
                    <th scope="col"
                        class="sticky left-[205px] z-40 bg-slate-200 border-r-2 border-b-2 border-slate-800 p-2 text-center font-bold w-[75px] min-w-[75px] print:static print:w-[65px] print:border-r-2 print:border-black shadow-xs">
                        まつり
                    </th>

                    <!-- 5. 各担当班（レーン）列ヘッダー -->
                    @foreach($lanes as $lane)
                    @php
                        $leadStaff = $lane->leadStaff;
                        $leadName = $leadStaff ? (explode(' ', $leadStaff->name)[0] ?? $leadStaff->name) : '';
                    @endphp
                    <th scope="col"
                        data-lane-id="{{ $lane->id }}"
                        class="lane-col-{{ $lane->id }} border-r border-b-2 border-slate-800 bg-slate-100 p-2 font-bold min-w-[190px] w-[210px] print:min-w-[140px] print:w-auto print:border-black">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-1 truncate">
                                <span>{{ $lane->icon ?: '📋' }}</span>
                                <span class="text-xs text-slate-900 font-bold truncate">
                                    {{ $lane->name }}
                                    @if($leadName)
                                    <span class="text-indigo-800 font-semibold font-sans">（{{ $leadName }}さん）</span>
                                    @endif
                                </span>
                            </div>
                            @can('update', $lane)
                            <a href="{{ route('timetables.lanes.index', $selectedTimetable) }}"
                                title="レーン設定"
                                class="print:hidden text-[11px] text-slate-400 hover:text-indigo-600 transition">
                                ⚙️
                            </a>
                            @endcan
                        </div>
                    </th>
                    @endforeach

                    <!-- 6. ➕ クイックレーン追加ボタン列 -->
                    <th scope="col" class="print:hidden border-b-2 border-slate-800 bg-slate-50 p-2 text-center w-[60px] min-w-[60px]">
                        @can('create', App\Models\Lane::class)
                        <button type="button" data-action="open-lane-modal"
                            title="新しい担当班を追加"
                            class="px-2 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded text-xs font-bold transition cursor-pointer">
                            ➕
                        </button>
                        @endcan
                    </th>
                </tr>
            </thead>

            <!-- テーブル本体（30分刻みスロット行） -->
            <tbody class="divide-y divide-slate-300 print:divide-black">
                @foreach($timeSlots as $sIdx => $slot)
                <tr data-slot-time="{{ $slot['time'] }}" class="border-b border-slate-400 hover:bg-slate-50/60 transition-colors print:border-black">
                    <!-- 1. 日付セル（全スロット数分、初回のみ縦結合） -->
                    @if($loop->first)
                    <td rowspan="{{ count($timeSlots) }}"
                        class="sticky left-0 z-20 bg-slate-100 border-r border-slate-800 p-2 text-center font-bold text-xs text-slate-800 align-middle print:static print:border-black">
                        <div class="[writing-mode:vertical-rl] tracking-widest mx-auto font-bold text-sm">
                            {{ $selectedTimetable->target_date?->format('m/d') }} {{ $selectedTimetable->name }}
                        </div>
                    </td>
                    @endif

                    <!-- 2. 時間セル（30分刻み） -->
                    <td class="sticky left-[65px] z-20 bg-slate-50 border-r border-slate-700 p-2 text-center font-mono font-bold text-xs text-slate-900 print:static print:border-black">
                        {{ $slot['time'] }}
                    </td>

                    <!-- 3. 道路列（車両通行止め） -->
                    <td class="sticky left-[130px] z-20 border-r border-slate-700 p-1 text-center text-xs print:static print:border-black {{ $slot['road']['status'] === 'closed' ? 'bg-rose-100 text-rose-800 font-bold print:bg-rose-50' : 'bg-white text-slate-400 font-mono' }}">
                        {{ $slot['road']['label'] }}
                    </td>

                    <!-- 4. まつり列（開催中・右側に太線） -->
                    <td class="sticky left-[205px] z-20 border-r-2 border-slate-800 p-1 text-center text-xs print:static print:border-r-2 print:border-black {{ $slot['festival']['status'] === 'active' ? 'bg-emerald-100 text-emerald-800 font-bold print:bg-emerald-50' : 'bg-white text-slate-400 font-mono' }}">
                        {{ $slot['festival']['label'] }}
                    </td>

                    <!-- 5. 各担当班（レーン）のセル -->
                    @foreach($lanes as $lane)
                    @php
                        $cell = $grid[$sIdx][$lane->id] ?? ['type' => 'empty', 'rowspan' => 1];
                    @endphp

                    @if($cell['type'] === 'task')
                    @php
                        $task = $cell['task'];
                        $reqCount = $task->required_people_count;
                        $isAtLeast = $task->is_at_least;
                        $peopleLabel = $reqCount ? ($isAtLeast ? "【{$reqCount}名以上】" : "【{$reqCount}名】") : '';
                    @endphp
                    <td rowspan="{{ $cell['rowspan'] }}"
                        data-lane-id="{{ $lane->id }}"
                        class="lane-col-{{ $lane->id }} border-r border-slate-700 p-2 align-top bg-white hover:bg-indigo-50/50 transition border-b border-b-slate-700 print:border-black print:p-1.5">
                        <div class="space-y-1.5 h-full flex flex-col justify-between">
                            <div>
                                <!-- 作業タイトル ＋ 人員規模 -->
                                <div class="font-bold text-xs text-slate-900 leading-snug">
                                    <a href="{{ route('timetable-tasks.show', $task) }}"
                                        class="hover:text-indigo-600 transition hover:underline">
                                        {{ $task->title }}
                                    </a>
                                    @if($peopleLabel)
                                    <span class="font-bold text-indigo-700 ml-1">{{ $peopleLabel }}</span>
                                    @endif
                                </div>

                                <!-- 作業指示概要・サブ作業箇条書き -->
                                @if($task->content)
                                <div class="text-[11px] text-slate-700 leading-tight mt-1 whitespace-pre-line border-l-2 border-slate-300 pl-1.5 py-0.5 print:text-[10px]">
                                    {{ $task->content }}
                                </div>
                                @endif

                                <!-- 場所情報 -->
                                @if($task->location)
                                <div class="text-[10px] text-slate-500 mt-1 flex items-center space-x-1">
                                    <span>📍</span>
                                    <span>{{ $task->location }}</span>
                                </div>
                                @endif
                            </div>

                            <!-- 下部: 人員充足インジケーター & アサインスタッフ（画面表示用） -->
                            <div class="pt-1 border-t border-slate-100 flex flex-wrap items-center justify-between gap-1 print:hidden">
                                @if($task->is_satisfied)
                                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    ✔ 充足 ({{ $task->assigned_count }})
                                </span>
                                @else
                                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-300">
                                    ⚠️ 不足 {{ $task->shortage_count }}名
                                </span>
                                @endif

                                <div class="flex items-center space-x-1.5">
                                    @can('update', $task)
                                    <button type="button" data-action="open-quick-assign" data-task-id="{{ $task->id }}"
                                        title="スタッフをクイック配置"
                                        class="px-1.5 py-0.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded text-[10px] font-bold transition cursor-pointer">
                                        👥 配置
                                    </button>
                                    @endcan
                                    <a href="{{ route('timetable-tasks.show', $task) }}"
                                        class="text-[10px] text-slate-500 hover:text-indigo-600 font-bold hover:underline">
                                        詳細 →
                                    </a>
                                </div>
                            </div>

                            <!-- 画面表示用アサインスタッフチップ -->
                            @if($task->assignments->isNotEmpty())
                            <div class="flex flex-wrap gap-1 mt-1 print:hidden">
                                @foreach($task->assignments->take(3) as $assignment)
                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] bg-slate-100 text-slate-700 border border-slate-200 truncate max-w-[80px]" title="{{ $assignment->staff?->name }} ({{ $assignment->role_description ?: '担当' }})">
                                    {{ $assignment->staff?->name }}
                                </span>
                                @endforeach
                                @if($task->assignments->count() > 3)
                                <span class="text-[9px] text-slate-400 font-mono self-center">+{{ $task->assignments->count() - 3 }}</span>
                                @endif
                            </div>
                            @endif

                            <!-- 印刷時のアサインスタッフ簡潔表示 -->
                            @if($task->assignments->isNotEmpty())
                            <div class="hidden print:block text-[9px] text-slate-600 border-t border-dotted border-slate-300 pt-0.5">
                                スタッフ: {{ $task->assignments->map(fn($a) => $a->staff?->name)->filter()->implode(', ') }}
                            </div>
                            @endif
                        </div>
                    </td>

                    @elseif($cell['type'] === 'empty')
                    <!-- 空きスロット（PDF準拠: ハイフン表示 & ホバーでタスク作成リンク） -->
                    <td data-lane-id="{{ $lane->id }}"
                        class="lane-col-{{ $lane->id }} border-r border-slate-700 p-2 text-center align-middle bg-white hover:bg-slate-50 transition border-b border-b-slate-400 group print:border-black">
                        <div class="relative flex items-center justify-center">
                            <span class="text-slate-400 font-mono text-xs group-hover:opacity-30 transition">
                                -
                            </span>
                            @can('create', App\Models\TimetableTask::class)
                            <a href="{{ route('timetable-tasks.create', ['timetable_id' => $selectedTimetable->id, 'lane_id' => $lane->id, 'start_time' => $slot['time']]) }}"
                                title="{{ $lane->name }}の{{ $slot['time'] }}にタスクを追加"
                                class="print:hidden absolute inset-0 opacity-0 group-hover:opacity-100 flex items-center justify-center text-xs font-bold text-indigo-600 bg-indigo-50/80 rounded transition">
                                ➕
                            </a>
                            @endcan
                        </div>
                    </td>

                    @elseif($cell['type'] === 'skip')
                    <!-- rowspanで上位セルに結合されているためセルを描画しない -->
                    @endif
                    @endforeach

                    <!-- 6. ➕ 末尾列の空枠 -->
                    <td class="print:hidden border-b border-slate-400 p-1 text-center text-slate-300">
                        &nbsp;
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- 5. 担当班（レーン）クイック追加モーダル（Vanilla JS） -->
    <div id="laneModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full overflow-hidden animate-in fade-in zoom-in duration-150">
            <!-- モーダルヘッダー -->
            <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="text-lg">🏷️</span>
                    <h3 class="font-bold text-sm">新規担当班（レーン）を追加</h3>
                </div>
                <button type="button" data-action="close-lane-modal"
                    class="text-slate-400 hover:text-white text-lg font-bold transition">
                    ✕
                </button>
            </div>

            <!-- フォーム -->
            <form action="{{ route('timetables.lanes.store', $selectedTimetable) }}" method="POST" class="p-5 space-y-4">
                @csrf
                <input type="hidden" name="redirect_to" value="matrix">

                <!-- レーン名 -->
                <div>
                    <label for="modal_lane_name" class="block text-xs font-bold text-slate-700 mb-1">
                        担当班名（レーン名） <span class="text-rose-600">*</span>
                    </label>
                    <input type="text" id="modal_lane_name" name="name" required
                        placeholder="例: 会場放送班、美化清掃班、救護班"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <!-- テーマカラー -->
                    <div>
                        <label for="modal_lane_color" class="block text-xs font-bold text-slate-700 mb-1">
                            カラー <span class="text-rose-600">*</span>
                        </label>
                        <select id="modal_lane_color" name="color" required
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach($colorOptions as $val => $lbl)
                            <option value="{{ $val }}" {{ $val === 'indigo' ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- アイコン -->
                    <div>
                        <label for="modal_lane_icon" class="block text-xs font-bold text-slate-700 mb-1">
                            アイコン
                        </label>
                        <input type="text" id="modal_lane_icon" name="icon" value="📋"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- 主担当スタッフ（現場リーダー） -->
                <div>
                    <label for="modal_lane_lead" class="block text-xs font-bold text-slate-700 mb-1">
                        主担当スタッフ（後から設定・変更可能）
                    </label>
                    <select id="modal_lane_lead" name="lead_staff_id"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- 未設定（後で割り当てる） --</option>
                        @foreach($activeStaff as $st)
                        <option value="{{ $st->id }}">{{ $st->name }} {{ $st->affiliation ? "({$st->affiliation})" : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 主要班フラグ -->
                <div class="flex items-center space-x-2 pt-1">
                    <input type="checkbox" id="modal_lane_is_core" name="is_core" value="1"
                        class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                    <label for="modal_lane_is_core" class="text-xs font-bold text-slate-700 cursor-pointer">
                        ⭐ 主要班（コアレーン）として左側に優先表示・強調する
                    </label>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" data-action="close-lane-modal"
                        class="px-4 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-bold transition">
                        キャンセル
                    </button>
                    <button type="submit"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                        担当班を追加して反映
                    </button>
                </div>
            </form>
        </div>
    </div>

    @endif

    <!-- 7. クイックアサイン スライドオーバーモーダル (CSP完全準拠・バニラJS駆動) -->
    <div id="quickAssignModal"
        class="hidden fixed inset-0 z-50 overflow-hidden bg-slate-900/60 backdrop-blur-xs flex justify-end">
        <div class="bg-white w-full max-w-2xl h-full shadow-2xl flex flex-col border-l border-slate-200">

            <!-- モーダルヘッダー（時間帯・担当班・場所・充足状況・自動配置を集約） -->
            <div class="px-4 py-3 bg-gradient-to-r from-slate-900 to-indigo-950 text-white shrink-0 space-y-1.5">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-1.5 text-xs">
                        <span id="quickAssignTaskTime" class="font-mono font-bold text-indigo-200 bg-slate-800/90 px-2 py-0.5 rounded border border-slate-700"></span>
                        <span id="quickAssignTaskLane" class="px-2 py-0.5 rounded bg-indigo-500/20 border border-indigo-400/30 text-[10px] font-bold text-indigo-200"></span>
                        <span id="quickAssignTaskLocation" class="hidden px-2 py-0.5 rounded bg-slate-800/80 border border-slate-700 text-[10px] text-slate-300"></span>
                        <span id="quickAssignHeaderStatus"></span>
                    </div>

                    <div class="flex items-center space-x-2 shrink-0">
                        <span id="quickAssignHeaderPoolBtn"></span>
                        <button type="button" data-action="close-quick-assign"
                            class="text-slate-400 hover:text-white text-2xl font-bold leading-none cursor-pointer px-1">
                            &times;
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2">
                    <h3 id="quickAssignTaskTitle" class="text-sm sm:text-base font-bold truncate text-white">作業タスク詳細</h3>
                </div>
            </div>

            <!-- モーダル本体（内部で候補リストのみ独立スクロールし、配置ボタンは常時表示） -->
            <div id="quickAssignModalBody" class="flex-1 min-h-0 flex flex-col overflow-hidden">
            </div>

            <!-- モーダルフッター（ナビゲーション） -->
            <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs shrink-0">
                <a id="quickAssignDetailLink" href="#" target="_blank"
                    class="text-indigo-600 hover:underline font-bold flex items-center space-x-1">
                    <span>タスク詳細ページを開く</span>
                    <span>↗</span>
                </a>
                <button type="button" data-action="reload-matrix"
                    class="px-3.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg font-bold transition cursor-pointer">
                    閉じて画面を更新
                </button>
            </div>
        </div>
    </div>

</div>

@vite(['resources/js/timetable-matrix.js'])
</x-layouts.app>

