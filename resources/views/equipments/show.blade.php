<x-layouts.app :title="$equipment->name . ' - 搬出入計画'">
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ヘッダーエリア -->
    <x-page-header
        :title="$equipment->name"
        :icon="$equipment->isCommittee() ? '🏛️' : '🏢'"
        :description="$equipment->isCommittee() ? '実行委員会管理機材（定常保管: ' . ($equipment->storage_location ?: '番所地下倉庫') . '）' : 'イベント会社レンタル（' . ($equipment->event_company_name ?: 'イベント会社') . '）'">
        <x-slot:breadcrumbs>
            <a href="{{ route('equipments.index') }}" class="hover:underline">資材一覧</a>
            <span>/</span>
            <span class="text-slate-800 font-bold">{{ $equipment->name }}</span>
        </x-slot:breadcrumbs>

        <x-slot:badges>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold font-mono {{ $equipment->isCommittee() ? 'bg-emerald-100 text-emerald-800' : 'bg-sky-100 text-sky-800' }}">
                {{ $equipment->quantity }} {{ $equipment->unit }}
            </span>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $equipment->isCommittee() ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-sky-50 text-sky-700 border border-sky-200' }}">
                {{ $equipment->type_label }}
            </span>
            @if($equipment->lane)
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                    {{ $equipment->lane->icon }} {{ $equipment->lane->name }}
                </span>
            @endif
        </x-slot:badges>

        <x-slot:actions>
            <a href="{{ route('equipments.index') }}"
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition">
                <span>← 一覧に戻る</span>
            </a>
            <button type="button" data-action="open-apply-template-modal"
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                <span>⚡</span>
                <span>テンプレート適用</span>
            </button>
            <button type="button" data-action="open-step-modal" data-mode="create"
                class="inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                <span>➕</span>
                <span>ステップ追加</span>
            </button>
            <a href="{{ route('equipments.edit', $equipment) }}"
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition">
                <span>✏️</span>
                <span>基本情報編集</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    @if($equipment->description || $equipment->event_company_contact)
        <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-4 space-y-2 text-xs">
            @if($equipment->event_company_contact)
                <div class="text-slate-600 flex items-center space-x-2">
                    <span class="font-bold text-slate-700">業者連絡先:</span>
                    <span class="font-mono text-slate-800">{{ $equipment->event_company_contact }}</span>
                </div>
            @endif
            @if($equipment->description)
                <div class="text-slate-600">
                    <span class="font-bold text-slate-700">備考・仕様:</span> {{ $equipment->description }}
                </div>
            @endif
        </div>
    @endif

    <!-- 搬出入ステップタイムライン -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                <span>⏱️</span>
                <span>搬出入・中継・夜間保管計画ステップ一覧</span>
            </h2>
            <span class="text-xs font-mono text-slate-500">
                全 {{ $equipment->plans->count() }} ステップ (完了: {{ $equipment->plans->where('status', 'completed')->count() }})
            </span>
        </div>

        @if($equipment->plans->isEmpty())
            <div class="p-12 text-center bg-white rounded-2xl border border-dashed border-slate-300 text-slate-500 space-y-3">
                <span class="text-3xl block">📋</span>
                <p class="font-bold text-slate-700">搬出入計画ステップがまだありません</p>
                <p class="text-xs text-slate-400">「テンプレート適用」から一括生成するか、「ステップ追加」から手動で登録してください。</p>
                <button type="button" data-action="open-apply-template-modal"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-sm hover:bg-indigo-500 transition">
                    ⚡ テンプレートを一括適用する
                </button>
            </div>
        @else
            <div class="space-y-3">
                @foreach($equipment->plans as $plan)
                    @php $warning = $plan->checkToLocationAvailability(); @endphp
                    <div class="bg-white rounded-2xl border {{ $plan->isCompleted() ? 'border-emerald-200 bg-emerald-50/15' : 'border-slate-200' }} shadow-xs p-4 sm:p-5 hover:shadow-md transition space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                            <div class="flex items-center space-x-2.5 flex-wrap">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold border
                                    @if($plan->day_phase === 'pre_day') bg-purple-50 text-purple-700 border-purple-200
                                    @elseif($plan->day_phase === 'day1_morning') bg-sky-50 text-sky-700 border-sky-200
                                    @elseif($plan->day_phase === 'day1_evening') bg-indigo-50 text-indigo-700 border-indigo-200
                                    @elseif($plan->day_phase === 'day2_morning') bg-teal-50 text-teal-700 border-teal-200
                                    @elseif($plan->day_phase === 'day2_evening') bg-amber-50 text-amber-700 border-amber-200
                                    @elseif($plan->day_phase === 'post_day') bg-rose-50 text-rose-700 border-rose-200
                                    @else bg-slate-50 text-slate-700 border-slate-200 @endif">
                                    {{ $plan->phase_label }}
                                </span>

                                <span class="font-bold text-slate-900 text-sm sm:text-base">{{ $plan->title }}</span>

                                @if($plan->quantity)
                                    <span class="text-xs font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded">
                                        {{ $plan->quantity }}{{ $equipment->unit }}
                                    </span>
                                @endif
                            </div>

                            <!-- ステータス切替 & 編集・削除 -->
                            <div class="flex items-center space-x-2 shrink-0 self-end sm:self-center">
                                <form method="POST" action="{{ route('equipment-plans.update-status', $plan) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    @if($plan->status === 'completed')
                                        <input type="hidden" name="status" value="planned">
                                        <button type="submit" class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 hover:bg-emerald-200 cursor-pointer">
                                            ✓ 完了
                                        </button>
                                    @elseif($plan->status === 'in_progress')
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300 hover:bg-emerald-100 cursor-pointer">
                                            ▶ 進行中
                                        </button>
                                    @else
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-300 hover:bg-emerald-100 hover:text-emerald-800 cursor-pointer">
                                            ○ 未着手
                                        </button>
                                    @endif
                                </form>

                                <button type="button"
                                        data-action="open-step-modal"
                                        data-mode="edit"
                                        data-plan="{{ json_encode($plan) }}"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition text-xs" title="ステップを編集">
                                    ✏️
                                </button>

                                <form method="POST" action="{{ route('equipment-plans.destroy', $plan) }}" data-confirm="このステップを削除しますか？">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition text-xs" title="ステップを削除">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- 移動ルート & 予定時刻 -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                                <span class="text-[10px] font-bold text-slate-400 uppercase">移動ルート</span>
                                <div class="flex items-center space-x-2 text-slate-800 font-bold">
                                    <span>🛫 {{ $plan->from_location ?: '（指定なし）' }}</span>
                                    <span class="text-slate-400">➔</span>
                                    <span>🛬 {{ $plan->to_location ?: '（指定なし）' }}</span>
                                </div>
                            </div>

                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                                <span class="text-[10px] font-bold text-slate-400 uppercase">予定時間・実施日</span>
                                <div class="font-mono font-bold text-slate-800">
                                    @if($plan->target_date)
                                        {{ $plan->target_date->format('Y年n月j日 (D)') }}
                                    @endif
                                    @if($plan->start_time)
                                        <span class="text-indigo-600 ml-1">{{ substr($plan->start_time, 0, 5) }}@if($plan->end_time)〜{{ substr($plan->end_time, 0, 5) }}@endif</span>
                                    @else
                                        <span class="text-slate-400 font-normal ml-1">時間指定なし</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- 貸切期間アラート -->
                        @if($warning)
                            <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 text-xs flex items-start space-x-2">
                                <span class="font-bold text-amber-600 text-base">⚠️</span>
                                <div class="leading-normal font-bold">
                                    {{ $warning['message'] }}
                                </div>
                            </div>
                        @endif

                        <!-- フッターメタ情報 -->
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-1 text-xs text-slate-500">
                            <div class="flex items-center space-x-2 flex-wrap">
                                <span class="px-2 py-0.5 rounded font-bold {{ $plan->operator_type === 'event_company' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $plan->operator_label }}
                                </span>

                                @if($plan->witness_required)
                                    <span class="px-2 py-0.5 rounded font-bold bg-rose-100 text-rose-800 border border-rose-300 flex items-center space-x-1">
                                        <span>👁️ 立会必須</span>
                                        @if($plan->witnessStaff)
                                            <span>({{ $plan->witnessStaff->name }})</span>
                                        @endif
                                    </span>
                                @endif

                                @if($plan->contact_info)
                                    <span class="font-mono text-slate-600">📞 {{ $plan->contact_info }}</span>
                                @endif
                            </div>

                            @if($plan->notes)
                                <div class="text-slate-600 italic">
                                    💬 {{ $plan->notes }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

<!-- ========================================================================= -->
<!-- ステップ新規登録・編集モーダル (既存標準モーダルデザイン・CSP準拠) -->
<!-- ========================================================================= -->
<div id="modal-step" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-xl w-full p-5 sm:p-6 shadow-2xl border border-slate-200 text-left space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 id="modal-step-title" class="text-sm sm:text-base font-bold text-slate-900 flex items-center space-x-2">
                <span>⏱️</span>
                <span>ステップの追加</span>
            </h3>
            <button type="button" data-action="close-step-modal" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">✕</button>
        </div>

        <form id="form-step" method="POST" action="{{ route('equipment-plans.store') }}" class="space-y-4">
            @csrf
            <div id="form-step-method"></div>
            <input type="hidden" name="equipment_id" value="{{ $equipment->id }}">

            <div>
                <label for="step_title" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">アクション表示名 <span class="text-rose-500">*</span></label>
                <input type="text" id="step_title" name="title" required placeholder="例: 1日目夜 分散夜間保管（税務署前駐車場）"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="step_day_phase" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">フェーズ <span class="text-rose-500">*</span></label>
                    <select id="step_day_phase" name="day_phase" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach(\App\Models\EquipmentLogisticsPlan::DAY_PHASES as $key => $lbl)
                            <option value="{{ $key }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="step_action_type" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">アクション種別</label>
                    <select id="step_action_type" name="action_type" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach(\App\Models\EquipmentLogisticsPlan::ACTION_TYPES as $key => $lbl)
                            <option value="{{ $key }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="step_target_date" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">実施日</label>
                    <input type="date" id="step_target_date" name="target_date"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>
                <div>
                    <label for="step_start_time" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">開始予定時刻</label>
                    <input type="time" id="step_start_time" name="start_time"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>
                <div>
                    <label for="step_end_time" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">終了予定時刻</label>
                    <input type="time" id="step_end_time" name="end_time"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>
            </div>

            <!-- 移動元 & 移動先 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200">
                <div>
                    <label for="step_from_location" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">移動元 (From)</label>
                    <input type="text" id="step_from_location" name="from_location" placeholder="例: 会場設置、番所地下倉庫"
                           class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="step_to_location_id" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">移動先拠点 (To)</label>
                    <select id="step_to_location_id" name="to_location_id"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">（拠点を選択 または自由入力）</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" data-name="{{ $loc->name }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" id="step_to_location" name="to_location" placeholder="移動先テキスト"
                           class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 mt-1.5">
                </div>
            </div>

            <!-- 数量 & 作業主体 & 立会 -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="step_quantity" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">移動数量</label>
                    <input type="number" id="step_quantity" name="quantity" min="1" placeholder="全量なら空欄可"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="step_operator_type" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">作業主体</label>
                    <select id="step_operator_type" name="operator_type" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="committee">委員会スタッフ</option>
                        <option value="event_company">イベント会社</option>
                    </select>
                </div>

                <div>
                    <label for="step_witness_staff_id" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">立会スタッフ</label>
                    <select id="step_witness_staff_id" name="witness_staff_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">立会不要 / 未定</option>
                        @foreach($staffs as $staff)
                            <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <input type="checkbox" id="step_witness_required" name="witness_required" value="1"
                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                <label for="step_witness_required" class="text-xs font-bold text-slate-700 cursor-pointer">
                    幹事・スタッフの立会い必須とする（パターンB受取・回収時など）
                </label>
            </div>

            <div>
                <label for="step_notes" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">条件・注意事項・申し送りメモ</label>
                <textarea id="step_notes" name="notes" rows="2" placeholder="例: 駐車場が貸切になったら移動。台車2台必要。"
                          class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" data-action="close-step-modal"
                        class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                    キャンセル
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                    保存する
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- テンプレート一括適用モーダル (既存標準モーダルデザイン・CSP準拠) -->
<!-- ========================================================================= -->
<div id="modal-apply-template" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-5 sm:p-6 shadow-2xl border border-slate-200 text-left space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm sm:text-base font-bold text-slate-900 flex items-center space-x-2">
                <span>⚡</span>
                <span>テンプレートから計画ステップを一括生成</span>
            </h3>
            <button type="button" data-action="close-apply-template-modal" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">✕</button>
        </div>

        <form method="POST" action="{{ route('equipments.apply-template', $equipment) }}" class="space-y-4">
            @csrf

            <div>
                <label for="tpl_type" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">適用パターン</label>
                <select id="tpl_type" name="template_type" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="pattern_a" {{ $equipment->isCommittee() ? 'selected' : '' }}>
                        【パターンA】自前機材（前日倉庫出し ➔ 1日目設置 ➔ 1日目夜分散保管 ➔ 2日目設置 ➔ 2日目夜格納）
                    </option>
                    <option value="pattern_b" {{ $equipment->isRental() ? 'selected' : '' }}>
                        【パターンB】レンタル自前設置（前日受取立会 ➔ 貸切後移動 ➔ 1日目夜分散保管 ➔ 2日目夜集約 ➔ 翌日回収立会）
                    </option>
                    <option value="pattern_c">
                        【パターンC】イベント会社設営・解体型（搬入設営 ➔ 解体搬出・立会不要）
                    </option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="tpl_staging_location_id" class="block text-[11px] font-bold text-slate-700 mb-1">前日中継拠点</label>
                    <select id="tpl_staging_location_id" name="staging_location_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ str_contains($loc->name, '税務署') ? 'selected' : '' }}>{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tpl_night_location_1_id" class="block text-[11px] font-bold text-slate-700 mb-1">1日目夜間保管①</label>
                    <select id="tpl_night_location_1_id" name="night_location_1_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ str_contains($loc->name, '税務署') ? 'selected' : '' }}>{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="tpl_night_location_2_id" class="block text-[11px] font-bold text-slate-700 mb-1">1日目夜間保管② (分散・任意)</label>
                    <select id="tpl_night_location_2_id" name="night_location_2_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">分散なし</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ str_contains($loc->name, '北川') ? 'selected' : '' }}>{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tpl_witness_staff_id" class="block text-[11px] font-bold text-slate-700 mb-1">立会スタッフ（パターンB用）</label>
                    <select id="tpl_witness_staff_id" name="witness_staff_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">選択してください</option>
                        @foreach($staffs as $staff)
                            <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" data-action="close-apply-template-modal"
                        class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                    キャンセル
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                    ステップを一括生成する
                </button>
            </div>
        </form>
    </div>
</div>

@vite(['resources/js/equipments.js'])
</x-layouts.app>

