<x-layouts.app :title="$timetable->name . ' 搬出入計画一覧'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    @php
        $printUrl = route('timetables.logistics.print', array_merge(['timetable' => $timetable], request()->except('preview')));
    @endphp

    <!-- 1. ヘッダーセクション -->
    <x-page-header
        :title="$timetable->name . ' 搬出入計画一覧'"
        icon="🚚"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）における資材の搬出入・中継・夜間保管計画の進捗台帳です。'">
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
                配備済 {{ $stats['planned_equipments'] }} 資材
            </span>
        </x-slot:badges>

        <x-slot:actions>
            <a href="{{ route('equipment-patterns.index') }}"
               class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold shadow-2xs transition whitespace-nowrap shrink-0"
               title="全運行日共通の搬出入ステップ・テンプレートを管理">
                <span>🧩</span>
                <span>搬出入パターンマスター</span>
            </a>
            <a href="{{ $printUrl }}"
               class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition whitespace-nowrap shrink-0"
               title="搬出入指示書兼現場チェックリストをPDFでダウンロード">
                <span>📄</span>
                <span>搬出入指示書PDF</span>
            </a>
            <a href="{{ route('equipments.index') }}"
               class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition whitespace-nowrap shrink-0">
                <span>📦</span>
                <span>機材・備品一覧</span>
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

    <!-- 2. サブナビゲーションバー -->
    <div class="flex items-center space-x-2 border-b border-slate-200 pb-2 overflow-x-auto flex-nowrap text-xs">
        <a href="{{ route('timetables.logistics.index', $timetable) }}"
           class="px-3.5 py-1.5 rounded-lg font-bold bg-indigo-600 text-white shadow-2xs transition whitespace-nowrap flex items-center space-x-1.5">
            <span>🚚</span>
            <span>搬出入計画一覧</span>
            <span class="text-[10px] opacity-80 font-mono">({{ $stats['planned_equipments'] }} / {{ $stats['total_equipments'] }} 配備)</span>
        </a>

        <a href="{{ route('equipment-patterns.index') }}"
           class="px-3.5 py-1.5 rounded-lg font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 transition whitespace-nowrap flex items-center space-x-1.5">
            <span>🧩</span>
            <span>搬出入パターンマスター</span>
            <span class="text-[10px] text-slate-400">↗</span>
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
    <!-- 🚚 搬出入計画一覧（資材別進捗） -->
    <!-- ========================================================================= -->
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
    </div>
</x-layouts.app>
