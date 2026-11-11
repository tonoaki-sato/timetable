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
               class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition whitespace-nowrap shrink-0"
               title="搬出入指示書兼現場チェックリストをPDFでダウンロード">
                <span>📄</span>
                <span>搬出入指示書PDF</span>
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

    <!-- 絞り込みツールバー -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-3">
        <form method="GET" action="{{ route('timetables.logistics.index', $timetable) }}" class="flex items-center justify-between flex-wrap gap-2 text-xs">
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
                <a href="{{ route('timetables.logistics.index', $timetable) }}"
                   class="text-xs text-rose-600 hover:text-rose-800 font-bold transition flex items-center space-x-1">
                    <span>✕</span>
                    <span>フィルタ解除</span>
                </a>
            @endif
        </form>
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
            <details id="equipment-{{ $equipment->id }}" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden group" {{ $equipment->plans->isNotEmpty() ? 'open' : '' }}>
                <summary class="px-3.5 sm:px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-2 sm:gap-3 cursor-pointer hover:bg-slate-100/70 transition select-none">
                    <div class="flex items-center gap-2 sm:gap-2.5 min-w-0 flex-1 flex-wrap">
                        <!-- 開閉トグルアイコン -->
                        <span class="w-4 shrink-0 text-center text-slate-400 group-open:rotate-90 transition-transform duration-150 inline-block text-xs">▶</span>

                        <!-- 資材アイコン・資材名リンク -->
                        <div class="flex items-center min-w-0">
                            <span class="mr-1.5 shrink-0 text-base">
                                {{ $equipment->type_enum->icon() }}
                            </span>
                            <a href="{{ route('timetables.logistics.equipments.show', [$timetable, $equipment]) }}"
                               class="text-indigo-600 hover:text-indigo-800 font-bold text-xs sm:text-sm truncate hover:underline">
                                {{ $equipment->name }}
                            </a>
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
                        <div>
                            <a href="{{ route('timetables.logistics.equipments.show', [$timetable, $equipment]) }}"
                               class="text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline inline-flex items-center space-x-1">
                                <span>⚡ パターン適用・ステップ追加画面を開く</span>
                                <span>→</span>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-xs">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold select-none text-xs">
                                <tr>
                                    <th scope="col" class="px-3.5 py-2.5 text-center whitespace-nowrap w-14">順序</th>
                                    <th scope="col" class="px-3.5 py-2.5 whitespace-nowrap w-40">フェーズ</th>
                                    <th scope="col" class="px-3.5 py-2.5 min-w-[160px]">ステップ表示名</th>
                                    <th scope="col" class="px-3.5 py-2.5 min-w-[200px]">移動元 ➔ 移動先</th>
                                    <th scope="col" class="px-3.5 py-2.5 whitespace-nowrap w-28">作業主体</th>
                                    <th scope="col" class="px-3.5 py-2.5 text-center whitespace-nowrap w-24">状態</th>
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
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 font-bold text-[10px] border border-rose-200" title="幹事・スタッフ立会必須">
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

                                        <!-- 作業主体 -->
                                        <td class="px-3.5 py-2.5 text-slate-600 whitespace-nowrap">
                                            {{ $plan->operator_label }}
                                        </td>

                                        <!-- 状態 -->
                                        <td class="px-3.5 py-2.5 text-center whitespace-nowrap">
                                            @if($plan->isCompleted())
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                    ✅ 完了
                                                </span>
                                            @elseif($plan->status === 'in_progress')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                    🏃 進行中
                                                </span>
                                            @elseif($plan->status === 'cancelled')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">
                                                    ⛔ 中止
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                                    ⚪ 計画中
                                                </span>
                                            @endif
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
                <p class="text-xs text-slate-400">絞り込み条件を変更するか、資材管理から新しい資材を登録してください。</p>
            </div>
        @endforelse
    </div>

    <!-- 個別ステップ詳細・追加・編集モーダル -->
    <x-logistics.step-detail-modal />
    <x-logistics.step-create-modal :locations="$locations" :staffs="$staffs" />
    <x-logistics.step-edit-modal :locations="$locations" :staffs="$staffs" />
</div>

@vite(['resources/js/logistics.js'])
</x-layouts.app>
