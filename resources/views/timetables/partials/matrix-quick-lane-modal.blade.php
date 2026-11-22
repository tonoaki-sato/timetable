<!-- 5. 担当班クイック追加モーダル（Vanilla JS） -->
<div id="laneModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="lane-modal-title">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-md w-full overflow-hidden animate-in fade-in zoom-in duration-150">
        <!-- モーダルヘッダー -->
        <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="text-lg">🏷️</span>
                <h3 id="lane-modal-title" class="font-bold text-sm">新規担当班を追加</h3>
            </div>
            <button type="button" data-action="close-lane-modal"
                class="text-slate-400 hover:text-white text-lg font-bold transition">
                ✕
            </button>
        </div>

        <!-- フォーム -->
        <form action="{{ route('timetables.lanes.store', $selectedTimetable) }}" method="POST" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="redirect_to" value="matrix">

            @if($availableMasterLanes->isEmpty())
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 space-y-2">
                <p class="font-bold">追加可能な有効担当班マスターがありません。</p>
                <p>すべての有効な担当班がこの運行日にすでに追加されているか、担当班マスターが未登録です。</p>
                <div>
                    <a href="{{ route('lanes.index') }}" class="text-indigo-700 font-bold underline hover:text-indigo-900">
                        🏷️ 担当班マスター管理へ →
                    </a>
                </div>
            </div>
            @else
            <!-- 担当班マスター選択 -->
            <div>
                <label for="modal_lane_id" class="block text-xs font-bold text-slate-700 mb-1">
                    追加する担当班（マスターから選択） <span class="text-red-600">*</span>
                </label>
                <select id="modal_lane_id" name="lane_id" required
                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- 担当班を選択してください --</option>
                    @foreach($availableMasterLanes as $master)
                    <option value="{{ $master->id }}">{{ $master->presenter()->iconLabel() }} {{ $master->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 主担当スタッフ（現場リーダー） -->
            <div>
                <label for="modal_lane_lead" class="block text-xs font-bold text-slate-700 mb-1">
                    主担当スタッフ（後から設定・変更可能）
                </label>
                <select id="modal_lane_lead" name="lead_staff_id"
                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- 未設定（後で割り当てる） --</option>
                    @foreach($activeStaff as $st)
                    <option value="{{ $st->id }}">{{ $st->name }} {{ $st->affiliation ? "({$st->affiliation})" : '' }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 当日運用メモ -->
            <div>
                <label for="modal_lane_notes" class="block text-xs font-bold text-slate-700 mb-1">
                    当日運用メモ（任意）
                </label>
                <textarea id="modal_lane_notes" name="notes" rows="2"
                    placeholder="この運行日特有の連絡事項・注意点など"
                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>
            @endif

            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                <button type="button" data-action="close-lane-modal"
                    class="px-4 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-bold transition">
                    キャンセル
                </button>
                <button type="submit" {{ $availableMasterLanes->isEmpty() ? 'disabled' : '' }}
                    class="px-5 py-2 rounded-xl text-xs font-bold shadow-sm transition {{ $availableMasterLanes->isEmpty() ? 'bg-slate-200 text-slate-400 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer' }}">
                    担当班を追加して反映
                </button>
            </div>
        </form>
    </div>
</div>
