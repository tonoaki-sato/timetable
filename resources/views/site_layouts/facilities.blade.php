<x-layouts.app :title="$timetable->name . ' インフラ設備一覧'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ページヘッダー -->
    <x-page-header
        title="インフラ設備一覧"
        icon="🚰"
        :description="'「' . $timetable->name . '」における給水設備（手洗い）、トイレ、授乳室、救護所、本陣、スタッフ待機場所の設置計画・確定状況を管理します。'">
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
            <span class="text-slate-700 font-bold truncate">🚰 インフラ設備一覧</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.layouts.index', $timetable) }}"
                class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>←</span>
                <span>配置ダッシュボードへ</span>
            </a>
            <a href="{{ route('timetables.layouts.booths', $timetable) }}"
                class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>📋</span>
                <span>コマ割り一覧</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="layouts-facilities" />

    <!-- フラッシュメッセージ -->
    @if(session('success'))
    <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl flex items-center space-x-2">
        <span>✅</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <!-- 表示施設フィルター（給水設備 / トイレ・授乳室 / その他） -->
    <div class="bg-white border border-slate-200 rounded-2xl p-3 shadow-2xs space-y-2">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center space-x-2">
                <span class="text-xs font-bold text-slate-700 flex items-center space-x-1">
                    <span>🔍</span>
                    <span>表示施設切り替え:</span>
                </span>
                <span class="text-[11px] text-slate-500">
                    表示中 <strong id="visibleCount" class="font-mono text-indigo-600">{{ $facilities->count() }}</strong> / 全 {{ $map->facilities->count() }} 件
                </span>
            </div>
            <div class="flex items-center space-x-2 text-xs">
                <button type="button" id="selectAllCategoriesBtn" class="text-indigo-600 hover:text-indigo-800 font-bold hover:underline cursor-pointer">
                    すべて表示
                </button>
                <span class="text-slate-300">|</span>
                <button type="button" id="deselectAllCategoriesBtn" class="text-slate-500 hover:text-slate-700 font-bold hover:underline cursor-pointer">
                    すべて非表示
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 pt-1">
            @foreach(\App\Enums\FacilityCategory::cases() as $cat)
                @php
                    $count = $map->facilities->filter(fn($f) => $f->facility_type->category() === $cat)->count();
                @endphp
                <button type="button"
                    data-action="toggle-category"
                    data-category="{{ $cat->value }}"
                    data-active="true"
                    class="category-toggle-btn px-3 py-1.5 rounded-xl font-bold text-xs transition flex items-center space-x-1.5 border shadow-2xs cursor-pointer select-none bg-indigo-50 border-indigo-300 text-indigo-900 hover:bg-indigo-100">
                    <span class="check-icon">✓</span>
                    <span>{{ $cat->icon() }}</span>
                    <span>{{ $cat->label() }}</span>
                    <span class="category-count text-[11px] font-mono opacity-80 px-1.5 py-0.5 bg-white/70 rounded-full border border-indigo-200">
                        {{ $count }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    <!-- 設備一覧テーブル -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-800">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3 whitespace-nowrap">設備コード</th>
                        <th class="py-2.5 px-3 whitespace-nowrap min-w-[160px]">設備名称</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">設備種別</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">設置エリア</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">状態</th>
                        <th class="py-2.5 px-3 whitespace-nowrap min-w-[180px]">設置場所の目安</th>
                        <th class="py-2.5 px-3 whitespace-nowrap min-w-[120px]">特記事項</th>
                        <th class="py-2.5 px-3 text-right whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($facilities as $facility)
                        <tr class="hover:bg-slate-50/80 transition facility-row"
                            data-facility-id="{{ $facility->id }}"
                            data-facility-category="{{ $facility->facility_type->category()->value }}">
                            <td class="py-2.5 px-3 font-mono font-bold whitespace-nowrap">
                                <span class="px-1.5 py-0.5 bg-slate-100 border border-slate-300 rounded text-slate-800">
                                    {{ $facility->facility_code }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 font-bold text-slate-900 flex items-center space-x-1.5">
                                <span class="text-base">{{ $facility->facility_type->icon() }}</span>
                                <span>{{ $facility->name }}</span>
                            </td>
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] {{ $facility->facility_type->badgeClasses() }}">
                                    {{ $facility->facility_type->label() }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-slate-600 whitespace-nowrap">
                                {{ $facility->area?->name ?? 'メイン通り' }}
                            </td>
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] {{ $facility->statusBadgeClass }}">
                                    {{ $facility->status }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-slate-600 text-[11px]">
                                {{ $facility->location_description ?? '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-slate-500 text-[11px]">
                                {{ $facility->notes ?? '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                <button type="button"
                                    data-action="open-facility-modal"
                                    data-id="{{ $facility->id }}"
                                    data-code="{{ $facility->facility_code }}"
                                    data-name="{{ $facility->name }}"
                                    data-type="{{ $facility->facility_type->value }}"
                                    data-status="{{ $facility->status }}"
                                    data-location="{{ $facility->location_description }}"
                                    data-notes="{{ $facility->notes }}"
                                    class="px-2.5 py-1 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition cursor-pointer">
                                    編集
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 text-xs">
                                該当する設備データがありません。
                            </td>
                        </tr>
                    @endforelse
                    <tr id="noMatchingRow" class="hidden">
                        <td colspan="8" class="py-8 text-center text-slate-400 text-xs">
                            選択された表示分類に該当する設備データがありません（すべての分類が非表示か、該当データが存在しません）。
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 設備編集モーダル -->
    <div id="facilityEditModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-5 shadow-2xl space-y-4 border border-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center space-x-1.5">
                    <span>✏️</span>
                    <span>設備の編集: <span id="modalFacilityCode" class="font-mono text-indigo-600"></span></span>
                </h3>
                <button type="button" id="modalFacCloseBtn" class="text-slate-400 hover:text-slate-600 text-lg font-bold cursor-pointer">
                    ✕
                </button>
            </div>

            <form id="facilityEditForm" method="POST" action="" class="space-y-3 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label for="inputFacName" class="block font-bold text-slate-700 mb-1">設備名称 <span class="text-rose-500">*</span></label>
                    <input type="text" id="inputFacName" name="name" required class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="selectFacType" class="block font-bold text-slate-700 mb-1">設備種別 <span class="text-rose-500">*</span></label>
                        <select id="selectFacType" name="facility_type" required class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500">
                            @foreach(\App\Enums\FacilityType::cases() as $case)
                                <option value="{{ $case->value }}">{{ $case->icon() }} {{ $case->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="selectFacStatus" class="block font-bold text-slate-700 mb-1">設置ステータス <span class="text-rose-500">*</span></label>
                        <select id="selectFacStatus" name="status" required class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500">
                            <option value="予定">予定</option>
                            <option value="確定">確定</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="inputFacLocation" class="block font-bold text-slate-700 mb-1">設置場所の目安</label>
                    <input type="text" id="inputFacLocation" name="location_description" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="例: 北川駐車場 奥, 甲陽ビル前">
                </div>

                <div>
                    <label for="inputFacNotes" class="block font-bold text-slate-700 mb-1">特記事項</label>
                    <textarea id="inputFacNotes" name="notes" rows="2" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="備品構成や注意事項"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end space-x-2">
                    <button type="button" id="modalFacCancelBtn" class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg font-bold shadow-2xs transition cursor-pointer">
                        キャンセル
                    </button>
                    <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-2xs transition cursor-pointer">
                        💾 変更を保存する
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- モーダル操作 & 分類切り替え用 Vanilla JS (外部スクリプト / CSP対応) -->
<script type="module">
    document.addEventListener('DOMContentLoaded', () => {
        // --- 1. 分類切り替えトグル機能 ---
        const categoryButtons = document.querySelectorAll('[data-action="toggle-category"]');
        const facilityRows = document.querySelectorAll('.facility-row');
        const visibleCountSpan = document.getElementById('visibleCount');
        const noMatchingRow = document.getElementById('noMatchingRow');
        const selectAllBtn = document.getElementById('selectAllCategoriesBtn');
        const deselectAllBtn = document.getElementById('deselectAllCategoriesBtn');

        // URLパラメータから初期選択状態を復元（例: ?categories=water_supply,toilet_nursing）
        const urlParams = new URLSearchParams(window.location.search);
        const categoriesParam = urlParams.get('categories');
        const allCategoryKeys = ['water_supply', 'toilet_nursing', 'other'];

        let activeCategories = new Set(
            categoriesParam !== null
                ? (categoriesParam ? categoriesParam.split(',').filter(k => allCategoryKeys.includes(k)) : [])
                : allCategoryKeys
        );

        const updateStylesAndRows = () => {
            let visibleCount = 0;

            // ボタンのスタイル更新
            categoryButtons.forEach(btn => {
                const cat = btn.getAttribute('data-category');
                const isActive = activeCategories.has(cat);
                btn.setAttribute('data-active', isActive ? 'true' : 'false');
                const checkIcon = btn.querySelector('.check-icon');

                if (isActive) {
                    btn.className = 'category-toggle-btn px-3 py-1.5 rounded-xl font-bold text-xs transition flex items-center space-x-1.5 border shadow-2xs cursor-pointer select-none bg-indigo-50 border-indigo-300 text-indigo-900 hover:bg-indigo-100';
                    if (checkIcon) checkIcon.textContent = '✓';
                } else {
                    btn.className = 'category-toggle-btn px-3 py-1.5 rounded-xl font-bold text-xs transition flex items-center space-x-1.5 border shadow-none cursor-pointer select-none bg-slate-50 border-slate-200 text-slate-400 opacity-60 hover:opacity-100';
                    if (checkIcon) checkIcon.textContent = '–';
                }
            });

            // テーブル行の表示切り替え
            facilityRows.forEach(row => {
                const rowCat = row.getAttribute('data-facility-category');
                if (activeCategories.has(rowCat)) {
                    row.classList.remove('hidden');
                    visibleCount++;
                } else {
                    row.classList.add('hidden');
                }
            });

            // 表示件数表示の更新
            if (visibleCountSpan) {
                visibleCountSpan.textContent = visibleCount;
            }

            // 該当なし行の表示制御
            if (noMatchingRow) {
                if (visibleCount === 0 && facilityRows.length > 0) {
                    noMatchingRow.classList.remove('hidden');
                } else {
                    noMatchingRow.classList.add('hidden');
                }
            }

            // URLパラメータ同期
            const newUrl = new URL(window.location);
            if (activeCategories.size === allCategoryKeys.length) {
                newUrl.searchParams.delete('categories');
            } else {
                newUrl.searchParams.set('categories', Array.from(activeCategories).join(','));
            }
            window.history.replaceState({}, '', newUrl);
        };

        categoryButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const cat = btn.getAttribute('data-category');
                if (activeCategories.has(cat)) {
                    activeCategories.delete(cat);
                } else {
                    activeCategories.add(cat);
                }
                updateStylesAndRows();
            });
        });

        selectAllBtn?.addEventListener('click', () => {
            allCategoryKeys.forEach(k => activeCategories.add(k));
            updateStylesAndRows();
        });

        deselectAllBtn?.addEventListener('click', () => {
            activeCategories.clear();
            updateStylesAndRows();
        });

        updateStylesAndRows();

        // --- 2. 設備編集モーダル機能 ---
        const modal = document.getElementById('facilityEditModal');
        const form = document.getElementById('facilityEditForm');
        const codeSpan = document.getElementById('modalFacilityCode');
        const nameInput = document.getElementById('inputFacName');
        const typeSelect = document.getElementById('selectFacType');
        const statusSelect = document.getElementById('selectFacStatus');
        const locationInput = document.getElementById('inputFacLocation');
        const notesTextarea = document.getElementById('inputFacNotes');
        const closeBtn = document.getElementById('modalFacCloseBtn');
        const cancelBtn = document.getElementById('modalFacCancelBtn');

        const closeModal = () => {
            modal.classList.add('hidden');
        };

        closeBtn?.addEventListener('click', closeModal);
        cancelBtn?.addEventListener('click', closeModal);

        document.querySelectorAll('[data-action="open-facility-modal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                const code = btn.getAttribute('data-code');
                const name = btn.getAttribute('data-name');
                const type = btn.getAttribute('data-type');
                const status = btn.getAttribute('data-status');
                const location = btn.getAttribute('data-location') || '';
                const notes = btn.getAttribute('data-notes') || '';

                codeSpan.textContent = code;
                nameInput.value = name;
                typeSelect.value = type;
                statusSelect.value = status;
                locationInput.value = location;
                notesTextarea.value = notes;

                form.action = `/timetables/{{ $timetable->id }}/layouts/facilities/${id}`;
                modal.classList.remove('hidden');
            });
        });
    });
</script>
</x-layouts.app>
