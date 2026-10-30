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

<div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs mb-6">
    <div class="flex items-center justify-between mb-3 pb-2.5 border-b border-slate-100">
        <div class="flex items-center space-x-2">
            <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">🏮</span>
            <h3 class="text-xs sm:text-sm font-bold text-slate-900">
                まつり運行準備ステップガイド
                @if($timetable)
                    <span class="text-xs font-normal text-slate-500 ml-2">【対象運行日: {{ $timetable->name }}】</span>
                @endif
            </h3>
        </div>
        <span class="text-[11px] font-mono font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">
            Step {{ $currentStep }} / 4
        </span>
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
</div>

