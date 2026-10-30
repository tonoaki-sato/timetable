@props(['plan', 'compact' => false])

@php
    $warning = $plan->checkToLocationAvailability();
    $statusColors = [
        'planned' => 'bg-slate-100 text-slate-700 border-slate-300',
        'in_progress' => 'bg-amber-100 text-amber-800 border-amber-300 animate-pulse',
        'completed' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
        'cancelled' => 'bg-rose-100 text-rose-700 border-rose-300 line-through',
    ];

    $phaseColors = [
        'pre_day' => 'bg-purple-50 text-purple-700 border-purple-200',
        'day1_morning' => 'bg-sky-50 text-sky-700 border-sky-200',
        'day1_evening' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'day2_morning' => 'bg-teal-50 text-teal-700 border-teal-200',
        'day2_evening' => 'bg-amber-50 text-amber-700 border-amber-200',
        'post_day' => 'bg-rose-50 text-rose-700 border-rose-200',
        'other' => 'bg-slate-50 text-slate-700 border-slate-200',
    ];
@endphp

<div class="relative bg-white border rounded-xl p-3 shadow-xs hover:shadow-md transition text-xs space-y-2 {{ $plan->status === 'completed' ? 'border-emerald-200 bg-emerald-50/20' : 'border-slate-200' }}">
    <!-- ヘッダー行: フェーズ & 時刻 & ステータス -->
    <div class="flex items-center justify-between gap-1 flex-wrap">
        <div class="flex items-center space-x-1.5 flex-wrap">
            <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $phaseColors[$plan->day_phase] ?? 'bg-slate-50 text-slate-700 border-slate-200' }}">
                {{ $plan->phase_label }}
            </span>
            @if($plan->start_time)
                <span class="font-mono text-slate-600 font-bold text-[11px]">
                    ⏱️ {{ substr($plan->start_time, 0, 5) }}@if($plan->end_time)〜{{ substr($plan->end_time, 0, 5) }}@endif
                </span>
            @endif
        </div>

        <!-- クイックステータス切替 -->
        <form method="POST" action="{{ route('equipment-plans.update-status', $plan) }}" class="inline">
            @csrf
            @method('PATCH')
            @if($plan->status === 'completed')
                <input type="hidden" name="status" value="planned">
                <button type="submit" title="未完了に戻す" class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-bold border bg-emerald-100 text-emerald-800 border-emerald-300 hover:bg-emerald-200 cursor-pointer transition">
                    <span>✓ 完了</span>
                </button>
            @elseif($plan->status === 'in_progress')
                <input type="hidden" name="status" value="completed">
                <button type="submit" title="完了にする" class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-bold border bg-amber-100 text-amber-800 border-amber-300 hover:bg-emerald-100 hover:text-emerald-800 cursor-pointer transition">
                    <span>▶ 進行中</span>
                </button>
            @else
                <input type="hidden" name="status" value="completed">
                <button type="submit" title="ワンクリックで完了" class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-bold border bg-slate-100 text-slate-600 border-slate-300 hover:bg-emerald-100 hover:text-emerald-800 hover:border-emerald-300 cursor-pointer transition">
                    <span>○ 計画中</span>
                </button>
            @endif
        </form>
    </div>

    <!-- タイトル & 数量 -->
    <div class="font-bold text-slate-800 text-xs flex items-center justify-between">
        <span>{{ $plan->title }}</span>
        @if($plan->quantity)
            <span class="text-[11px] font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-1.5 py-0.2 rounded shrink-0 ml-1">
                {{ $plan->quantity }}{{ $plan->equipment->unit ?? '個' }}
            </span>
        @endif
    </div>

    <!-- ルート: 移動元 ➔ 移動先 -->
    <div class="flex items-center space-x-1.5 text-[11px] text-slate-600 bg-slate-50 p-1.5 rounded-lg border border-slate-100">
        <span class="text-slate-500 shrink-0">🛫</span>
        <span class="font-medium truncate text-slate-700">{{ $plan->from_location ?: '（指定なし）' }}</span>
        <span class="text-slate-400 font-bold shrink-0">➔</span>
        <span class="text-slate-500 shrink-0">🛬</span>
        <span class="font-bold truncate text-slate-900">{{ $plan->to_location ?: '（指定なし）' }}</span>
    </div>

    <!-- 貸切期間・整合性アラート（該当時） -->
    @if($warning)
        <div class="p-1.5 rounded-lg bg-amber-50 border border-amber-300 text-amber-900 text-[10px] flex items-start space-x-1.5">
            <span class="shrink-0 text-amber-600 font-bold">⚠️</span>
            <span class="leading-tight">{{ $warning['message'] }}</span>
        </div>
    @endif

    <!-- フッター情報: 作業主体、立会、申し送り -->
    <div class="flex items-center justify-between gap-1 pt-1 border-t border-slate-100 text-[10px] text-slate-500">
        <div class="flex items-center space-x-1 flex-wrap">
            @if($plan->operator_type === 'event_company')
                <span class="bg-indigo-100 text-indigo-800 font-bold px-1.5 py-0.2 rounded">🏢 イベント会社作業</span>
            @else
                <span class="bg-slate-200 text-slate-700 font-bold px-1.5 py-0.2 rounded">👥 委員会スタッフ</span>
            @endif

            @if($plan->witness_required)
                <span class="bg-rose-100 text-rose-800 border border-rose-300 font-bold px-1.5 py-0.2 rounded flex items-center space-x-0.5">
                    <span>👁️ 立会必須</span>
                    @if($plan->witnessStaff)
                        <span>({{ $plan->witnessStaff->name }})</span>
                    @endif
                </span>
            @endif
        </div>

        @if($plan->notes)
            <span class="text-slate-500 truncate max-w-[140px]" title="{{ $plan->notes }}">
                💬 {{ $plan->notes }}
            </span>
        @endif
    </div>
</div>

