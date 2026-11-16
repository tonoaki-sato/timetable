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
                <span>現場配置計画</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">📋 配置一覧</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <button type="button"
                id="btnOpenCreateBoothModal"
                data-store-url="{{ route('timetables.layouts.booths.store', $timetable) }}"
                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0 cursor-pointer">
                <span>➕</span>
                <span>新規配置追加</span>
            </button>
            <a href="{{ route('timetables.layouts.index', $timetable) }}"
                class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>←</span>
                <span>ダッシュボードに戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="layouts-booths" />

    <!-- フラッシュメッセージ -->
    @if(session('success'))
    <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl flex items-center space-x-2">
        <span>✅</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl space-y-1">
        <div class="font-bold flex items-center space-x-1">
            <span>⚠️</span>
            <span>入力内容をご確認ください</span>
        </div>
        <ul class="list-disc list-inside text-[11px] pl-2 space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- 検索・絞り込みバー -->
    <div class="bg-white border border-slate-200 rounded-2xl p-3 sm:p-4 shadow-2xs">
        <form method="GET" action="{{ route('timetables.layouts.booths', $timetable) }}" class="space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <!-- エリア絞り込み -->
                <div class="flex items-center space-x-1 text-xs">
                    <span class="text-slate-500 font-bold whitespace-nowrap">エリア:</span>
                    <select name="area_id" class="bg-slate-50 border border-slate-300 rounded-lg text-xs py-1 px-2 text-slate-800 focus:bg-white focus:outline-hidden focus:ring-1 focus:ring-indigo-500">
                        <option value="">全エリア ({{ $map->booths->count() }})</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}" {{ ($filters['area_id'] ?? '') == $area->id ? 'selected' : '' }}>
                                {{ $area->name }} ({{ $area->booths->count() }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 出店区分絞り込み -->
                <div class="flex items-center space-x-1 text-xs">
                    <span class="text-slate-500 font-bold whitespace-nowrap">出店区分:</span>
                    <select name="category" class="bg-slate-50 border border-slate-300 rounded-lg text-xs py-1 px-2 text-slate-800 focus:bg-white focus:outline-hidden focus:ring-1 focus:ring-indigo-500">
                        <option value="">全区分</option>
                        @foreach(\App\Enums\BoothCategory::cases() as $case)
                            <option value="{{ $case->value }}" {{ ($filters['category'] ?? '') === $case->value ? 'selected' : '' }}>
                                {{ $case->icon() }} {{ $case->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- キーワード検索 -->
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="出店名・コマ番号・品目・近接店舗で検索..."
                        class="w-full bg-slate-50 border border-slate-300 rounded-lg text-xs py-1 px-2.5 text-slate-800 focus:bg-white focus:outline-hidden focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400">
                </div>

                <div class="flex items-center space-x-1.5 shrink-0">
                    <button type="submit" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer">
                        絞り込み
                    </button>
                    @if(!empty($filters['area_id']) || !empty($filters['category']) || !empty($filters['search']))
                        <a href="{{ route('timetables.layouts.booths', $timetable) }}" class="px-2.5 py-1 bg-white border border-slate-300 hover:bg-slate-100 text-slate-600 rounded-lg text-xs font-medium transition">
                            クリア
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- 配置一覧テーブル -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 flex items-center space-x-1">
                <span>📋</span>
                <span>配置一覧（全{{ $booths->total() }}コマ）</span>
            </span>
            <span class="text-slate-500 font-mono">
                {{ $booths->firstItem() ?? 0 }} - {{ $booths->lastItem() ?? 0 }} 件を表示
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold">
                        <th class="py-2.5 px-3 whitespace-nowrap">コマ</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">エリア</th>
                        <th class="py-2.5 px-3">コマ表示名</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">出店区分</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">形態</th>
                        <th class="py-2.5 px-3">取扱品目</th>
                        <th class="py-2.5 px-3 text-right whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($booths as $booth)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-2.5 px-3 font-mono font-bold text-slate-800 whitespace-nowrap">
                                <span class="px-1.5 py-0.5 bg-slate-100 border border-slate-300 rounded text-[11px]">
                                    {{ $booth->booth_code }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-slate-600 whitespace-nowrap text-[11px]">
                                {{ $booth->area?->name ?? '通り' }}
                            </td>
                            <td class="py-2.5 px-3">
                                <button type="button"
                                    class="btn-view-booth font-bold text-slate-800 hover:text-indigo-600 hover:underline transition cursor-pointer text-left inline-flex items-center space-x-1.5"
                                    data-id="{{ $booth->id }}"
                                    data-code="{{ $booth->booth_code }}"
                                    data-area="{{ $booth->area?->name ?? '通り' }}"
                                    data-name="{{ $booth->displayName }}"
                                    data-category-label="{{ $booth->category->label() }}"
                                    data-category-icon="{{ $booth->category->icon() }}"
                                    data-type-label="{{ $booth->booth_type?->label() ?? 'テント' }}"
                                    data-status-label="{{ $booth->status?->label() ?? '確定' }}"
                                    data-status-icon="{{ $booth->status?->icon() ?? '🟢' }}"
                                    data-vendor-name="{{ $booth->vendor?->name ?? '' }}"
                                    data-vendor-contact-person="{{ $booth->vendor?->contact_person ?? '' }}"
                                    data-vendor-contact="{{ $booth->vendor?->contact ?? '' }}"
                                    data-items="{{ $booth->product_items ?? '' }}"
                                    data-landmark="{{ $booth->adjacent_landmark ?? '' }}"
                                    data-notes="{{ $booth->notes ?? '' }}">
                                    <span>{{ $booth->status?->icon() ?? '🟢' }}</span>
                                    <span>{{ $booth->displayName }}</span>
                                </button>
                            </td>
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] {{ $booth->category->badgeClasses() }}">
                                    {{ $booth->category->icon() }} {{ $booth->category->label() }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 whitespace-nowrap text-slate-700 font-medium">
                                {{ $booth->booth_type?->label() ?? 'テント' }}
                            </td>
                            <td class="py-2.5 px-3 text-slate-600 text-[11px] max-w-xs truncate" title="{{ $booth->product_items }}">
                                {{ $booth->product_items ?: '—' }}
                            </td>
                            <td class="py-2.5 px-3 text-right whitespace-nowrap space-x-1">
                                <button type="button"
                                    data-action="open-booth-modal"
                                    data-id="{{ $booth->id }}"
                                    data-code="{{ $booth->booth_code }}"
                                    data-area-id="{{ $booth->layout_area_id }}"
                                    data-name="{{ $booth->name }}"
                                    data-category="{{ $booth->category->value }}"
                                    data-type="{{ $booth->booth_type?->value ?? 'tent' }}"
                                    data-status="{{ $booth->status?->value ?? 'confirmed' }}"
                                    data-vendor-id="{{ $booth->vendor_id }}"
                                    data-items="{{ $booth->product_items }}"
                                    data-landmark="{{ $booth->adjacent_landmark }}"
                                    data-notes="{{ $booth->notes }}"
                                    class="inline-flex items-center space-x-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                    title="配置を編集">
                                    <span>✏️</span>
                                    <span>編集</span>
                                </button>
                                <form action="{{ route('timetables.layouts.booths.destroy', [$timetable, $booth]) }}" method="POST" class="inline"
                                    data-confirm="コマ「{{ $booth->displayName }}（{{ $booth->booth_code }}）」の配置を削除しますか？">
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
                                該当するコマ割りデータがありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($booths->hasPages())
            <div class="px-4 py-3 border-t border-slate-200 bg-slate-50/50">
                {{ $booths->links() }}
            </div>
        @endif
    </div>

    <!-- 閲覧専用詳細モーダル -->
    <div id="modal-booth-detail" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-5 shadow-2xl space-y-4 border border-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="text-xl" id="detail-status-icon">🟢</span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800" id="detail-booth-name">コマ詳細</h3>
                        <p class="text-[11px] font-mono text-slate-500" id="detail-booth-code"></p>
                    </div>
                </div>
                <button type="button" class="btn-close-detail-modal text-slate-400 hover:text-slate-600 transition cursor-pointer p-1">
                    ✕
                </button>
            </div>

            <div class="space-y-3.5 text-xs">
                <!-- 基本情報グリッド -->
                <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <div>
                        <span class="text-slate-500 font-medium block text-[10px]">所属エリア</span>
                        <span class="font-bold text-slate-800" id="detail-area-name">—</span>
                    </div>
                    <div>
                        <span class="text-slate-500 font-medium block text-[10px]">配置状態</span>
                        <span class="font-bold text-slate-800" id="detail-status-label">—</span>
                    </div>
                    <div>
                        <span class="text-slate-500 font-medium block text-[10px]">出店区分</span>
                        <span class="font-bold text-slate-800" id="detail-category-label">—</span>
                    </div>
                    <div>
                        <span class="text-slate-500 font-medium block text-[10px]">ブース形態</span>
                        <span class="font-bold text-slate-800" id="detail-type-label">—</span>
                    </div>
                </div>

                <!-- 割り当て出展・出店者情報 -->
                <div class="bg-indigo-50/50 p-3 rounded-xl border border-indigo-100 space-y-1.5">
                    <span class="text-indigo-900 font-bold block text-[11px] flex items-center space-x-1">
                        <span>🎪</span>
                        <span>割り当て出展・出店者マスター</span>
                    </span>
                    <div id="detail-vendor-section">
                        <div class="font-bold text-indigo-950 text-xs" id="detail-vendor-name">—</div>
                        <div class="text-slate-600 text-[11px] flex flex-wrap gap-x-3 gap-y-0.5 mt-1">
                            <span id="detail-vendor-contact-person"></span>
                            <span id="detail-vendor-contact"></span>
                        </div>
                    </div>
                    <div id="detail-vendor-empty" class="text-slate-400 text-[11px] hidden">
                        （出展・出店者マスターは未割当です）
                    </div>
                </div>

                <!-- 取扱品目 -->
                <div>
                    <span class="text-slate-500 font-bold block mb-0.5">取扱品目・展示内容</span>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-slate-800 whitespace-pre-wrap min-h-[38px]" id="detail-items">—</div>
                </div>

                <!-- 近接ランドマーク -->
                <div>
                    <span class="text-slate-500 font-bold block mb-0.5">近接店舗・建物 (ランドマーク)</span>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-slate-800" id="detail-landmark">—</div>
                </div>

                <!-- 特記事項・メモ -->
                <div>
                    <span class="text-slate-500 font-bold block mb-0.5">特記事項・メモ</span>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-slate-800 whitespace-pre-wrap min-h-[38px]" id="detail-notes">—</div>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-200 flex justify-end">
                <button type="button" class="btn-close-detail-modal px-4 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg font-bold transition cursor-pointer text-xs">
                    閉じる
                </button>
            </div>
        </div>
    </div>

    <!-- 配置 登録・編集兼用モーダル -->
    <div id="boothEditModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full p-4 sm:p-5 shadow-2xl space-y-3.5 border border-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                <h3 class="text-base font-bold text-slate-900 flex items-center space-x-1.5">
                    <span id="modalFormIcon">✏️</span>
                    <span id="modalFormTitle">コマ情報の編集</span>
                    <span id="modalBoothCodeBadge" class="font-mono text-indigo-600 text-sm ml-1"></span>
                </h3>
                <button type="button" id="modalCloseBtn" class="text-slate-400 hover:text-slate-600 text-lg font-bold cursor-pointer">
                    ✕
                </button>
            </div>

            <form id="boothEditForm" method="POST" action=""
                data-timetable-id="{{ $timetable->id }}"
                data-store-url="{{ route('timetables.layouts.booths.store', $timetable) }}"
                data-base-update-url="/timetables/{{ $timetable->id }}/layouts/booths"
                class="space-y-2.5 text-xs">
                @csrf
                <input type="hidden" name="_method" id="boothFormMethod" value="PUT">

                <!-- 第1段: コマ番号 / 所属エリア / 配置状態 (3列グリッド) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <div>
                        <label for="inputBoothCode" class="block font-bold text-slate-700 mb-1">コマ番号 <span class="text-rose-500">*</span></label>
                        <input type="text" id="inputBoothCode" name="booth_code" required
                            class="w-full bg-slate-50 border border-slate-300 rounded-lg py-1.5 px-2.5 font-mono uppercase text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500"
                            placeholder="例: A01, B12, KC-01">
                    </div>
                    <div>
                        <label for="selectBoothArea" class="block font-bold text-slate-700 mb-1">所属エリア</label>
                        <select id="selectBoothArea" name="layout_area_id" class="w-full bg-slate-50 border border-slate-300 rounded-lg py-1.5 px-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500">
                            <option value="">(未設定・通り)</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">配置状態 <span class="text-rose-500">*</span></label>
                        <input type="hidden" name="status" id="inputBoothStatus" value="confirmed">
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

                <!-- 第2段: 出展・出店者マスター / 個別表示名 (2列グリッド) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label for="selectVendor" class="block font-bold text-slate-700 mb-1">配置する出展・出店者マスター</label>
                        <select id="selectVendor" name="vendor_id" class="w-full bg-slate-50 border border-slate-300 rounded-lg py-1.5 px-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500">
                            <option value="">(未割当・個別名のみまたは空きコマ)</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="inputBoothName" class="block font-bold text-slate-700 mb-1">このブースの個別表示名</label>
                        <input type="text" id="inputBoothName" name="name" class="w-full bg-slate-50 border border-slate-300 rounded-lg py-1.5 px-2.5 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="未入力時は出展者マスター名が反映">
                    </div>
                </div>

                <!-- 第3段: 出店区分 / 形態 (2列グリッド) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label for="selectBoothCategory" class="block font-bold text-slate-700 mb-1">出店区分 <span class="text-rose-500">*</span></label>
                        <select id="selectBoothCategory" name="category" required class="w-full bg-slate-50 border border-slate-300 rounded-lg py-1.5 px-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500">
                            @foreach(\App\Enums\BoothCategory::cases() as $case)
                                <option value="{{ $case->value }}">{{ $case->icon() }} {{ $case->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="selectBoothType" class="block font-bold text-slate-700 mb-1">形態 <span class="text-rose-500">*</span></label>
                        <select id="selectBoothType" name="booth_type" required class="w-full bg-slate-50 border border-slate-300 rounded-lg py-1.5 px-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500">
                            @foreach(\App\Enums\BoothType::cases() as $case)
                                <option value="{{ $case->value }}">{{ $case->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- 第4段: 取扱品目 / 近接店舗・ランドマーク (2列グリッド) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label for="textareaItems" class="block font-bold text-slate-700 mb-1">取扱品目・展示内容</label>
                        <input type="text" id="textareaItems" name="product_items" class="w-full bg-slate-50 border border-slate-300 rounded-lg py-1.5 px-2.5 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="例: 焼きそば、フランクフルト、生搾りジュース">
                    </div>
                    <div>
                        <label for="inputLandmark" class="block font-bold text-slate-700 mb-1">近接店舗・建物 (ランドマーク)</label>
                        <input type="text" id="inputLandmark" name="adjacent_landmark" class="w-full bg-slate-50 border border-slate-300 rounded-lg py-1.5 px-2.5 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="例: 川むら前、おもちゃのうえの前">
                    </div>
                </div>

                <!-- 第5段: 特記事項・メモ (全幅) -->
                <div>
                    <label for="textareaNotes" class="block font-bold text-slate-700 mb-1">特記事項・メモ</label>
                    <textarea id="textareaNotes" name="notes" rows="1" class="w-full bg-slate-50 border border-slate-300 rounded-lg py-1.5 px-2.5 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="消火器持参、搬入時間要請など"></textarea>
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
