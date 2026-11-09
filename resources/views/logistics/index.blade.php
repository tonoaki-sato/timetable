<x-layouts.app :title="$timetable->name . ' 搬出入計画・パターン管理'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    @php
        $printUrl = route('timetables.logistics.print', array_merge(['timetable' => $timetable], request()->except('preview')));
    @endphp

    <!-- 1. ヘッダーセクション -->
    <x-page-header
        :title="$timetable->name . ' 搬出入計画・パターン管理'"
        icon="🚚"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）における資材の搬出入・中継・夜間保管計画、および共通ステップ構成パターン（テンプレート）を管理します。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>⏱️</span>
                <span>運行情報一覧</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>📅</span>
                <span>{{ $timetable->name }}</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">🚚 搬出入計画</span>
        </x-slot:breadcrumbs>

        <x-slot:badges>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                対象日: {{ $timetable->target_date?->format('Y/m/d') }}
            </span>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0">
                全 {{ $stats['total_equipments'] }} 資材
            </span>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200 shrink-0">
                全 {{ $patterns->count() }} パターン
            </span>
        </x-slot:badges>

        <x-slot:actions>
            @if($tab === 'patterns')
                <button type="button" data-action="open-pattern-modal" data-mode="create"
                        class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs transition cursor-pointer whitespace-nowrap shrink-0">
                    <span>➕</span>
                    <span>新規パターン</span>
                </button>
            @else
                <a href="{{ $printUrl }}"
                   class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition whitespace-nowrap shrink-0"
                   title="搬出入指示書兼現場チェックリストをPDFでダウンロード">
                    <span>📄</span>
                    <span>搬出入指示書PDF</span>
                </a>
            @endif
            <a href="{{ route('equipments.index') }}"
               class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition whitespace-nowrap shrink-0">
                <span>📦</span>
                <span>資材管理（台帳）</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="logistics" />

    <!-- アラートメッセージ -->
    @if (session('status'))
        <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between">
            <span>✅ {{ session('status') }}</span>
        </div>
    @endif

    @if (session('success'))
        <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between">
            <span>✅ {{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
            <div class="font-bold">⚠️ 入力内容に不備があります：</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 2. タブ切り替えバー -->
    <div class="flex items-center space-x-2 border-b border-slate-200 pb-2 overflow-x-auto flex-nowrap text-xs">
        <a href="{{ route('timetables.logistics.index', array_merge(['timetable' => $timetable, 'tab' => 'plans'], request()->except('tab'))) }}"
           class="px-3.5 py-1.5 rounded-lg font-bold transition whitespace-nowrap flex items-center space-x-1.5 {{ $tab !== 'patterns' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-200' }}">
            <span>🚚</span>
            <span>搬出入計画一覧</span>
            <span class="text-[10px] opacity-80 font-mono">({{ $stats['planned_equipments'] }} / {{ $stats['total_equipments'] }} 配備)</span>
        </a>

        <a href="{{ route('timetables.logistics.index', array_merge(['timetable' => $timetable, 'tab' => 'patterns'], request()->except('tab'))) }}"
           class="px-3.5 py-1.5 rounded-lg font-bold transition whitespace-nowrap flex items-center space-x-1.5 {{ $tab === 'patterns' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-200' }}">
            <span>🧩</span>
            <span>搬出入パターン管理</span>
            <span class="text-[10px] opacity-80 font-mono">({{ $patterns->count() }})</span>
        </a>

        <a href="{{ $printUrl }}"
           class="px-3.5 py-1.5 rounded-lg font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 transition whitespace-nowrap flex items-center space-x-1 shrink-0 ml-auto"
           title="搬出入指示書兼現場チェックリストをPDFでプレビュー・ダウンロード">
            <span>📄</span>
            <span>搬出入指示書PDF</span>
            <span class="text-[10px] text-slate-400">↗</span>
        </a>
    </div>

    <!-- ========================================================================= -->
    <!-- タブ1: 🚚 搬出入計画一覧（資材別進捗） -->
    <!-- ========================================================================= -->
    @if($tab !== 'patterns')
        @php
            $progressPercent = $stats['total_steps'] > 0 ? (int) round(($stats['completed_steps'] / $stats['total_steps']) * 100) : 0;
        @endphp

        <!-- KPIサマリーカード -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-slate-200 shadow-2xs space-y-1">
                <span class="text-[11px] font-bold text-slate-500 block">配備資材数</span>
                <div class="flex items-baseline space-x-1">
                    <strong class="text-xl font-bold font-mono text-slate-900">{{ $stats['planned_equipments'] }}</strong>
                    <span class="text-xs text-slate-500">/ {{ $stats['total_equipments'] }} 種</span>
                </div>
            </div>

            <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-slate-200 shadow-2xs space-y-1">
                <span class="text-[11px] font-bold text-slate-500 block">計画ステップ総数</span>
                <div class="flex items-baseline space-x-1">
                    <strong class="text-xl font-bold font-mono text-slate-900">{{ $stats['total_steps'] }}</strong>
                    <span class="text-xs text-slate-500">ステップ</span>
                </div>
            </div>

            <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-slate-200 shadow-2xs space-y-1">
                <span class="text-[11px] font-bold text-slate-500 block">完了ステップ進捗</span>
                <div class="flex items-baseline space-x-1">
                    <strong class="text-xl font-bold font-mono {{ $stats['completed_steps'] === $stats['total_steps'] && $stats['total_steps'] > 0 ? 'text-emerald-600' : 'text-indigo-600' }}">
                        {{ $stats['completed_steps'] }}
                    </strong>
                    <span class="text-xs text-slate-500">/ {{ $stats['total_steps'] }} ({{ $progressPercent }}%)</span>
                </div>
            </div>

            <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-slate-200 shadow-2xs space-y-1">
                <span class="text-[11px] font-bold text-slate-500 block">幹事立会必須</span>
                <div class="flex items-baseline space-x-1">
                    <strong class="text-xl font-bold font-mono {{ $stats['witness_required_steps'] > 0 ? 'text-rose-600' : 'text-slate-700' }}">
                        {{ $stats['witness_required_steps'] }}
                    </strong>
                    <span class="text-xs text-slate-500">ステップ</span>
                </div>
            </div>
        </div>

        <!-- 絞り込みツールバー -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-3">
            <form method="GET" action="{{ route('timetables.logistics.index', $timetable) }}" class="flex items-center justify-between flex-wrap gap-2 text-xs">
                <input type="hidden" name="tab" value="plans">
                <div class="flex items-center space-x-2 flex-wrap gap-y-1.5">
                    <span class="font-bold text-slate-600 flex items-center space-x-1">
                        <span>🔍</span>
                        <span>絞り込み:</span>
                    </span>

                    <!-- 担当班フィルタ -->
                    <select name="lane_id" data-auto-submit class="px-2.5 py-1 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800">
                        <option value="">すべての担当班</option>
                        @foreach($lanes as $lane)
                            <option value="{{ $lane->id }}" {{ $laneFilter === $lane->id ? 'selected' : '' }}>
                                {{ $lane->icon ?: '🏷️' }} {{ $lane->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- 資材区分フィルタ -->
                    <select name="type" data-auto-submit class="px-2.5 py-1 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800">
                        <option value="">すべての区分</option>
                        <option value="committee" {{ $typeFilter === 'committee' ? 'selected' : '' }}>自前備品 (委員会)</option>
                        <option value="rental" {{ $typeFilter === 'rental' ? 'selected' : '' }}>レンタル機材 (イベント会社)</option>
                        <option value="personal" {{ $typeFilter === 'personal' ? 'selected' : '' }}>私物 (個人持ち込み)</option>
                    </select>
                </div>

                @if($laneFilter || $typeFilter)
                    <a href="{{ route('timetables.logistics.index', ['timetable' => $timetable, 'tab' => 'plans']) }}"
                       class="text-xs text-rose-600 hover:text-rose-800 font-bold transition flex items-center space-x-1">
                        <span>✕</span>
                        <span>フィルタ解除</span>
                    </a>
                @endif
            </form>
        </div>

        <!-- 資材別 計画ステップ一覧 -->
        <div class="space-y-3">
            @forelse($equipments as $equipment)
                @php
                    $plans = $equipment->plans;
                    $completedCount = $plans->where('status', 'completed')->count();
                    $hasShortage = $plans->isEmpty();
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 space-y-3 hover:border-slate-300 transition">
                    <!-- 資材ヘッダー行 -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div class="flex items-center space-x-2.5 flex-wrap">
                            <span class="text-xl">
                                {{ $equipment->isCommittee() ? '🏛️' : ($equipment->isRental() ? '🏢' : '🎒') }}
                            </span>
                            <a href="{{ route('timetables.logistics.equipments.show', [$timetable, $equipment]) }}"
                               class="text-sm sm:text-base font-bold text-slate-900 hover:text-indigo-600 transition">
                                {{ $equipment->name }}
                            </a>
                            <span class="px-2 py-0.5 rounded-full text-xs font-mono font-bold {{ $equipment->isCommittee() ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($equipment->isRental() ? 'bg-sky-50 text-sky-800 border border-sky-200' : 'bg-amber-50 text-amber-800 border border-amber-200') }}">
                                {{ $equipment->formatted_quantity }}
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $equipment->isCommittee() ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($equipment->isRental() ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                {{ $equipment->type_label }}
                            </span>
                            @if($equipment->lane)
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $equipment->lane->icon ?: '🏷️' }} {{ $equipment->lane->name }}
                                </span>
                            @endif
                            @if($equipment->storage_location)
                                <span class="text-[11px] text-slate-500 font-medium">
                                    保管: {{ $equipment->storage_location }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center space-x-2 shrink-0 self-end sm:self-center">
                            @if($plans->isNotEmpty())
                                <span class="text-xs font-mono font-bold px-2.5 py-1 rounded-lg {{ $completedCount === $plans->count() ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200' }}">
                                    {{ $completedCount }}/{{ $plans->count() }} ステップ完了
                                </span>
                            @else
                                <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 border border-amber-200">
                                    ⚠️ ステップ未設定
                                </span>
                            @endif

                            <a href="{{ route('timetables.logistics.equipments.show', [$timetable, $equipment]) }}"
                               class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition inline-flex items-center space-x-1 shadow-2xs whitespace-nowrap">
                                <span>✏️</span>
                                <span>計画・ステップ編集</span>
                                <span>→</span>
                            </a>
                        </div>
                    </div>

                    <!-- ステップ一覧チップス -->
                    @if($plans->isNotEmpty())
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 pt-1">
                            @foreach($plans as $plan)
                                <div class="p-2.5 rounded-xl border {{ $plan->isCompleted() ? 'border-emerald-200 bg-emerald-50/20' : 'border-slate-100 bg-slate-50/70' }} space-y-1.5 text-xs">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold border
                                            @if($plan->day_phase === 'pre_day') bg-purple-50 text-purple-700 border-purple-200
                                            @elseif($plan->day_phase === 'day1_morning') bg-sky-50 text-sky-700 border-sky-200
                                            @elseif($plan->day_phase === 'day1_evening') bg-indigo-50 text-indigo-700 border-indigo-200
                                            @elseif($plan->day_phase === 'day2_morning') bg-teal-50 text-teal-700 border-teal-200
                                            @elseif($plan->day_phase === 'day2_evening') bg-amber-50 text-amber-700 border-amber-200
                                            @else bg-slate-100 text-slate-700 border-slate-200 @endif">
                                            {{ $plan->phase_label }}
                                        </span>

                                        <span class="text-[10px] font-bold {{ $plan->isCompleted() ? 'text-emerald-700' : ($plan->status === 'in_progress' ? 'text-amber-700 font-bold' : 'text-slate-500') }}">
                                            @if($plan->isCompleted())
                                                ✅ 完了
                                            @elseif($plan->status === 'in_progress')
                                                🏃 進行中
                                            @else
                                                ⚪ 計画中
                                            @endif
                                        </span>
                                    </div>

                                    <div class="font-bold text-slate-900 truncate" title="{{ $plan->title }}">
                                        {{ $plan->title }}
                                    </div>

                                    <div class="text-[11px] text-slate-600 flex items-center space-x-1 truncate">
                                        <span class="text-slate-400">場所:</span>
                                        <span class="truncate">{{ $plan->from_location ?: '定常場所' }}</span>
                                        <span>➔</span>
                                        <span class="truncate font-bold text-slate-800">{{ $plan->to_location ?: '現場' }}</span>
                                    </div>

                                    @if($plan->witness_required)
                                        <div class="text-[10px] text-rose-600 font-bold flex items-center space-x-1">
                                            <span>⚠️</span>
                                            <span>幹事立会必須</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 bg-slate-50 rounded-xl border border-dashed border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                            <span class="text-xs text-slate-500">
                                💡 この運行日の搬出入・中継・夜間保管ステップがまだ作成されていません。
                            </span>
                            <a href="{{ route('timetables.logistics.equipments.show', [$timetable, $equipment]) }}"
                               class="text-xs font-bold text-indigo-700 hover:text-indigo-900 underline flex items-center space-x-1">
                                <span>⚡ パターン適用・ステップ追加画面を開く</span>
                                <span>→</span>
                            </a>
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-12 text-center bg-white rounded-2xl border border-dashed border-slate-300 text-slate-500 space-y-2">
                    <span class="text-3xl block">📦</span>
                    <p class="font-bold text-slate-700">条件に該当する資材はありません</p>
                    <p class="text-xs text-slate-400">絞り込み条件を変更するか、資材管理から新しい資材を登録してください。</p>
                </div>
            @endforelse
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- タブ2: 🧩 搬出入パターン管理（テンプレート一覧） -->
    <!-- ========================================================================= -->
    @if($tab === 'patterns')
        <div class="space-y-4">
            @forelse($patterns as $pattern)
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2 flex-wrap">
                                <span class="font-bold text-slate-900 text-base">{{ $pattern->name }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    {{ $pattern->target_type_label }}
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-mono font-bold bg-slate-100 text-slate-700">
                                    {{ $pattern->steps->count() }} ステップ
                                </span>
                            </div>
                            @if($pattern->description)
                                <p class="text-xs text-slate-500">{{ $pattern->description }}</p>
                            @endif
                        </div>

                        <div class="flex items-center space-x-2 shrink-0 self-end sm:self-center">
                            <button type="button"
                                    data-action="open-pattern-modal"
                                    data-mode="edit"
                                    data-pattern="{{ json_encode($pattern) }}"
                                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition cursor-pointer inline-flex items-center space-x-1">
                                <span>✏️</span>
                                <span>編集</span>
                            </button>
                            <form method="POST" action="{{ route('logistics.patterns.destroy', $pattern) }}" class="inline"
                                  data-confirm="パターン「{{ $pattern->name }}」を削除しますか？&#10;（すでに各資材に生成済みの計画ステップは消えません）">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-2.5 py-1.5 bg-white hover:bg-rose-50 border border-rose-200 text-rose-600 rounded-lg text-xs font-bold transition cursor-pointer inline-flex items-center space-x-1">
                                    <span>🗑️</span>
                                    <span>削除</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- パターン内ステップ構成テーブル -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-xs divide-y divide-slate-100">
                            <thead class="bg-slate-50 text-slate-600 font-bold">
                                <tr>
                                    <th class="py-2 px-3 w-12 text-center">順序</th>
                                    <th class="py-2 px-3 w-28">フェーズ</th>
                                    <th class="py-2 px-3 w-32">種別</th>
                                    <th class="py-2 px-3">ステップ表示名</th>
                                    <th class="py-2 px-3">移動元 ➔ 移動先</th>
                                    <th class="py-2 px-3 w-28">作業主体</th>
                                    <th class="py-2 px-3 w-20 text-center">立会</th>
                                    <th class="py-2 px-3">注意事項</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($pattern->steps as $step)
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="py-2 px-3 text-center font-mono text-slate-400 font-bold">
                                            {{ $loop->iteration }}
                                        </td>
                                        <td class="py-2 px-3">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border
                                                @if($step->day_phase === 'pre_day') bg-purple-50 text-purple-700 border-purple-200
                                                @elseif($step->day_phase === 'day1_morning') bg-sky-50 text-sky-700 border-sky-200
                                                @elseif($step->day_phase === 'day1_evening') bg-indigo-50 text-indigo-700 border-indigo-200
                                                @elseif($step->day_phase === 'day2_morning') bg-teal-50 text-teal-700 border-teal-200
                                                @elseif($step->day_phase === 'day2_evening') bg-amber-50 text-amber-700 border-amber-200
                                                @else bg-slate-50 text-slate-700 border-slate-200 @endif">
                                                {{ $step->phase_label }}
                                            </span>
                                        </td>
                                        <td class="py-2 px-3 font-medium text-slate-800">
                                            {{ $step->action_label }}
                                        </td>
                                        <td class="py-2 px-3 font-bold text-slate-900">
                                            {{ $step->title }}
                                        </td>
                                        <td class="py-2 px-3 text-slate-700 font-mono text-[11px]">
                                            <span class="text-slate-500">{{ $step->fromLocationModel?->name ?: '（資材定常保管場所）' }}</span>
                                            <span class="text-indigo-600 font-bold px-1">➔</span>
                                            <span class="font-bold text-slate-800">{{ $step->toLocationModel?->name ?: '（現地指定）' }}</span>
                                        </td>
                                        <td class="py-2 px-3 text-slate-600">
                                            {{ $step->operator_type_label }}
                                        </td>
                                        <td class="py-2 px-3 text-center">
                                            @if($step->witness_required)
                                                <span class="px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 font-bold text-[10px] border border-rose-200">必須</span>
                                            @else
                                                <span class="text-slate-300">-</span>
                                            @endif
                                        </td>
                                        <td class="py-2 px-3 text-slate-500 max-w-xs truncate">
                                            {{ $step->notes ?: '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center bg-white rounded-2xl border border-dashed border-slate-300 text-slate-500 space-y-3">
                    <span class="text-3xl block">🧩</span>
                    <p class="font-bold text-slate-700">登録されている搬出入パターンはありません</p>
                    <p class="text-xs text-slate-400">「新規パターン」ボタンから登録するか、資材詳細画面で組んだステップをパターンとして保存してください。</p>
                    <button type="button" data-action="open-pattern-modal" data-mode="create"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-sm hover:bg-indigo-500 transition cursor-pointer">
                        ➕ 新規パターンを作成する
                    </button>
                </div>
            @endforelse
        </div>
    @endif

</div>

<!-- ========================================================================= -->
<!-- パターン新規作成・編集モーダル (CSP準拠) -->
<!-- ========================================================================= -->
<div id="modal-pattern" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-4xl w-full p-5 sm:p-6 shadow-2xl border border-slate-200 text-left space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 id="modal-pattern-title" class="text-sm sm:text-base font-bold text-slate-900 flex items-center space-x-2">
                <span>🧩</span>
                <span>新規パターンの作成</span>
            </h3>
            <button type="button" data-action="close-pattern-modal" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">✕</button>
        </div>

        <form id="form-pattern" method="POST" action="{{ route('logistics.patterns.store') }}" class="space-y-4">
            @csrf
            <div id="form-pattern-method"></div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                    <label for="pattern_name" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">パターン名 <span class="text-rose-500">*</span></label>
                    <input type="text" id="pattern_name" name="name" required
                           placeholder="例: パターンA: 自前備品標準、パターンB: テント一式"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="pattern_target_type" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">主な対象区分 <span class="text-rose-500">*</span></label>
                    <select id="pattern_target_type" name="target_type" required
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach(\App\Models\EquipmentLogisticsPattern::TARGET_TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="pattern_description" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">パターンの説明（任意）</label>
                <input type="text" id="pattern_description" name="description"
                       placeholder="例: 前日倉庫出し ➔ 本部テント設置 ➔ 2日目夕方撤収"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- ステップ行コンテナ -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-800">構成ステップ一覧 <span class="text-rose-500">*</span></label>
                    <button type="button" data-action="add-pattern-step"
                            class="px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition cursor-pointer">
                        ＋ ステップ行を追加
                    </button>
                </div>

                <div id="pattern-steps-container" class="space-y-3"></div>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" data-action="close-pattern-modal"
                        class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                    キャンセル
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                    パターンを保存する
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ステップ行のHTMLテンプレート（JSから複製利用） -->
<template id="tpl-pattern-step-row">
    <div class="pattern-step-row p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-2.5 text-xs">
        <div class="flex items-center justify-between gap-2 border-b border-slate-200/60 pb-2">
            <span class="font-bold text-slate-700 step-row-number">ステップ #1</span>
            <button type="button" data-action="remove-pattern-step" class="text-rose-600 hover:text-rose-800 font-bold text-xs cursor-pointer">
                ✕ この行を削除
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">フェーズ *</label>
                <select data-field="day_phase" required class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded text-xs font-bold">
                    @foreach(\App\Models\EquipmentLogisticsPlan::DAY_PHASES as $key => $lbl)
                        <option value="{{ $key }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">アクション種別 *</label>
                <select data-field="action_type" required class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded text-xs font-bold">
                    @foreach(\App\Models\EquipmentLogisticsPlan::ACTION_TYPES as $key => $lbl)
                        <option value="{{ $key }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">作業主体 *</label>
                <select data-field="operator_type" required class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded text-xs font-bold">
                    <option value="committee">👥 委員会スタッフ</option>
                    <option value="event_company">🏢 イベント会社</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">ステップ表示名 *</label>
            <input type="text" data-field="title" required placeholder="例: 前日倉庫出し・現場へ搬送、夕方夜間保管場所へ移動"
                   class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded text-xs">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <div class="p-2.5 rounded-lg bg-white border border-slate-200 space-y-1">
                <label class="block text-[10px] font-bold text-slate-600 flex items-center space-x-1">
                    <span>📍</span>
                    <span>移動元拠点 (From)</span>
                </label>
                <select data-field="from_location_id" class="w-full px-2 py-1.5 bg-slate-50 border border-slate-300 rounded text-xs font-medium">
                    <option value="">未指定（現場または各資材設定を参照）</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="p-2.5 rounded-lg bg-white border border-slate-200 space-y-1">
                <label class="block text-[10px] font-bold text-slate-600 flex items-center space-x-1">
                    <span>🏁</span>
                    <span>移動先拠点 (To)</span>
                </label>
                <select data-field="to_location_id" class="w-full px-2 py-1.5 bg-slate-50 border border-slate-300 rounded text-xs font-medium">
                    <option value="">未指定（現場または各資材設定を参照）</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 pt-1">
            <div class="flex-1 w-full">
                <input type="text" data-field="notes" placeholder="申し送り・注意事項メモ（任意）"
                       class="w-full px-2 py-1 bg-white border border-slate-300 rounded text-xs">
            </div>
            <div class="shrink-0">
                <label class="inline-flex items-center space-x-1.5 cursor-pointer">
                    <input type="checkbox" data-field="witness_required" value="1" class="rounded border-slate-300 text-indigo-600">
                    <span class="font-bold text-slate-700 text-xs">👁️ 幹事・スタッフ立会必須</span>
                </label>
            </div>
        </div>
    </div>
</template>

@vite(['resources/js/equipments.js'])
</x-layouts.app>
