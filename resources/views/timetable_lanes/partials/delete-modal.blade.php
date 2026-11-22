<!-- 4. 解除・削除モーダル（タスク移行 / 一括削除対応） -->
<x-ui.modal id="delete-migration-modal" title="担当班の編成解除・削除" icon="🗑️" max-width="lg">
    <form id="tlane-delete-form" action="" method="POST" class="space-y-4 text-xs">
        @csrf
        @method('DELETE')

        <p class="text-slate-700 leading-relaxed">
            「{{ $timetable->name }}」の編成から「<strong id="delete-tlane-name" class="text-slate-900"></strong>」を解除します。
        </p>

        <div id="delete-no-tasks-msg" class="hidden p-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-600">
            この班に登録されている作業タスクはありません。そのまま解除できます。
        </div>

        <div id="delete-with-tasks-options" class="hidden space-y-3">
            <div class="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl font-bold">
                ⚠️ この班には <span id="delete-tlane-tasks-count" class="font-mono">0</span> 件の作業タスクが登録されています。タスクの扱いを選択してください。
            </div>

            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                <label for="migrate-to-timetable-lane-id" class="block font-bold text-slate-700">
                    方法A: 登録タスクを他の担当班へ移行して解除する
                </label>
                <select id="migrate-to-timetable-lane-id" name="migrate_to_timetable_lane_id"
                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- 移行先の担当班を選択 --</option>
                    @foreach($timetableLanes as $otherTl)
                        <option value="{{ $otherTl->id }}">{{ $otherTl->presenter()->iconLabel() }} {{ $otherTl->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="p-3 bg-red-50/70 border border-red-200 rounded-xl">
                <label class="flex items-start space-x-2 cursor-pointer">
                    <input type="checkbox" id="cascade-delete-tasks" name="cascade_delete_tasks" value="1"
                        class="mt-0.5 rounded border-red-300 text-red-600 focus:ring-red-500 h-4 w-4">
                    <span class="text-red-800">
                        <strong>方法B: タスクもまとめて完全に削除する</strong>
                        <span class="block text-[11px] text-red-600 mt-0.5">この班の作業タスクとスタッフ配置データもすべて削除されます。</span>
                    </span>
                </label>
            </div>

            <div id="delete-modal-error" class="hidden p-2.5 bg-red-50 border border-red-200 text-red-700 rounded-xl font-bold"></div>
        </div>

        <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
            <x-ui.button variant="secondary" data-close-target="#delete-migration-modal">キャンセル</x-ui.button>
            <button type="submit"
                class="inline-flex items-center space-x-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold shadow-xs transition cursor-pointer">
                <span>🗑️</span>
                <span>編成から解除する</span>
            </button>
        </div>
    </form>
</x-ui.modal>
