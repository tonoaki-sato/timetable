<x-layouts.app :title="$timetable->name . ' 出店者マスター管理'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ページヘッダー (他の一覧と同一の共通レイアウト) -->
    <x-page-header
        title="出店者マスター管理"
        icon="🏪"
        description="イベントにおける商業出店者の基本情報と配置コマを一元管理します。">
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
            <span class="text-slate-700 font-bold truncate">🏪 出店者マスター管理</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <button type="button"
                id="btnOpenCreateVendorModal"
                data-store-url="{{ route('timetables.vendors.store', $timetable) }}"
                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0 cursor-pointer">
                <span>➕</span>
                <span>新規出店者を登録</span>
            </button>
            <a href="{{ route('timetables.layouts.index', $timetable) }}"
                class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>←</span>
                <span>配置ダッシュボードへ</span>
            </a>
            <a href="{{ route('timetables.layouts.facilities', $timetable) }}"
                class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>🚰</span>
                <span>インフラ設備一覧</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="vendors" />

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

    <!-- 検索・絞り込みフィルターバー -->
    <div class="print:hidden bg-slate-50 border border-slate-200 rounded-xl p-3 shadow-2xs">
        <form method="GET" action="{{ route('timetables.vendors.index', $timetable) }}" class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[280px]">
                <!-- 出店区分絞り込み -->
                <div class="flex items-center space-x-1.5 text-xs shrink-0">
                    <span class="text-slate-500 font-bold whitespace-nowrap">🔍 区分:</span>
                    <select name="category" class="bg-white border border-slate-300 rounded-lg text-xs py-1 px-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">全区分</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->value }}" {{ ($filters['category'] ?? '') === $cat->value ? 'selected' : '' }}>
                                {{ $cat->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- キーワード検索 -->
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="出店者名・代表者・品目・コマ番号で検索..."
                        class="w-full bg-white border border-slate-300 rounded-lg text-xs py-1 px-2.5 text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400">
                </div>

                <!-- ボタン -->
                <button type="submit" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center space-x-1 shrink-0 cursor-pointer">
                    <span>検索</span>
                </button>
                @if(!empty($filters['category']) || !empty($filters['search']))
                    <a href="{{ route('timetables.vendors.index', $timetable) }}" class="px-2.5 py-1 bg-white border border-slate-300 hover:bg-slate-100 text-slate-600 rounded-lg text-xs transition flex items-center space-x-1 shrink-0">
                        <span>✖</span>
                        <span>リセット</span>
                    </a>
                @endif
            </div>

            <!-- 件数インジケータ -->
            <div class="text-xs text-slate-500 font-mono shrink-0">
                全 {{ $vendors->total() }} 件中 {{ $vendors->firstItem() ?? 0 }} 〜 {{ $vendors->lastItem() ?? 0 }} 件を表示
            </div>
        </form>
    </div>

    <!-- 出店者一覧テーブル（名前・配置・操作の3列） -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-800">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3 whitespace-nowrap min-w-[180px]">名前</th>
                        <th class="py-2.5 px-3 whitespace-nowrap min-w-[260px]">配置</th>
                        <th class="py-2.5 px-3 text-right whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($vendors as $vendor)
                        @php
                            $boothsData = $vendor->items->map(fn($b) => [
                                'id' => $b->id,
                                'booth_code' => $b->code,
                                'area_name' => $b->area?->name ?? '特設エリア',
                                'category' => $b->boothCategory()?->value ?? '',
                                'category_label' => $b->boothCategory()?->label() ?? '',
                                'category_badge' => $b->boothCategory()?->badgeClasses() ?? 'bg-slate-100 text-slate-700',
                                'product_items' => $b->notes,
                                'adjacent_landmark' => $b->location_description,
                            ]);
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- 1. 名前（代表者・連絡先は非表示、クリックで詳細モーダル） -->
                            <td class="py-2.5 px-3 align-top font-bold text-slate-900">
                                <button type="button"
                                    data-action="open-vendor-detail"
                                    data-id="{{ $vendor->id }}"
                                    data-name="{{ $vendor->name }}"
                                    data-rep="{{ $vendor->representative_name }}"
                                    data-contact="{{ $vendor->emergency_contact }}"
                                    data-notes="{{ $vendor->notes }}"
                                    data-booths="{{ json_encode($boothsData) }}"
                                    class="text-left font-bold text-slate-900 hover:text-indigo-600 hover:underline cursor-pointer flex items-baseline space-x-1"
                                    title="クリックして詳細を表示">
                                    <span>{{ $vendor->name }}</span>
                                    <span class="font-normal text-slate-500 text-[11px]">({{ $vendor->items->count() }}コマ)</span>
                                </button>
                            </td>

                            <!-- 2. 配置（割当コマ・エリア・区分・品目） -->
                            <td class="py-2.5 px-3 align-top space-y-1.5">
                                @if($vendor->items->isNotEmpty())
                                    @foreach($vendor->items as $booth)
                                        <div class="p-1.5 bg-slate-50/80 border border-slate-200/80 rounded-lg space-y-1">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span class="px-1.5 py-0.2 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded font-mono font-bold text-[11px]">
                                                    {{ $booth->code }}
                                                </span>
                                                <span class="font-bold text-slate-700 text-[11px]">{{ $booth->area?->name }}</span>
                                                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold border {{ $booth->boothCategory()?->badgeClasses() ?? 'bg-slate-100 text-slate-700' }}">
                                                    {{ $booth->boothCategory()?->label() ?? '出店' }}
                                                </span>
                                                @if($booth->location_description)
                                                    <span class="text-[10px] text-slate-400">（{{ $booth->location_description }}）</span>
                                                @endif
                                            </div>
                                            @if($booth->notes)
                                                <div class="text-[11px] text-slate-600 pl-1">
                                                    {{ $booth->notes }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <span class="inline-flex items-center space-x-1 px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded text-[11px] font-bold">
                                        <span>⚠️</span>
                                        <span>コマ未割当 (コマ未定)</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 3. 操作（編集・削除） -->
                            <td class="py-2.5 px-3 text-right align-top whitespace-nowrap space-x-1">
                                <button type="button"
                                    data-action="open-edit-vendor-modal"
                                    data-id="{{ $vendor->id }}"
                                    data-name="{{ $vendor->name }}"
                                    data-rep="{{ $vendor->representative_name }}"
                                    data-contact="{{ $vendor->emergency_contact }}"
                                    data-notes="{{ $vendor->notes }}"
                                    data-booths="{{ json_encode($boothsData) }}"
                                    class="inline-flex items-center space-x-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                    title="出店者を編集">
                                    <span>✏️</span>
                                    <span>編集</span>
                                </button>
                                <form method="POST" action="{{ route('timetables.vendors.destroy', [$timetable, $vendor]) }}" class="inline"
                                    data-confirm="出店者「{{ $vendor->name }}」を削除しますか？">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center space-x-1 px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                        title="出店者を削除">
                                        <span>🗑️</span>
                                        <span>削除</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-slate-400 text-xs">
                                登録されている出店者データがありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($vendors->hasPages())
            <div class="px-4 py-3 border-t border-slate-200 bg-slate-50/50">
                {{ $vendors->links() }}
            </div>
        @endif
    </div>

    <!-- 出店者 詳細モーダル (名前クリック時にポップアップ) -->
    <div id="vendorDetailModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full p-5 shadow-2xl space-y-4 border border-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                    <span>🏪</span>
                    <span>出店者詳細:</span>
                    <span id="detailVendorName" class="text-indigo-600 ml-1"></span>
                </h3>
                <button type="button" id="detailModalCloseBtn" class="text-slate-400 hover:text-slate-600 text-lg font-bold cursor-pointer">
                    ✕
                </button>
            </div>

            <div class="space-y-4 text-xs">
                <!-- 基本情報カード -->
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-2">
                    <h4 class="font-bold text-slate-800 text-xs border-b border-slate-200 pb-1.5 flex items-center space-x-1">
                        <span>📋</span>
                        <span>出店者基本情報</span>
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                        <div>
                            <span class="text-slate-500 font-bold block text-[11px]">出店代表者:</span>
                            <span id="detailRepName" class="text-slate-800 font-medium"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 font-bold block text-[11px]">緊急連絡先:</span>
                            <a id="detailContact" class="font-mono text-slate-800 font-medium"></a>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="text-slate-500 font-bold block text-[11px]">確保コマ数:</span>
                            <span id="detailBoothsCount" class="font-bold text-indigo-700"></span>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="text-slate-500 font-bold block text-[11px]">特記事項・メモ:</span>
                            <div id="detailNotes" class="text-slate-700 whitespace-pre-wrap mt-0.5 bg-white p-2 rounded-lg border border-slate-200"></div>
                        </div>
                    </div>
                </div>

                <!-- 割当ブース一覧カード -->
                <div class="space-y-2">
                    <h4 class="font-bold text-slate-800 text-xs flex items-center space-x-1">
                        <span>🗺️</span>
                        <span>割当ブース詳細</span>
                    </h4>
                    <div id="detailBoothsContainer" class="space-y-2 max-h-60 overflow-y-auto pr-1">
                        <!-- JSで動的レンダリング -->
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-end space-x-2">
                <button type="button" id="detailModalBottomCloseBtn" class="px-3.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition cursor-pointer">
                    閉じる
                </button>
                <button type="button" id="btnEditFromDetail" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-bold shadow-2xs transition cursor-pointer flex items-center space-x-1">
                    <span>✏️</span>
                    <span>この出店者を編集する</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 出店者 登録・編集モーダル -->
    <div id="vendorEditModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full p-5 shadow-2xl space-y-4 border border-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center space-x-1.5">
                    <span id="modalVendorIcon">🏪</span>
                    <span id="modalVendorTitle">出店者の登録</span>
                    <span id="modalVendorId" class="font-mono text-indigo-600 ml-1 text-xs"></span>
                </h3>
                <button type="button" id="vendorModalCloseBtn" class="text-slate-400 hover:text-slate-600 text-lg font-bold cursor-pointer">
                    ✕
                </button>
            </div>

            <form id="vendorEditForm" method="POST" action=""
                data-base-action="/timetables/{{ $timetable->id }}/vendors"
                data-store-url="{{ route('timetables.vendors.store', $timetable) }}"
                class="space-y-3.5 text-xs">
                @csrf
                <input type="hidden" name="_method" id="vendorFormMethod" value="POST">

                <div>
                    <label for="inputVendorName" class="block font-bold text-slate-700 mb-1">出店名・屋号 <span class="text-rose-500">*</span></label>
                    <input type="text" id="inputVendorName" name="name" required class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="例: 水浅青果">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="inputVendorRep" class="block font-bold text-slate-700 mb-1">出店代表者氏名</label>
                        <input type="text" id="inputVendorRep" name="representative_name" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="例: 水浅 太郎">
                    </div>

                    <div>
                        <label for="inputVendorContact" class="block font-bold text-slate-700 mb-1">緊急連絡先</label>
                        <input type="text" id="inputVendorContact" name="emergency_contact" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="例: 090-1234-5678">
                    </div>
                </div>

                <div>
                    <label for="inputVendorNotes" class="block font-bold text-slate-700 mb-1">備考・搬入情報メモ</label>
                    <textarea id="inputVendorNotes" name="notes" rows="2" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="搬入希望時間、車両証申請、電源メモなど"></textarea>
                </div>

                <!-- 新規登録時のみ表示する初期コマ割当セクション -->
                <div id="createBoothSection" class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <span class="font-bold text-slate-800 text-xs block border-b border-slate-200 pb-1">初期ブース割当 (任意)</span>
                    <div class="space-y-2 pt-1">
                        <div>
                            <label for="selectInitialBooth" class="block font-bold text-slate-600 text-[11px] mb-0.5">割当コマ番号</label>
                            <select id="selectInitialBooth" name="initial_booth_id" class="w-full bg-white border border-slate-300 rounded-lg p-1.5 text-xs text-slate-800">
                                <option value="">(未割当・後で設定する)</option>
                                @foreach($allBooths as $b)
                                    <option value="{{ $b->id }}">
                                        {{ $b->booth_code }} : {{ $b->area?->name ?? 'エリア未定' }} {{ $b->vendor ? '（現在: '.$b->vendor->name.'）' : '（空き）' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="selectInitialCategory" class="block font-bold text-slate-600 text-[11px] mb-0.5">出店区分</label>
                                <select id="selectInitialCategory" name="initial_category" class="w-full bg-white border border-slate-300 rounded-lg p-1.5 text-xs text-slate-800">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="inputInitialItems" class="block font-bold text-slate-600 text-[11px] mb-0.5">主な販売品目</label>
                                <input type="text" id="inputInitialItems" name="initial_product_items" class="w-full bg-white border border-slate-300 rounded-lg p-1.5 text-xs text-slate-800" placeholder="例: 新鮮野菜、果物">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 編集時のみ表示する割当ブース管理セクション -->
                <div id="editBoothsSection" class="hidden space-y-2.5">
                    <span class="font-bold text-slate-800 text-xs block border-b border-slate-200 pb-1">割当ブースの管理</span>
                    <div id="existingBoothsContainer" class="space-y-2">
                        <!-- JSで動的描画 -->
                    </div>

                    <!-- 追加割当 -->
                    <div class="p-3 bg-indigo-50/50 border border-indigo-100 rounded-xl space-y-2">
                        <span class="font-bold text-indigo-900 text-xs block">＋ 新しいコマを追加割当</span>
                        <div class="space-y-2">
                            <div>
                                <label for="selectAddBooth" class="block font-bold text-slate-600 text-[11px] mb-0.5">空きコマを選択</label>
                                <select id="selectAddBooth" name="add_booth_id" class="w-full bg-white border border-slate-300 rounded-lg p-1.5 text-xs text-slate-800">
                                    <option value="">(追加しない)</option>
                                    @foreach($allBooths as $b)
                                        <option value="{{ $b->id }}">
                                            {{ $b->booth_code }} : {{ $b->area?->name ?? 'エリア未定' }} {{ $b->vendor ? '（現在: '.$b->vendor->name.'）' : '（空き）' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label for="selectAddCategory" class="block font-bold text-slate-600 text-[11px] mb-0.5">このコマの出店区分</label>
                                    <select id="selectAddCategory" name="add_category" class="w-full bg-white border border-slate-300 rounded-lg p-1.5 text-xs text-slate-800">
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="inputAddItems" class="block font-bold text-slate-600 text-[11px] mb-0.5">このコマの販売品目</label>
                                    <input type="text" id="inputAddItems" name="add_product_items" class="w-full bg-white border border-slate-300 rounded-lg p-1.5 text-xs text-slate-800" placeholder="例: 生搾りジュース">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end space-x-2">
                    <button type="button" id="vendorModalCancelBtn" class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg font-bold shadow-2xs transition cursor-pointer">
                        キャンセル
                    </button>
                    <button type="submit" id="modalVendorSubmitBtn" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-2xs transition cursor-pointer">
                        💾 登録して保存
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@vite(['resources/js/vendors.js'])
</x-layouts.app>
