@props([
    'currentStep' => 1,
    'timetable' => null,
    'timetables' => null,
    'workflowState' => null,
])

@php
    $steps = [
        1 => [
            'num' => 1,
            'title' => 'タイムテーブル作成',
            'desc' => '運行日・基本枠の登録',
            'icon' => '📅',
            'route' => route('timetables.create'),
        ],
        2 => [
            'num' => 2,
            'title' => '担当班・スタッフ編成',
            'desc' => '班長・固定スタッフ登録',
            'icon' => '🏷️',
            'route' => $timetable ? route('timetables.lanes.index', $timetable) : route('lanes.index'),
        ],
        3 => [
            'num' => 3,
            'title' => 'タスク登録',
            'desc' => '作業登録・班員自動配置',
            'icon' => '📝',
            'route' => $timetable
                ? route('timetable-tasks.create', ['timetable_id' => $timetable->id])
                : route('timetables.index'),
        ],
        4 => [
            'num' => 4,
            'title' => '人員不足・余剰スタッフ配置',
            'desc' => 'マトリクス進行表で過不足調整',
            'icon' => '⚡',
            'route' => $timetable
                ? route('timetable.index', ['timetable_id' => $timetable->id])
                : route('timetable.index'),
        ],
    ];
@endphp

<div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs mb-6 space-y-4">
    <!-- ヘッダー行: ガイドタイトル、対象運行日切替、ステップ番号 -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-slate-100">
        <div class="flex flex-wrap items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold shrink-0">🏮</span>
            <h3 class="text-xs sm:text-sm font-bold text-slate-900 flex items-center space-x-1.5">
                <span>まつり運行準備ステップガイド</span>
                @if($timetable && (!$timetables || $timetables->isEmpty()))
                    <span class="text-xs font-normal text-slate-500 ml-1">【対象運行日: {{ $timetable->name }}】</span>
                @endif
            </h3>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($timetables && $timetables->isNotEmpty())
            <!-- 運行日（タイムテーブル）切り替えセレクター -->
            <div class="flex items-center space-x-1.5 text-xs">
                <span class="text-slate-500 font-bold text-[11px] hidden sm:inline">準備対象の運行日:</span>
                <div class="flex flex-wrap items-center gap-1">
                    @foreach($timetables as $tt)
                        @php
                            $isSelected = $timetable && $timetable->id === $tt->id;
                        @endphp
                        <a href="{{ route('preparation-navigator.index', ['timetable_id' => $tt->id]) }}"
                           class="px-2.5 py-1 rounded-lg text-xs font-bold transition flex items-center space-x-1 {{ $isSelected ? 'bg-indigo-600 text-white shadow-2xs ring-1 ring-indigo-300' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                            <span>{{ $tt->name }}</span>
                            @if($tt->status === 'active')
                                <span class="px-1 py-0.2 rounded text-[9px] font-bold {{ $isSelected ? 'bg-indigo-700 text-indigo-100' : 'bg-emerald-100 text-emerald-800' }}">
                                    運行中
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
            @endif

            <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0">
                Step {{ $currentStep }} / 4
            </span>
        </div>
    </div>

    <!-- ステップ一覧 (横並び / スマホ対応) -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 relative">
        @foreach($steps as $num => $step)
            @php
                $isCurrent = ($num === $currentStep);
                $isCompleted = ($num < $currentStep);
                $isUpcoming = ($num > $currentStep);

                $cardBg = $isCurrent
                    ? 'bg-indigo-600 text-white shadow-xs border-indigo-600 ring-2 ring-indigo-200'
                    : ($isCompleted
                        ? 'bg-emerald-50/70 hover:bg-emerald-50 text-slate-700 border-emerald-200'
                        : 'bg-slate-50 hover:bg-slate-100 text-slate-500 border-slate-200');
            @endphp
            <a href="{{ $step['route'] }}"
               class="p-2.5 sm:p-3 rounded-xl border transition flex sm:flex-col items-center sm:items-start justify-between sm:justify-start gap-1.5 sm:gap-2 select-none group min-w-0 {{ $cardBg }}">
                <div class="flex items-center space-x-2 sm:space-x-1.5 w-full min-w-0">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold font-mono shrink-0 {{ $isCurrent ? 'bg-white text-indigo-600' : ($isCompleted ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-600') }}">
                        @if($isCompleted)
                            ✔
                        @else
                            {{ $num }}
                        @endif
                    </span>
                    <span class="text-xs font-bold leading-tight break-words flex-1 min-w-0 {{ $isCurrent ? 'text-white' : ($isCompleted ? 'text-emerald-950 group-hover:text-emerald-800' : 'text-slate-700 group-hover:text-slate-900') }}">
                        {{ $step['title'] }}
                    </span>
                    <span class="text-sm shrink-0 sm:hidden">{{ $step['icon'] }}</span>
                </div>
                <div class="text-[10px] hidden sm:block leading-tight break-words w-full mt-0.5 {{ $isCurrent ? 'text-indigo-100' : ($isCompleted ? 'text-emerald-700' : 'text-slate-400') }}">
                    {{ $step['desc'] }}
                </div>
            </a>
        @endforeach
    </div>

    @if($workflowState)
    <!-- 現在の推奨アクション (統合エリア) -->
    <div class="rounded-xl border {{ $workflowState['shortage_tasks_count'] > 0 ? 'bg-amber-50/80 border-amber-300 text-amber-950' : 'bg-indigo-50/70 border-indigo-200 text-slate-900' }} p-4 sm:p-5 space-y-3">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="space-y-1.5">
                <div class="flex items-center space-x-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold {{ $workflowState['shortage_tasks_count'] > 0 ? 'bg-amber-200 text-amber-900 border border-amber-300' : 'bg-indigo-200 text-indigo-900 border border-indigo-300' }}">
                        現在の推奨アクション (Step {{ $workflowState['current_step'] }} / 4)
                    </span>
                    @if($workflowState['active_timetable'])
                    <span class="text-xs text-slate-500">
                        対象: <strong>{{ $workflowState['active_timetable']->name }}</strong>
                    </span>
                    @endif
                </div>
                <h4 class="text-xs sm:text-sm font-bold flex items-center space-x-1.5">
                    <span>💡</span>
                    <span>{{ $workflowState['action_title'] }}</span>
                </h4>
                <p class="text-xs text-slate-600 leading-relaxed max-w-3xl">
                    {{ $workflowState['action_desc'] }}
                </p>
            </div>

            @if(!empty($workflowState['action_url']))
            <div class="shrink-0 flex items-center">
                <a href="{{ $workflowState['action_url'] }}"
                   class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center justify-center space-x-1.5 group">
                    <span>{{ $workflowState['action_btn_text'] }}</span>
                    <span class="transition transform group-hover:translate-x-0.5">→</span>
                </a>
            </div>
            @endif
        </div>

        @if($workflowState['active_timetable'])
        <!-- KPIステータスミニバー -->
        <div class="pt-2.5 border-t border-slate-200/80 flex flex-wrap items-center justify-between gap-2 text-xs">
            <div class="flex flex-wrap items-center gap-3 font-mono text-[11px]">
                <span class="text-slate-600">
                    担当班数: <strong class="text-slate-800">{{ $workflowState['lanes_count'] }}</strong> 班
                    <span class="text-slate-400 text-[10px]">（班長設定: {{ $workflowState['lead_configured_count'] }}/{{ $workflowState['lanes_count'] }}）</span>
                </span>
                <span class="text-slate-300">|</span>
                <span class="text-slate-600">
                    作業タスク総数: <strong class="text-slate-800">{{ $workflowState['total_tasks_count'] }}</strong> 件
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
               class="text-[11px] text-indigo-600 hover:text-indigo-800 font-bold flex items-center space-x-1">
                <span>推奨ステップを直接実行</span>
                <span>↗</span>
            </a>
        </div>
        @endif
    </div>
    @endif
</div>
