<x-layouts.app title="資材・機材 搬出入計画">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- 1. ヘッダーセクション -->
    <x-page-header
        title="資材・機材 搬出入計画"
        icon="🚚"
        description="実行委員会管理機材・提携イベント会社レンタル機材・個人私物の倉庫出し・中継・現場設置・夜間分散保管・最終撤収スケジュール">
        <x-slot:badges>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                全 {{ $equipments->count() }} 資材
            </span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                🧩 {{ $patterns->count() }} パターン
            </span>
        </x-slot:badges>
        <x-slot:actions>
            <a href="{{ route('equipments.index') }}"
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                <span>📦</span>
                <span>資材管理を開く</span>
            </a>
            <a href="{{ route('logistics.print', ['format' => $tab]) }}" target="_blank"
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                <span>🖨️</span>
                <span>A4帳票印刷</span>
            </a>
            <a href="{{ route('equipments.create') }}"
                class="inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                <span>➕</span>
                <span>新規資材を登録</span>
            </a>
        </x-slot:actions>
    </x-page-header>

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

    <!-- 2. ナビゲーションタブ -->
    <div class="border-b border-slate-200">
        <nav class="flex space-x-2 sm:space-x-3 overflow-x-auto pb-2 scrollbar-none" aria-label="Tabs">
            <a href="{{ route('logistics.index', ['tab' => 'flow']) }}"
               class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition flex items-center space-x-2 {{ $tab === 'flow' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <span>📊</span>
                <span>① 資材別フロー（全体俯瞰）</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $tab === 'flow' ? 'bg-indigo-700 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $equipments->count() }}</span>
            </a>

            <a href="{{ route('logistics.index', ['tab' => 'checklist']) }}"
               class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition flex items-center space-x-2 {{ $tab === 'checklist' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <span>⏱️</span>
                <span>② フェーズ別チェックリスト</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $tab === 'checklist' ? 'bg-indigo-700 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $checklistPlans->count() }}</span>
            </a>

            <a href="{{ route('logistics.index', ['tab' => 'locations']) }}"
               class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition flex items-center space-x-2 {{ $tab === 'locations' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <span>📍</span>
                <span>③ 拠点別 保管 & 利用可能時間</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $tab === 'locations' ? 'bg-indigo-700 text-white' : 'bg-slate-200 text-slate-700' }}">{{ count($locationsData) }}</span>
            </a>

            <a href="{{ route('logistics.index', ['tab' => 'events']) }}"
               class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition flex items-center space-x-2 {{ $tab === 'events' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <span>🏢</span>
                <span>④ 提携イベント会社スケジュール表</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $tab === 'events' ? 'bg-indigo-700 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $eventCompanyPlans->count() }}</span>
            </a>

            <a href="{{ route('logistics.index', ['tab' => 'patterns']) }}"
               class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition flex items-center space-x-2 {{ $tab === 'patterns' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <span>🧩</span>
                <span>⑤ 搬出入パターン管理</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $tab === 'patterns' ? 'bg-indigo-700 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $patterns->count() }}</span>
            </a>
        </nav>
    </div>

    <!-- ========================================================================= -->
    <!-- タブ①: 資材別フロー（全体俯瞰） -->
    <!-- ========================================================================= -->
    @if($tab === 'flow')
        <!-- 絞り込みバー -->
        <div class="bg-white p-3 sm:p-4 rounded-xl border border-slate-200 shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center space-x-1.5 flex-wrap gap-y-1">
                    <span class="text-xs font-bold text-slate-500 mr-1">区分:</span>
                    <a href="{{ route('logistics.index', array_filter(['tab' => 'flow', 'lane_id' => $laneFilter])) }}"
                       class="px-3 py-1 rounded-lg text-xs font-bold transition {{ !$typeFilter ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        すべて ({{ $equipments->count() }})
                    </a>
                    <a href="{{ route('logistics.index', array_filter(['tab' => 'flow', 'type' => 'committee', 'lane_id' => $laneFilter])) }}"
                       class="px-3 py-1 rounded-lg text-xs font-bold transition {{ $typeFilter === 'committee' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200' }}">
                        🏛️ 実行委員会管理
                    </a>
                    <a href="{{ route('logistics.index', array_filter(['tab' => 'flow', 'type' => 'rental', 'lane_id' => $laneFilter])) }}"
                       class="px-3 py-1 rounded-lg text-xs font-bold transition {{ $typeFilter === 'rental' ? 'bg-sky-600 text-white' : 'bg-sky-50 text-sky-800 hover:bg-sky-100 border border-sky-200' }}">
                        🏢 イベント会社レンタル
                    </a>
                    <a href="{{ route('logistics.index', array_filter(['tab' => 'flow', 'type' => 'personal', 'lane_id' => $laneFilter])) }}"
                       class="px-3 py-1 rounded-lg text-xs font-bold transition {{ $typeFilter === 'personal' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200' }}">
                        🎒 私物
                    </a>
                </div>

                <!-- 担当班選択 -->
                <form method="GET" action="{{ route('logistics.index') }}" class="flex items-center space-x-2">
                    <input type="hidden" name="tab" value="flow">
                    @if($typeFilter)
                        <input type="hidden" name="type" value="{{ $typeFilter }}">
                    @endif
                    <label for="lane-select" class="text-xs font-bold text-slate-500 whitespace-nowrap">担当班:</label>
                    <select id="lane-select" name="lane_id" data-auto-submit
                            class="px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 pr-8">
                        <option value="">全班表示</option>
                        @foreach($lanes as $lane)
                            <option value="{{ $lane->id }}" {{ $laneFilter == $lane->id ? 'selected' : '' }}>
                                {{ $lane->icon }} {{ $lane->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        <!-- 資材カード一覧 -->
        <div class="space-y-4">
            @forelse($equipments as $item)
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 hover:border-slate-300 transition space-y-3">
                    <!-- 資材基本情報行 -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div class="flex items-center space-x-2.5 flex-wrap">
                            <span class="text-xl">
                                @if($item->isCommittee()) 🏛️ @elseif($item->isRental()) 🏢 @else 🎒 @endif
                            </span>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('equipments.show', $item) }}" class="font-bold text-slate-900 text-base hover:text-indigo-600 transition">
                                        {{ $item->name }}
                                    </a>
                                    <span class="text-sm font-black font-mono px-2 py-0.5 rounded-full {{ $item->isCommittee() ? 'bg-emerald-100 text-emerald-800' : ($item->isRental() ? 'bg-sky-100 text-sky-800' : 'bg-amber-100 text-amber-800') }}">
                                        {{ $item->formatted_quantity }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2 text-xs text-slate-500 mt-0.5 flex-wrap">
                                    <span class="font-bold {{ $item->isCommittee() ? 'text-emerald-700' : ($item->isRental() ? 'text-sky-700' : 'text-amber-700') }}">
                                        {{ $item->type_label }}
                                    </span>
                                    @if(!$item->isRental())
                                        <span>•</span>
                                        <span>定常保管: <strong class="text-slate-700">{{ $item->storage_location ?: ($item->isPersonal() ? '自宅' : '番所地下倉庫') }}</strong></span>
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
                            <a href="{{ route('equipments.show', $item) }}" class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition inline-flex items-center space-x-1" title="詳細・計画設定">
                                <span>詳細・計画</span>
                            </a>
                            <a href="{{ route('equipments.edit', $item) }}" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium transition inline-flex items-center space-x-1" title="資材情報を編集">
                                <span>✏️</span>
                                <span>編集</span>
                            </a>
                            @can('delete', $item)
                                <form method="POST" action="{{ route('equipments.destroy', $item) }}" class="inline"
                                      data-confirm="資材「{{ $item->name }}」を削除しますか？&#10;紐づくすべての搬出入計画ステップも削除されます。">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-xs font-bold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition cursor-pointer" title="資材を削除">
                                        <span>🗑️</span>
                                    </button>
                                </form>
                            @endcan
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
                                    パターンを適用して一括生成 ➔
                                </a>
                            </div>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5">
                                @foreach($item->plans as $plan)
                                    <x-logistics.step-badge :plan="$plan" />
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
            <a href="{{ route('logistics.index', ['tab' => 'checklist']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition {{ !$phaseFilter ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                全フェーズ ({{ $checklistPlans->count() }})
            </a>
            @foreach(\App\Models\EquipmentLogisticsPlan::DAY_PHASES as $key => $label)
                <a href="{{ route('logistics.index', ['tab' => 'checklist', 'phase' => $key]) }}"
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
                            <th class="py-3 px-3 text-right">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($checklistPlans as $plan)
                            @php $warnings = $plan->location_warnings; @endphp
                            <tr class="hover:bg-slate-50/70 transition {{ $plan->isCompleted() ? 'bg-emerald-50/20 text-slate-500' : '' }}">
                                <!-- 状態トグル -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <form method="POST" action="{{ route('logistics.plans.update-status', $plan) }}">
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
                                        @if($plan->formatted_quantity !== '')
                                            対象: <strong class="text-indigo-700 font-mono">{{ $plan->formatted_quantity }}</strong>
                                            (全体: {{ $plan->equipment->formatted_quantity }})
                                        @else
                                            全体: {{ $plan->equipment->formatted_quantity }}
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
                                    @foreach($warnings as $warning)
                                        <div class="text-[10px] text-amber-700 bg-amber-50 border border-amber-200 rounded p-1 mt-1 font-bold">
                                            ⚠️ {{ $warning['message'] }}
                                        </div>
                                    @endforeach
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
                                </td>

                                <td class="py-3 px-3 text-right whitespace-nowrap">
                                    @can('delete', $plan)
                                        <form method="POST" action="{{ route('logistics.plans.destroy', $plan) }}" class="inline"
                                              data-confirm="ステップ「{{ $plan->title }}」を削除しますか？">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-1 text-slate-400 hover:text-rose-600 rounded transition cursor-pointer"
                                                    title="ステップを削除">
                                                🗑️
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-8 text-center text-slate-400">該当フェーズの搬出入ステップはありません</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- タブ③: 拠点別 保管状況 & 利用可能時間 -->
    <!-- ========================================================================= -->
    @if($tab === 'locations')
        <div class="space-y-6">
            <!-- 拠点一覧グリッド -->
            @if(empty($locationsData))
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-10 text-center space-y-3">
                    <div class="text-3xl">📍</div>
                    <div class="text-sm font-bold text-slate-800">登録されている拠点がありません</div>
                    <p class="text-xs text-slate-500">
                        拠点管理画面から保管場所・中継駐車場・コミュニティスペース等の拠点を登録してください。
                    </p>
                    <div>
                        <a href="{{ route('locations.create') }}"
                           class="inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                            <span>➕</span>
                            <span>拠点を新規登録する</span>
                        </a>
                    </div>
                </div>
            @else
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
                                            <span class="text-xl">📍</span>
                                            <h2 class="font-bold text-slate-900 text-base">{{ $loc->name }}</h2>
                                        </div>
                                        <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">
                                            {{ $loc->type?->label ?? '未分類' }}
                                        </span>
                                    </div>

                                    <a href="{{ route('locations.show', $loc) }}"
                                       class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition text-xs font-bold" title="拠点詳細・利用枠">
                                        🔍 詳細・利用枠
                                    </a>
                                </div>

                                <!-- 貸切期間・終日利用表示 -->
                                <div class="p-2.5 rounded-xl border text-xs {{ $loc->is_all_day ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-amber-50 border-amber-200 text-amber-900' }}">
                                    <div class="font-bold flex items-center justify-between">
                                        <span>{{ $loc->is_all_day ? '🟢 終日常時利用可能' : '🟡 個別時間帯枠での管理' }}</span>
                                    </div>
                                    @if(!$loc->is_all_day)
                                        @if($loc->schedules->isNotEmpty())
                                            <div class="mt-1.5 space-y-1">
                                                @foreach($loc->schedules as $slot)
                                                    <div class="flex items-center justify-between text-[11px] bg-white/80 px-2 py-1 rounded border border-amber-200/70">
                                                        <span class="font-mono font-bold text-slate-800">
                                                            {{ $slot->start_at->format('n/j H:i') }}〜{{ $slot->end_at->format($slot->start_at->isSameDay($slot->end_at) ? 'H:i' : 'n/j H:i') }}
                                                        </span>
                                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold border {{ $slot->availability_status_badge_class }}">
                                                            {{ $slot->availability_status_label }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="mt-1 text-[11px] text-rose-700 font-bold">利用可能時間帯枠が未登録です</div>
                                        @endif
                                    @endif
                                    @if($loc->notes)
                                        <p class="text-[10px] mt-1.5 text-slate-600 leading-tight line-clamp-2">{{ $loc->notes }}</p>
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
                                                <div class="p-2 rounded-lg bg-indigo-50/60 border border-indigo-100 text-xs flex items-center justify-between gap-2">
                                                    <a href="{{ route('equipments.show', $np->equipment) }}" class="font-bold text-indigo-950 hover:underline truncate">
                                                        {{ $np->equipment->name }}
                                                    </a>
                                                    <span class="font-mono font-bold text-indigo-700 shrink-0">
                                                        {{ $np->formatted_quantity !== '' ? $np->formatted_quantity : $np->equipment->formatted_quantity }}
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
                                            <span>📦 業者回収待ち・撤収後保管</span>
                                            <span class="font-mono font-bold text-amber-700">{{ $collectionPlans->count() }}件</span>
                                        </div>
                                        <div class="space-y-1">
                                            @foreach($collectionPlans as $cp)
                                                <div class="p-2 rounded-lg bg-amber-50 border border-amber-200 text-xs flex items-center justify-between gap-2">
                                                    <a href="{{ route('equipments.show', $cp->equipment) }}" class="font-bold text-amber-950 hover:underline truncate">
                                                        {{ $cp->equipment->name }}
                                                    </a>
                                                    <span class="font-mono font-bold text-amber-800 shrink-0">
                                                        {{ $cp->formatted_quantity !== '' ? $cp->formatted_quantity : $cp->equipment->formatted_quantity }}
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

            <!-- ⛺ 現地・テント内夜間保管（要防犯・荒天時退避確認） -->
            <div class="bg-white rounded-2xl border border-amber-300 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-amber-950 flex items-center space-x-2">
                            <span>⛺</span>
                            <span>現地・テント内夜間保管（要防犯・荒天時退避確認）</span>
                        </h3>
                        <p class="text-xs text-slate-600 mt-0.5">
                            1日目夜（day1_evening）に特定の拠点マスタへ回収せず、会場そのまま・テント内等で現地保管する計画の機材一覧です。
                        </p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                        {{ $onsiteNightPlans->count() }} 件
                    </span>
                </div>

                @if($onsiteNightPlans->isEmpty())
                    <p class="text-xs text-slate-400 italic bg-slate-50 p-3 rounded-xl">現地・テント内夜間保管のステップは現在登録されていません。</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($onsiteNightPlans as $op)
                            <div class="p-3 rounded-xl bg-amber-50/60 border border-amber-200 text-xs space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    <a href="{{ route('equipments.show', $op->equipment) }}" class="font-bold text-slate-900 hover:text-indigo-600 truncate">
                                        {{ $op->equipment->name }}
                                    </a>
                                    <span class="font-mono font-bold text-amber-800 shrink-0">
                                        {{ $op->formatted_quantity !== '' ? $op->formatted_quantity : $op->equipment->formatted_quantity }}
                                    </span>
                                </div>
                                <div class="text-[11px] font-bold text-amber-900">
                                    📍 {{ $op->to_location ?: '現地保管' }}
                                </div>
                                @if($op->notes)
                                    <div class="text-[10px] text-slate-600">💬 {{ $op->notes }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- タブ④: 提携イベント会社スケジュール表 -->
    <!-- ========================================================================= -->
    @if($tab === 'events')
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-6 space-y-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">提携イベント会社 事前搬入・設営・解体・搬出スケジュール</h2>
                <p class="text-xs text-slate-500 mt-0.5">提携イベント会社（1社）が実施するパイプテント等の設営・解体時刻、およびレンタル機材の受取・回収立会時刻一覧</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-3">実施日 / 時刻</th>
                            <th class="py-3 px-4">資材名・数量</th>
                            <th class="py-3 px-4">作業区分 / タイトル</th>
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
                                    <div class="text-indigo-600">
                                        @if($plan->start_time)
                                            {{ substr($plan->start_time, 0, 5) }}@if($plan->end_time)〜{{ substr($plan->end_time, 0, 5) }}@endif
                                        @else
                                            時間未定
                                        @endif
                                    </div>
                                </td>

                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">
                                        <a href="{{ route('equipments.show', $plan->equipment) }}" class="hover:underline">
                                            {{ $plan->equipment->name }}
                                        </a>
                                    </div>
                                    <span class="text-xs font-mono font-bold text-slate-600">
                                        {{ $plan->formatted_quantity !== '' ? $plan->formatted_quantity : $plan->equipment->formatted_quantity }}
                                    </span>
                                </td>

                                <td class="py-3 px-4 font-bold text-slate-800">
                                    {{ $plan->title }}
                                    <div class="text-[10px] text-slate-400 font-normal">作業主体: {{ $plan->operator_label }}</div>
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
                                <td colspan="6" class="p-8 text-center text-slate-400">イベント会社関連の計画はありません</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- タブ⑤: 搬出入パターン管理 (カスタムテンプレート CRUD) -->
    <!-- ========================================================================= -->
    @if($tab === 'patterns')
        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                        <span>🧩</span>
                        <span>カスタム搬出入パターン（テンプレート）管理</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        よく使う搬出入・中継・夜間保管のステップ構成をパターンとして自由に登録・編集できます。登録したパターンは各資材詳細画面からワンクリックで一括適用できます。
                    </p>
                </div>
                <button type="button" data-action="open-pattern-modal" data-mode="create"
                        class="inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer shrink-0">
                    <span>➕</span>
                    <span>新規パターンを作成</span>
                </button>
            </div>

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
                                        class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition cursor-pointer inline-flex items-center space-x-1">
                                    <span>🗑️</span>
                                    <span>削除</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-bold text-[11px]">
                                <tr>
                                    <th class="py-2 px-3">#</th>
                                    <th class="py-2 px-3">フェーズ</th>
                                    <th class="py-2 px-3">予定時刻</th>
                                    <th class="py-2 px-3">ステップ名</th>
                                    <th class="py-2 px-3">移動ルート (From ➔ To)</th>
                                    <th class="py-2 px-3">数量ルール</th>
                                    <th class="py-2 px-3">作業主体 / 立会</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($pattern->steps as $idx => $step)
                                    <tr>
                                        <td class="py-2 px-3 font-mono text-slate-400">{{ $idx + 1 }}</td>
                                        <td class="py-2 px-3 font-bold text-slate-700 whitespace-nowrap">{{ $step->phase_label }}</td>
                                        <td class="py-2 px-3 font-mono text-slate-600 whitespace-nowrap">
                                            @if($step->start_time)
                                                {{ substr($step->start_time, 0, 5) }}@if($step->end_time)〜{{ substr($step->end_time, 0, 5) }}@endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="py-2 px-3 font-bold text-slate-900">{{ $step->title }}</td>
                                        <td class="py-2 px-3 text-slate-700">
                                            <span>{{ $step->from_location_display }}</span>
                                            <span class="text-slate-400 font-bold mx-1">➔</span>
                                            <span class="font-bold">{{ $step->to_location_display }}</span>
                                        </td>
                                        <td class="py-2 px-3 whitespace-nowrap">
                                            <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold text-[11px]">
                                                {{ $step->quantity_rule_label }}
                                                @if($step->quantity_note)
                                                    ({{ $step->quantity_note }})
                                                @endif
                                            </span>
                                        </td>
                                        <td class="py-2 px-3 whitespace-nowrap">
                                            <span>{{ $step->operator_type === 'event_company' ? '🏢 イベント会社' : '👥 委員会スタッフ' }}</span>
                                            @if($step->witness_required)
                                                <span class="ml-1 text-rose-700 font-bold">👁️立会</span>
                                            @endif
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
                    <p class="text-xs text-slate-400">「新規パターンを作成」ボタンから登録するか、任意の資材詳細画面で組んだステップをパターンとして保存してください。</p>
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
    <div class="bg-white rounded-2xl max-w-5xl w-full p-5 sm:p-6 shadow-2xl border border-slate-200 text-left space-y-4 max-h-[90vh] overflow-y-auto">
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
                           placeholder="例: パターンA: 自前備品標準（前日出し・分散保管）、パターンB-2: カラーコーン型"
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
                       placeholder="例: 前日倉庫出し➔中継➔1日目設置➔夜間分散保管➔2日目設置➔撤収格納"
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
    <div class="pattern-step-row p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-2 text-xs">
        <div class="flex items-center justify-between gap-2">
            <span class="font-bold text-slate-700 step-row-number">ステップ #1</span>
            <button type="button" data-action="remove-pattern-step" class="text-rose-600 hover:text-rose-800 font-bold text-xs cursor-pointer">
                ✕ この行を削除
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-2">
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
                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">開始時刻</label>
                <input type="time" data-field="start_time" class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded text-xs font-mono">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">終了時刻</label>
                <input type="time" data-field="end_time" class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded text-xs font-mono">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
            <div class="sm:col-span-2">
                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">ステップ表示名 *</label>
                <input type="text" data-field="title" required placeholder="例: 前日倉庫出し・中継拠点へ移動"
                       class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded text-xs">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">作業主体 *</label>
                <select data-field="operator_type" required class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded text-xs font-bold">
                    <option value="committee">委員会スタッフ</option>
                    <option value="event_company">イベント会社</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <div class="p-2 rounded bg-white border border-slate-200 space-y-1">
                <label class="block text-[10px] font-bold text-slate-500">移動元ロール (From) *</label>
                <select data-field="from_location_role" required class="w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs font-bold">
                    @foreach(\App\Models\EquipmentLogisticsPatternStep::LOCATION_ROLES as $key => $lbl)
                        <option value="{{ $key }}">{{ $lbl }}</option>
                    @endforeach
                </select>
                <select data-field="from_location_id" class="hidden w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs">
                    <option value="">固定拠点を選択（未指定時はテキスト参照）</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" data-name="{{ $loc->name }}">{{ $loc->name }}</option>
                    @endforeach
                </select>
                <input type="text" data-field="from_location" placeholder="移動元テキスト（任意）"
                       class="w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs">
            </div>
            <div class="p-2 rounded bg-white border border-slate-200 space-y-1">
                <label class="block text-[10px] font-bold text-slate-500">移動先ロール (To) *</label>
                <select data-field="to_location_role" required class="w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs font-bold">
                    @foreach(\App\Models\EquipmentLogisticsPatternStep::LOCATION_ROLES as $key => $lbl)
                        <option value="{{ $key }}">{{ $lbl }}</option>
                    @endforeach
                </select>
                <select data-field="to_location_id" class="hidden w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs">
                    <option value="">固定拠点を選択（未指定時はテキスト参照）</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" data-name="{{ $loc->name }}">{{ $loc->name }}</option>
                    @endforeach
                </select>
                <input type="text" data-field="to_location" placeholder="移動先テキスト（任意）"
                       class="w-full px-2 py-1 bg-slate-50 border border-slate-300 rounded text-xs">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 items-center">
            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">数量ルール *</label>
                <select data-field="quantity_rule" required class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded text-xs font-bold">
                    @foreach(\App\Models\EquipmentLogisticsPatternStep::QUANTITY_RULES as $key => $lbl)
                        <option value="{{ $key }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">数量補足（分割理由・エリア名等）</label>
                <input type="text" data-field="quantity_note" placeholder="例: 西側・中央分、通行止め用分"
                       class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded text-xs">
            </div>
            <div class="pt-4">
                <label class="inline-flex items-center space-x-1.5 cursor-pointer">
                    <input type="checkbox" data-field="witness_required" value="1" class="rounded border-slate-300 text-indigo-600">
                    <span class="font-bold text-slate-700">👁️ 幹事・スタッフ立会必須</span>
                </label>
            </div>
        </div>

        <div>
            <input type="text" data-field="notes" placeholder="申し送り・注意事項メモ（任意）"
                   class="w-full px-2 py-1 bg-white border border-slate-300 rounded text-xs">
        </div>
    </div>
</template>

@vite(['resources/js/equipments.js'])
</x-layouts.app>
