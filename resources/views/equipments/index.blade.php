<x-layouts.app title="資材管理">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- 1. ヘッダーセクション -->
    <x-page-header
        title="資材管理"
        icon="📦"
        description="宿場まつり運行に使用する自前保有備品および提携レンタル機材の台帳管理・スペック一覧">
        <x-slot:badges>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                全 {{ $totalCount }} 品目
            </span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                🏛️ 自前: {{ $committeeCount }}
            </span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-sky-50 text-sky-700 border border-sky-200">
                🏢 レンタル: {{ $rentalCount }}
            </span>
        </x-slot:badges>
        <x-slot:actions>
            <a href="{{ route('logistics.index') }}"
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                <span>🚚</span>
                <span>搬出入計画を開く</span>
            </a>
            <a href="{{ route('equipments.create') }}"
                class="inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                <span>➕</span>
                <span>新規資材を登録</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 2. 検索・絞り込みフィルターバー -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-3">
        <!-- 区分 & カテゴリフィルター -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center space-x-1.5 flex-wrap gap-y-1">
                <span class="text-xs font-bold text-slate-500 mr-1">区分:</span>
                <a href="{{ route('equipments.index', array_filter(['lane_id' => $laneFilter, 'category' => $categoryFilter, 'keyword' => $keyword])) }}"
                   class="px-3 py-1 rounded-lg text-xs font-bold transition {{ !$typeFilter ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    すべて ({{ $totalCount }})
                </a>
                <a href="{{ route('equipments.index', array_filter(['type' => 'committee', 'lane_id' => $laneFilter, 'category' => $categoryFilter, 'keyword' => $keyword])) }}"
                   class="px-3 py-1 rounded-lg text-xs font-bold transition {{ $typeFilter === 'committee' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200' }}">
                    🏛️ 実行委員会 ({{ $committeeCount }})
                </a>
                <a href="{{ route('equipments.index', array_filter(['type' => 'rental', 'lane_id' => $laneFilter, 'category' => $categoryFilter, 'keyword' => $keyword])) }}"
                   class="px-3 py-1 rounded-lg text-xs font-bold transition {{ $typeFilter === 'rental' ? 'bg-sky-600 text-white' : 'bg-sky-50 text-sky-800 hover:bg-sky-100 border border-sky-200' }}">
                    🏢 イベント会社 ({{ $rentalCount }})
                </a>
            </div>

            <!-- 所管班 & キーワード検索フォーム -->
            <form method="GET" action="{{ route('equipments.index') }}" class="flex items-center space-x-2 flex-wrap gap-2">
                @if($typeFilter)
                    <input type="hidden" name="type" value="{{ $typeFilter }}">
                @endif
                @if($categoryFilter)
                    <input type="hidden" name="category" value="{{ $categoryFilter }}">
                @endif

                <!-- 所管班 -->
                <select name="lane_id" data-auto-submit
                        class="px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 pr-8">
                    <option value="">全班表示</option>
                    @foreach($lanes as $lane)
                        <option value="{{ $lane->id }}" {{ $laneFilter == $lane->id ? 'selected' : '' }}>
                            {{ $lane->icon }} {{ $lane->name }}
                        </option>
                    @endforeach
                </select>

                <!-- キーワード入力 -->
                <div class="relative">
                    <input type="text" name="keyword" value="{{ $keyword }}" placeholder="品名・保管場所・備考検索..."
                           class="pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 w-48 sm:w-60">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs">🔍</span>
                </div>

                <button type="submit" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs font-bold transition cursor-pointer">
                    検索
                </button>

                @if($typeFilter || $laneFilter || $categoryFilter || $keyword)
                    <a href="{{ route('equipments.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800 underline">
                        クリア
                    </a>
                @endif
            </form>
        </div>

        <!-- カテゴリ切り替えサブバー -->
        <div class="flex items-center space-x-1.5 border-t border-slate-100 pt-2.5 flex-wrap">
            <span class="text-xs font-bold text-slate-500 mr-1">種別:</span>
            <a href="{{ route('equipments.index', array_filter(['type' => $typeFilter, 'lane_id' => $laneFilter, 'keyword' => $keyword])) }}"
               class="px-2.5 py-1 rounded-md text-[11px] font-bold transition {{ !$categoryFilter ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                全種別
            </a>
            <a href="{{ route('equipments.index', array_filter(['category' => 'heavy_equipment', 'type' => $typeFilter, 'lane_id' => $laneFilter, 'keyword' => $keyword])) }}"
               class="px-2.5 py-1 rounded-md text-[11px] font-bold transition {{ $categoryFilter === 'heavy_equipment' ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200' }}">
                📦 大型機材・設備
            </a>
            <a href="{{ route('equipments.index', array_filter(['category' => 'carrier', 'type' => $typeFilter, 'lane_id' => $laneFilter, 'keyword' => $keyword])) }}"
               class="px-2.5 py-1 rounded-md text-[11px] font-bold transition {{ $categoryFilter === 'carrier' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200' }}">
                🛒 台車・運搬具 ({{ $carrierCount }})
            </a>
            <a href="{{ route('equipments.index', array_filter(['category' => 'tool_consumable', 'type' => $typeFilter, 'lane_id' => $laneFilter, 'keyword' => $keyword])) }}"
               class="px-2.5 py-1 rounded-md text-[11px] font-bold transition {{ $categoryFilter === 'tool_consumable' ? 'bg-teal-600 text-white' : 'bg-teal-50 text-teal-800 hover:bg-teal-100 border border-teal-200' }}">
                🧰 工具・消耗品 ({{ $toolConsumableCount }})
            </a>
        </div>
    </div>

    <!-- 3. 資材台帳一覧テーブル -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3.5 px-4">資材名 / 仕様メモ</th>
                        <th class="py-3.5 px-3">区分</th>
                        <th class="py-3.5 px-3">総数量</th>
                        <th class="py-3.5 px-4">定常保管元 / 提携業者</th>
                        <th class="py-3.5 px-3">主担当班</th>
                        <th class="py-3.5 px-3">搬出入計画配備</th>
                        <th class="py-3.5 px-4 text-right">アクション</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($equipments as $item)
                        <tr class="hover:bg-slate-50/70 transition">
                            <!-- 資材名 -->
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-1.5 flex-wrap">
                                    <a href="{{ route('equipments.show', $item) }}" class="font-bold text-slate-900 text-sm hover:text-indigo-600 transition">
                                        {{ $item->name }}
                                    </a>
                                    @if($item->isCarrier())
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">🛒 台車</span>
                                    @elseif($item->isToolConsumable())
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-teal-100 text-teal-800 border border-teal-200">🧰 工具・消耗品</span>
                                    @endif
                                    @if($item->checkout_rule === 'permission_required')
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">⚠️ 本陣許可制</span>
                                    @elseif($item->checkout_rule === 'leader_only')
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">👑 班長限定</span>
                                    @endif
                                </div>
                                @if($item->description)
                                    <div class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                                        {{ $item->description }}
                                    </div>
                                @endif
                            </td>

                            <!-- 区分 -->
                            <td class="py-3 px-3 whitespace-nowrap">
                                @if($item->isCommittee())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        🏛️ 自前保有
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-sky-100 text-sky-800">
                                        🏢 提携レンタル
                                    </span>
                                @endif
                            </td>

                            <!-- 数量・単位 -->
                            <td class="py-3 px-3 whitespace-nowrap">
                                <span class="font-bold font-mono text-slate-900 text-sm">{{ number_format($item->quantity) }}</span>
                                <span class="text-[11px] text-slate-500 ml-0.5">{{ $item->unit }}</span>
                            </td>

                            <!-- 保管場所 / 提携業者 -->
                            <td class="py-3 px-4">
                                @if($item->isCommittee())
                                    <div class="font-medium text-slate-800 flex items-center space-x-1">
                                        <span class="text-slate-400">📍</span>
                                        <span>{{ $item->storage_location ?: '番所地下倉庫' }}</span>
                                    </div>
                                    @if($item->storage_detail)
                                        <div class="text-[10px] text-slate-400 mt-0.5 line-clamp-1">
                                            {{ $item->storage_detail }}
                                        </div>
                                    @endif
                                @else
                                    <div class="text-slate-800 font-medium">
                                        {{ $item->event_company_name ?: '東京イベントサプライ株式会社' }}
                                    </div>
                                    @if($item->event_company_contact)
                                        <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                            {{ $item->event_company_contact }}
                                        </div>
                                    @endif
                                @endif
                            </td>

                            <!-- 所管班 -->
                            <td class="py-3 px-3 whitespace-nowrap">
                                @if($item->lane)
                                    <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-800 border border-slate-200">
                                        <span>{{ $item->lane->icon }}</span>
                                        <span>{{ $item->lane->name }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 text-[11px]">未設定</span>
                                @endif
                            </td>

                            <!-- 搬出入計画配備状況 -->
                            <td class="py-3 px-3 whitespace-nowrap">
                                @php $planCount = $item->plans->count(); @endphp
                                @if($planCount > 0)
                                    <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        <span>🚚</span>
                                        <span>{{ $planCount }} ステップ設定済</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-400">
                                        未配備
                                    </span>
                                @endif
                            </td>

                            <!-- アクション -->
                            <td class="py-3 px-4 text-right whitespace-nowrap space-x-1">
                                <a href="{{ route('logistics.index', ['tab' => 'flow', 'lane_id' => $item->lane_id]) }}"
                                   class="px-2 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition inline-flex items-center space-x-1"
                                   title="この資材の搬出入計画を確認">
                                    <span>🚚</span>
                                    <span class="hidden sm:inline">計画を確認</span>
                                </a>
                                <a href="{{ route('equipments.show', $item) }}"
                                   class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition inline-flex items-center"
                                   title="詳細表示">
                                    詳細
                                </a>
                                <a href="{{ route('equipments.edit', $item) }}"
                                   class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition inline-flex items-center"
                                   title="編集">
                                    編集
                                </a>
                                @can('delete', $item)
                                    <form method="POST" action="{{ route('equipments.destroy', $item) }}" class="inline"
                                          data-confirm="資材「{{ $item->name }}」を削除しますか？&#10;紐づくすべての搬出入計画ステップも削除されます。">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition inline-flex items-center space-x-0.5 cursor-pointer"
                                                title="削除">
                                            <span>🗑️</span>
                                            <span>削除</span>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="text-3xl mb-2">📦</div>
                                <div class="font-bold text-slate-700">登録されている資材がありません</div>
                                <div class="text-xs text-slate-400 mt-1">「新規資材を登録」ボタンから自前備品や提携レンタル機材を登録してください。</div>
                                <div class="mt-4">
                                    <a href="{{ route('equipments.create') }}" class="inline-block px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-sm hover:bg-indigo-500 transition">
                                        ＋ 新規資材を登録
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($equipments->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50">
                {{ $equipments->links() }}
            </div>
        @endif
    </div>

</div>
</x-layouts.app>
