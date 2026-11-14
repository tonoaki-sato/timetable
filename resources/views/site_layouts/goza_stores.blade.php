<x-layouts.app :title="$timetable->name . ' ござ市出店者管理'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ページヘッダー -->
    <x-page-header
        title="ござ市出店者マスター管理"
        icon="🏮"
        :description="'「' . $timetable->name . '」におけるござ市出店者の屋号、代表者、緊急連絡先、販売品目、出店状況を管理します。'">
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
            <span class="text-slate-700 font-bold truncate">🏮 ござ市出店者</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <button type="button"
                id="btnOpenCreateStoreModal"
                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>➕</span>
                <span>新規出店者を登録</span>
            </button>
            <a href="{{ route('timetables.layouts.index', $timetable) }}"
                class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>←</span>
                <span>配置ダッシュボードへ</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="goza-stores" />

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
        <form method="GET" action="{{ route('timetables.goza-stores.index', $timetable) }}" class="space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <!-- ステータス絞り込み -->
                <div class="flex items-center space-x-1 text-xs">
                    <span class="text-slate-500 font-bold whitespace-nowrap">状態:</span>
                    <select name="status" class="bg-slate-50 border border-slate-300 rounded-lg text-xs py-1 px-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">全ステータス</option>
                        @foreach(\App\Models\GozaStore::STATUSES as $key => $label)
                            <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- キーワード検索 -->
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="屋号・代表者・電話番号・品目で検索..."
                        class="w-full bg-slate-50 border border-slate-300 rounded-lg text-xs py-1 px-2.5 text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400">
                </div>

                <!-- ボタン -->
                <button type="submit" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center space-x-1 shrink-0">
                    <span>🔍</span>
                    <span>検索</span>
                </button>
                @if(!empty($filters['status']) || !empty($filters['search']))
                    <a href="{{ route('timetables.goza-stores.index', $timetable) }}" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs transition flex items-center space-x-1 shrink-0">
                        <span>✖</span>
                        <span>リセット</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- 出店者一覧テーブル -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
        <div class="p-3 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700">
                全 {{ $stores->total() }} 件中 {{ $stores->firstItem() ?? 0 }} 〜 {{ $stores->lastItem() ?? 0 }} 件を表示
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-800">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3 whitespace-nowrap min-w-[160px]">出店名・屋号</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">出店代表者</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">緊急連絡先</th>
                        <th class="py-2.5 px-3 whitespace-nowrap min-w-[180px]">主な販売品目</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">割当コマ</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">状態</th>
                        <th class="py-2.5 px-3 text-right whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($stores as $store)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-2.5 px-3 font-bold text-slate-900">
                                {{ $store->store_name }}
                            </td>
                            <td class="py-2.5 px-3 text-slate-700 whitespace-nowrap">
                                {{ $store->representative_name ?? '-' }}
                            </td>
                            <td class="py-2.5 px-3 font-mono text-slate-700 whitespace-nowrap">
                                {{ $store->emergency_contact ?? '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-slate-600 text-[11px]">
                                {{ $store->product_items ?? '-' }}
                            </td>
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                @if($store->booth)
                                    <span class="px-1.5 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded font-mono font-bold text-[11px]">
                                        {{ $store->booth->booth_code }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-[10px]">未割当</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] {{ $store->statusBadgeClass }}">
                                    {{ $store->statusLabel }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right whitespace-nowrap space-x-1">
                                <button type="button"
                                    data-action="open-edit-store-modal"
                                    data-id="{{ $store->id }}"
                                    data-name="{{ $store->store_name }}"
                                    data-rep="{{ $store->representative_name }}"
                                    data-contact="{{ $store->emergency_contact }}"
                                    data-items="{{ $store->product_items }}"
                                    data-status="{{ $store->status }}"
                                    data-notes="{{ $store->notes }}"
                                    class="px-2.5 py-1 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition">
                                    編集
                                </button>
                                <form method="POST" action="{{ route('timetables.goza-stores.destroy', [$timetable, $store]) }}" class="inline" onsubmit="return confirm('出店者「{{ $store->store_name }}」を削除しますか？');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition">
                                        削除
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                登録されている出店者データがありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($stores->hasPages())
            <div class="px-4 py-3 border-t border-slate-200 bg-slate-50/50">
                {{ $stores->links() }}
            </div>
        @endif
    </div>

    <!-- 出店者 登録・編集モーダル -->
    <div id="storeModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-5 shadow-2xl space-y-4 border border-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center space-x-1.5">
                    <span>🏮</span>
                    <span id="storeModalTitle">ござ市出店者の登録</span>
                </h3>
                <button type="button" id="storeModalCloseBtn" class="text-slate-400 hover:text-slate-600 text-lg font-bold">
                    ✕
                </button>
            </div>

            <form id="storeForm" method="POST" action="" class="space-y-3 text-xs">
                @csrf
                <input type="hidden" name="_method" id="storeFormMethod" value="POST">

                <div>
                    <label for="inputStoreName" class="block font-bold text-slate-700 mb-1">出店名・屋号 <span class="text-rose-500">*</span></label>
                    <input type="text" id="inputStoreName" name="store_name" required class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="例: 水浅青果">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="inputRepName" class="block font-bold text-slate-700 mb-1">出店代表者氏名</label>
                        <input type="text" id="inputRepName" name="representative_name" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="例: 山田 太郎">
                    </div>

                    <div>
                        <label for="inputContact" class="block font-bold text-slate-700 mb-1">緊急連絡先</label>
                        <input type="text" id="inputContact" name="emergency_contact" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="例: 090-1234-5678">
                    </div>
                </div>

                <div>
                    <label for="inputProducts" class="block font-bold text-slate-700 mb-1">主な販売品目</label>
                    <textarea id="inputProducts" name="product_items" rows="2" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="例: 焼きそば、フランクフルト、生ビール"></textarea>
                </div>

                <div>
                    <label for="selectStoreStatus" class="block font-bold text-slate-700 mb-1">出店ステータス <span class="text-rose-500">*</span></label>
                    <select id="selectStoreStatus" name="status" required class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500">
                        @foreach(\App\Models\GozaStore::STATUSES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="inputStoreNotes" class="block font-bold text-slate-700 mb-1">備考</label>
                    <textarea id="inputStoreNotes" name="notes" rows="2" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-800 focus:bg-white focus:ring-1 focus:ring-indigo-500" placeholder="注意事項や要望など"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end space-x-2">
                    <button type="button" id="storeModalCancelBtn" class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg font-bold shadow-2xs transition">
                        キャンセル
                    </button>
                    <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-2xs transition">
                        💾 登録・保存
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- モーダル操作用 Vanilla JS (外部スクリプト / CSP対応) -->
<script type="module">
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('storeModal');
        const form = document.getElementById('storeForm');
        const formMethod = document.getElementById('storeFormMethod');
        const titleSpan = document.getElementById('storeModalTitle');
        const nameInput = document.getElementById('inputStoreName');
        const repInput = document.getElementById('inputRepName');
        const contactInput = document.getElementById('inputContact');
        const productsTextarea = document.getElementById('inputProducts');
        const statusSelect = document.getElementById('selectStoreStatus');
        const notesTextarea = document.getElementById('inputStoreNotes');
        const openCreateBtn = document.getElementById('btnOpenCreateStoreModal');
        const closeBtn = document.getElementById('storeModalCloseBtn');
        const cancelBtn = document.getElementById('storeModalCancelBtn');

        const closeModal = () => {
            modal.classList.add('hidden');
        };

        closeBtn?.addEventListener('click', closeModal);
        cancelBtn?.addEventListener('click', closeModal);

        openCreateBtn?.addEventListener('click', () => {
            titleSpan.textContent = 'ござ市出店者の新規登録';
            formMethod.value = 'POST';
            form.action = '{{ route('timetables.goza-stores.store', $timetable) }}';
            nameInput.value = '';
            repInput.value = '';
            contactInput.value = '';
            productsTextarea.value = '';
            statusSelect.value = 'confirmed';
            notesTextarea.value = '';
            modal.classList.remove('hidden');
        });

        document.querySelectorAll('[data-action="open-edit-store-modal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');
                const rep = btn.getAttribute('data-rep') || '';
                const contact = btn.getAttribute('data-contact') || '';
                const items = btn.getAttribute('data-items') || '';
                const status = btn.getAttribute('data-status');
                const notes = btn.getAttribute('data-notes') || '';

                titleSpan.textContent = `ござ市出店者の編集: ${name}`;
                formMethod.value = 'PUT';
                form.action = `/timetables/{{ $timetable->id }}/goza-stores/${id}`;
                nameInput.value = name;
                repInput.value = rep;
                contactInput.value = contact;
                productsTextarea.value = items;
                statusSelect.value = status;
                notesTextarea.value = notes;
                modal.classList.remove('hidden');
            });
        });
    });
</script>
</x-layouts.app>
