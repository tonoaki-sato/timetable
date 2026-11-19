<x-layouts.app :title="$timetable->name . ' 配置一覧'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ページヘッダー -->
    <x-page-header
        title="{{ $timetable->name }} 配置一覧"
        icon="📋"
        description="ござ市、インフラ設備などの配置を管理します。">
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
            <a href="{{ route('timetables.layouts.index', $timetable) }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>🗺️</span>
                <span>配置計画</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">📋 配置一覧</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button variant="header-action" :href="route('timetables.site-maps.editor', $timetable)">
                <span>🎨</span>
                <span>白地図エディタ</span>
            </x-ui.button>
            <x-ui.button variant="header-action" id="btnOpenCreateItemModal" data-store-url="{{ route('timetables.layouts.booths.store', $timetable) }}">
                <span>➕</span>
                <span>新規配置追加</span>
            </x-ui.button>
            <x-ui.button variant="header-action" :href="route('timetables.layouts.index', $timetable)">
                <span>←</span>
                <span>配置計画に戻る</span>
            </x-ui.button>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="layouts-booths" />

    <x-ui.validation-errors title="入力内容をご確認ください" />

    <!-- 検索・絞り込みバー -->
    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 shadow-2xs space-y-3">
        <!-- 表示種別クイックチップ (すべて / 出店ブース / 現場設備) -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 pb-2.5">
            <span class="text-xs font-bold text-slate-600 flex items-center mr-1">
                <span>🏷️ 表示種別:</span>
            </span>
            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-0.5 text-xs" id="item-type-filter-group">
                <button type="button" data-type-filter="all" class="type-filter-btn px-2.5 py-1 rounded-md font-bold transition {{ ($filters['item_type'] ?? '') === '' ? 'bg-white text-indigo-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                    すべて表示 (全{{ $items->total() }})
                </button>
                <button type="button" data-type-filter="booth" class="type-filter-btn px-2.5 py-1 rounded-md font-bold transition flex items-center space-x-1 {{ ($filters['item_type'] ?? '') === 'booth' ? 'bg-white text-indigo-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                    <span>🎪</span>
                    <span>出店ブース</span>
                </button>
                <button type="button" data-type-filter="facility" class="type-filter-btn px-2.5 py-1 rounded-md font-bold transition flex items-center space-x-1 {{ ($filters['item_type'] ?? '') === 'facility' ? 'bg-white text-cyan-800 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                    <span>🚰</span>
                    <span>現場設備</span>
                </button>
            </div>
        </div>

        <form method="GET" action="{{ route('timetables.layouts.booths', $timetable) }}" id="filterForm">
            <input type="hidden" name="item_type" id="hiddenItemType" value="{{ $filters['item_type'] ?? '' }}">

            <div class="flex flex-wrap items-center gap-2">
                <!-- エリア絞り込み -->
                <div class="flex items-center space-x-1 text-xs">
                    <span class="text-slate-500 font-bold whitespace-nowrap">エリア:</span>
                    <select name="area_id" id="filterAreaId" class="bg-slate-50 border border-slate-300 rounded-lg text-xs py-1 px-2 text-slate-800 focus:bg-white focus:outline-hidden focus:ring-1 focus:ring-indigo-500">
                        <option value="">全エリア ({{ $map->items->count() }})</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}" {{ ($filters['area_id'] ?? '') == $area->id ? 'selected' : '' }}>
                                {{ $area->name }} ({{ $area->items->count() }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 区分絞り込み -->
                <div class="flex items-center space-x-1 text-xs">
                    <span class="text-slate-500 font-bold whitespace-nowrap">区分:</span>
                    <select name="category" id="filterCategory" class="bg-slate-50 border border-slate-300 rounded-lg text-xs py-1 px-2 text-slate-800 focus:bg-white focus:outline-hidden focus:ring-1 focus:ring-indigo-500">
                        <option value="">全区分</option>
                        <optgroup label="出展区分">
                            @foreach($boothCategories as $cat)
                                <option value="{{ $cat->value }}" {{ ($filters['category'] ?? '') === $cat->value ? 'selected' : '' }}>
                                    {{ $cat->icon() }} {{ $cat->label() }}
                                </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="設備区分">
                            @foreach($facilityTypes as $fac)
                                <option value="{{ $fac->value }}" {{ ($filters['category'] ?? '') === $fac->value ? 'selected' : '' }}>
                                    {{ $fac->icon() }} {{ $fac->label() }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                <!-- キーワード検索 -->
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="コード・出店名・設備名・場所・品目で検索..."
                        class="w-full bg-slate-50 border border-slate-300 rounded-lg text-xs py-1 px-2.5 text-slate-800 focus:bg-white focus:outline-hidden focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400">
                </div>

                <div class="flex items-center space-x-1.5 shrink-0">
                    <button type="submit" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer">
                        絞り込み
                    </button>
                    @if(!empty($filters['area_id']) || !empty($filters['item_type']) || !empty($filters['category']) || !empty($filters['search']))
                        <a href="{{ route('timetables.layouts.booths', $timetable) }}" class="px-2.5 py-1 bg-white border border-slate-300 hover:bg-slate-100 text-slate-600 rounded-lg text-xs font-medium transition">
                            クリア
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- 統合配置一覧テーブル -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 flex items-center space-x-1.5">
                <span>📋</span>
                <span>配置一覧（全{{ $items->total() }}件）</span>
            </span>
            <span class="text-slate-500 font-mono">
                {{ $items->firstItem() ?? 0 }} - {{ $items->lastItem() ?? 0 }} 件を表示
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold">
                        <th class="py-2.5 px-3 whitespace-nowrap">コード</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">エリア</th>
                        <th class="py-2.5 px-3">配置名称</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">区分 / 種別</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">形態</th>
                        <th class="py-2.5 px-3">内容 / 場所目安</th>
                        <th class="py-2.5 px-3 text-right whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50/60 transition item-row" data-item-type="{{ $item->item_type->value }}">
                            <!-- コード (アイコンなしのシンプルなバッジ) -->
                            <td class="py-2.5 px-3 font-mono font-bold whitespace-nowrap">
                                <span class="px-1.5 py-0.5 rounded text-[11px] {{ $item->isBooth() ? 'bg-slate-100 border border-slate-300 text-slate-800' : 'bg-sky-50 border border-sky-300 text-sky-800' }}">
                                    {{ $item->code }}
                                </span>
                            </td>

                            <!-- エリア -->
                            <td class="py-2.5 px-3 text-slate-600 whitespace-nowrap text-[11px]">
                                {{ $item->area?->name ?? '通り' }}
                            </td>

                            <!-- 配置名称 (🟢/🟡 アイコン + 詳細モーダル起動) -->
                            <td class="py-2.5 px-3">
                                <button type="button"
                                    class="btn-view-item font-bold text-slate-800 hover:text-indigo-600 hover:underline transition cursor-pointer text-left inline-flex items-center space-x-1.5"
                                    data-id="{{ $item->id }}"
                                    data-type="{{ $item->item_type->value }}"
                                    data-code="{{ $item->code }}"
                                    data-area="{{ $item->area?->name ?? '通り' }}"
                                    data-name="{{ $item->displayName }}"
                                    data-status-label="{{ $item->status?->label() ?? '確定' }}"
                                    data-status-icon="{{ $item->status?->icon() ?? '🟢' }}"
                                    data-type-icon="{{ $item->typeIcon() }}"
                                    data-type-label="{{ $item->typeLabel() }}"
                                    data-booth-type-label="{{ $item->boothType()?->label() ?? '—' }}"
                                    data-vendor-name="{{ $item->vendor?->name ?? '' }}"
                                    data-vendor-contact-person="{{ $item->vendor?->contact_person ?? '' }}"
                                    data-vendor-contact="{{ $item->vendor?->contact ?? '' }}"
                                    data-vendor-booths="{{ $item->vendor ? json_encode($item->vendor->booths_detail_list) : '[]' }}"
                                    data-location="{{ $item->location_description ?? '' }}"
                                    data-notes="{{ $item->notes ?? '' }}">
                                    <span>{{ $item->status?->icon() ?? '🟢' }}</span>
                                    <span>{{ $item->displayName }}</span>
                                </button>
                            </td>

                            <!-- 区分 / 種別 -->
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] {{ $item->typeBadgeClasses() }}">
                                    {{ $item->typeIcon() }} {{ $item->typeLabel() }}
                                </span>
                            </td>

                            <!-- 形態 (出展・設備 共通) -->
                            <td class="py-2.5 px-3 whitespace-nowrap text-slate-700 font-medium">
                                {{ $item->boothType()?->label() ?? '—' }}
                            </td>

                            <!-- 内容 / 場所目安 -->
                            <td class="py-2.5 px-3 text-slate-600 text-[11px] max-w-xs truncate" title="{{ $item->notes ?: $item->location_description }}">
                                {{ $item->notes ?: ($item->location_description ?: '—') }}
                            </td>

                            <!-- 操作 -->
                            <td class="py-2.5 px-3 text-right whitespace-nowrap space-x-1">
                                <button type="button"
                                    data-action="open-edit-modal"
                                    data-id="{{ $item->id }}"
                                    data-type="{{ $item->item_type->value }}"
                                    data-code="{{ $item->code }}"
                                    data-area-id="{{ $item->layout_area_id }}"
                                    data-name="{{ $item->name }}"
                                    data-status="{{ $item->status?->value ?? 'confirmed' }}"
                                    data-category="{{ $item->category }}"
                                    data-booth-type="{{ $item->booth_type }}"
                                    data-vendor-id="{{ $item->vendor_id }}"
                                    data-location="{{ $item->location_description }}"
                                    data-notes="{{ $item->notes }}"
                                    class="inline-flex items-center space-x-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                    title="配置を編集">
                                    <span>✏️</span>
                                    <span>編集</span>
                                </button>
                                <form action="{{ route('timetables.layouts.booths.destroy', [$timetable, $item]) }}" method="POST" class="inline"
                                    data-confirm="「{{ $item->displayName }}（{{ $item->code }}）」の配置を削除しますか？">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center space-x-1 px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                        title="配置を削除">
                                        <span>🗑️</span>
                                        <span>削除</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                該当する配置データがありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="px-4 py-3 border-t border-slate-200 bg-slate-50/50">
                {{ $items->links() }}
            </div>
        @endif
    </div>

    <!-- 閲覧専用詳細モーダル (上部見切れ防止＆固定ヘッダー・フッター構造) -->
    <div id="modal-item-detail" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex justify-center items-start sm:items-center p-3 sm:p-6 min-h-screen" role="dialog" aria-modal="true" aria-labelledby="detail-name">
        <div class="bg-white rounded-2xl max-w-lg w-full max-h-[90vh] flex flex-col shadow-xl border border-slate-200 my-auto">
            <!-- 固定ヘッダー (見切れ防止) -->
            <div class="flex items-center justify-between border-b border-slate-200 p-4 shrink-0">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <span class="text-2xl shrink-0" id="detail-status-icon">🟢</span>
                    <div class="min-w-0">
                        <div class="flex items-center space-x-2">
                            <h3 class="text-sm sm:text-base font-bold text-slate-900 truncate" id="detail-name">配置詳細</h3>
                            <span class="font-mono text-[11px] font-bold text-indigo-700 px-1.5 py-0.2 bg-indigo-50 border border-indigo-200 rounded shrink-0" id="detail-code"></span>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-slate-500 mt-1">
                            <span class="font-bold text-slate-700" id="detail-area-name">—</span>
                            <span>•</span>
                            <span id="detail-type-label">—</span>
                            <span>•</span>
                            <span id="detail-booth-type-label">—</span>
                            <span>•</span>
                            <span id="detail-status-label" class="font-bold">—</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close-detail-modal text-slate-400 hover:text-slate-600 transition cursor-pointer p-1 shrink-0 text-lg">
                    ✕
                </button>
            </div>

            <!-- スクロール可能なコンテンツ本体 (基本情報カードを削除しすっきり整理) -->
            <div class="p-4 sm:p-5 overflow-y-auto space-y-3.5 text-xs">
                <!-- 割り当て出展・出店者情報 (ブース時のみ) -->
                <div id="detail-vendor-section" class="bg-indigo-50/50 p-3 rounded-xl border border-indigo-100 space-y-2">
                    <span class="text-indigo-900 font-bold block text-[11px] flex items-center space-x-1">
                        <span>🎪</span>
                        <span>出展・出店者</span>
                    </span>
                    <div>
                        <div class="font-bold text-indigo-950 text-xs" id="detail-vendor-name">—</div>
                        <div class="text-slate-600 text-[11px] flex flex-wrap gap-x-3 gap-y-0.5 mt-1">
                            <span id="detail-vendor-contact-person"></span>
                            <span id="detail-vendor-contact"></span>
                        </div>
                    </div>

                    <!-- 出展・出店者の複数配置ブース一覧（スクロール無しで全件直接表示、出店状況バッジ付き） -->
                    <div id="detail-vendor-multiple-booths-container" class="hidden space-y-1.5 pt-2 border-t border-indigo-100/80">
                        <div class="flex items-center justify-between text-[11px] font-bold text-indigo-950">
                            <span>🏪 出展ブース一覧 (全<span id="detail-vendor-booths-count"></span>箇所)</span>
                            <span class="text-[10px] text-indigo-600 font-normal">※ 閲覧中と他のブースを色分け表示</span>
                        </div>
                        <div id="detail-vendor-booths-list" class="space-y-1.5"></div>
                    </div>
                </div>

                <!-- 近接ランドマーク・設置場所目安 -->
                <div>
                    <span class="text-slate-500 font-bold block mb-0.5">近接店舗・設置場所の目安</span>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-slate-800" id="detail-location">—</div>
                </div>

                <!-- 内容・特記事項・メモ (notes) -->
                <div>
                    <span class="text-slate-500 font-bold block mb-0.5">内容・特記事項・メモ</span>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-slate-800 whitespace-pre-wrap min-h-[38px]" id="detail-notes">—</div>
                </div>
            </div>

            <!-- 固定フッター -->
            <div class="p-3 border-t border-slate-200 flex justify-end shrink-0 bg-slate-50/50 rounded-b-2xl">
                <button type="button" class="btn-close-detail-modal px-4 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg font-bold transition cursor-pointer text-xs">
                    閉じる
                </button>
            </div>
        </div>
    </div>

    <!-- 配置 登録・編集兼用モーダル (2列対称グリッドレイアウト) -->
    <div id="itemEditModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="modalFormTitle">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-4 sm:p-5 shadow-xl space-y-3.5 border border-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                <h3 class="text-base font-bold text-slate-900 flex items-center space-x-1.5">
                    <span id="modalFormIcon">✏️</span>
                    <span id="modalFormTitle">配置情報の編集</span>
                    <span id="modalItemCodeBadge" class="font-mono text-indigo-600 text-sm ml-1"></span>
                </h3>
                <button type="button" id="modalCloseBtn" class="text-slate-400 hover:text-slate-600 text-lg font-bold cursor-pointer">
                    ✕
                </button>
            </div>

            <form id="itemEditForm" method="POST" action=""
                data-timetable-id="{{ $timetable->id }}"
                data-store-url="{{ route('timetables.layouts.booths.store', $timetable) }}"
                data-base-update-url="/timetables/{{ $timetable->id }}/layouts/booths"
                class="space-y-3 text-xs">
                @csrf
                <input type="hidden" name="_method" id="itemFormMethod" value="PUT">

                <!-- 第1段: 配置種別 ＆ 配置状態 (横幅均等2列グリッド) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">配置種別 <span class="text-red-500">*</span></label>
                        <input type="hidden" name="item_type" id="inputItemType" value="booth">
                        <div class="inline-flex rounded-lg border border-slate-300 bg-slate-100 p-0.5 w-full">
                            <button type="button" id="typeBtnBooth" data-type-val="booth"
                                class="item-type-toggle-btn flex-1 py-1.5 px-2 rounded-md text-xs font-bold transition flex items-center justify-center space-x-1 cursor-pointer bg-white text-indigo-700 shadow-2xs border border-indigo-200">
                                <span>🎪</span>
                                <span>出店</span>
                            </button>
                            <button type="button" id="typeBtnFacility" data-type-val="facility"
                                class="item-type-toggle-btn flex-1 py-1.5 px-2 rounded-md text-xs font-bold transition flex items-center justify-center space-x-1 cursor-pointer text-slate-500 hover:text-slate-700 border border-transparent">
                                <span>🚰</span>
                                <span>設備</span>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">配置状態 <span class="text-red-500">*</span></label>
                        <input type="hidden" name="status" id="inputItemStatus" value="confirmed">
                        <div class="inline-flex rounded-lg border border-slate-300 bg-slate-100 p-0.5 w-full">
                            <button type="button" id="statusBtnConfirmed" data-status-val="confirmed"
                                class="status-toggle-btn flex-1 py-1.5 px-2 rounded-md text-xs font-bold transition flex items-center justify-center space-x-1 cursor-pointer bg-white text-emerald-700 shadow-2xs border border-emerald-200">
                                <span>🟢</span>
                                <span>確定</span>
                            </button>
                            <button type="button" id="statusBtnTentative" data-status-val="tentative"
                                class="status-toggle-btn flex-1 py-1.5 px-2 rounded-md text-xs font-bold transition flex items-center justify-center space-x-1 cursor-pointer text-slate-500 hover:text-slate-700 border border-transparent">
                                <span>🟡</span>
                                <span>未定</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 第2段: 識別コード ＆ 所属エリア (2列グリッド) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="inputItemCode" class="block font-bold text-slate-700 mb-1">識別コード <span class="text-red-500">*</span></label>
                        <input type="text" id="inputItemCode" name="code" required
                            class="w-full bg-slate-50 border border-slate-300 rounded-lg py-1.5 px-2.5 font-mono uppercase text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500"
                            placeholder="例: A01, B12, W-01, HQ-01">
                    </div>
                    <div>
                        <label for="selectItemArea" class="block font-bold text-slate-700 mb-1">所属エリア</label>
                        <select id="selectItemArea" name="layout_area_id" class="w-full bg-slate-50 border border-slate-300 rounded-lg py-1.5 px-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500">
                            <option value="">(未設定・通り)</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- 第3段: 配置名称 ＆ 出展・出店者 (2列グリッド) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="inputItemName" class="block font-bold text-slate-700 mb-1">配置名称</label>
                        <input type="text" id="inputItemName" name="name" class="w-full bg-slate-50 border border-slate-300 rounded-lg py-1.5 px-2.5 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="未入力時ブースはマスター名が反映">
                    </div>
                    <div id="rowVendorSelect">
                        <label for="selectVendor" class="block font-bold text-slate-700 mb-1">出展・出店者</label>
                        <select id="selectVendor" name="vendor_id" class="w-full bg-slate-50 border border-slate-300 rounded-lg py-1.5 px-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500">
                            <option value="">(未割当・個別名のみまたは空きコマ)</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- 第4段: 区分 / 種別 ＆ ブース形態 (出展・設備共通、2列グリッド) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">区分 / 種別</label>
                        <!-- ブース用カテゴリセレクト -->
                        <select id="selectBoothCategory" name="category" class="w-full bg-slate-50 border border-slate-300 rounded-lg py-1.5 px-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500">
                            @foreach(\App\Enums\BoothCategory::cases() as $case)
                                <option value="{{ $case->value }}">{{ $case->icon() }} {{ $case->label() }}</option>
                            @endforeach
                        </select>
                        <!-- 設備用種別セレクト (JSで表示切替) -->
                        <select id="selectFacilityType" name="facility_type" class="hidden w-full bg-slate-50 border border-slate-300 rounded-lg py-1.5 px-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500">
                            @foreach(\App\Enums\FacilityType::cases() as $fCase)
                                <option value="{{ $fCase->value }}">{{ $fCase->icon() }} {{ $fCase->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="rowBoothTypeSelect">
                        <label for="selectBoothType" class="block font-bold text-slate-700 mb-1">ブース形態 (出展・設備共通)</label>
                        <select id="selectBoothType" name="booth_type" class="w-full bg-slate-50 border border-slate-300 rounded-lg py-1.5 px-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500">
                            @foreach(\App\Enums\BoothType::cases() as $bType)
                                <option value="{{ $bType->value }}">{{ $bType->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- 第5段: 近接店舗・設置場所の目安 (1列全幅) -->
                <div>
                    <label for="inputLocation" class="block font-bold text-slate-700 mb-1">近接店舗・設置場所の目安</label>
                    <input type="text" id="inputLocation" name="location_description" class="w-full bg-slate-50 border border-slate-300 rounded-lg py-1.5 px-2.5 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="例: 川むら前、北川P水栓横">
                </div>

                <!-- 第6段: 内容・特記事項・メモ (全幅) -->
                <div>
                    <label for="textareaNotes" class="block font-bold text-slate-700 mb-1">内容・取扱品目・特記事項・メモ</label>
                    <textarea id="textareaNotes" name="notes" rows="2" class="w-full bg-slate-50 border border-slate-300 rounded-lg py-1.5 px-2.5 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="取扱品目（焼きそば、生搾りジュース等）、消火器持参、搬入時間要請、設備仕様メモなど"></textarea>
                </div>

                <!-- フッター操作ボタン -->
                <div class="flex items-center justify-end space-x-2 pt-2.5 border-t border-slate-200">
                    <button type="button" id="modalCancelBtn" class="px-3.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg font-bold transition cursor-pointer">
                        キャンセル
                    </button>
                    <button type="submit" id="modalSubmitBtn" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-2xs transition cursor-pointer">
                        💾 変更を保存する
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

    @vite(['resources/js/booths.js'])
</x-layouts.app>
