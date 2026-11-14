<x-layouts.app :title="$timetable->name . ' 現場設備一覧'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ページヘッダー -->
    <x-page-header
        title="現場設備・インフラ一覧"
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
            <span class="text-slate-700 font-bold truncate">🚰 現場設備一覧</span>
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

    <!-- 絞り込みタブ -->
    <div class="bg-white border border-slate-200 rounded-2xl p-2.5 shadow-2xs flex flex-wrap items-center gap-1.5 text-xs">
        <a href="{{ route('timetables.layouts.facilities', $timetable) }}"
            class="px-3 py-1 rounded-lg font-bold transition {{ empty($filters['type']) ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100' }}">
            すべて ({{ $map->facilities->count() }})
        </a>
        @foreach(\App\Enums\FacilityType::cases() as $case)
            @php $cnt = $map->facilities->filter(fn($f) => $f->facility_type === $case)->count(); @endphp
            @if($cnt > 0)
            <a href="{{ route('timetables.layouts.facilities', array_merge(['timetable' => $timetable], ['type' => $case->value])) }}"
                class="px-2.5 py-1 rounded-lg font-bold transition flex items-center space-x-1 {{ ($filters['type'] ?? '') === $case->value ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <span>{{ $case->icon() }}</span>
                <span>{{ $case->label() }}</span>
                <span class="text-[10px] opacity-75 font-mono">({{ $cnt }})</span>
            </a>
            @endif
        @endforeach
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
                        <tr class="hover:bg-slate-50/80 transition">
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
                                    class="px-2.5 py-1 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition">
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
                    <span>現場設備の編集: <span id="modalFacilityCode" class="font-mono text-indigo-600"></span></span>
                </h3>
                <button type="button" id="modalFacCloseBtn" class="text-slate-400 hover:text-slate-600 text-lg font-bold">
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
                    <button type="button" id="modalFacCancelBtn" class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg font-bold shadow-2xs transition">
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
