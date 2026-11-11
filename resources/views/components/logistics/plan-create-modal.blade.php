@props([
    'timetable',
    'equipments' => collect(),
    'locations' => collect(),
    'staffs' => collect(),
    'patterns' => collect(),
    'defaultTargetDate' => '',
])

<!-- 搬出入計画 新規作成モーダル（個別ステップ登録 / パターン一括生成 切替対応） -->
<div id="logistics-plan-create-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" data-action="close-modal"></div>

    <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden transform transition-all max-h-[calc(100vh-2.5rem)] flex flex-col">

            <!-- モーダルヘッダー (固定) -->
            <div class="shrink-0 px-5 py-3.5 bg-white border-b border-slate-100 flex items-center justify-between gap-2">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center text-sm shadow-2xs shrink-0 font-bold">
                        ➕
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-slate-900 truncate">
                            搬出入計画の新規作成
                        </h3>
                        <p class="text-[11px] text-slate-500 truncate">
                            対象資材を選び、個別ステップ追加またはパターンからの一括生成を行います。
                        </p>
                    </div>
                </div>

                <button type="button" data-action="close-modal"
                    class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition text-sm leading-none cursor-pointer shrink-0"
                    title="閉じる">
                    ✕
                </button>
            </div>

            <!-- 登録方法切替タブ & 対象資材選択 (固定上部) -->
            <div class="shrink-0 px-5 pt-3.5 pb-3 bg-slate-50/80 border-b border-slate-200 space-y-3 text-xs">
                @if($equipments->isEmpty())
                    <div class="p-4 bg-white rounded-xl border border-slate-200 text-center space-y-2">
                        <div class="text-2xl">📦</div>
                        <p class="font-bold text-slate-800">資材がまだ登録されていません</p>
                        <p class="text-[11px] text-slate-500">先に資材管理画面から機材・備品を登録してください。</p>
                        <div>
                            <a href="{{ route('timetables.equipments.index', $timetable) }}"
                                class="inline-flex items-center space-x-1 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition">
                                <span>📦</span>
                                <span>機材・備品一覧を開く</span>
                            </a>
                        </div>
                    </div>
                @else
                    <!-- 登録モード切替タブ -->
                    <div class="grid grid-cols-2 gap-1.5 p-1 bg-slate-200/70 rounded-xl" role="tablist" aria-label="登録方法の選択">
                        <button type="button"
                            role="tab"
                            aria-selected="true"
                            data-plan-mode-tab="step"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer bg-indigo-600 text-white shadow-2xs flex items-center justify-center space-x-1.5">
                            <span>📝</span>
                            <span>個別ステップ登録</span>
                        </button>
                        <button type="button"
                            role="tab"
                            aria-selected="false"
                            data-plan-mode-tab="pattern"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer bg-white text-slate-600 hover:text-slate-900 flex items-center justify-center space-x-1.5">
                            <span>⚡</span>
                            <span>パターンから一括生成</span>
                        </button>
                    </div>

                    <!-- 共通: 対象資材選択 -->
                    <div>
                        <label for="new_plan_equipment_select" class="block text-[11px] font-bold text-slate-700 mb-1">
                            対象資材 <span class="text-rose-500">*</span>
                        </label>
                        <select id="new_plan_equipment_select"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition cursor-pointer">
                            <option value="">（対象資材を選択してください）</option>
                            @foreach($equipments as $eq)
                                <option value="{{ $eq->id }}"
                                    data-type="{{ $eq->type }}"
                                    data-quantity="{{ $eq->quantity }}"
                                    data-is-rental="{{ $eq->type_enum === \App\Enums\EquipmentType::Rental ? '1' : '0' }}"
                                    data-plans-count="{{ $eq->plans->count() }}"
                                    data-apply-template-url="{{ route('timetables.logistics.apply-template', [$timetable, $eq]) }}">
                                    {{ $eq->type_enum->icon() }} {{ $eq->name }}（{{ $eq->formatted_quantity }} / {{ $eq->type_enum->shortLabel() }}・登録済 {{ $eq->plans->count() }}件）
                                </option>
                            @endforeach
                        </select>
                        <p id="new_plan_equipment_error" class="hidden mt-1 text-[11px] font-bold text-rose-600">
                            ⚠️ 対象資材を選択してください。
                        </p>
                    </div>
                @endif
            </div>

            @if($equipments->isNotEmpty())
                <!-- パネル1: 個別ステップ登録フォーム -->
                <div id="new-plan-step-panel" class="flex-1 flex flex-col min-h-0">
                    <form id="new-plan-step-form" action="{{ route('timetables.logistics.plans.store', $timetable) }}" method="POST" data-default-target-date="{{ $defaultTargetDate }}" class="flex-1 flex flex-col min-h-0">
                        @csrf
                        <input type="hidden" id="new_plan_equipment_id" name="equipment_id" value="">

                        <div class="overflow-y-auto flex-1 p-5 space-y-4 text-xs">
                            <x-logistics.step-form-fields prefix="new_plan" :locations="$locations" :staffs="$staffs" :default-target-date="$defaultTargetDate" />
                        </div>

                        <div class="shrink-0 px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-2">
                            <button type="button" data-action="close-modal"
                                class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer">
                                キャンセル
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                                計画ステップを追加する
                            </button>
                        </div>
                    </form>
                </div>

                <!-- パネル2: パターンから一括生成フォーム -->
                <div id="new-plan-pattern-panel" class="hidden flex-1 flex flex-col min-h-0">
                    @if($patterns->isEmpty())
                        <div class="overflow-y-auto flex-1 p-5">
                            <div class="p-6 text-center bg-slate-50 rounded-xl border border-slate-200 space-y-3 text-xs">
                                <div class="text-2xl">🧩</div>
                                <div class="font-bold text-slate-700">搬出入パターンがまだ登録されていません</div>
                                <p class="text-[11px] text-slate-500">
                                    搬出入パターン一覧からテンプレートを作成すると、選択した資材へ複数ステップを一括生成できます。
                                </p>
                                <a href="{{ route('equipment-patterns.index') }}"
                                    class="inline-block px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition">
                                    🧩 パターン一覧画面を開く
                                </a>
                            </div>
                        </div>
                        <div class="shrink-0 px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-end">
                            <button type="button" data-action="close-modal"
                                class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer">
                                閉じる
                            </button>
                        </div>
                    @else
                        <form id="new-plan-pattern-form" action="" method="POST" class="flex-1 flex flex-col min-h-0">
                            @csrf

                            <div class="overflow-y-auto flex-1 p-5 space-y-4 text-xs">
                                <!-- 既存ステップ上書き警告（登録済ステップがある資材を選択した場合に表示） -->
                                <div id="new_plan_override_warning" class="hidden p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 space-y-1">
                                    <div class="font-bold flex items-center space-x-1">
                                        <span>⚠️</span>
                                        <span>既存ステップが <span id="new_plan_existing_count">0</span> 件登録されています</span>
                                    </div>
                                    <p class="text-[11px] text-amber-800">
                                        チェックを外すと既存ステップを残したまま末尾に追加生成します。
                                    </p>
                                    <input type="hidden" name="override_existing" value="0">
                                    <label class="flex items-center space-x-2 pt-1 font-bold cursor-pointer">
                                        <input type="checkbox" id="new_plan_override_existing" name="override_existing" value="1" checked
                                            class="rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                                        <span>既存ステップをすべて削除して上書き生成する（推奨）</span>
                                    </label>
                                </div>

                                <!-- 適用パターン選択 -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="new_plan_pattern_id" class="block text-[11px] font-bold text-slate-700">
                                            適用パターン <span class="text-rose-500">*</span>
                                        </label>
                                        <a href="{{ route('equipment-patterns.index') }}" class="text-[11px] text-indigo-600 hover:underline font-bold">
                                            🧩 パターンを編集・追加
                                        </a>
                                    </div>
                                    <select id="new_plan_pattern_id" name="pattern_id" required
                                        class="w-full px-3 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition cursor-pointer">
                                        @foreach($patterns as $pattern)
                                            <option value="{{ $pattern->id }}" data-target-type="{{ $pattern->target_type }}">
                                                {{ $pattern->name }} ({{ $pattern->steps->count() }}ステップ / {{ $pattern->target_type_label }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="shrink-0 px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-2">
                                <button type="button" data-action="close-modal"
                                    class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer">
                                    キャンセル
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                                    パターンから一括生成する
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

