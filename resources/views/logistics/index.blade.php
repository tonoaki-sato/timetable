<x-layouts.app :title="$timetable->name . ' 搬出入計画一覧'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

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

        <x-slot:actions>
            <a href="{{ $printUrl }}"
               id="btn-download-logistics-pdf"
               data-base-print-url="{{ route('timetables.logistics.print', $timetable) }}"
               class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition whitespace-nowrap shrink-0"
               title="画面で選択中の資材を反映した搬出入指示書兼現場チェックリストPDFをダウンロード">
                <span>📄</span>
                <span>搬出入指示書PDF</span>
            </a>

            @can('create', \App\Models\EquipmentLogisticsPlan::class)
                <button type="button"
                    data-action="open-logistics-plan-create"
                    class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition cursor-pointer whitespace-nowrap shrink-0">
                    <span>➕</span>
                    <span>計画の新規作成</span>
                </button>
            @endcan
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

    <!-- 表示資材切り替えフィルターバー -->
    <div class="print:hidden bg-slate-50 border border-slate-200 rounded-xl p-3 flex flex-col md:flex-row md:items-center justify-between gap-3 shadow-2xs">
        <!-- 全選択 / 全解除 & 資材チップ -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-slate-700 flex items-center mr-1">
                <span>🔍 表示資材:</span>
            </span>

            <!-- 全選択 / 全解除 ボタン -->
            <div class="inline-flex rounded border border-slate-300 bg-white p-0.5 mr-1 shrink-0">
                <button type="button" data-action="select-all-equipments"
                    title="全資材を表示"
                    class="px-1.5 py-0.5 text-slate-600 hover:text-indigo-600 hover:bg-slate-100 rounded text-[10px] font-bold transition cursor-pointer">
                    全選択
                </button>
                <button type="button" data-action="deselect-all-equipments"
                    title="全資材を非表示"
                    class="px-1.5 py-0.5 text-slate-600 hover:text-indigo-600 hover:bg-slate-100 rounded text-[10px] font-bold transition cursor-pointer">
                    全解除
                </button>
            </div>

            <!-- 各資材のチップ（ON/OFF トグル） -->
            <div class="flex flex-wrap items-center gap-1.5" id="equipment-filter-chips">
                @foreach($equipments as $equipment)
                    @php
                        $isChipVisible = $selectedEquipmentIds === null || in_array($equipment->id, $selectedEquipmentIds, true);
                    @endphp
                    <button type="button"
                        id="chip-equipment-{{ $equipment->id }}"
                        data-equipment-id="{{ $equipment->id }}"
                        data-equipment-name="{{ $equipment->name }}"
                        class="equipment-chip inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border transition {{ $isChipVisible ? 'bg-white text-slate-800 border-slate-300' : 'opacity-40 bg-slate-100 text-slate-800 border-slate-300' }} hover:border-indigo-400 whitespace-nowrap shrink-0 cursor-pointer">
                        <span class="mr-1 text-[10px] text-emerald-600 font-bold">{{ $isChipVisible ? '✔' : '✕' }}</span>
                        <span>{{ $equipment->type_enum->icon() }} {{ $equipment->name }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- 表示資材数インジケータ -->
        <div class="flex items-center space-x-2 shrink-0">
            <span class="text-xs text-slate-500 font-mono" id="equipment-count-indicator">
                @if($selectedEquipmentIds === null || $visibleEquipmentCount === $equipments->count())
                    全 {{ $equipments->count() }} 資材表示中
                @else
                    全 {{ $equipments->count() }} 資材中 {{ $visibleEquipmentCount }} 資材表示中
                @endif
            </span>
        </div>
    </div>

    <!-- セクションヘッダーバー (見出し + 折りたたみトグル) -->
    <div class="flex items-center justify-between gap-2.5 px-1">
        <h2 class="font-bold text-sm sm:text-base text-slate-900 flex items-center space-x-2">
            <span>🚚</span>
            <span>資材別 搬出入計画一覧 (ステップ構成)</span>
        </h2>

        @if($equipments->isNotEmpty())
            <button type="button"
                data-action="toggle-accordions"
                data-target="#logistics-accordions"
                data-state="open"
                class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer">
                <span data-icon>🔼</span>
                <span data-label>すべて折りたたむ</span>
            </button>
        @endif
    </div>

    <!-- 資材別アコーディオン一覧 -->
    <div id="logistics-accordions" class="space-y-3">
        @forelse($equipments as $equipment)
            @php
                $isEquipmentVisible = $selectedEquipmentIds === null || in_array($equipment->id, $selectedEquipmentIds, true);
            @endphp
            <details id="equipment-{{ $equipment->id }}"
                data-equipment-panel="{{ $equipment->id }}"
                class="equipment-panel-{{ $equipment->id }} bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden group {{ $isEquipmentVisible ? '' : 'hidden' }}"
                {{ $equipment->plans->isNotEmpty() ? 'open' : '' }}>
                <summary class="px-3.5 sm:px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-2 sm:gap-3 cursor-pointer hover:bg-slate-100/70 transition select-none">
                    <div class="flex items-center gap-2 sm:gap-2.5 min-w-0 flex-1 flex-wrap">
                        <!-- 開閉トグルアイコン -->
                        <span class="w-4 shrink-0 text-center text-slate-400 group-open:rotate-90 transition-transform duration-150 inline-block text-xs">▶</span>

                        <!-- 資材アイコン・資材名 -->
                        <div class="flex items-center min-w-0">
                            <span class="mr-1.5 shrink-0 text-base">
                                {{ $equipment->type_enum->icon() }}
                            </span>
                            <span class="text-slate-900 font-bold text-xs sm:text-sm truncate">
                                {{ $equipment->name }}
                            </span>
                        </div>

                        <!-- 数量・区分・保管場所 -->
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-mono font-bold border {{ $equipment->type_enum->badgeClasses() }} shrink-0">
                            {{ $equipment->formatted_quantity }}
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $equipment->type_enum->badgeClasses() }} shrink-0">
                            {{ $equipment->type_enum->shortLabel() }}
                        </span>
                        @if($equipment->storage_location)
                            <span class="text-[11px] text-slate-500 font-medium hidden md:inline truncate">
                                保管: {{ $equipment->storage_location }}
                            </span>
                        @endif
                    </div>

                    <!-- ステータスバッジ・アクションボタン (右寄せ) -->
                    <div class="flex items-center space-x-2 shrink-0 ml-auto">
                        @if($equipment->plans->isEmpty())
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                                ⚠️ ステップ未設定
                            </span>
                        @endif

                        @can('create', \App\Models\EquipmentLogisticsPlan::class)
                            <button type="button"
                                data-action="open-plan-create"
                                data-equipment-id="{{ $equipment->id }}"
                                data-equipment-name="{{ $equipment->name }}"
                                data-equipment-quantity="{{ $equipment->quantity }}"
                                data-equipment-is-rental="{{ $equipment->type_enum === \App\Enums\EquipmentType::Rental ? '1' : '0' }}"
                                data-store-url="{{ route('timetables.logistics.plans.store', $timetable) }}"
                                class="inline-flex items-center space-x-1 px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                title="この資材のステップを追加">
                                <span>➕</span>
                                <span>ステップ追加</span>
                            </button>
                        @endcan
                    </div>
                </summary>

                @if($equipment->plans->isEmpty())
                    <div class="px-4 py-8 text-center text-slate-400 text-xs space-y-2">
                        <span class="text-2xl block mb-1">📋</span>
                        <p>この運行日の搬出入・中継・夜間保管ステップがまだ作成されていません。</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-xs">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold select-none text-xs">
                                <tr>
                                    <th scope="col" class="px-3.5 py-2.5 text-center whitespace-nowrap w-14">順序</th>
                                    <th scope="col" class="px-3.5 py-2.5 whitespace-nowrap w-40">フェーズ</th>
                                    <th scope="col" class="px-3.5 py-2.5 min-w-[160px]">ステップ表示名</th>
                                    <th scope="col" class="px-3.5 py-2.5 whitespace-nowrap w-28">日付</th>
                                    <th scope="col" class="px-3.5 py-2.5 whitespace-nowrap w-28">時間</th>
                                    <th scope="col" class="px-3.5 py-2.5 min-w-[200px]">移動元 ➔ 移動先</th>
                                    <th scope="col" class="px-3.5 py-2.5 text-center whitespace-nowrap w-32">操作</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach($equipment->plans as $plan)
                                    @php
                                        $needsTimeWarning = $plan->needsTimeWarning();
                                    @endphp
                                    <tr data-plan-row
                                        data-plan-id="{{ $plan->id }}"
                                        data-plan='@json($plan->toModalPayload($timetable))'
                                        class="transition {{ $plan->row_highlight_class }}">
                                        <!-- 順序 -->
                                        <td class="px-3.5 py-2.5 text-center font-mono font-bold whitespace-nowrap {{ $needsTimeWarning ? 'border-l-4 border-l-amber-400 text-amber-700' : 'text-slate-400' }}">
                                            #{{ $loop->iteration }}
                                        </td>

                                        <!-- フェーズ + 立会バッジ -->
                                        <td class="px-3.5 py-2.5 whitespace-nowrap">
                                            <div class="inline-flex items-center space-x-1">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border {{ $plan->phase_badge_class }}">
                                                    {{ $plan->phase_label }}
                                                </span>
                                                @if($plan->witness_required)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 font-bold text-[10px] border border-rose-200" title="スタッフ立会必須">
                                                        立会
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- ステップ表示名 (詳細モーダルリンク) -->
                                        <td class="px-3.5 py-2.5">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <button type="button"
                                                    data-action="open-plan-detail"
                                                    class="font-bold text-slate-900 hover:text-indigo-600 hover:underline transition text-left cursor-pointer"
                                                    title="ステップの詳細を表示">
                                                    {{ $plan->title }}
                                                </button>
                                                @if($needsTimeWarning)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-bold shrink-0"
                                                          title="実施日または開始時刻が未指定です（同フェーズ内の末尾に表示されます）">
                                                        ⚠️ 時間未指定
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- 日付 -->
                                        <td class="px-3.5 py-2.5 font-mono text-xs whitespace-nowrap {{ $plan->target_date ? 'text-slate-700' : 'text-slate-400' }}">
                                            {{ $plan->target_date?->format('Y-m-d') ?: '-' }}
                                        </td>

                                        <!-- 時間 -->
                                        <td class="px-3.5 py-2.5 font-mono text-xs whitespace-nowrap {{ $plan->formatted_time_range ? 'text-slate-700' : 'text-slate-400' }}">
                                            {{ $plan->formatted_time_range ?: '-' }}
                                        </td>

                                        <!-- 移動元 ➔ 移動先 -->
                                        <td class="px-3.5 py-2.5 font-mono text-[11px] whitespace-nowrap">
                                            <span class="{{ filled($plan->from_location) || $plan->from_location_id ? 'text-slate-600 font-medium' : 'text-slate-400 italic' }}">
                                                {{ $plan->resolved_from_location ?: '定常保管場所' }}
                                            </span>
                                            <span class="text-indigo-600 font-bold px-1">➔</span>
                                            <span class="{{ filled($plan->to_location) || $plan->to_location_id ? 'text-slate-900 font-bold' : 'text-slate-400 italic' }}">
                                                {{ $plan->resolved_to_location ?: '現地会場' }}
                                            </span>
                                        </td>

                                        <!-- 操作 -->
                                        <td class="px-3.5 py-2.5 text-center whitespace-nowrap space-x-1">
                                            @can('update', $plan)
                                                <button type="button"
                                                    data-action="open-plan-edit"
                                                    class="inline-flex items-center space-x-1 px-2 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded text-xs font-bold transition cursor-pointer"
                                                    title="ステップを編集">
                                                    <span>✏️</span>
                                                    <span>編集</span>
                                                </button>
                                            @endcan

                                            @can('delete', $plan)
                                                <form action="{{ $plan->destroyUrl($timetable) }}" method="POST" class="inline"
                                                    data-confirm="ステップ「{{ $plan->title }}」を削除しますか？">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="inline-flex items-center space-x-1 px-2 py-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded text-xs font-bold transition cursor-pointer"
                                                        title="ステップを削除">
                                                        <span>🗑️</span>
                                                        <span>削除</span>
                                                    </button>
                                                </form>
                                            @endcan

                                            @if(auth()->user()?->cannot('update', $plan) && auth()->user()?->cannot('delete', $plan))
                                                <span class="text-slate-300 text-xs">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </details>
        @empty
            <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-400 space-y-2">
                <span class="text-3xl block">📦</span>
                <p class="font-bold text-slate-700">条件に該当する資材はありません</p>
                <p class="text-xs text-slate-400">資材管理から新しい資材を登録してください。</p>
            </div>
        @endforelse

        @if($equipments->isNotEmpty())
            <div id="logistics-filter-empty-state"
                 class="{{ $visibleEquipmentCount === 0 ? '' : 'hidden ' }}bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-400 space-y-2">
                <span class="text-3xl block">🔍</span>
                <p class="font-bold text-slate-700">表示対象の資材が選択されていません</p>
                <p class="text-xs text-slate-400">上部の「全選択」または資材チップをクリックして表示を切り替えてください。</p>
            </div>
        @endif
    </div>

    <!-- 個別ステップ詳細・追加・編集モーダル -->
    @php
        $defaultTargetDate = $timetable->target_date?->format('Y-m-d') ?? '';
    @endphp
    <x-logistics.step-detail-modal />
    <x-logistics.step-create-modal :locations="$locations" :staffs="$staffs" :default-target-date="$defaultTargetDate" />
    <x-logistics.step-edit-modal :locations="$locations" :staffs="$staffs" :default-target-date="$defaultTargetDate" />
    @can('create', \App\Models\EquipmentLogisticsPlan::class)
        <x-logistics.plan-create-modal
            :timetable="$timetable"
            :equipments="$equipments"
            :locations="$locations"
            :staffs="$staffs"
            :patterns="$patterns"
            :default-target-date="$defaultTargetDate" />
    @endcan
</div>

@vite(['resources/js/logistics.js'])
</x-layouts.app>
