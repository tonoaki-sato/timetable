<!-- 2. 担当班を追加モーダル（単一指定 / マスターから一括追加タブ） -->
<x-ui.modal id="modal-tlane-add" title="この運行日に担当班を追加" icon="➕" max-width="xl">
    @if($availableMasterLanes->isEmpty())
        <div class="space-y-4 text-xs">
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 space-y-1.5">
                <div class="font-bold">追加可能な有効担当班マスターがありません。</div>
                <p class="leading-relaxed">
                    すべての有効な担当班マスターがこの運行日にすでに追加されているか、マスターが未登録です。新しい班が必要な場合は「担当班マスター管理」から登録してください。
                </p>
            </div>
            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <a href="{{ route('lanes.index') }}" class="text-indigo-600 hover:underline font-bold">
                    ⚙️ 担当班マスター管理へ →
                </a>
                <x-ui.button variant="secondary" data-close-target="#modal-tlane-add">閉じる</x-ui.button>
            </div>
        </div>
    @else
        <div class="space-y-4 text-xs">
            <div class="flex border-b border-slate-200 gap-2">
                <button type="button" data-add-tab="single"
                    class="tlane-add-tab-btn px-3 py-2 font-bold border-b-2 border-indigo-600 text-indigo-700 cursor-pointer">
                    1班ずつ追加（班長・固定スタッフ指定）
                </button>
                <button type="button" data-add-tab="bulk"
                    class="tlane-add-tab-btn px-3 py-2 font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-700 cursor-pointer">
                    マスターから一括追加（{{ $availableMasterLanes->count() }}班選択可）
                </button>
            </div>

            <!-- Tab 1: 単一追加フォーム -->
            <form id="tlane-add-single-panel" action="{{ route('timetables.lanes.store', $timetable) }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="add-lane-id" class="block font-bold text-slate-700 mb-1">
                        担当班マスターを選択 <span class="text-red-600">*</span>
                    </label>
                    <select id="add-lane-id" name="lane_id" required
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- 担当班を選択してください --</option>
                        @foreach($availableMasterLanes as $master)
                            <option value="{{ $master->id }}">{{ $master->presenter()->iconLabel() }} {{ $master->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="add-lead-staff-id" class="block font-bold text-slate-700 mb-1">
                        👑 当日の班長（後から設定・変更可能）
                    </label>
                    <select id="add-lead-staff-id" name="lead_staff_id"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- 未設定 --</option>
                        @foreach($activeStaff as $st)
                            <option value="{{ $st->id }}">{{ $st->name }} {{ $st->affiliation ? "({$st->affiliation})" : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <div class="font-bold text-slate-700 mb-1">📌 固定スタッフ（この日の班タスクに基本自動配置）</div>
                    <div class="max-h-40 overflow-y-auto border border-slate-200 rounded-xl p-2 grid grid-cols-1 sm:grid-cols-2 gap-1.5 bg-slate-50/50">
                        @foreach($activeStaff as $st)
                            <label class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-white cursor-pointer">
                                <input type="checkbox" name="fixed_staff_ids[]" value="{{ $st->id }}"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5">
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
                    <label for="add-notes" class="block font-bold text-slate-700 mb-1">当日運用メモ</label>
                    <textarea id="add-notes" name="notes" rows="2" maxlength="2000"
                        placeholder="当日の連絡事項・集合場所・無線チャンネルなど"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <x-ui.button variant="secondary" data-close-target="#modal-tlane-add">キャンセル</x-ui.button>
                    <x-ui.button type="submit" variant="primary">この担当班を追加</x-ui.button>
                </div>
            </form>

            <!-- Tab 2: マスターから一括追加フォーム -->
            <form id="tlane-add-bulk-panel" action="{{ route('timetables.lanes.bulk-store', $timetable) }}" method="POST" class="hidden space-y-4">
                @csrf

                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-700">追加する担当班マスターを選択</span>
                    <div class="space-x-2">
                        <button type="button" id="btn-bulk-select-all" class="text-indigo-600 hover:underline font-bold cursor-pointer">全選択</button>
                        <button type="button" id="btn-bulk-clear-all" class="text-slate-500 hover:underline font-bold cursor-pointer">全解除</button>
                    </div>
                </div>

                <div class="max-h-64 overflow-y-auto border border-slate-200 rounded-xl p-2 space-y-1.5 bg-slate-50/50">
                    @foreach($availableMasterLanes as $master)
                        <label class="flex items-center justify-between p-2 rounded-lg bg-white border border-slate-200 hover:border-indigo-300 cursor-pointer">
                            <div class="flex items-center space-x-2 min-w-0">
                                <input type="checkbox" name="lane_ids[]" value="{{ $master->id }}"
                                    class="tlane-bulk-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 shrink-0">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border shrink-0 {{ $master->presenter()->colorClasses()['badge'] }}">
                                    <span class="mr-1">{{ $master->presenter()->iconLabel() }}</span>
                                    <span>{{ $master->name }}</span>
                                </span>
                            </div>
                            @if($master->description)
                                <span class="text-[11px] text-slate-400 truncate max-w-xs ml-2">{{ $master->description }}</span>
                            @endif
                        </label>
                    @endforeach
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <x-ui.button variant="secondary" data-close-target="#modal-tlane-add">キャンセル</x-ui.button>
                    <x-ui.button type="submit" variant="primary">選択した担当班を一括追加</x-ui.button>
                </div>
            </form>
        </div>
    @endif
</x-ui.modal>
