@props([
    'prefix' => 'plan',
    'locations' => collect(),
    'staffs' => collect(),
])

<!-- フェーズ & アクション種別 & 進捗ステータス -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div>
        <label for="{{ $prefix }}_day_phase" class="block text-[11px] font-bold text-slate-700 mb-1">
            実施フェーズ <span class="text-rose-500">*</span>
        </label>
        <select id="{{ $prefix }}_day_phase" name="day_phase" required
            class="w-full px-2.5 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition cursor-pointer">
            @foreach(\App\Enums\EquipmentDayPhase::cases() as $phase)
                <option value="{{ $phase->value }}">{{ $phase->label() }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="{{ $prefix }}_action_type" class="block text-[11px] font-bold text-slate-700 mb-1">
            動作アクション種別 <span class="text-rose-500">*</span>
        </label>
        <select id="{{ $prefix }}_action_type" name="action_type" required
            class="w-full px-2.5 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition cursor-pointer">
            @foreach(\App\Enums\EquipmentActionType::cases() as $action)
                <option value="{{ $action->value }}">{{ $action->label() }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="{{ $prefix }}_status" class="block text-[11px] font-bold text-slate-700 mb-1">
            進捗ステータス <span class="text-rose-500">*</span>
        </label>
        <select id="{{ $prefix }}_status" name="status" required
            class="w-full px-2.5 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition cursor-pointer">
            @foreach(\App\Enums\EquipmentPlanStatus::cases() as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
</div>

<!-- ステップ表示名 -->
<div>
    <label for="{{ $prefix }}_title" class="block text-[11px] font-bold text-slate-700 mb-1">
        ステップ表示名 <span class="text-rose-500">*</span>
    </label>
    <input type="text" id="{{ $prefix }}_title" name="title" required
        placeholder="例: 前日倉庫出し・現場へ搬送"
        class="w-full px-3 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
</div>

<!-- 実施日 & 時間帯 -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div>
        <label for="{{ $prefix }}_target_date" class="block text-[11px] font-bold text-slate-700 mb-1">実施予定日</label>
        <input type="date" id="{{ $prefix }}_target_date" name="target_date"
            class="w-full px-3 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
    </div>

    <div>
        <label for="{{ $prefix }}_start_time" class="block text-[11px] font-bold text-slate-700 mb-1">開始時刻</label>
        <input type="time" id="{{ $prefix }}_start_time" name="start_time"
            class="w-full px-3 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
    </div>

    <div>
        <label for="{{ $prefix }}_end_time" class="block text-[11px] font-bold text-slate-700 mb-1">完了目標時刻</label>
        <input type="time" id="{{ $prefix }}_end_time" name="end_time"
            class="w-full px-3 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
    </div>
</div>

<!-- 移動元 & 移動先 -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 border-t border-slate-100 pt-3">
    <div class="p-2.5 rounded-xl bg-slate-50/70 border border-slate-200 space-y-2">
        <label for="{{ $prefix }}_from_location_id" class="block text-[11px] font-bold text-slate-700 flex items-center space-x-1">
            <span>📍</span>
            <span>移動元（出発場所）</span>
        </label>
        <select id="{{ $prefix }}_from_location_id" name="from_location_id"
            class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <option value="">（拠点マスタから選択 または自由入力）</option>
            @foreach($locations as $loc)
                <option value="{{ $loc->id }}" data-name="{{ $loc->name }}">{{ $loc->name }} ({{ $loc->type_label }})</option>
            @endforeach
        </select>
        <input type="text" id="{{ $prefix }}_from_location" name="from_location" placeholder="または場所名を自由入力 (例: 自宅, 番所前)"
            class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
    </div>

    <div class="p-2.5 rounded-xl bg-slate-50/70 border border-slate-200 space-y-2">
        <label for="{{ $prefix }}_to_location_id" class="block text-[11px] font-bold text-slate-700 flex items-center space-x-1">
            <span>🏁</span>
            <span>移動先（到着場所）</span>
        </label>
        <select id="{{ $prefix }}_to_location_id" name="to_location_id"
            class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <option value="">（拠点マスタから選択 または自由入力）</option>
            @foreach($locations as $loc)
                <option value="{{ $loc->id }}" data-name="{{ $loc->name }}">{{ $loc->name }} ({{ $loc->type_label }})</option>
            @endforeach
        </select>
        <input type="text" id="{{ $prefix }}_to_location" name="to_location" placeholder="または場所名を自由入力 (例: 現地指定, 会場南側)"
            class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
    </div>
</div>

<!-- 運搬数量・作業主体・立会幹事 -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 border-t border-slate-100 pt-3">
    <div>
        <label for="{{ $prefix }}_quantity" class="block text-[11px] font-bold text-slate-700 mb-1">運搬数量</label>
        <input type="number" id="{{ $prefix }}_quantity" name="quantity" min="1" placeholder="数量"
            class="w-full px-3 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
        <input type="text" id="{{ $prefix }}_quantity_note" name="quantity_note" placeholder="数量補足（例: 西側分）"
            class="w-full mt-1.5 px-3 py-1.5 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
    </div>

    <div>
        <label for="{{ $prefix }}_operator_type" class="block text-[11px] font-bold text-slate-700 mb-1">
            作業主体 <span class="text-rose-500">*</span>
        </label>
        <select id="{{ $prefix }}_operator_type" name="operator_type" required
            class="w-full px-2.5 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition cursor-pointer">
            @foreach(\App\Enums\EquipmentOperatorType::cases() as $op)
                <option value="{{ $op->value }}">{{ $op->label() }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="{{ $prefix }}_witness_staff_id" class="block text-[11px] font-bold text-slate-700 mb-1">立会幹事スタッフ</label>
        <select id="{{ $prefix }}_witness_staff_id" name="witness_staff_id"
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
        <input type="checkbox" id="{{ $prefix }}_witness_required" name="witness_required" value="1"
            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 cursor-pointer">
        <span class="font-bold text-slate-700 text-xs">👁️ 幹事・スタッフ立会必須</span>
    </label>
</div>

<!-- メモ -->
<div>
    <label for="{{ $prefix }}_notes" class="block text-[11px] font-bold text-slate-700 mb-1">条件・注意事項・申し送りメモ</label>
    <textarea id="{{ $prefix }}_notes" name="notes" rows="2" placeholder="例: 駐車場が貸切になったら移動。台車2台必要。"
        class="w-full px-3 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed"></textarea>
</div>

