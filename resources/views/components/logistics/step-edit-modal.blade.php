@props(['locations' => collect(), 'staffs' => collect()])

<!-- 個別搬出入ステップ編集モーダル -->
<div id="plan-edit-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" data-action="close-modal"></div>

    <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden transform transition-all max-h-[calc(100vh-2.5rem)] flex flex-col">

            <!-- モーダルヘッダー (固定) -->
            <div class="shrink-0 px-5 py-3.5 bg-white border-b border-slate-100 flex items-center justify-between gap-2">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center text-sm shadow-2xs shrink-0 font-bold">
                        ✏️
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-slate-900 truncate flex items-center space-x-2">
                            <span>搬出入ステップ編集:</span>
                            <span id="edit_plan_equipment_name" class="text-indigo-600 font-bold truncate"></span>
                        </h3>
                    </div>
                </div>

                <button type="button" data-action="close-modal"
                    class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition text-sm leading-none cursor-pointer shrink-0"
                    title="閉じる">
                    ✕
                </button>
            </div>

            <!-- 入力フォーム (縦スクロール可能ボディ) -->
            <form id="plan-edit-form" action="" method="POST" class="flex-1 flex flex-col min-h-0">
                @csrf
                @method('PUT')

                <div class="overflow-y-auto flex-1 p-5 space-y-4 text-xs">
                    <!-- フェーズ & アクション種別 & 進捗ステータス -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label for="edit_plan_day_phase" class="block text-[11px] font-bold text-slate-700 mb-1">
                                実施フェーズ <span class="text-rose-500">*</span>
                            </label>
                            <select id="edit_plan_day_phase" name="day_phase" required
                                class="w-full px-2.5 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition cursor-pointer">
                                @foreach(\App\Enums\EquipmentDayPhase::cases() as $phase)
                                    <option value="{{ $phase->value }}">{{ $phase->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="edit_plan_action_type" class="block text-[11px] font-bold text-slate-700 mb-1">
                                動作アクション種別 <span class="text-rose-500">*</span>
                            </label>
                            <select id="edit_plan_action_type" name="action_type" required
                                class="w-full px-2.5 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition cursor-pointer">
                                @foreach(\App\Enums\EquipmentActionType::cases() as $action)
                                    <option value="{{ $action->value }}">{{ $action->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="edit_plan_status" class="block text-[11px] font-bold text-slate-700 mb-1">
                                進捗ステータス <span class="text-rose-500">*</span>
                            </label>
                            <select id="edit_plan_status" name="status" required
                                class="w-full px-2.5 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition cursor-pointer">
                                @foreach(\App\Enums\EquipmentPlanStatus::cases() as $status)
                                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- ステップ表示名 & 表示順序 -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label for="edit_plan_title" class="block text-[11px] font-bold text-slate-700 mb-1">
                                ステップ表示名 <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="edit_plan_title" name="title" required
                                placeholder="例: 前日倉庫出し・現場へ搬送"
                                class="w-full px-3 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>

                        <div>
                            <label for="edit_plan_display_order" class="block text-[11px] font-bold text-slate-700 mb-1">
                                表示順序（小さい順）
                            </label>
                            <input type="number" id="edit_plan_display_order" name="display_order" min="0" step="5"
                                class="w-full px-3 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>
                    </div>

                    <!-- 実施日 & 時間帯 -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label for="edit_plan_target_date" class="block text-[11px] font-bold text-slate-700 mb-1">実施予定日</label>
                            <input type="date" id="edit_plan_target_date" name="target_date"
                                class="w-full px-3 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>

                        <div>
                            <label for="edit_plan_start_time" class="block text-[11px] font-bold text-slate-700 mb-1">開始時刻</label>
                            <input type="time" id="edit_plan_start_time" name="start_time"
                                class="w-full px-3 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>

                        <div>
                            <label for="edit_plan_end_time" class="block text-[11px] font-bold text-slate-700 mb-1">完了目標時刻</label>
                            <input type="time" id="edit_plan_end_time" name="end_time"
                                class="w-full px-3 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>
                    </div>

                    <!-- 移動元 & 移動先 -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 border-t border-slate-100 pt-3">
                        <div class="p-2.5 rounded-xl bg-slate-50/70 border border-slate-200 space-y-2">
                            <label for="edit_plan_from_location_id" class="block text-[11px] font-bold text-slate-700 flex items-center space-x-1">
                                <span>📍</span>
                                <span>移動元（出発場所）</span>
                            </label>
                            <select id="edit_plan_from_location_id" name="from_location_id"
                                class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="">（拠点マスタから選択 または自由入力）</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" data-name="{{ $loc->name }}">{{ $loc->name }} ({{ $loc->type_label }})</option>
                                @endforeach
                            </select>
                            <input type="text" id="edit_plan_from_location" name="from_location" placeholder="または場所名を自由入力 (例: 自宅, 番所前)"
                                class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>

                        <div class="p-2.5 rounded-xl bg-slate-50/70 border border-slate-200 space-y-2">
                            <label for="edit_plan_to_location_id" class="block text-[11px] font-bold text-slate-700 flex items-center space-x-1">
                                <span>🏁</span>
                                <span>移動先（到着場所）</span>
                            </label>
                            <select id="edit_plan_to_location_id" name="to_location_id"
                                class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="">（拠点マスタから選択 または自由入力）</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" data-name="{{ $loc->name }}">{{ $loc->name }} ({{ $loc->type_label }})</option>
                                @endforeach
                            </select>
                            <input type="text" id="edit_plan_to_location" name="to_location" placeholder="または場所名を自由入力 (例: 現地指定, 会場南側)"
                                class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- 運搬数量・作業主体・立会幹事 -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 border-t border-slate-100 pt-3">
                        <div>
                            <label for="edit_plan_quantity" class="block text-[11px] font-bold text-slate-700 mb-1">運搬数量</label>
                            <input type="number" id="edit_plan_quantity" name="quantity" min="1" placeholder="数量"
                                class="w-full px-3 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                            <input type="text" id="edit_plan_quantity_note" name="quantity_note" placeholder="数量補足（例: 西側分）"
                                class="w-full mt-1.5 px-3 py-1.5 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>

                        <div>
                            <label for="edit_plan_operator_type" class="block text-[11px] font-bold text-slate-700 mb-1">
                                作業主体 <span class="text-rose-500">*</span>
                            </label>
                            <select id="edit_plan_operator_type" name="operator_type" required
                                class="w-full px-2.5 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition cursor-pointer">
                                @foreach(\App\Enums\EquipmentOperatorType::cases() as $op)
                                    <option value="{{ $op->value }}">{{ $op->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="edit_plan_witness_staff_id" class="block text-[11px] font-bold text-slate-700 mb-1">立会幹事スタッフ</label>
                            <select id="edit_plan_witness_staff_id" name="witness_staff_id"
                                class="w-full px-2.5 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition cursor-pointer">
                                <option value="">（指定なし）</option>
                                @foreach($staffs as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- 立会必須チェック -->
                    <div class="pt-1">
                        <label class="inline-flex items-center space-x-2 cursor-pointer select-none">
                            <input type="checkbox" id="edit_plan_witness_required" name="witness_required" value="1"
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 cursor-pointer">
                            <span class="font-bold text-slate-700 text-xs">👁️ 幹事・スタッフ立会必須</span>
                        </label>
                    </div>

                    <!-- メモ -->
                    <div>
                        <label for="edit_plan_notes" class="block text-[11px] font-bold text-slate-700 mb-1">条件・注意事項・申し送りメモ</label>
                        <textarea id="edit_plan_notes" name="notes" rows="2" placeholder="例: 駐車場が貸切になったら移動。台車2台必要。"
                            class="w-full px-3 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed"></textarea>
                    </div>
                </div>

                <!-- モーダルフッター (固定) -->
                <div class="shrink-0 px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-2">
                    <button type="button" data-action="close-modal"
                        class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer">
                        キャンセル
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                        変更を保存する
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

