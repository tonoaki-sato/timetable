<x-layouts.app :title="$timetable->name . ' - 搬出入タイムライン'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー (共通 x-page-header) -->
    <x-page-header
        :title="$timetable->name . ' 搬出入タイムライン'"
        icon="🚚"
        description="資材・機材の搬出入計画をガントチャートで俯瞰し、当日の進行状況を把握します。">
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
            <span class="text-slate-700 font-bold truncate">🚚 搬出入タイムライン</span>
        </x-slot:breadcrumbs>
    </x-page-header>

    <!-- 運行日コンテキストバー (日程切替・サブナビ) -->
    <x-timetable-context-bar :timetable="$timetable" active="logistics-timeline" />

    <!-- フィルターバー -->
    <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-2xs flex flex-col lg:flex-row lg:items-center justify-between gap-3 print:hidden">
        <div class="flex flex-wrap items-center gap-2 flex-1">
            <span class="text-xs font-bold text-slate-700">🔍 絞り込み:</span>

            <!-- キーワード検索 -->
            <input type="text" id="logistics-timeline-search" placeholder="資材名・保管場所で検索..."
                class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 w-52">

            <!-- 区分フィルター -->
            <div class="flex flex-wrap items-center gap-1.5">
                <button type="button" data-filter-type="all"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer bg-indigo-600 text-white border-indigo-600">
                    全資材 ({{ $totalEquipmentCount }})
                </button>
                <button type="button" data-filter-type="committee"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer bg-white text-slate-700 border-slate-200 hover:bg-slate-50">
                    委員会管理 ({{ $committeeCount }})
                </button>
                <button type="button" data-filter-type="rental"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer bg-white text-slate-700 border-slate-200 hover:bg-slate-50">
                    レンタル ({{ $rentalCount }})
                </button>
                <button type="button" data-filter-type="personal"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer bg-white text-slate-700 border-slate-200 hover:bg-slate-50">
                    私物 ({{ $personalCount }})
                </button>
            </div>
        </div>

        <!-- 進捗ステータスフィルター -->
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="text-xs font-bold text-slate-600 mr-1">進捗:</span>
            <button type="button" data-filter-status="all"
                class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer bg-slate-800 text-white border-slate-800">
                全て
            </button>
            <button type="button" data-filter-status="planned"
                class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer bg-white text-slate-700 border-slate-200 hover:bg-slate-50">
                計画中 ({{ $plannedCount }})
            </button>
            <button type="button" data-filter-status="in_progress"
                class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer bg-white text-slate-700 border-slate-200 hover:bg-slate-50">
                進行中 ({{ $inProgressCount }})
            </button>
            <button type="button" data-filter-status="completed"
                class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer bg-white text-slate-700 border-slate-200 hover:bg-slate-50">
                完了 ({{ $completedCount }})
            </button>
        </div>
    </div>

    <!-- 日付別タイムラインブロック -->
    @forelse($dateBlocks as $block)
        <section class="space-y-2">
            <!-- 日付ブロック見出し -->
            <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-100 border border-slate-300 rounded-xl px-3.5 py-2 shadow-2xs">
                <div class="flex items-center space-x-2">
                    <h2 class="text-sm font-bold text-slate-900 flex items-center space-x-1.5">
                        <span>📅</span>
                        <span>{{ $block['date_formatted'] }}</span>
                    </h2>
                    <span class="px-2 py-0.5 rounded text-[11px] font-bold border {{ $block['is_timetable_date'] ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-700 border-slate-300' }}">
                        {{ $block['label'] }}
                    </span>
                </div>
                <div class="text-xs font-mono font-bold text-slate-600">
                    計画: {{ $block['plans_count'] }}件
                </div>
            </div>

            <!-- ガントチャートテーブル -->
            <div class="bg-white border border-slate-300 rounded-xl shadow-xs overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr class="bg-slate-800 text-white select-none">
                            <!-- 資材・機材情報列（固定） -->
                            <th scope="col" class="sticky left-0 z-20 bg-slate-900 border-r border-slate-700 p-2.5 w-[250px] min-w-[250px]">
                                機材・資材情報
                            </th>

                            <!-- 30分スロット列 -->
                            @foreach($block['time_slots'] as $slot)
                                <th scope="col" class="border-r border-slate-700 p-2 text-center font-mono text-[11px] min-w-[75px] w-[75px]">
                                    {{ $slot['time'] }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($equipments as $equipment)
                            @php
                                $meta = $equipmentMeta[$equipment->id] ?? [];
                                $typeBadgeClass = match ($equipment->type_enum) {
                                    \App\Enums\EquipmentType::Committee => 'bg-sky-100 text-sky-800 border-sky-300',
                                    \App\Enums\EquipmentType::Rental => 'bg-purple-100 text-purple-800 border-purple-300',
                                    \App\Enums\EquipmentType::Personal => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                };
                                $typeShortLabel = match ($equipment->type_enum) {
                                    \App\Enums\EquipmentType::Committee => '委員会',
                                    \App\Enums\EquipmentType::Rental => 'レンタル',
                                    \App\Enums\EquipmentType::Personal => '私物',
                                };
                            @endphp
                            <tr class="equipment-row hover:bg-slate-50/80 transition"
                                data-equipment-name="{{ $equipment->name }}"
                                data-equipment-storage="{{ $equipment->storage_location_name ?? '' }}"
                                data-equipment-type="{{ $equipment->type_enum->value }}"
                                data-has-planned="{{ ($meta['has_planned'] ?? false) ? '1' : '0' }}"
                                data-has-in-progress="{{ ($meta['has_in_progress'] ?? false) ? '1' : '0' }}"
                                data-has-completed="{{ ($meta['has_completed'] ?? false) ? '1' : '0' }}">

                                <!-- 機材・資材情報セル (Sticky) -->
                                <td class="sticky left-0 z-10 bg-white border-r border-slate-300 px-2.5 py-2 align-middle shadow-xs">
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between gap-1.5">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold border shrink-0 {{ $typeBadgeClass }}">
                                                    {{ $typeShortLabel }}
                                                </span>
                                                <a href="{{ route('equipments.index', ['keyword' => $equipment->name]) }}"
                                                   class="font-bold text-slate-900 hover:text-indigo-600 transition truncate"
                                                   title="{{ $equipment->name }}">
                                                    {{ $equipment->name }}
                                                </a>
                                            </div>
                                            <span class="font-mono text-slate-600 text-[11px] font-medium shrink-0">
                                                {{ $equipment->formatted_quantity }}
                                            </span>
                                        </div>

                                        <div class="flex items-center justify-between text-[10px] text-slate-500">
                                            <span class="truncate" title="保管場所: {{ $equipment->storage_location_name ?: '未指定' }}">
                                                📍 {{ $equipment->storage_location_name ?: '保管場所未指定' }}
                                            </span>
                                            @if(($meta['unspecified_time_count'] ?? 0) > 0)
                                                <span class="text-amber-700 font-bold shrink-0 ml-1" title="時間未定の計画あり">
                                                    ⚠️ 未定{{ $meta['unspecified_time_count'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- 各スロットセル -->
                                @foreach($block['time_slots'] as $sIdx => $slot)
                                    @php
                                        $cell = $block['grid'][$equipment->id][$sIdx] ?? ['type' => 'empty'];
                                    @endphp

                                    @if($cell['type'] === 'plan')
                                        @php
                                            $plan = $cell['plan'];
                                        @endphp
                                        <td colspan="{{ $cell['colspan'] }}" class="p-1 border-r border-slate-300 align-middle">
                                            <a href="{{ route('timetables.logistics.index', $timetable) }}"
                                               class="block p-1.5 rounded-lg border {{ $cell['badge_bg'] }} text-xs shadow-2xs hover:shadow-md transition space-y-0.5"
                                               title="{{ $plan->title }} ({{ $plan->formatted_time_range }}) : {{ $plan->resolved_from_location }} → {{ $plan->resolved_to_location }}">
                                                <div class="font-bold truncate leading-tight flex items-center justify-between gap-1">
                                                    <span class="truncate">
                                                        {{ $plan->action_type_enum->icon() }} {{ $plan->title }}
                                                    </span>
                                                    <span class="text-[9px] font-mono shrink-0 opacity-80">
                                                        {{ $plan->formatted_time_range }}
                                                    </span>
                                                </div>
                                                <div class="text-[10px] flex items-center justify-between gap-1 opacity-85">
                                                    <span class="truncate">
                                                        {{ $plan->resolved_from_location }} → {{ $plan->resolved_to_location }}
                                                    </span>
                                                    <span class="shrink-0 font-bold">
                                                        @if($plan->isCompleted())
                                                            ✅ 完了
                                                        @elseif($plan->status_enum === \App\Enums\EquipmentPlanStatus::InProgress)
                                                            ⏳ 進行中
                                                        @elseif($plan->witness_required)
                                                            👁️ 立会要
                                                        @else
                                                            {{ $plan->operator_type_enum === \App\Enums\EquipmentOperatorType::EventCompany ? '業者' : '委員会' }}
                                                        @endif
                                                    </span>
                                                </div>
                                            </a>
                                        </td>
                                    @elseif($cell['type'] === 'empty')
                                        <td class="p-1 border-r border-slate-200 text-center align-middle">
                                            <span class="text-slate-300 font-mono text-[10px] select-none">-</span>
                                        </td>
                                    @elseif($cell['type'] === 'skip')
                                        <!-- colspan により結合されているためスキップ -->
                                    @endif
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($block['time_slots']) + 1 }}" class="p-6 text-center text-slate-500">
                                    登録されている資材・機材がありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 当該日付内の時間未定計画（start_time が未設定） -->
            @if($block['unspecified_time_plans']->isNotEmpty())
                <div class="bg-amber-50/70 border border-amber-200 rounded-xl px-3.5 py-2.5 text-xs space-y-1.5">
                    <div class="font-bold text-amber-900 flex items-center space-x-1.5">
                        <span>⏰</span>
                        <span>{{ $block['date_formatted'] }} の時間未指定計画（{{ $block['unspecified_time_plans']->count() }}件）</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($block['unspecified_time_plans'] as $uPlan)
                            <a href="{{ route('timetables.logistics.index', $timetable) }}"
                               class="inline-flex items-center space-x-1.5 px-2.5 py-1 bg-white border border-amber-300 rounded-lg text-slate-800 hover:border-indigo-400 transition shadow-2xs">
                                <span class="font-bold">{{ $uPlan->equipment?->name }}</span>
                                <span class="text-slate-400">|</span>
                                <span>{{ $uPlan->action_type_enum->icon() }} {{ $uPlan->title }}</span>
                                <span class="text-[10px] text-slate-500">({{ $uPlan->resolved_from_location }} → {{ $uPlan->resolved_to_location }})</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    @empty
        <div class="bg-white border border-slate-200 rounded-xl p-8 text-center text-slate-500 text-sm">
            表示対象の運行日程または搬出入計画がありません。
        </div>
    @endforelse

    <!-- 日付未指定の計画がある場合 -->
    @if($unspecifiedDatePlans->isNotEmpty())
        <section class="bg-amber-50 border border-amber-300 rounded-xl p-3.5 space-y-2">
            <h3 class="text-xs font-bold text-amber-900 flex items-center space-x-1.5">
                <span>⚠️</span>
                <span>実施日未指定の搬出入計画（{{ $unspecifiedDatePlans->count() }}件）</span>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 text-xs">
                @foreach($unspecifiedDatePlans as $uDatePlan)
                    <a href="{{ route('timetables.logistics.index', $timetable) }}"
                       class="p-2 bg-white border border-amber-200 rounded-lg hover:border-indigo-400 transition space-y-0.5">
                        <div class="font-bold text-slate-900 truncate">{{ $uDatePlan->equipment?->name }} : {{ $uDatePlan->title }}</div>
                        <div class="text-[11px] text-slate-600 truncate">
                            {{ $uDatePlan->resolved_from_location }} → {{ $uDatePlan->resolved_to_location }}
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

</div>

@vite(['resources/js/logistics-timeline.js'])
</x-layouts.app>
