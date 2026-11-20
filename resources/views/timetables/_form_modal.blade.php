<!-- 運行情報 登録・編集・複製モーダル (SC-TIMETABLE-FORM) -->
<div id="modal-timetable-form" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="timetable-modal-title">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity modal-backdrop"></div>
    <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
        <div class="relative flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-4rem)] w-full sm:max-w-2xl rounded-2xl bg-white text-left shadow-xl border border-slate-200 overflow-hidden my-auto">
            <form id="form-timetable-mutation" method="POST" action="{{ route('timetables.store') }}" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                @csrf
                <input type="hidden" name="_method" id="timetable-form-method" value="POST">
                <input type="hidden" name="redirect_to" id="timetable-redirect-to" value="{{ $redirectTo ?? 'index' }}">
                <input type="hidden" name="source_timetable_id" id="modal_source_timetable_id" value="">

                <!-- モーダルヘッダー (固定) -->
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70 shrink-0">
                    <div class="flex items-center space-x-2">
                        <span class="text-xl" id="timetable-modal-icon">⏱️</span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800" id="timetable-modal-title">新規運行情報登録</h3>
                            <p class="text-[11px] text-slate-500 mt-0.5" id="timetable-modal-subtitle">
                                まつり開催日の基本枠および規制時間帯を設定します。
                            </p>
                        </div>
                    </div>
                    <button type="button" class="btn-close-timetable-modal text-slate-400 hover:text-slate-600 transition cursor-pointer p-1 rounded-lg">
                        <span class="text-base font-bold">✕</span>
                    </button>
                </div>

                <!-- モーダル本文 (スクロール可能) -->
                <div class="p-4 sm:p-6 space-y-5 text-xs flex-1 min-h-0 overflow-y-auto">

                    <!-- 1. 過去運行からの複製セクション (複製時のみ表示) -->
                    @if(!($isEditOnly ?? false))
                    <div id="section-clone-options" class="hidden p-4 bg-indigo-50/60 border border-indigo-200 rounded-xl space-y-3">
                        <div>
                            <h4 class="text-xs font-bold text-indigo-950 flex items-center space-x-1.5">
                                <span>📋</span>
                                <span>複製元の運行情報</span>
                            </h4>
                            <!-- 確定した複製元の情報カード -->
                            <div id="clone-source-info" class="mt-2 p-2.5 bg-white rounded-lg border border-indigo-200/80">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                    <span id="clone-source-name" class="font-bold text-slate-900 text-xs">—</span>
                                    <span id="clone-source-date" class="font-mono text-[11px] text-indigo-800 bg-indigo-50 px-2 py-0.5 rounded font-bold shrink-0 self-start sm:self-auto">—</span>
                                </div>
                            </div>
                        </div>

                        <!-- 引き継ぎ対象チェックボックス群 -->
                        <div id="clone-options-detail" class="pt-2 border-t border-indigo-200/80 space-y-2.5">
                            <div class="flex flex-wrap items-center justify-between gap-1">
                                <span class="text-xs font-bold text-indigo-950">引き継ぎ対象データを選択</span>
                                <span class="text-[10px] text-indigo-800 bg-white/80 px-2 py-0.5 rounded border border-indigo-200">
                                    ※ ステータス等は初期化され、日付は新開催日に合わせて自動シフトされます
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                <!-- 担当班 -->
                                <label data-clone-option-item class="flex items-start space-x-2 p-2 bg-white rounded-lg border border-indigo-200/80 cursor-pointer select-none">
                                    <input type="hidden" name="copy_lanes" value="0">
                                    <input type="checkbox" id="modal_copy_lanes" name="copy_lanes" value="1" checked
                                        class="w-4 h-4 mt-0.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-slate-900 flex items-center justify-between">
                                            <span>🏷️ 担当班（レーン）</span>
                                            <span id="badge-clone-lanes-count" class="font-mono text-[10px] text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded">0件</span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 mt-0.5">班名・色・アイコン・表示順</p>
                                    </div>
                                </label>

                                <!-- 班長・固定スタッフ -->
                                <label data-clone-option-item class="flex items-start space-x-2 p-2 bg-white rounded-lg border border-indigo-200/80 cursor-pointer select-none">
                                    <input type="hidden" name="copy_lane_staff" value="0">
                                    <input type="checkbox" id="modal_copy_lane_staff" name="copy_lane_staff" value="1" checked
                                        class="w-4 h-4 mt-0.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-slate-900">👑 班長・固定スタッフ</div>
                                        <p class="text-[10px] text-slate-500 mt-0.5">専属メンバー編成を引き継ぐ</p>
                                    </div>
                                </label>

                                <!-- 作業タスク -->
                                <label data-clone-option-item class="flex items-start space-x-2 p-2 bg-white rounded-lg border border-indigo-200/80 cursor-pointer select-none">
                                    <input type="hidden" name="copy_tasks" value="0">
                                    <input type="checkbox" id="modal_copy_tasks" name="copy_tasks" value="1" checked
                                        class="w-4 h-4 mt-0.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-slate-900 flex items-center justify-between">
                                            <span>📝 作業タスク</span>
                                            <span id="badge-clone-tasks-count" class="font-mono text-[10px] text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded">0件</span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 mt-0.5">時間枠・必要人数（未着手に初期化）</p>
                                    </div>
                                </label>

                                <!-- タスクアサイン -->
                                <label data-clone-option-item class="flex items-start space-x-2 p-2 bg-white rounded-lg border border-indigo-200/80 cursor-pointer select-none">
                                    <input type="hidden" name="copy_task_assignments" value="0">
                                    <input type="checkbox" id="modal_copy_task_assignments" name="copy_task_assignments" value="1"
                                        class="w-4 h-4 mt-0.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-slate-900">👥 タスク割当人員</div>
                                        <p class="text-[10px] text-slate-500 mt-0.5">人員の割当を引き継ぐ（欠席除外）</p>
                                    </div>
                                </label>

                                <!-- 拠点・スケジュール -->
                                <label data-clone-option-item class="flex items-start space-x-2 p-2 bg-white rounded-lg border border-indigo-200/80 cursor-pointer select-none">
                                    <input type="hidden" name="copy_locations" value="0">
                                    <input type="checkbox" id="modal_copy_locations" name="copy_locations" value="1" checked
                                        class="w-4 h-4 mt-0.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-slate-900 flex items-center justify-between">
                                            <span>📍 拠点スケジュール</span>
                                            <span id="badge-clone-locations-count" class="font-mono text-[10px] text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded">0件</span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 mt-0.5">日付を新開催日に自動シフト</p>
                                    </div>
                                </label>

                                <!-- 搬出入計画 -->
                                <label data-clone-option-item class="flex items-start space-x-2 p-2 bg-white rounded-lg border border-indigo-200/80 cursor-pointer select-none">
                                    <input type="hidden" name="copy_logistics_plans" value="0">
                                    <input type="checkbox" id="modal_copy_logistics_plans" name="copy_logistics_plans" value="1" checked
                                        class="w-4 h-4 mt-0.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-slate-900 flex items-center justify-between">
                                            <span>🚚 資材搬出入計画</span>
                                            <span id="badge-clone-logistics-count" class="font-mono text-[10px] text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded">0件</span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 mt-0.5">日付を自動シフト（計画中に初期化）</p>
                                    </div>
                                </label>

                                <!-- 現場配置＆白地図 -->
                                <label data-clone-option-item class="sm:col-span-2 flex items-start space-x-2 p-2 bg-white rounded-lg border border-indigo-200/80 cursor-pointer select-none">
                                    <input type="hidden" name="copy_site_layouts" value="0">
                                    <input type="checkbox" id="modal_copy_site_layouts" name="copy_site_layouts" value="1" checked
                                        class="w-4 h-4 mt-0.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-slate-900 flex items-center justify-between">
                                            <span>🗺️ 現場配置計画＆白地図配置</span>
                                            <span id="badge-clone-layouts-count" class="font-mono text-[10px] text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded">0件</span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 mt-0.5">エリア・ブース・設備・CAD図面配置を引き継ぐ</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- 2. 基本情報 (開催日・ステータス) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <!-- 開催日 -->
                        <div>
                            <label for="modal_target_date" class="block font-bold text-slate-700 mb-1">
                                開催日 <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="modal_target_date" name="target_date" required
                                value="{{ now()->toDateString() }}"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-indigo-500 font-mono">
                        </div>

                        <!-- ステータス -->
                        <div>
                            <label for="modal_status" class="block font-bold text-slate-700 mb-1">
                                ステータス <span class="text-red-500">*</span>
                            </label>
                            <select id="modal_status" name="status" required
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-indigo-500">
                                @foreach($statuses ?? \App\Models\Timetable::STATUSES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- 3. 運行日名称 -->
                    <div>
                        <label for="modal_name" class="block font-bold text-slate-700 mb-1">
                            運行日名称 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="modal_name" name="name" required
                            placeholder="例: 第35回保土ヶ谷宿場まつり(1日目)"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- 4. 運行時間枠 -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label for="modal_start_time" class="block font-bold text-slate-700 mb-1">
                                運行開始予定時刻 <span class="text-red-500">*</span>
                            </label>
                            <input type="time" id="modal_start_time" name="start_time" required value="08:00"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-indigo-500 font-mono">
                        </div>

                        <div>
                            <label for="modal_end_time" class="block font-bold text-slate-700 mb-1">
                                運行終了予定時刻 <span class="text-red-500">*</span>
                            </label>
                            <input type="time" id="modal_end_time" name="end_time" required value="18:00"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-indigo-500 font-mono">
                        </div>
                    </div>

                    <!-- 5. 概要・全体注意事項 -->
                    <div>
                        <label for="modal_description" class="block font-bold text-slate-700 mb-1">
                            概要・全体注意事項
                        </label>
                        <textarea id="modal_description" name="description" rows="2"
                            placeholder="交通規制の時間帯や各班の全体連絡事項など"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-indigo-500 leading-relaxed"></textarea>
                    </div>

                    <!-- 6. 運行状況・時間帯指定（道路・まつり） -->
                    @include('timetables._periods_form', ['timetable' => null])

                    <!-- 7. 白紙作成時の初期班自動生成オプション (新規時のみ表示) -->
                    <div id="section-default-lanes" class="p-3 bg-indigo-50/70 border border-indigo-200 rounded-xl">
                        <label class="flex items-start space-x-2.5 cursor-pointer select-none">
                            <input type="hidden" name="create_default_lanes" value="0">
                            <input type="checkbox" id="modal_create_default_lanes" name="create_default_lanes" value="1" checked
                                class="w-4 h-4 mt-0.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <div>
                                <span class="text-xs font-bold text-indigo-950">
                                    🏷️ まつり標準10班（カラーコーン・給水・衛生・巡回・キッズ村など）を自動生成する（推奨）
                                </span>
                                <p class="text-[10px] text-slate-500 mt-0.5">
                                    標準の担当班が自動生成され、登録完了後すぐに各班の作業タスクや配置を管理できます。
                                </p>
                            </div>
                        </label>
                    </div>

                </div>

                <!-- モーダルフッター (固定) -->
                <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-end space-x-2 shrink-0">
                    <button type="button" class="btn-close-timetable-modal px-3.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-bold transition cursor-pointer shadow-2xs">
                        キャンセル
                    </button>
                    <button type="submit" id="btn-save-timetable"
                        class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition cursor-pointer shadow-2xs flex items-center space-x-1">
                        <span id="btn-save-icon">💾</span>
                        <span id="btn-save-label">登録する</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
