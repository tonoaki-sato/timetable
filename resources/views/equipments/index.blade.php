<x-layouts.app title="資材・機材 搬出入計画">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <span>✅</span>
                <span>{{ session('status') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-bold">&times;</button>
        </div>
    @endif

    <!-- 1. ヘッダーセクション -->
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 rounded-2xl p-5 sm:p-6 text-white shadow-lg border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <span class="text-3xl">📦</span>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight">資材・機材 搬出入計画</h1>
                    <p class="text-xs text-slate-300 mt-1">実行委員会管理機材・イベント会社レンタル機材の倉庫出し・中継・現場設置・夜間分散保管・最終撤収スケジュール</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('equipments.create') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-md transition flex items-center space-x-1.5 cursor-pointer">
                <span>➕</span>
                <span>新規資材を登録</span>
            </a>
            <a href="{{ route('equipments.print', ['format' => $tab]) }}" target="_blank"
               class="px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white border border-slate-700 transition flex items-center space-x-1.5 cursor-pointer">
                <span>🖨️</span>
                <span>A4帳票印刷</span>
            </a>
            <button type="button" onclick="document.getElementById('modal-location-create').classList.remove('hidden')"
               class="px-3 py-2 rounded-xl text-xs font-bold bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 transition flex items-center space-x-1.5 cursor-pointer">
                <span>📍</span>
                <span>拠点を追加</span>
            </button>
        </div>
    </div>

    <!-- 2. ナビゲーションタブ -->
    <div class="border-b border-slate-200">
        <nav class="flex space-x-2 sm:space-x-4 overflow-x-auto pb-2 scrollbar-none" aria-label="Tabs">
            <a href="{{ route('equipments.index', ['tab' => 'flow']) }}"
               class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition flex items-center space-x-2 {{ $tab === 'flow' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <span>📊</span>
                <span>① 資材別フロー（全体俯瞰）</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $tab === 'flow' ? 'bg-indigo-700 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $equipments->count() }}</span>
            </a>

            <a href="{{ route('equipments.index', ['tab' => 'checklist']) }}"
               class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition flex items-center space-x-2 {{ $tab === 'checklist' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <span>⏱️</span>
                <span>② フェーズ別チェックリスト</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $tab === 'checklist' ? 'bg-indigo-700 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $checklistPlans->count() }}</span>
            </a>

            <a href="{{ route('equipments.index', ['tab' => 'locations']) }}"
               class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition flex items-center space-x-2 {{ $tab === 'locations' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <span>📍</span>
                <span>③ 拠点別 保管 & 貸切スケジュール</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $tab === 'locations' ? 'bg-indigo-700 text-white' : 'bg-slate-200 text-slate-700' }}">{{ count($locationsData) }}</span>
            </a>

            <a href="{{ route('equipments.index', ['tab' => 'events']) }}"
               class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition flex items-center space-x-2 {{ $tab === 'events' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <span>🏢</span>
                <span>④ イベント会社スケジュール表</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $tab === 'events' ? 'bg-indigo-700 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $eventCompanyPlans->count() }}</span>
            </a>
        </nav>
    </div>

    <!-- ========================================================================= -->
    <!-- タブ①: 資材別フロー（全体俯瞰） -->
    <!-- ========================================================================= -->
    @if($tab === 'flow')
        <!-- 絞り込みバー -->
        <div class="bg-white p-3 sm:p-4 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center space-x-1.5 flex-wrap">
                <span class="text-xs font-bold text-slate-500 mr-1">区分:</span>
                <a href="{{ route('equipments.index', ['tab' => 'flow']) }}"
                   class="px-3 py-1 rounded-lg text-xs font-bold transition {{ !$typeFilter ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    すべて ({{ $equipments->count() }})
                </a>
                <a href="{{ route('equipments.index', ['tab' => 'flow', 'type' => 'committee', 'lane_id' => $laneFilter]) }}"
                   class="px-3 py-1 rounded-lg text-xs font-bold transition {{ $typeFilter === 'committee' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200' }}">
                    🏛️ 実行委員会管理
                </a>
                <a href="{{ route('equipments.index', ['tab' => 'flow', 'type' => 'rental', 'lane_id' => $laneFilter]) }}"
                   class="px-3 py-1 rounded-lg text-xs font-bold transition {{ $typeFilter === 'rental' ? 'bg-sky-600 text-white' : 'bg-sky-50 text-sky-800 hover:bg-sky-100 border border-sky-200' }}">
                    🏢 イベント会社レンタル
                </a>
            </div>

            <!-- 担当班選択 -->
            <form method="GET" action="{{ route('equipments.index') }}" class="flex items-center space-x-2">
                <input type="hidden" name="tab" value="flow">
                @if($typeFilter)
                    <input type="hidden" name="type" value="{{ $typeFilter }}">
                @endif
                <label for="lane-select" class="text-xs font-bold text-slate-500 whitespace-nowrap">担当班:</label>
                <select id="lane-select" name="lane_id" onchange="this.form.submit()"
                        class="text-xs rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 pl-2.5 pr-8">
                    <option value="">全班表示</option>
                    @foreach($lanes as $lane)
                        <option value="{{ $lane->id }}" {{ $laneFilter == $lane->id ? 'selected' : '' }}>
                            {{ $lane->icon }} {{ $lane->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <!-- 資材カード一覧 -->
        <div class="space-y-4">
            @forelse($equipments as $item)
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 hover:border-slate-300 transition space-y-3">
                    <!-- 資材基本情報行 -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div class="flex items-center space-x-2.5 flex-wrap">
                            <span class="text-xl">
                                @if($item->isCommittee()) 🏛️ @else 🏢 @endif
                            </span>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('equipments.show', $item) }}" class="font-bold text-slate-900 text-base hover:text-indigo-600 transition">
                                        {{ $item->name }}
                                    </a>
                                    <span class="text-sm font-black font-mono px-2 py-0.5 rounded-full {{ $item->isCommittee() ? 'bg-emerald-100 text-emerald-800' : 'bg-sky-100 text-sky-800' }}">
                                        {{ $item->quantity }} {{ $item->unit }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2 text-xs text-slate-500 mt-0.5 flex-wrap">
                                    <span class="font-bold {{ $item->isCommittee() ? 'text-emerald-700' : 'text-sky-700' }}">
                                        {{ $item->type_label }}
                                    </span>
                                    <span>•</span>
                                    @if($item->isCommittee())
                                        <span>定常保管: <strong class="text-slate-700">{{ $item->storage_location ?: '番所地下倉庫' }}</strong></span>
                                    @else
                                        <span>業者: <strong class="text-slate-700">{{ $item->event_company_name ?: 'イベント会社' }}</strong></span>
                                        @if($item->event_company_contact)
                                            <span class="text-[11px] text-slate-400">({{ $item->event_company_contact }})</span>
                                        @endif
                                    @endif
                                    @if($item->lane)
                                        <span>•</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                            {{ $item->lane->icon }} {{ $item->lane->name }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- 操作リンク -->
                        <div class="flex items-center space-x-1.5 shrink-0 self-end sm:self-center">
                            <a href="{{ route('equipments.show', $item) }}" class="px-2.5 py-1 rounded-lg text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 transition">
                                詳細・ステップ編集 ({{ $item->plans->count() }})
                            </a>
                            <a href="{{ route('equipments.edit', $item) }}" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" title="資材情報を編集">
                                ✏️
                            </a>
                        </div>
                    </div>

                    <!-- 搬出入ステップの水平フロー（タイムライン） -->
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center justify-between">
                            <span>搬出入・中継・夜間保管フロー</span>
                            <span class="text-[10px] font-normal text-slate-500 font-mono">
                                完了進捗: {{ $item->plans->where('status', 'completed')->count() }} / {{ $item->plans->count() }}
                            </span>
                        </div>

                        @if($item->plans->isEmpty())
                            <div class="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-200 text-center text-xs text-slate-500 flex items-center justify-center space-x-2">
                                <span>計画ステップがまだ登録されていません。</span>
                                <a href="{{ route('equipments.show', $item) }}" class="text-indigo-600 font-bold hover:underline">
                                    テンプレートを適用して自動生成 ➔
                                </a>
                            </div>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5">
                                @foreach($item->plans as $plan)
                                    <x-equipments.step-badge :plan="$plan" />
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-12 text-center bg-white rounded-2xl border border-slate-200 text-slate-400 space-y-3">
                    <span class="text-4xl block">📦</span>
                    <p class="font-bold text-slate-700">登録されている資材はありません</p>
                    <p class="text-xs text-slate-400">「新規資材を登録」から機材と搬出入計画を追加してください。</p>
                    <a href="{{ route('equipments.create') }}" class="inline-block px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-sm hover:bg-indigo-500 transition">
                        新規資材を登録する
                    </a>
                </div>
            @endforelse
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- タブ②: フェーズ別チェックリスト（当日・前日運行用） -->
    <!-- ========================================================================= -->
    @if($tab === 'checklist')
        <!-- フェーズ選択ピル -->
        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs flex items-center space-x-2 overflow-x-auto scrollbar-none">
            <span class="text-xs font-bold text-slate-500 whitespace-nowrap mr-1">フェーズ:</span>
            <a href="{{ route('equipments.index', ['tab' => 'checklist']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition {{ !$phaseFilter ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                全フェーズ ({{ $checklistPlans->count() }})
            </a>
            @foreach(\App\Models\EquipmentLogisticsPlan::DAY_PHASES as $key => $label)
                <a href="{{ route('equipments.index', ['tab' => 'checklist', 'phase' => $key]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition {{ $phaseFilter === $key ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <!-- チェックリスト一覧 -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-4">状態</th>
                            <th class="py-3 px-3">フェーズ / 実施日</th>
                            <th class="py-3 px-3">予定時刻</th>
                            <th class="py-3 px-4">資材名・数量</th>
                            <th class="py-3 px-4">アクション / タイトル</th>
                            <th class="py-3 px-4">移動ルート (元 ➔ 先)</th>
                            <th class="py-3 px-3">作業主体 / 立会</th>
                            <th class="py-3 px-4">注意事項・申し送り</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($checklistPlans as $plan)
                            @php $warning = $plan->checkToLocationAvailability(); @endphp
                            <tr class="hover:bg-slate-50/70 transition {{ $plan->isCompleted() ? 'bg-emerald-50/20 text-slate-500' : '' }}">
                                <!-- 状態トグル -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <form method="POST" action="{{ route('equipment-plans.update-status', $plan) }}">
                                        @csrf
                                        @method('PATCH')
                                        @if($plan->status === 'completed')
                                            <input type="hidden" name="status" value="planned">
                                            <button type="submit" class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 hover:bg-emerald-200 cursor-pointer">
                                                <span>✓ 完了</span>
                                            </button>
                                        @elseif($plan->status === 'in_progress')
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300 hover:bg-emerald-100 cursor-pointer">
                                                <span>▶ 進行中</span>
                                            </button>
                                        @else
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-300 hover:bg-emerald-100 hover:text-emerald-800 cursor-pointer">
                                                <span>○ 未着手</span>
                                            </button>
                                        @endif
                                    </form>
                                </td>

                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="font-bold text-slate-800">{{ $plan->phase_label }}</span>
                                    @if($plan->target_date)
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $plan->target_date->format('m/d') }}</div>
                                    @endif
                                </td>

                                <td class="py-3 px-3 whitespace-nowrap font-mono font-bold text-slate-700">
                                    @if($plan->start_time)
                                        {{ substr($plan->start_time, 0, 5) }}@if($plan->end_time)〜{{ substr($plan->end_time, 0, 5) }}@endif
                                    @else
                                        -
                                    @endif
                                </td>

                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">
                                        <a href="{{ route('equipments.show', $plan->equipment) }}" class="hover:underline">
                                            {{ $plan->equipment->name }}
                                        </a>
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        @if($plan->quantity)
                                            移動数量: <strong class="text-indigo-700 font-mono">{{ $plan->quantity }}{{ $plan->equipment->unit }}</strong>
                                            (全体: {{ $plan->equipment->quantity }}{{ $plan->equipment->unit }})
                                        @else
                                            全体: {{ $plan->equipment->quantity }}{{ $plan->equipment->unit }}
                                        @endif
                                    </div>
                                </td>

                                <td class="py-3 px-4 font-medium text-slate-800">
                                    <div>{{ $plan->title }}</div>
                                    <span class="text-[10px] text-slate-400">({{ $plan->action_label }})</span>
                                </td>

                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-1 text-slate-700">
                                        <span class="text-slate-500">{{ $plan->from_location ?: '-' }}</span>
                                        <span class="text-slate-400 font-bold">➔</span>
                                        <span class="font-bold text-slate-900">{{ $plan->to_location ?: '-' }}</span>
                                    </div>
                                    @if($warning)
                                        <div class="text-[10px] text-amber-700 bg-amber-50 border border-amber-200 rounded p-1 mt-1 font-bold">
                                            ⚠️ {{ $warning['message'] }}
                                        </div>
                                    @endif
                                </td>

                                <td class="py-3 px-3 whitespace-nowrap">
                                    @if($plan->operator_type === 'event_company')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800">イベント会社</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700">委員会スタッフ</span>
                                    @endif

                                    @if($plan->witness_required)
                                        <div class="mt-1 text-[10px] font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded px-1 py-0.2">
                                            👁️ 立会: {{ $plan->witnessStaff?->name ?: '要立ち会い' }}
                                        </div>
                                    @endif
                                </td>

                                <td class="py-3 px-4 text-slate-600 max-w-xs">
                                    {{ $plan->notes ?: '-' }}
                                    @if($plan->contact_info)
                                        <div class="text-[10px] text-slate-400 font-mono mt-0.5">📞 {{ $plan->contact_info }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-slate-400">該当フェーズの搬出入ステップはありません</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- タブ③: 拠点別 保管状況 & 貸切スケジュール -->
    <!-- ========================================================================= -->
    @if($tab === 'locations')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($locationsData as $locItem)
                @php
                    $loc = $locItem['location'];
                    $nightPlans = $locItem['nightPlans'];
                    $collectionPlans = $locItem['collectionPlans'];
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4 hover:border-slate-300 transition flex flex-col justify-between">
                    <div class="space-y-3">
                        <!-- 拠点ヘッダー -->
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xl">
                                        @if($loc->type === 'parking') 🅿️
                                        @elseif($loc->type === 'facility') 🏛️
                                        @elseif($loc->type === 'open_space') 🎪
                                        @else 📍 @endif
                                    </span>
                                    <h2 class="font-bold text-slate-900 text-base">{{ $loc->name }}</h2>
                                </div>
                                <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">
                                    {{ $loc->type_label }}
                                </span>
                            </div>

                            <button type="button"
                                    onclick="editLocation({{ json_encode($loc) }})"
                                    class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition text-xs" title="拠点を編集">
                                ✏️ 編集
                            </button>
                        </div>

                        <!-- 貸切期間・終日利用表示 -->
                        <div class="p-2.5 rounded-xl border text-xs {{ $loc->is_all_day ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-amber-50 border-amber-200 text-amber-900' }}">
                            <div class="font-bold flex items-center justify-between">
                                <span>{{ $loc->is_all_day ? '🟢 終日使用可能' : '🟡 貸切・利用可能期間あり' }}</span>
                            </div>
                            @if(!$loc->is_all_day)
                                <div class="mt-1 font-mono text-[11px] font-bold">
                                    @if($loc->available_from)
                                        {{ $loc->available_from->format('n/j (D) H:i') }}
                                    @else
                                        制限なし
                                    @endif
                                    〜
                                    @if($loc->available_to)
                                        {{ $loc->available_to->format('n/j (D) H:i') }}
                                    @else
                                        制限なし
                                    @endif
                                </div>
                            @endif
                            @if($loc->description)
                                <p class="text-[10px] mt-1 text-slate-600 leading-tight">{{ $loc->description }}</p>
                            @endif
                        </div>

                        <!-- 1日目夜の分散夜間保管機材 -->
                        <div class="space-y-1.5">
                            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider flex items-center justify-between">
                                <span>🌙 1日目夜の夜間保管機材</span>
                                <span class="font-mono text-indigo-700 font-bold">{{ $nightPlans->count() }}件</span>
                            </div>
                            @if($nightPlans->isEmpty())
                                <p class="text-xs text-slate-400 italic bg-slate-50 p-2 rounded-lg">夜間保管機材の指定はありません</p>
                            @else
                                <div class="space-y-1">
                                    @foreach($nightPlans as $np)
                                        <div class="p-2 rounded-lg bg-indigo-50/60 border border-indigo-100 text-xs flex items-center justify-between">
                                            <span class="font-bold text-indigo-950 truncate">{{ $np->equipment->name }}</span>
                                            <span class="font-mono font-bold text-indigo-700 shrink-0 ml-1">
                                                {{ $np->quantity ?: $np->equipment->quantity }}{{ $np->equipment->unit }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- 2日目夜〜翌日の業者回収待ち機材 -->
                        @if($collectionPlans->isNotEmpty())
                            <div class="space-y-1.5 pt-1">
                                <div class="text-[11px] font-bold text-amber-700 uppercase tracking-wider flex items-center justify-between">
                                    <span>📦 業者回収待ち一時保管</span>
                                    <span class="font-mono font-bold text-amber-700">{{ $collectionPlans->count() }}件</span>
                                </div>
                                <div class="space-y-1">
                                    @foreach($collectionPlans as $cp)
                                        <div class="p-2 rounded-lg bg-amber-50 border border-amber-200 text-xs flex items-center justify-between">
                                            <span class="font-bold text-amber-950 truncate">{{ $cp->equipment->name }}</span>
                                            <span class="font-mono font-bold text-amber-800 shrink-0 ml-1">
                                                {{ $cp->quantity ?: $cp->equipment->quantity }}{{ $cp->equipment->unit }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- タブ④: イベント会社スケジュール表 -->
    <!-- ========================================================================= -->
    @if($tab === 'events')
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-6 space-y-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">イベント会社 事前搬入・設営・解体・搬出スケジュール</h2>
                <p class="text-xs text-slate-500 mt-0.5">イベント会社が一任で実施するパイプテント等の設営・解体時刻、および給水設備等の受取・引渡立会時刻一覧</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-3">実施日 / 時刻</th>
                            <th class="py-3 px-4">資材名・数量</th>
                            <th class="py-3 px-4">作業区分 / タイトル</th>
                            <th class="py-3 px-4">イベント会社名・担当連絡先</th>
                            <th class="py-3 px-4">作業場所 / 移動ルート</th>
                            <th class="py-3 px-3">幹事・スタッフ立会</th>
                            <th class="py-3 px-4">申し送り・注意事項</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($eventCompanyPlans as $plan)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-3 whitespace-nowrap font-mono font-bold text-slate-800">
                                    <div>{{ $plan->target_date ? $plan->target_date->format('m/d (D)') : $plan->phase_label }}</div>
                                    <div class="text-indigo-600">{{ substr($plan->start_time, 0, 5) }}@if($plan->end_time)〜{{ substr($plan->end_time, 0, 5) }}@endif</div>
                                </td>

                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $plan->equipment->name }}</div>
                                    <span class="text-xs font-mono font-bold text-slate-600">{{ $plan->quantity ?: $plan->equipment->quantity }}{{ $plan->equipment->unit }}</span>
                                </td>

                                <td class="py-3 px-4 font-bold text-slate-800">
                                    {{ $plan->title }}
                                    <div class="text-[10px] text-slate-400 font-normal">作業主体: {{ $plan->operator_label }}</div>
                                </td>

                                <td class="py-3 px-4 text-slate-700">
                                    <div class="font-bold">{{ $plan->equipment->event_company_name ?: 'イベント会社' }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono">
                                        {{ $plan->contact_info ?: ($plan->equipment->event_company_contact ?: '-') }}
                                    </div>
                                </td>

                                <td class="py-3 px-4 text-slate-700">
                                    <div class="flex items-center space-x-1">
                                        <span>{{ $plan->from_location ?: '-' }}</span>
                                        <span class="text-slate-400 font-bold">➔</span>
                                        <span class="font-bold text-slate-900">{{ $plan->to_location ?: '-' }}</span>
                                    </div>
                                </td>

                                <td class="py-3 px-3 whitespace-nowrap">
                                    @if($plan->witness_required)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                            👁️ 要立会い: {{ $plan->witnessStaff?->name ?: '幹事' }}
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500">
                                            立会不要（一任）
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3 px-4 text-slate-600 max-w-xs">
                                    {{ $plan->notes ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-slate-400">イベント会社関連の計画はありません</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

<!-- ========================================================================= -->
<!-- 拠点登録・編集モーダル -->
<!-- ========================================================================= -->
<div id="modal-location-create" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 id="modal-location-title" class="text-base font-bold text-slate-900 flex items-center space-x-2">
                <span>📍</span>
                <span>拠点の登録</span>
            </h3>
            <button type="button" onclick="closeLocationModal()" class="text-slate-400 hover:text-slate-600 font-bold text-lg">&times;</button>
        </div>

        <form id="form-location" method="POST" action="{{ route('logistics-locations.store') }}" class="space-y-4">
            @csrf
            <div id="form-location-method"></div>

            <div>
                <label for="location_name" class="block text-xs font-bold text-slate-700 mb-1">拠点名 <span class="text-rose-500">*</span></label>
                <input type="text" id="location_name" name="name" required placeholder="例: 税務署前駐車場、北川駐車場、○○広場"
                       class="w-full text-xs rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 p-2.5">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="location_type" class="block text-xs font-bold text-slate-700 mb-1">拠点種別</label>
                    <select id="location_type" name="type" class="w-full text-xs rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 p-2.5">
                        <option value="parking">駐車場</option>
                        <option value="facility">施設・倉庫</option>
                        <option value="open_space">空きスペース</option>
                        <option value="other">その他</option>
                    </select>
                </div>

                <div>
                    <label for="location_display_order" class="block text-xs font-bold text-slate-700 mb-1">表示順</label>
                    <input type="number" id="location_display_order" name="display_order" value="10" min="0"
                           class="w-full text-xs rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 p-2.5">
                </div>
            </div>

            <!-- 終日利用可能チェックボックス -->
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="location_is_all_day" name="is_all_day" value="1"
                           onchange="toggleAvailabilityInputs(this.checked)"
                           class="rounded text-indigo-600 focus:ring-indigo-500">
                    <span class="text-xs font-bold text-slate-800">終日使用可能（貸切期間の制限なし）</span>
                </label>
                <p class="text-[11px] text-slate-400 mt-1">番所地下倉庫やコミュニティルームなど常時利用できる場合はチェック</p>
            </div>

            <!-- 貸切期間設定（終日利用時は非活性） -->
            <div id="availability-inputs" class="grid grid-cols-2 gap-3 p-3 rounded-xl bg-amber-50/50 border border-amber-200">
                <div>
                    <label for="location_available_from" class="block text-[11px] font-bold text-amber-900 mb-1">貸切開始日時</label>
                    <input type="datetime-local" id="location_available_from" name="available_from"
                           class="w-full text-xs rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 p-2">
                </div>
                <div>
                    <label for="location_available_to" class="block text-[11px] font-bold text-amber-900 mb-1">貸切終了日時</label>
                    <input type="datetime-local" id="location_available_to" name="available_to"
                           class="w-full text-xs rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 p-2">
                </div>
            </div>

            <div>
                <label for="location_description" class="block text-xs font-bold text-slate-700 mb-1">備考・注意事項</label>
                <textarea id="location_description" name="description" rows="2" placeholder="例: 関係車両のみ13時より駐車可。鍵は事務室。"
                          class="w-full text-xs rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 p-2.5"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeLocationModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                    キャンセル
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-500 shadow-sm transition">
                    保存する
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleAvailabilityInputs(isAllDay) {
        const box = document.getElementById('availability-inputs');
        if (isAllDay) {
            box.classList.add('opacity-40', 'pointer-events-none');
        } else {
            box.classList.remove('opacity-40', 'pointer-events-none');
        }
    }

    function editLocation(location) {
        document.getElementById('modal-location-title').innerHTML = '<span>📍</span><span>拠点の編集</span>';
        const form = document.getElementById('form-location');
        form.action = `/logistics-locations/${location.id}`;
        document.getElementById('form-location-method').innerHTML = '<input type="hidden" name="_method" value="PUT">';

        document.getElementById('location_name').value = location.name || '';
        document.getElementById('location_type').value = location.type || 'parking';
        document.getElementById('location_display_order').value = location.display_order ?? 10;
        document.getElementById('location_description').value = location.description || '';

        const isAllDay = !!location.is_all_day;
        document.getElementById('location_is_all_day').checked = isAllDay;
        toggleAvailabilityInputs(isAllDay);

        if (location.available_from) {
            document.getElementById('location_available_from').value = location.available_from.replace(' ', 'T').substring(0, 16);
        } else {
            document.getElementById('location_available_from').value = '';
        }

        if (location.available_to) {
            document.getElementById('location_available_to').value = location.available_to.replace(' ', 'T').substring(0, 16);
        } else {
            document.getElementById('location_available_to').value = '';
        }

        document.getElementById('modal-location-create').classList.remove('hidden');
    }

    function closeLocationModal() {
        document.getElementById('modal-location-create').classList.add('hidden');
        document.getElementById('form-location').reset();
        document.getElementById('form-location').action = "{{ route('logistics-locations.store') }}";
        document.getElementById('form-location-method').innerHTML = '';
        document.getElementById('modal-location-title').innerHTML = '<span>📍</span><span>拠点の登録</span>';
        toggleAvailabilityInputs(false);
    }
</script>
</x-layouts.app>

