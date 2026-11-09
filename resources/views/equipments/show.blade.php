<x-layouts.app :title="$equipment->name . ' - 資機材詳細'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$equipment->name"
        :icon="$equipment->type_enum->icon()"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）における資機材台帳の基本情報、主担当班体制および搬出入動態ステップです。'">
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
            <a href="{{ route('timetables.equipments.index', $timetable) }}" class="hover:text-indigo-600 transition truncate">
                📦 資機材管理
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">{{ $equipment->name }}</span>
        </x-slot:breadcrumbs>

        <x-slot:badges>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold font-mono bg-slate-100 text-slate-800 border border-slate-200">
                {{ $equipment->formatted_quantity }}
            </span>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $equipment->type_enum->badgeClasses() }}">
                {{ $equipment->type_enum->icon() }} {{ $equipment->type_label }}
            </span>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $equipment->checkout_rule_enum->badgeClasses() }}">
                {{ $equipment->checkout_rule_label }}
            </span>
            @if($equipment->lane)
                @php $laneClasses = $equipment->lane->color_classes; @endphp
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $laneClasses['badge'] ?? 'bg-slate-100 text-slate-800 border-slate-200' }}">
                    {{ $equipment->lane->icon ?: '🏷️' }} {{ $equipment->lane->name }}
                </span>
            @endif
        </x-slot:badges>

        <x-slot:actions>
            <a href="{{ route('timetables.equipments.index', $timetable) }}"
                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>←</span>
                <span>一覧に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="equipments" />

    <!-- アラートメッセージ -->
    @if(session('status'))
        <div class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl flex items-center space-x-2">
            <span>✅</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- 2:1 カラムレイアウト (班担当管理 show 準拠) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- 左カラム (2/3): 基本仕様 & 本日の搬出入動態ステップ一覧 -->
        <div class="lg:col-span-2 space-y-4">

            <!-- 1. 基本スペックカード -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                        <span>📦</span>
                        <span>資材基本台帳情報</span>
                    </h2>
                    <a href="{{ route('timetables.equipments.edit', [$timetable, $equipment]) }}"
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-bold transition">
                        ✏️ 編集する
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[11px]">資機材名</span>
                        <span class="font-bold text-slate-900 text-sm block mt-0.5">{{ $equipment->name }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[11px]">所有・調達区分</span>
                        <div class="mt-0.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $equipment->type_enum->badgeClasses() }}">
                                {{ $equipment->type_enum->icon() }} {{ $equipment->type_label }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[11px]">基準総数量</span>
                        <span class="font-bold font-mono text-slate-900 text-sm block mt-0.5">
                            {{ $equipment->formatted_quantity }}
                        </span>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[11px]">持出・利用ルール</span>
                        <div class="mt-0.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $equipment->checkout_rule_enum->badgeClasses() }}">
                                {{ $equipment->checkout_rule_label }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[11px]">定常保管場所</span>
                        <span class="font-bold text-slate-800 flex items-center space-x-1 mt-0.5">
                            <span>📍</span>
                            <span>{{ $equipment->storage_location ?: ($equipment->isPersonal() ? '自宅（個人所有）' : ($equipment->isRental() ? 'イベント会社手配' : '番所地下倉庫')) }}</span>
                        </span>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[11px]">主担当班</span>
                        <span class="font-bold text-slate-800 block mt-0.5">
                            @if($equipment->lane)
                                {{ $equipment->lane->icon ?: '🏷️' }} {{ $equipment->lane->name }}
                            @else
                                <span class="text-slate-400 font-normal">未指定</span>
                            @endif
                        </span>
                    </div>
                </div>

                <!-- 仕様メモ・注意事項 -->
                <div class="pt-3 border-t border-slate-100">
                    <span class="text-slate-400 block text-[11px] mb-1 font-bold">仕様・注意事項・申し送りメモ</span>
                    <div class="p-3 bg-slate-50 rounded-xl text-xs text-slate-700 leading-relaxed">
                        {{ $equipment->description ?: '仕様メモ・注意事項は登録されていません。「編集する」からサイズや付属品のメモを追記できます。' }}
                    </div>
                </div>
            </div>

            <!-- 2. 本日の搬出入動態ステップ一覧カード -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h2 class="font-bold text-base text-slate-900 flex items-center space-x-2">
                            <span>🚚</span>
                            <span>搬出入計画・現場配備状況</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-mono font-normal">
                                全 {{ $equipment->plans->count() }} ステップ
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            前日搬出、中継待機、会場設置、夜間保管、撤収格納の動態フローです。
                        </p>
                    </div>

                    <div class="flex items-center space-x-2 shrink-0">
                        <a href="{{ route('logistics.equipments.show', $equipment) }}"
                           class="inline-flex items-center space-x-1 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition">
                            <span>⚙️</span>
                            <span>ステップを編集・追加</span>
                            <span>→</span>
                        </a>
                    </div>
                </div>

                @if($equipment->plans->isEmpty())
                    <div class="p-8 text-center text-slate-400 space-y-3">
                        <div class="text-3xl">🚚</div>
                        <div class="font-bold text-slate-700 text-xs">「{{ $timetable->name }}」向けの搬出入ステップが未登録です</div>
                        <p class="text-[11px] text-slate-400">
                            搬出入パターンを適用するか、ステップを追加して移動スケジュールを登録してください。
                        </p>
                        <div>
                            <a href="{{ route('timetables.logistics.equipments.show', [$timetable, $equipment]) }}"
                               class="inline-flex items-center space-x-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition">
                                <span>⚡</span>
                                <span>ステップを設定する</span>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 text-[11px]">
                                <tr>
                                    <th class="py-3 px-3 whitespace-nowrap">フェーズ</th>
                                    <th class="py-3 px-4 whitespace-nowrap">ステップ名称 / 動作</th>
                                    <th class="py-3 px-3 whitespace-nowrap">移動ルート</th>
                                    <th class="py-3 px-3 whitespace-nowrap">数量</th>
                                    <th class="py-3 px-3 whitespace-nowrap">担当 / 立会</th>
                                    <th class="py-3 px-3 whitespace-nowrap">ステータス</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach($equipment->plans as $plan)
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <!-- フェーズ -->
                                        <td class="py-3 px-3 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $plan->day_phase_enum->badgeClasses() }}">
                                                {{ $plan->phase_label }}
                                            </span>
                                        </td>

                                        <!-- ステップ名称 / アクション -->
                                        <td class="py-3 px-4">
                                            <div class="font-bold text-slate-900 text-xs">
                                                {{ $plan->title }}
                                            </div>
                                            <div class="text-[11px] text-slate-500 flex items-center space-x-2 mt-0.5">
                                                <span>{{ $plan->action_type_enum->icon() }} {{ $plan->action_label }}</span>
                                                @if($plan->formatted_time_range)
                                                    <span class="font-mono text-indigo-600">⏱️ {{ $plan->formatted_time_range }}</span>
                                                @endif
                                            </div>
                                            @if($warnings = $plan->location_warnings)
                                                @foreach($warnings as $w)
                                                    <div class="mt-1 px-2 py-0.5 bg-rose-50 border border-rose-200 text-rose-700 rounded text-[10px] font-bold inline-flex items-center space-x-1">
                                                        <span>⚠️</span>
                                                        <span>{{ $w['message'] }}</span>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </td>

                                        <!-- ルート -->
                                        <td class="py-3 px-3 whitespace-nowrap text-slate-700">
                                            <div class="flex items-center space-x-1">
                                                <span>{{ $plan->resolved_from_location }}</span>
                                                <span class="text-slate-400">➔</span>
                                                <span class="font-bold text-slate-900">{{ $plan->resolved_to_location }}</span>
                                            </div>
                                        </td>

                                        <!-- 数量 -->
                                        <td class="py-3 px-3 whitespace-nowrap font-mono font-bold text-slate-800">
                                            {{ $plan->formatted_quantity ?: '全量' }}
                                        </td>

                                        <!-- 担当 / 立会 -->
                                        <td class="py-3 px-3 whitespace-nowrap">
                                            <div class="text-[11px] text-slate-800">
                                                {{ $plan->operator_label }}
                                            </div>
                                            @if($plan->witness_required)
                                                <div class="text-[10px] text-amber-700 font-bold flex items-center space-x-0.5 mt-0.5">
                                                    <span>👁️</span>
                                                    <span>立会: {{ $plan->witnessStaff?->name ?: '要立会（未指名）' }}</span>
                                                </div>
                                            @endif
                                        </td>

                                        <!-- ステータス -->
                                        <td class="py-3 px-3 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $plan->status_enum->badgeClasses() }}">
                                                {{ $plan->status_label }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>

        <!-- 右カラム (1/3): 所管班情報 & 調達・保管 & クイック操作 -->
        <div class="space-y-4">

            <!-- 3. 主担当班情報カード -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                        <span>🏷️</span>
                        <span>主担当班の体制</span>
                    </h2>
                </div>

                @if($equipment->lane)
                    @php $laneClasses = $equipment->lane->color_classes; @endphp
                    <div class="space-y-2.5 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[11px]">担当班</span>
                            <div class="mt-0.5">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg font-bold border {{ $laneClasses['badge'] ?? 'bg-slate-100 text-slate-800 border-slate-200' }}">
                                    {{ $equipment->lane->icon ?: '🏷️' }} {{ $equipment->lane->name }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <span class="text-slate-400 block text-[11px]">班長・リーダー</span>
                            <span class="font-bold text-slate-800 flex items-center space-x-1 mt-0.5">
                                <span>👑</span>
                                <span>{{ $equipment->lane->leadStaff?->name ?: '未指名' }}</span>
                            </span>
                        </div>

                        @if($equipment->lane->description)
                            <div>
                                <span class="text-slate-400 block text-[11px]">業務所管</span>
                                <p class="text-slate-600 line-clamp-2 mt-0.5">
                                    {{ $equipment->lane->description }}
                                </p>
                            </div>
                        @endif

                        <div class="pt-2 border-t border-slate-100">
                            <a href="{{ route('timetables.lanes.show', [$timetable, $equipment->lane]) }}"
                               class="text-xs text-indigo-600 hover:text-indigo-800 font-bold flex items-center space-x-1">
                                <span>🏷️</span>
                                <span>担当班の詳細を見る</span>
                                <span>→</span>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="p-4 text-center bg-slate-50 rounded-xl border border-slate-200 text-slate-400 text-xs">
                        主担当班が設定されていません
                    </div>
                @endif
            </div>

            <!-- 4. 保管・調達・私物情報カード -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                        <span>📍</span>
                        <span>保管・調達・所有情報</span>
                    </h2>
                </div>

                <div class="space-y-2 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[11px]">定常保管場所</span>
                        <span class="font-bold text-slate-900 block mt-0.5">
                            {{ $equipment->storage_location ?: ($equipment->isPersonal() ? '自宅（個人保管）' : '番所地下倉庫') }}
                        </span>
                    </div>

                    @if($equipment->storage_detail)
                        <div>
                            <span class="text-slate-400 block text-[11px]">棚番・置場・所有者メモ</span>
                            <div class="p-2 bg-slate-50 rounded-lg text-slate-700 font-mono mt-0.5">
                                {{ $equipment->storage_detail }}
                            </div>
                        </div>
                    @endif

                    @if($equipment->creator)
                        <div class="pt-2 border-t border-slate-100 text-[11px] text-slate-400">
                            登録者: {{ $equipment->creator->name }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- 5. クイック操作カード -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-2">
                <h2 class="font-bold text-xs text-slate-500 uppercase tracking-wider mb-2">クイック操作</h2>

                <a href="{{ route('logistics.equipments.show', $equipment) }}"
                   class="w-full px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition flex items-center justify-between">
                    <span class="flex items-center space-x-1.5">
                        <span>⚡</span>
                        <span>搬出入ステップ設定</span>
                    </span>
                    <span>→</span>
                </a>

                <a href="{{ route('timetables.equipments.edit', [$timetable, $equipment]) }}"
                   class="w-full px-3 py-2 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center justify-between">
                    <span class="flex items-center space-x-1.5">
                        <span>✏️</span>
                        <span>基本情報を編集</span>
                    </span>
                    <span>→</span>
                </a>

                @can('delete', $equipment)
                    <form method="POST" action="{{ route('timetables.equipments.destroy', [$timetable, $equipment]) }}"
                          data-confirm="資機材「{{ $equipment->name }}」を削除しますか？&#10;紐づくすべての搬出入計画ステップも削除されます。">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full mt-2 px-3 py-2 bg-white hover:bg-rose-50 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition flex items-center justify-between cursor-pointer">
                            <span class="flex items-center space-x-1.5">
                                <span>🗑️</span>
                                <span>この資機材を削除</span>
                            </span>
                            <span>✕</span>
                        </button>
                    </form>
                @endcan
            </div>

        </div>

    </div>

</div>
</x-layouts.app>
