@props([
    'currentStep' => 1,
    'timetable' => null,
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
            'title' => '担当班編成',
            'desc' => '現場班長・固定スタッフ設定',
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
            'title' => '人員配置',
            'desc' => 'スタッフタイムラインで過不足調整',
            'icon' => '👥',
            'route' => $timetable
                ? route('timetables.staff-timeline', $timetable)
                : route('timetables.index'),
        ],
    ];
@endphp

<div class="bg-white border border-slate-200 rounded-xl p-2.5 sm:p-3 shadow-2xs mb-4">
    <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100">
        <div class="flex items-center space-x-2 min-w-0">
            <span class="w-5 h-5 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-[11px] font-bold shrink-0">🏮</span>
            <h3 class="text-xs font-bold text-slate-800 truncate">
                まつり運行準備ステップガイド
                @if($timetable)
                    <span class="text-[11px] font-normal text-slate-500 ml-1.5 truncate">【対象: {{ $timetable->name }}】</span>
                @endif
            </h3>
        </div>
        <div class="flex items-center space-x-2 shrink-0">
            <a href="{{ route('preparation-navigator.index') }}" class="text-[10px] text-slate-400 hover:text-indigo-600 transition flex items-center space-x-0.5">
                <span>ナビゲーターへ</span>
                <span>→</span>
            </a>
            <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">
                Step {{ $currentStep }} / 4
            </span>
        </div>
    </div>

    <!-- ステップ一覧 (スリムな横並びプログレス) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 relative">
        @foreach($steps as $num => $step)
            @php
                $isCurrent = ($num === $currentStep);
                $isCompleted = ($num < $currentStep);

                $cardBg = $isCurrent
                    ? 'bg-indigo-600 text-white shadow-2xs border-indigo-600 ring-1 ring-indigo-300'
                    : ($isCompleted
                        ? 'bg-emerald-50/70 hover:bg-emerald-50 text-slate-700 border-emerald-200'
                        : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border-slate-200');
            @endphp
            <a href="{{ $step['route'] }}"
               class="p-2 rounded-lg border transition flex items-center justify-between gap-1.5 select-none group min-w-0 {{ $cardBg }}">
                <div class="flex items-center space-x-1.5 min-w-0 flex-1">
                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-[9px] font-bold font-mono shrink-0 {{ $isCurrent ? 'bg-white text-indigo-600' : ($isCompleted ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-600') }}">
                        @if($isCompleted)
                            ✔
                        @else
                            {{ $num }}
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="text-[11px] font-bold leading-tight truncate {{ $isCurrent ? 'text-white' : ($isCompleted ? 'text-emerald-950 group-hover:text-emerald-800' : 'text-slate-800 group-hover:text-indigo-900') }}">
                            {{ $step['title'] }}
                        </div>
                        <div class="text-[9px] leading-none truncate mt-0.5 hidden sm:block {{ $isCurrent ? 'text-indigo-100' : ($isCompleted ? 'text-emerald-700' : 'text-slate-400') }}">
                            {{ $step['desc'] }}
                        </div>
                    </div>
                </div>
                <span class="text-xs shrink-0 opacity-70">{{ $step['icon'] }}</span>
            </a>
        @endforeach
    </div>
</div>
