<x-layouts.app :title="$timetable->name . ' コマ割り一覧'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ページヘッダー -->
    <x-page-header
        title="コマ割り・出店ブース一覧"
        icon="📋"
        :description="'「' . $timetable->name . '」におけるござ市コマ割り（A列・B列）および各特設エリアの出店情報を管理します。'">
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
            <span class="text-slate-700 font-bold truncate">📋 コマ割り一覧</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.layouts.index', $timetable) }}"
                class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>←</span>
                <span>配置ダッシュボードへ</span>
            </a>
            <a href="{{ route('timetables.goza-stores.index', $timetable) }}"
                class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>🏮</span>
                <span>ござ市出店者管理</span>
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
                    <select name="area_id" class="bg-slate-50 border border-slate-300 rounded-lg text-xs py-1 px-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
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
                    <select name="category" class="bg-slate-50 border border-slate-300 rounded-lg text-xs py-1 px-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">全区分</option>
                        @foreach(\App\Enums\BoothCategory::cases() as $case)
                            <option value="{{ $case->value }}" {{ ($filters['category'] ?? '') === $case->value ? 'selected' : '' }}>
                                {{ $case->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- キーワード検索 -->
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="出店名・コマ番号・近接店舗で検索..."
                        class="w-full bg-slate-50 border border-slate-300 rounded-lg text-xs py-1 px-2.5 text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400">
                </div>

                <!-- ボタン -->
                <button type="submit" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center space-x-1 shrink-0">
                    <span>🔍</span>
                    <span>検索</span>
                </button>
                @if(!empty($filters['area_id']) || !empty($filters['category']) || !empty($filters['search']))
                    <a href="{{ route('timetables.layouts.booths', $timetable) }}" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs transition flex items-center space-x-1 shrink-0">
                        <span>✖</span>
                        <span>リセット</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- コマ割り一覧テーブル -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
        <div class="p-3 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700">
                全 {{ $booths->total() }} 件中 {{ $booths->firstItem() ?? 0 }} 〜 {{ $booths->lastItem() ?? 0 }} 件を表示
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-800">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3 whitespace-nowrap">コマ番号</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">エリア</th>
                        <th class="py-2.5 px-3 whitespace-nowrap min-w-[160px]">表示名・屋号</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">出店区分</th>
                        <th class="py-2.5 px-3 whitespace-nowrap min-w-[140px]">ござ市紐付け</th>
                        <th class="py-2.5 px-3 whitespace-nowrap min-w-[140px]">近接店舗・建物</th>
                        <th class="py-2.5 px-3 text-right whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($booths as $booth)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-2.5 px-3 font-mono font-bold whitespace-nowrap">
                                <span class="px-1.5 py-0.5 bg-slate-100 border border-slate-300 rounded text-slate-800">
                                    {{ $booth->booth_code }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-slate-600 whitespace-nowrap">
                                {{ $booth->area?->name ?? '-' }}
                            </td>
                            <td class="py-2.5 px-3 font-bold text-slate-900">
                                {{ $booth->displayName }}
                            </td>
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] {{ $booth->category->badgeClasses() }}">
                                    {{ $booth->category->label() }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 whitespace-nowrap text-slate-600">
                                @if($booth->gozaStore)
                                    <span class="inline-flex items-center space-x-1 px-1.5 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 rounded text-[10px]">
                                        <span>🏮</span>
                                        <span class="truncate max-w-[120px]">{{ $booth->gozaStore->store_name }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 text-[11px]">-</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-slate-600 text-[11px]">
                                {{ $booth->adjacent_landmark ?? '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                <button type="button"
                                    data-action="open-booth-modal"
                                    data-id="{{ $booth->id }}"
                                    data-code="{{ $booth->booth_code }}"
                                    data-area="{{ $booth->area?->name }}"
                                    data-name="{{ $booth->name }}"
                                    data-category="{{ $booth->category->value }}"
                                    data-goza-id="{{ $booth->goza_store_id }}"
                                    data-landmark="{{ $booth->adjacent_landmark }}"
                                    data-notes="{{ $booth->notes }}"
                                    class="px-2.5 py-1 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition">
                                    編集
                                </button>
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

    <!-- コマ割りクイック編集モーダル -->
    <div id="boothEditModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-5 shadow-2xl space-y-4 border border-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center space-x-1.5">
                    <span>✏️</span>
                    <span>コマ情報の編集: <span id="modalBoothCode" class="font-mono text-indigo-600"></span></span>
                </h3>
                <button type="button" id="modalCloseBtn" class="text-slate-400 hover:text-slate-600 text-lg font-bold">
                    ✕
                </button>
            </div>

            <form id="boothEditForm" method="POST" action="" class="space-y-3 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-bold text-slate-700 mb-1">所属エリア</label>
                    <div id="modalAreaName" class="text-slate-600 bg-slate-50 p-2 rounded-lg border border-slate-200 font-medium"></div>
                </div>

                <div>
                    <label for="inputBoothName" class="block font-bold text-slate-700 mb-1">ブース表示名・屋号</label>
                    <input type="text" id="inputBoothName" name="name" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="未入力の場合はござ市出店者名が表示されます">
                </div>

                <div>
                    <label for="selectBoothCategory" class="block font-bold text-slate-700 mb-1">出店区分 <span class="text-rose-500">*</span></label>
                    <select id="selectBoothCategory" name="category" required class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500">
                        @foreach(\App\Enums\BoothCategory::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="selectGozaStore" class="block font-bold text-slate-700 mb-1">ござ市出店者の紐付け (任意)</label>
                    <select id="selectGozaStore" name="goza_store_id" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500">
                        <option value="">(紐付けなし・一般または企業ブース)</option>
                        @foreach($gozaStores as $gs)
                            <option value="{{ $gs->id }}">
                                {{ $gs->store_name }} {{ $gs->representative_name ? '(' . $gs->representative_name . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="inputLandmark" class="block font-bold text-slate-700 mb-1">近接店舗・建物 (ランドマーク)</label>
                    <input type="text" id="inputLandmark" name="adjacent_landmark" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="例: おもちゃのうえの前, 川むら前">
                </div>

                <div>
                    <label for="inputNotes" class="block font-bold text-slate-700 mb-1">特記事項・メモ</label>
                    <textarea id="inputNotes" name="notes" rows="2" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="備考・運用条件など"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end space-x-2">
                    <button type="button" id="modalCancelBtn" class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg font-bold shadow-2xs transition">
                        キャンセル
                    </button>
                    <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-2xs transition">
                        💾 変更を保存する
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- モーダル操作用 Vanilla JS (外部スクリプト / CSP対応) -->
<script type="module">
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('boothEditModal');
        const form = document.getElementById('boothEditForm');
        const codeSpan = document.getElementById('modalBoothCode');
        const areaNameDiv = document.getElementById('modalAreaName');
        const nameInput = document.getElementById('inputBoothName');
        const categorySelect = document.getElementById('selectBoothCategory');
        const gozaSelect = document.getElementById('selectGozaStore');
        const landmarkInput = document.getElementById('inputLandmark');
        const notesTextarea = document.getElementById('inputNotes');
        const closeBtn = document.getElementById('modalCloseBtn');
        const cancelBtn = document.getElementById('modalCancelBtn');

        const closeModal = () => {
            modal.classList.add('hidden');
        };

        closeBtn?.addEventListener('click', closeModal);
        cancelBtn?.addEventListener('click', closeModal);

        document.querySelectorAll('[data-action="open-booth-modal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                const code = btn.getAttribute('data-code');
                const area = btn.getAttribute('data-area');
                const name = btn.getAttribute('data-name') || '';
                const category = btn.getAttribute('data-category');
                const gozaId = btn.getAttribute('data-goza-id') || '';
                const landmark = btn.getAttribute('data-landmark') || '';
                const notes = btn.getAttribute('data-notes') || '';

                codeSpan.textContent = code;
                areaNameDiv.textContent = area || '-';
                nameInput.value = name;
                categorySelect.value = category;
                gozaSelect.value = gozaId;
                landmarkInput.value = landmark;
                notesTextarea.value = notes;

                form.action = `/timetables/{{ $timetable->id }}/layouts/booths/${id}`;
                modal.classList.remove('hidden');
            });
        });
    });
</script>
</x-layouts.app>
