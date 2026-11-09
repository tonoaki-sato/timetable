<x-layouts.app :title="$equipment->name . ' - 搬出入計画'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    @php
        $clearAction = isset($timetable) ? route('timetables.logistics.plans.clear', [$timetable, $equipment]) : route('logistics.plans.clear', $equipment);
        $updateStatusUrl = fn($p) => isset($timetable) ? route('timetables.logistics.plans.update-status', [$timetable, $p]) : route('logistics.plans.update-status', $p);
        $destroyPlanUrl = fn($p) => isset($timetable) ? route('timetables.logistics.plans.destroy', [$timetable, $p]) : route('logistics.plans.destroy', $p);
        $storePlanUrl = isset($timetable) ? route('timetables.logistics.plans.store', $timetable) : route('logistics.plans.store');
        $updatePlanUrl = fn($p) => isset($timetable) ? route('timetables.logistics.plans.update', [$timetable, $p]) : route('logistics.plans.update', $p);
        $applyTemplateUrl = isset($timetable) ? route('timetables.logistics.apply-template', [$timetable, $equipment]) : route('logistics.apply-template', $equipment);
        $backLogisticsUrl = isset($timetable) ? route('timetables.logistics.index', $timetable) : route('logistics.index');
    @endphp

    <!-- ヘッダーエリア -->
    <x-page-header
        :title="$equipment->name"
        :icon="$equipment->isCommittee() ? '🏛️' : ($equipment->isRental() ? '🏢' : '🎒')"
        :description="$equipment->isRental()
            ? '提携イベント会社レンタル機材（6大フェーズ搬出入・現場配備・回収計画）'
            : ($equipment->isPersonal()
                ? '個人持ち込み私物（定常保管: ' . ($equipment->storage_location ?: '自宅') . '）'
                : '実行委員会保有備品（定常保管: ' . ($equipment->storage_location ?: '番所地下倉庫') . '）')">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>⏱️</span>
                <span>運行情報一覧</span>
            </a>
            @if(isset($timetable))
                <span class="text-slate-300">/</span>
                <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                    <span>📅</span>
                    <span>{{ $timetable->name }}</span>
                </a>
            @endif
            <span class="text-slate-300">/</span>
            <a href="{{ $backLogisticsUrl }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>🚚</span>
                <span>搬出入計画</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">📦 {{ $equipment->name }}</span>
        </x-slot:breadcrumbs>

        <x-slot:badges>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold font-mono {{ $equipment->isCommittee() ? 'bg-emerald-100 text-emerald-800' : ($equipment->isRental() ? 'bg-sky-100 text-sky-800' : 'bg-amber-100 text-amber-800') }}">
                {{ $equipment->formatted_quantity }}
            </span>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $equipment->isCommittee() ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($equipment->isRental() ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                {{ $equipment->type_label }}
            </span>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold
                @if($equipment->checkout_rule === 'permission_required') bg-rose-50 text-rose-700 border border-rose-200
                @elseif($equipment->checkout_rule === 'leader_only') bg-purple-50 text-purple-700 border border-purple-200
                @else bg-slate-50 text-slate-600 border border-slate-200 @endif">
                {{ $equipment->checkout_rule_label }}
            </span>
            @if($equipment->lane)
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                    {{ $equipment->lane->icon }} {{ $equipment->lane->name }}
                </span>
            @endif
        </x-slot:badges>

        <x-slot:actions>
            <a href="{{ $backLogisticsUrl }}"
                class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition whitespace-nowrap shrink-0">
                <span>←</span>
                <span>搬出入計画へ戻る</span>
            </a>
            <button type="button" data-action="open-apply-template-modal"
                class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold shadow-2xs transition cursor-pointer whitespace-nowrap shrink-0">
                <span>⚡</span>
                <span>パターン適用</span>
            </button>
            @if($equipment->plans->isNotEmpty())
                <button type="button" data-action="open-save-pattern-modal"
                    class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold shadow-2xs transition cursor-pointer whitespace-nowrap shrink-0">
                    <span>🧩</span>
                    <span>パターン保存</span>
                </button>
            @endif
            <button type="button" data-action="open-step-modal" data-mode="create"
                class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs transition cursor-pointer whitespace-nowrap shrink-0">
                <span>➕</span>
                <span>ステップ追加</span>
            </button>
            <a href="{{ route('equipments.show', $equipment) }}"
                class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition whitespace-nowrap shrink-0">
                <span>📦</span>
                <span>詳細</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    @if(isset($timetable))
        <x-timetable-context-bar :timetable="$timetable" active="logistics" />
    @endif

    @if (session('status'))
        <div class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between">
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
            <div class="font-bold">入力内容に不備があります：</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($equipment->description || $equipment->storage_detail || $equipment->storage_location)
        <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-4 space-y-2 text-xs">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2">
                <div class="font-bold text-slate-800 flex items-center space-x-1.5">
                    <span>📦</span>
                    <span>資材マスター基本情報</span>
                </div>
            </div>
            @if($equipment->storage_location)
                <div class="text-slate-600 flex items-center space-x-2">
                    <span class="font-bold text-slate-700">定常保管場所:</span>
                    <span class="text-slate-900 font-medium">{{ $equipment->storage_location }}</span>
                    @if($equipment->storage_detail)
                        <span class="text-slate-500 font-medium bg-slate-50 border border-slate-200 px-2 py-0.5 rounded text-[11px]">定位置詳細: {{ $equipment->storage_detail }}</span>
                    @endif
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
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                <span>⏱️</span>
                <span>搬出入・中継・夜間保管計画ステップ一覧</span>
            </h2>
            <div class="flex items-center space-x-3">
                <span class="text-xs font-mono text-slate-500">
                    全 {{ $equipment->plans->count() }} ステップ (完了: {{ $equipment->plans->where('status', 'completed')->count() }})
                </span>
                @if($equipment->plans->isNotEmpty())
                    @can('update', $equipment)
                        <form method="POST" action="{{ $clearAction }}" class="inline"
                              data-confirm="資材「{{ $equipment->name }}」の全ステップ（全{{ $equipment->plans->count() }}件）を削除しますか？&#10;※資材本体は削除されません。">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-2.5 py-1 text-xs font-bold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition cursor-pointer inline-flex items-center space-x-1"
                                    title="全ステップを一括削除">
                                <span>🗑️</span>
                                <span>全ステップ一括クリア</span>
                            </button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>

        @if($equipment->plans->isEmpty())
            <div class="p-12 text-center bg-white rounded-2xl border border-dashed border-slate-300 text-slate-500 space-y-3">
                <span class="text-3xl block">📋</span>
                <p class="font-bold text-slate-700">搬出入計画ステップがまだありません</p>
                <p class="text-xs text-slate-400">「パターン適用」から一括生成するか、「ステップ追加」から手動で登録してください。</p>
                <div class="flex items-center justify-center space-x-2">
                    <button type="button" data-action="open-apply-template-modal"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-sm hover:bg-indigo-500 transition cursor-pointer">
                        ⚡ パターンを一括適用する
                    </button>
                    <button type="button" data-action="open-step-modal" data-mode="create"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 transition cursor-pointer">
                        ➕ ステップを手動追加
                    </button>
                </div>
            </div>
        @else
            <div class="space-y-3">
                @foreach($equipment->plans as $plan)
                    @php $warnings = $plan->location_warnings; @endphp
                    <div class="bg-white rounded-2xl border {{ $plan->isCompleted() ? 'border-emerald-200 bg-emerald-50/15' : 'border-slate-200' }} shadow-xs p-4 sm:p-5 hover:shadow-md transition space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                            <div class="flex items-center space-x-2.5 flex-wrap">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold border
                                    @if($plan->day_phase === 'pre_day') bg-purple-50 text-purple-700 border-purple-200
                                    @elseif($plan->day_phase === 'day1_morning') bg-sky-50 text-sky-700 border-sky-200
                                    @elseif($plan->day_phase === 'day1_evening') bg-indigo-50 text-indigo-700 border-indigo-200
                                    @elseif($plan->day_phase === 'day2_morning') bg-teal-50 text-teal-700 border-teal-200
                                    @elseif($plan->day_phase === 'day2_evening') bg-amber-50 text-amber-700 border-amber-200
                                    @else bg-slate-50 text-slate-700 border-slate-200 @endif">
                                    {{ $plan->phase_label }}
                                </span>
                                <span class="font-bold text-sm text-slate-900">{{ $plan->title }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $plan->action_label }}
                                </span>
                                @if($plan->witness_required)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        ⚠️ 幹事・立会い必須
                                    </span>
                                @endif
                            </div>

                            <!-- ステータス切替ボタン & アクション -->
                            <div class="flex items-center space-x-2 shrink-0 self-end sm:self-center">
                                <form method="POST" action="{{ $updateStatusUrl($plan) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    @if($plan->isCompleted())
                                        <input type="hidden" name="status" value="planned">
                                        <button type="submit"
                                                class="px-2.5 py-1 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 rounded-lg text-xs font-bold transition inline-flex items-center space-x-1 cursor-pointer"
                                                title="クリックで計画中に戻す">
                                            <span>✅ 完了</span>
                                        </button>
                                    @elseif($plan->status === 'in_progress')
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit"
                                                class="px-2.5 py-1 bg-amber-100 hover:bg-amber-200 text-amber-800 rounded-lg text-xs font-bold transition inline-flex items-center space-x-1 cursor-pointer animate-pulse"
                                                title="クリックで完了にする">
                                            <span>🏃 進行中</span>
                                        </button>
                                    @else
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit"
                                                class="px-2.5 py-1 bg-slate-100 hover:bg-emerald-100 text-slate-700 hover:text-emerald-800 rounded-lg text-xs font-bold transition inline-flex items-center space-x-1 cursor-pointer"
                                                title="クリックで完了にする">
                                            <span>⚪ 計画中</span>
                                        </button>
                                    @endif
                                </form>

                                <button type="button"
                                        data-action="open-step-modal"
                                        data-mode="edit"
                                        data-update-url="{{ $updatePlanUrl($plan) }}"
                                        data-plan="{{ json_encode([
                                            'id' => $plan->id,
                                            'status' => $plan->status,
                                            'day_phase' => $plan->day_phase,
                                            'action_type' => $plan->action_type,
                                            'title' => $plan->title,
                                            'target_date' => $plan->target_date?->format('Y-m-d'),
                                            'start_time' => $plan->start_time ? substr($plan->start_time, 0, 5) : '',
                                            'end_time' => $plan->end_time ? substr($plan->end_time, 0, 5) : '',
                                            'from_location_id' => $plan->from_location_id,
                                            'from_location' => $plan->from_location,
                                            'to_location_id' => $plan->to_location_id,
                                            'to_location' => $plan->to_location,
                                            'quantity' => $plan->quantity,
                                            'quantity_note' => $plan->quantity_note,
                                            'operator_type' => $plan->operator_type,
                                            'witness_required' => $plan->witness_required ? 1 : 0,
                                            'witness_staff_id' => $plan->witness_staff_id,
                                            'notes' => $plan->notes,
                                            'display_order' => $plan->display_order,
                                        ]) }}"
                                        class="px-2.5 py-1 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition cursor-pointer">
                                    編集
                                </button>

                                <form method="POST" action="{{ $destroyPlanUrl($plan) }}" data-confirm="このステップを削除しますか？">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-xs font-bold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 rounded-lg transition cursor-pointer">
                                        ✕
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- 移動ルート & 詳細情報 -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                            <!-- 移動元 ➔ 移動先 -->
                            <div class="md:col-span-2 flex items-center space-x-2 bg-slate-50 p-2.5 rounded-xl border border-slate-200 flex-wrap gap-y-1">
                                <div class="flex items-center space-x-1.5 min-w-0">
                                    <span class="text-slate-400 font-bold">発:</span>
                                    <span class="font-bold text-slate-800 truncate">{{ $plan->resolved_from_location }}</span>
                                </div>
                                <span class="text-indigo-500 font-bold px-1">➔</span>
                                <div class="flex items-center space-x-1.5 min-w-0">
                                    <span class="text-slate-400 font-bold">着:</span>
                                    <span class="font-bold text-slate-900 truncate">{{ $plan->resolved_to_location }}</span>
                                </div>
                            </div>

                            <!-- 時間 & 運搬数量 -->
                            <div class="flex items-center justify-between md:justify-end space-x-3 text-slate-600 font-mono">
                                <div>
                                    <span class="text-slate-400">⏱️</span>
                                    <span>{{ $plan->formatted_time_range ?: '時間指定なし' }}</span>
                                </div>
                                <div class="font-bold text-slate-800">
                                    <span class="text-slate-400">📦</span>
                                    <span>{{ $plan->formatted_quantity ?: '数量指定なし' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- 担当主体・立会者 & メモ -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-slate-600 pt-1 border-t border-slate-100">
                            <div class="flex items-center space-x-3 flex-wrap gap-y-1">
                                <div class="flex items-center space-x-1">
                                    <span class="text-slate-400">担当:</span>
                                    <span class="font-bold text-slate-800">{{ $plan->operator_label }}</span>
                                    @if($plan->operator_type === 'event_company' && $plan->contractor_name)
                                        <span class="text-sky-700 font-bold font-mono">({{ $plan->contractor_name }})</span>
                                    @endif
                                </div>
                                @if($plan->witnessStaff)
                                    <div class="flex items-center space-x-1">
                                        <span class="text-slate-400">立会幹事:</span>
                                        <span class="font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-1.5 py-0.2 rounded text-[11px]">{{ $plan->witnessStaff->name }}</span>
                                    </div>
                                @endif
                                @if($plan->notes)
                                    <div class="text-slate-500 line-clamp-1">
                                        <span class="font-bold text-slate-400">メモ:</span> {{ $plan->notes }}
                                    </div>
                                @endif
                            </div>

                            <span class="text-[10px] text-slate-400 font-mono">表示順: #{{ $plan->display_order }}</span>
                        </div>

                        <!-- 時間外・重複警告アラート -->
                        @if(!empty($warnings))
                            <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs space-y-1">
                                @foreach($warnings as $warn)
                                    <div class="flex items-center space-x-1.5 font-bold">
                                        <span>⚠️</span>
                                        <span>{{ is_array($warn) ? ($warn['message'] ?? '') : $warn }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

<!-- ========================================================================= -->
<!-- 搬出入ステップ登録・編集モーダル (CSP準拠) -->
<!-- ========================================================================= -->
<div id="modal-step" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-5 sm:p-6 shadow-2xl border border-slate-200 text-left space-y-4 my-auto max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 id="modal-step-title" class="text-sm sm:text-base font-bold text-slate-900 flex items-center space-x-2">
                <span>➕</span>
                <span>搬出入ステップの登録</span>
            </h3>
            <button type="button" data-action="close-step-modal" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">✕</button>
        </div>

        <form id="form-step" method="POST" action="{{ $storePlanUrl }}" class="space-y-4">
            @csrf
            <div id="form-step-method"></div>
            <input type="hidden" name="equipment_id" value="{{ $equipment->id }}">

            <!-- フェーズ & アクション種別 & 進捗ステータス -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="step_day_phase" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">実施フェーズ <span class="text-rose-500">*</span></label>
                    <select id="step_day_phase" name="day_phase" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach(\App\Models\EquipmentLogisticsPlan::DAY_PHASES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="step_action_type" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">動作アクション種別 <span class="text-rose-500">*</span></label>
                    <select id="step_action_type" name="action_type" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach(\App\Models\EquipmentLogisticsPlan::ACTION_TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="step_status" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">進捗ステータス <span class="text-rose-500">*</span></label>
                    <select id="step_status" name="status" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach(\App\Models\EquipmentLogisticsPlan::STATUSES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- タイトル & 表示順 -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                    <label for="step_title" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">ステップ名称 <span class="text-rose-500">*</span></label>
                    <input type="text" id="step_title" name="title" required placeholder="例: 前日午後 倉庫出し・中継駐車場へ運搬"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="step_display_order" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">表示順序（小さい順）</label>
                    <input type="number" id="step_display_order" name="display_order" value="10" step="5"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <!-- 実施日 & 時間帯 -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="step_target_date" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">実施予定日</label>
                    <input type="date" id="step_target_date" name="target_date"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="step_start_time" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">開始時刻</label>
                    <input type="time" id="step_start_time" name="start_time"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="step_end_time" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">完了目標時刻</label>
                    <input type="time" id="step_end_time" name="end_time"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <!-- 移動元 & 移動先 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 border-t border-slate-100 pt-3">
                <div class="space-y-2">
                    <label class="block text-[11px] sm:text-xs font-bold text-slate-700">移動元（出発場所）</label>
                    <select id="step_from_location_id" name="from_location_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">（拠点マスタから選択 または自由入力）</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" data-name="{{ $loc->name }}">{{ $loc->name }} ({{ $loc->type_label }})</option>
                        @endforeach
                    </select>
                    <input type="text" id="step_from_location" name="from_location" placeholder="または場所名を自由入力 (例: 自宅, 会場設置)"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="space-y-2">
                    <label class="block text-[11px] sm:text-xs font-bold text-slate-700">移動先（到着場所）</label>
                    <select id="step_to_location_id" name="to_location_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">（拠点マスタから選択 または自由入力）</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" data-name="{{ $loc->name }}">{{ $loc->name }} ({{ $loc->type_label }})</option>
                        @endforeach
                    </select>
                    <input type="text" id="step_to_location" name="to_location" placeholder="または場所名を自由入力 (例: 番所前広場, 税務署前)"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <!-- 数量・担当・立会い -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 border-t border-slate-100 pt-3">
                <div>
                    <label for="step_quantity" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">運搬数量</label>
                    <input type="number" id="step_quantity" name="quantity" min="1" placeholder="数量 (例: {{ $equipment->quantity }})"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" id="step_quantity_note" name="quantity_note" placeholder="数量補足（例: 通行止め用、西側分）"
                           class="w-full mt-1.5 px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="step_operator_type" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">運搬・設置担当</label>
                    <select id="step_operator_type" name="operator_type" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach(\App\Models\EquipmentLogisticsPlan::OPERATOR_TYPES as $key => $label)
                            <option value="{{ $key }}" {{ $equipment->isRental() && $key === 'event_company' ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="step_witness_staff_id" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">立会幹事スタッフ</label>
                    <select id="step_witness_staff_id" name="witness_staff_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">（立会い不要）</option>
                        @foreach($staffs as $staff)
                            <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- 立会いチェック & メモ -->
            <div class="flex items-center space-x-2 pt-1">
                <input type="checkbox" id="step_witness_required" name="witness_required" value="1"
                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                <label for="step_witness_required" class="text-xs font-bold text-slate-700 cursor-pointer">
                    幹事・スタッフの立会い必須とする（受取・回収時など）
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
<!-- パターン一括適用モーダル (CSP準拠) -->
<!-- ========================================================================= -->
<div id="modal-apply-template" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-5 sm:p-6 shadow-2xl border border-slate-200 text-left space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm sm:text-base font-bold text-slate-900 flex items-center space-x-2">
                <span>⚡</span>
                <span>カスタム搬出入パターンから一括生成</span>
            </h3>
            <button type="button" data-action="close-apply-template-modal" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">✕</button>
        </div>

        @if($patterns->isEmpty())
            <div class="p-6 text-center bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                <div class="text-2xl">🧩</div>
                <div class="text-xs font-bold text-slate-700">搬出入パターンがまだ登録されていません</div>
                <p class="text-[11px] text-slate-500">
                    搬出入計画画面の「⑤ 搬出入パターン管理」からカスタムパターンを作成するか、この資材にステップを手動追加して「パターンとして保存」してください。
                </p>
                <a href="{{ route('logistics.index', ['tab' => 'patterns']) }}"
                   class="inline-block px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition">
                    🧩 パターン管理画面を開く
                </a>
            </div>
        @else
            <form method="POST" action="{{ $applyTemplateUrl }}" class="space-y-4">
                @csrf

                @if($equipment->plans->isNotEmpty())
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 space-y-1">
                        <div class="font-bold flex items-center space-x-1">
                            <span>⚠️</span>
                            <span>既存ステップが {{ $equipment->plans->count() }} 件登録されています</span>
                        </div>
                        <p class="text-[11px] text-amber-800">
                            パターン適用時に既存ステップをクリアして上書き再生成します。
                        </p>
                        <label class="flex items-center space-x-2 pt-1 font-bold cursor-pointer">
                            <input type="checkbox" name="override_existing" value="1" checked class="rounded text-amber-600 focus:ring-amber-500">
                            <span>既存ステップをすべて削除して上書き生成する（推奨）</span>
                        </label>
                    </div>
                @endif

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="tpl_pattern_id" class="block text-[11px] sm:text-xs font-bold text-slate-700">適用パターン <span class="text-rose-500">*</span></label>
                        <a href="{{ route('logistics.index', ['tab' => 'patterns']) }}" class="text-[11px] text-indigo-600 hover:underline font-bold">
                            🧩 パターンを編集・追加
                        </a>
                    </div>
                    <select id="tpl_pattern_id" name="pattern_id" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach($patterns as $pattern)
                            <option value="{{ $pattern->id }}" {{ $pattern->target_type === $equipment->type ? 'selected' : '' }}>
                                {{ $pattern->name }} ({{ $pattern->steps->count() }}ステップ / {{ $pattern->target_type_label }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="tpl_staging_location_id" class="block text-[11px] font-bold text-slate-700 mb-1">中継拠点（ロール: staging）</label>
                        <select id="tpl_staging_location_id" name="staging_location_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">（未指定）</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ str_contains($loc->name, '税務署') ? 'selected' : '' }}>{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="tpl_night_location_1_id" class="block text-[11px] font-bold text-slate-700 mb-1">夜間保管拠点①（ロール: night_storage_1）</label>
                        <select id="tpl_night_location_1_id" name="night_location_1_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">（未指定）</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ str_contains($loc->name, '税務署') ? 'selected' : '' }}>{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="tpl_night_location_2_id" class="block text-[11px] font-bold text-slate-700 mb-1">夜間保管拠点②（分散・任意）</label>
                        <select id="tpl_night_location_2_id" name="night_location_2_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">分散なし</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ str_contains($loc->name, '北川') ? 'selected' : '' }}>{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="tpl_witness_staff_id" class="block text-[11px] font-bold text-slate-700 mb-1">立会スタッフ</label>
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
        @endif
    </div>
</div>

<!-- ========================================================================= -->
<!-- 現在のステップ構成を新しいパターンとして保存するモーダル (CSP準拠) -->
<!-- ========================================================================= -->
<div id="modal-save-pattern" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-5 sm:p-6 shadow-2xl border border-slate-200 text-left space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm sm:text-base font-bold text-slate-900 flex items-center space-x-2">
                <span>🧩</span>
                <span>このステップ構成を新しいパターンとして保存</span>
            </h3>
            <button type="button" data-action="close-save-pattern-modal" class="text-slate-400 hover:text-slate-600 font-bold text-lg cursor-pointer">✕</button>
        </div>

        <form method="POST" action="{{ route('logistics.save-as-pattern', $equipment) }}" class="space-y-4">
            @csrf

            <p class="text-xs text-slate-600">
                現在「{{ $equipment->name }}」に登録されている {{ $equipment->plans->count() }} 件のステップをテンプレート化し、他の資材にも再利用できるように保存します。
            </p>

            <div>
                <label for="save_pattern_name" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">パターン名 <span class="text-rose-500">*</span></label>
                <input type="text" id="save_pattern_name" name="name" required
                       value="{{ $equipment->name }}型パターン"
                       placeholder="例: パターンA: 自前備品標準、パターンB-2: カラーコーン型"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label for="save_pattern_target_type" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">主な対象区分 <span class="text-rose-500">*</span></label>
                <select id="save_pattern_target_type" name="target_type" required
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach(\App\Models\EquipmentLogisticsPattern::TARGET_TYPES as $key => $label)
                        <option value="{{ $key }}" {{ $equipment->type === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="save_pattern_description" class="block text-[11px] sm:text-xs font-bold text-slate-700 mb-1">パターンの説明（任意）</label>
                <textarea id="save_pattern_description" name="description" rows="2"
                          placeholder="例: 前日倉庫出し→中継→1日目夜分散保管→2日目夜格納"
                          class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" data-action="close-save-pattern-modal"
                        class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                    キャンセル
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                    パターンとして保存
                </button>
            </div>
        </form>
    </div>
</div>

@vite(['resources/js/equipments.js'])
</x-layouts.app>

