<x-layouts.app title="まつり運行準備ナビゲーター">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ページヘッダー -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-6 text-white shadow-md border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1.5">
            <div class="flex items-center space-x-2.5">
                <span class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-300 border border-indigo-400/30 flex items-center justify-center text-xl shrink-0">
                    🚀
                </span>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight flex items-center space-x-2">
                        <span>まつり運行準備ナビゲーター</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                            Step 1〜4
                        </span>
                    </h1>
                    <p class="text-xs text-slate-300 mt-1">
                        タイムテーブル作成から班編成・タスク登録・余剰スタッフ配置まで、本番運行に向けた準備手順をガイドします。
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($activeTimetable)
            <a href="{{ route('timetable.index', ['timetable_id' => $activeTimetable->id]) }}"
               class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white rounded-xl text-xs font-bold border border-slate-700 transition flex items-center space-x-1.5 shadow-xs">
                <span>⏱️</span>
                <span>進行表（マトリクス）を開く</span>
            </a>
            @endif
            <a href="{{ route('timetables.index') }}"
               class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white rounded-xl text-xs font-bold border border-slate-700 transition flex items-center space-x-1.5 shadow-xs">
                <span>📋</span>
                <span>運行日一覧</span>
            </a>
        </div>
    </div>

    <!-- 運行日（タイムテーブル）セレクター（複数登録がある場合） -->
    @if($timetables->isNotEmpty())
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center space-x-2 shrink-0">
            <span class="text-xs font-bold text-slate-700 flex items-center space-x-1">
                <span>📅</span>
                <span>準備対象の運行日:</span>
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 flex-1 justify-start sm:justify-end">
            @foreach($timetables as $tt)
                @php
                    $isSelected = $activeTimetable && $activeTimetable->id === $tt->id;
                @endphp
                <a href="{{ route('preparation-navigator.index', ['timetable_id' => $tt->id]) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 {{ $isSelected ? 'bg-indigo-600 text-white shadow-xs ring-2 ring-indigo-200' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                    <span>{{ $tt->name }}</span>
                    @if($tt->status === 'active')
                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold {{ $isSelected ? 'bg-indigo-700 text-indigo-100' : 'bg-emerald-100 text-emerald-800' }}">
                            運行中
                        </span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- ステップガイドバー (全4ステップ) -->
    <x-timetable-workflow-steps
        :current-step="$workflowState['current_step']"
        :timetable="$workflowState['active_timetable']" />

    <!-- メイン推奨アクションバナー (目立つ実行カード) -->
    <div class="bg-white rounded-2xl border-2 {{ $workflowState['shortage_tasks_count'] > 0 ? 'border-amber-300 bg-amber-50/40' : 'border-indigo-200 bg-indigo-50/40' }} shadow-sm p-6 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-2">
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 rounded-md text-xs font-mono font-bold {{ $workflowState['shortage_tasks_count'] > 0 ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-indigo-100 text-indigo-900 border border-indigo-200' }}">
                        現在の推奨アクション (Step {{ $workflowState['current_step'] }} / 4)
                    </span>
                    @if($workflowState['active_timetable'])
                    <span class="text-xs text-slate-500">
                        対象: <strong>{{ $workflowState['active_timetable']->name }}</strong>
                    </span>
                    @endif
                </div>
                <h2 class="text-base sm:text-lg font-black text-slate-900 flex items-center space-x-2">
                    <span>💡</span>
                    <span>{{ $workflowState['action_title'] }}</span>
                </h2>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed max-w-3xl">
                    {{ $workflowState['action_desc'] }}
                </p>
            </div>

            @if(!empty($workflowState['action_url']))
            <div class="shrink-0 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <a href="{{ $workflowState['action_url'] }}"
                   class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-md transition flex items-center justify-center space-x-2 group">
                    <span>{{ $workflowState['action_btn_text'] }}</span>
                    <span class="transition transform group-hover:translate-x-1">→</span>
                </a>
            </div>
            @endif
        </div>

        @if($workflowState['active_timetable'])
        <!-- KPIステータスミニバー -->
        <div class="pt-3 border-t border-slate-200/80 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex flex-wrap items-center gap-4 font-mono">
                <span class="text-slate-600">
                    担当班数: <strong class="text-slate-900">{{ $workflowState['lanes_count'] }}</strong> 班
                    <span class="text-slate-400 text-[11px]">（班長設定: {{ $workflowState['lead_configured_count'] }}/{{ $workflowState['lanes_count'] }}）</span>
                </span>
                <span class="text-slate-300">|</span>
                <span class="text-slate-600">
                    作業タスク総数: <strong class="text-slate-900">{{ $workflowState['total_tasks_count'] }}</strong> 件
                </span>
                <span class="text-slate-300">|</span>
                @if($workflowState['shortage_tasks_count'] > 0)
                    <span class="text-rose-600 font-bold flex items-center space-x-1">
                        <span>⚠️ 人員不足タスク:</span>
                        <span>{{ $workflowState['shortage_tasks_count'] }} 件</span>
                    </span>
                @else
                    <span class="text-emerald-600 font-bold flex items-center space-x-1">
                        <span>✔</span>
                        <span>全タスク人員充足</span>
                    </span>
                @endif
            </div>

            <a href="{{ route('preparation-navigator.run', ['timetable_id' => $workflowState['active_timetable']->id]) }}"
               class="text-xs text-indigo-600 hover:text-indigo-800 font-bold flex items-center space-x-1">
                <span>推奨ステップを直接実行</span>
                <span>↗</span>
            </a>
        </div>
        @endif
    </div>

    <!-- 4ステップ詳細カード一覧 -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-2">
                <span>📋</span>
                <span>運行準備 4つのステップ詳細</span>
            </h3>
            <span class="text-xs text-slate-500">
                各ステップカードから直接画面へ移動できます
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

            <!-- Step 1 カード -->
            @php
                $s1Completed = $workflowState['has_timetable'];
                $s1Current = ($workflowState['current_step'] === 1);
            @endphp
            <div class="bg-white rounded-2xl border {{ $s1Current ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-slate-200' }} p-4 shadow-2xs flex flex-col justify-between space-y-3">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold font-mono {{ $s1Completed ? 'bg-emerald-500 text-white' : ($s1Current ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600') }}">
                            {{ $s1Completed ? '✔' : '1' }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $s1Completed ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($s1Current ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-600') }}">
                            {{ $s1Completed ? '登録済 (' . $timetables->count() . '日)' : ($s1Current ? '対応中' : '未着手') }}
                        </span>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 flex items-center space-x-1.5">
                            <span>📅</span>
                            <span>Step 1: タイムテーブル作成</span>
                        </h4>
                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                            本祭りや前夜祭などの運行日枠を登録し、まつり運行の土台を設定します。
                        </p>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                    <a href="{{ route('timetables.create') }}" class="text-indigo-600 hover:text-indigo-800 font-bold">
                        ＋ 新規作成
                    </a>
                    <a href="{{ route('timetables.index') }}" class="text-slate-500 hover:text-slate-800 text-[11px]">
                        一覧確認 →
                    </a>
                </div>
            </div>

            <!-- Step 2 カード -->
            @php
                $s2Completed = $workflowState['active_timetable'] && $workflowState['lanes_count'] > 0 && $workflowState['lead_configured_count'] > 0;
                $s2Current = ($workflowState['current_step'] === 2);
            @endphp
            <div class="bg-white rounded-2xl border {{ $s2Current ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-slate-200' }} p-4 shadow-2xs flex flex-col justify-between space-y-3">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold font-mono {{ $s2Completed ? 'bg-emerald-500 text-white' : ($s2Current ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600') }}">
                            {{ $s2Completed ? '✔' : '2' }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $s2Completed ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($s2Current ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-600') }}">
                            @if(!$workflowState['active_timetable'])
                                運行日未登録
                            @elseif($workflowState['lanes_count'] === 0)
                                未編成
                            @elseif($workflowState['lead_configured_count'] < $workflowState['lanes_count'])
                                班長未設定あり
                            @else
                                編成完了 ({{ $workflowState['lanes_count'] }}班)
                            @endif
                        </span>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 flex items-center space-x-1.5">
                            <span>🏷️</span>
                            <span>Step 2: 担当班・スタッフ編成</span>
                        </h4>
                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                            各班（カラーコーン、給水など）に現場班長や専任スタッフを設定します。
                        </p>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                    @if($workflowState['active_timetable'])
                    <a href="{{ route('timetables.lanes.index', $workflowState['active_timetable']) }}" class="text-indigo-600 hover:text-indigo-800 font-bold">
                        班編成画面を開く →
                    </a>
                    @else
                    <a href="{{ route('lanes.index') }}" class="text-slate-400">
                        班管理へ →
                    </a>
                    @endif
                </div>
            </div>

            <!-- Step 3 カード -->
            @php
                $s3Completed = $workflowState['active_timetable'] && $workflowState['total_tasks_count'] > 0;
                $s3Current = ($workflowState['current_step'] === 3);
            @endphp
            <div class="bg-white rounded-2xl border {{ $s3Current ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-slate-200' }} p-4 shadow-2xs flex flex-col justify-between space-y-3">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold font-mono {{ $s3Completed ? 'bg-emerald-500 text-white' : ($s3Current ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600') }}">
                            {{ $s3Completed ? '✔' : '3' }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $s3Completed ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($s3Current ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-600') }}">
                            @if(!$workflowState['active_timetable'])
                                運行日未登録
                            @elseif($workflowState['total_tasks_count'] === 0)
                                未登録
                            @else
                                登録済 ({{ $workflowState['total_tasks_count'] }}件)
                            @endif
                        </span>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 flex items-center space-x-1.5">
                            <span>📝</span>
                            <span>Step 3: 作業タスク登録</span>
                        </h4>
                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                            時間帯別の作業タスクを登録します。設定済みの班員は自動配置されます。
                        </p>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                    @if($workflowState['active_timetable'])
                    <a href="{{ route('timetable-tasks.create', ['timetable_id' => $workflowState['active_timetable']->id]) }}" class="text-indigo-600 hover:text-indigo-800 font-bold">
                        ＋ タスクを追加
                    </a>
                    @else
                    <span class="text-slate-400 text-[11px]">前ステップ待機</span>
                    @endif
                </div>
            </div>

            <!-- Step 4 カード -->
            @php
                $s4Completed = $workflowState['active_timetable'] && $workflowState['total_tasks_count'] > 0 && $workflowState['shortage_tasks_count'] === 0;
                $s4Current = ($workflowState['current_step'] === 4);
            @endphp
            <div class="bg-white rounded-2xl border {{ $s4Current ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-slate-200' }} p-4 shadow-2xs flex flex-col justify-between space-y-3">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold font-mono {{ $s4Completed ? 'bg-emerald-500 text-white' : ($s4Current ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600') }}">
                            {{ $s4Completed ? '✔' : '4' }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $s4Completed ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($workflowState['shortage_tasks_count'] > 0 ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-100 text-slate-600') }}">
                            @if(!$workflowState['active_timetable'] || $workflowState['total_tasks_count'] === 0)
                                前ステップ待機
                            @elseif($workflowState['shortage_tasks_count'] > 0)
                                不足 {{ $workflowState['shortage_tasks_count'] }}件
                            @else
                                人員充足 ✔
                            @endif
                        </span>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 flex items-center space-x-1.5">
                            <span>⚡</span>
                            <span>Step 4: 人員不足・過不足調整</span>
                        </h4>
                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                            マトリクス進行表で過不足を確認し、他班の余剰スタッフを割り当てます。
                        </p>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                    @if($workflowState['active_timetable'])
                    <a href="{{ route('timetable.index', ['timetable_id' => $workflowState['active_timetable']->id]) }}" class="text-indigo-600 hover:text-indigo-800 font-bold">
                        進行表で配置 →
                    </a>
                    @else
                    <span class="text-slate-400 text-[11px]">前ステップ待機</span>
                    @endif
                </div>
            </div>

        </div>
    </div>

</div>
</x-layouts.app>

