<!-- 3. 担当班編成の編集モーダル -->
<x-ui.modal id="modal-tlane-edit" title="担当班編成の編集" icon="✏️" max-width="xl">
    <form id="tlane-edit-form" action="" method="POST" class="space-y-4 text-xs">
        @csrf
        @method('PUT')
        <input type="hidden" name="sync_fixed_staff" value="1">

        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span id="edit-tlane-badge" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border bg-slate-100 text-slate-800 border-slate-300">
                    <span id="edit-tlane-badge-icon" class="mr-1.5">📋</span>
                    <span id="edit-tlane-badge-name">班名</span>
                </span>
                <a href="{{ route('lanes.index') }}" class="text-[11px] text-indigo-600 hover:underline font-bold">
                    班名・表示色の変更はマスター管理へ →
                </a>
            </div>
            <div id="edit-tlane-description" class="text-[11px] text-slate-500"></div>
        </div>

        <div>
            <label for="edit-lead-staff-id" class="block font-bold text-slate-700 mb-1">
                👑 当日の班長（リーダー）
            </label>
            <select id="edit-lead-staff-id" name="lead_staff_id"
                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">-- 未設定 --</option>
                @foreach($activeStaff as $st)
                    <option value="{{ $st->id }}">{{ $st->name }} {{ $st->affiliation ? "({$st->affiliation})" : '' }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <div class="font-bold text-slate-700 mb-1">📌 固定スタッフ</div>
            <div id="edit-fixed-staff-list" class="max-h-44 overflow-y-auto border border-slate-200 rounded-xl p-2 grid grid-cols-1 sm:grid-cols-2 gap-1.5 bg-slate-50/50">
                @foreach($activeStaff as $st)
                    <label class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-white cursor-pointer">
                        <input type="checkbox" name="fixed_staff_ids[]" value="{{ $st->id }}"
                            class="edit-fixed-staff-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5">
                        <span class="truncate text-slate-700 font-medium">
                            {{ $st->name }}
                            @if($st->affiliation)
                                <span class="text-[10px] text-slate-400">({{ $st->affiliation }})</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <label for="edit-notes" class="block font-bold text-slate-700 mb-1">当日運用メモ</label>
            <textarea id="edit-notes" name="notes" rows="3" maxlength="2000"
                placeholder="当日の連絡事項・集合場所・注意点など"
                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
        </div>

        <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
            <x-ui.button variant="secondary" data-close-target="#modal-tlane-edit">キャンセル</x-ui.button>
            <x-ui.button type="submit" variant="primary">編成内容を保存</x-ui.button>
        </div>
    </form>
</x-ui.modal>
