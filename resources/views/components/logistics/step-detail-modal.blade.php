<!-- 個別搬出入ステップ詳細モーダル -->
<div id="plan-detail-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" data-action="close-modal"></div>

    <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden transform transition-all max-h-[calc(100vh-2.5rem)] flex flex-col">

            <!-- モーダルヘッダー (固定) -->
            <div class="shrink-0 px-5 py-3.5 bg-white border-b border-slate-100 flex items-center justify-between gap-2">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center text-sm shadow-2xs shrink-0 font-bold">
                        🚚
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                            <h3 class="text-base font-bold text-slate-900 truncate">
                                搬出入ステップ詳細
                            </h3>
                            <span id="detail_plan_equipment_name" class="px-2 py-0.5 rounded text-[11px] font-bold border bg-slate-50 text-slate-700 border-slate-200 truncate max-w-[220px]">
                            </span>
                        </div>
                    </div>
                </div>

                <button type="button" data-action="close-modal"
                    class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition text-sm leading-none cursor-pointer shrink-0"
                    title="閉じる">
                    ✕
                </button>
            </div>

            <!-- モーダルボディ (縦スクロール可能) -->
            <div class="overflow-y-auto flex-1 p-5 space-y-3.5 text-xs">
                <!-- ステップ表示名 & バッジ群 -->
                <div class="space-y-2">
                    <div class="flex items-center flex-wrap gap-1.5">
                        <span id="detail_plan_phase_badge" class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border"></span>
                        <span id="detail_plan_witness_badge" class="hidden inline-flex items-center px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 font-bold text-[10px] border border-rose-200">立会</span>
                        <span id="detail_plan_status_badge" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border"></span>
                    </div>
                    <div id="detail_plan_title" class="text-sm sm:text-base font-bold text-slate-900 bg-slate-50/60 px-3 py-2 rounded-lg border border-slate-200/80">
                    </div>
                </div>

                <!-- 種別 & 実施日時 -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <span class="block text-[11px] font-bold text-slate-500 mb-1">種別</span>
                        <div id="detail_plan_action_label" class="font-bold text-slate-800 bg-slate-50/60 px-3 py-2 rounded-lg border border-slate-200/80">
                        </div>
                    </div>
                    <div>
                        <span class="block text-[11px] font-bold text-slate-500 mb-1">実施予定日時</span>
                        <div id="detail_plan_datetime" class="font-mono font-bold text-slate-800 bg-slate-50/60 px-3 py-2 rounded-lg border border-slate-200/80">
                        </div>
                    </div>
                </div>

                <!-- 移動元 ➔ 移動先 -->
                <div>
                    <span class="block text-[11px] font-bold text-slate-500 mb-1">移動元 ➔ 移動先</span>
                    <div class="flex items-center space-x-2 flex-wrap gap-y-1 bg-slate-50/60 px-3 py-2 rounded-lg border border-slate-200/80 font-mono">
                        <span id="detail_plan_from_location" class="font-medium text-slate-700"></span>
                        <span class="text-indigo-600 font-bold px-1">➔</span>
                        <span id="detail_plan_to_location" class="font-bold text-slate-900"></span>
                    </div>
                </div>

                <!-- 作業主体 & 運搬数量 & 立会スタッフ -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <span class="block text-[11px] font-bold text-slate-500 mb-1">作業主体</span>
                        <div id="detail_plan_operator_label" class="font-bold text-slate-800 bg-slate-50/60 px-3 py-2 rounded-lg border border-slate-200/80">
                        </div>
                    </div>
                    <div>
                        <span class="block text-[11px] font-bold text-slate-500 mb-1">運搬数量</span>
                        <div id="detail_plan_quantity" class="font-mono font-bold text-slate-800 bg-slate-50/60 px-3 py-2 rounded-lg border border-slate-200/80">
                        </div>
                    </div>
                    <div>
                        <span class="block text-[11px] font-bold text-slate-500 mb-1">立会スタッフ</span>
                        <div id="detail_plan_witness_staff" class="font-bold text-slate-800 bg-slate-50/60 px-3 py-2 rounded-lg border border-slate-200/80">
                        </div>
                    </div>
                </div>

                <!-- 申し送り・注意事項メモ -->
                <div>
                    <span class="block text-[11px] font-bold text-slate-500 mb-1">条件・注意事項・申し送りメモ</span>
                    <div id="detail_plan_notes" class="min-h-[72px] p-3 bg-slate-50/60 rounded-xl border border-slate-200/80 text-slate-700 whitespace-pre-wrap leading-relaxed">
                    </div>
                </div>
            </div>

            <!-- モーダルフッター (固定) -->
            <div class="shrink-0 px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-2">
                <div>
                    @can('create', \App\Models\EquipmentLogisticsPlan::class)
                        <button type="button"
                            data-action="edit-from-detail"
                            class="inline-flex items-center space-x-1 px-3.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-xl text-xs font-bold transition cursor-pointer">
                            <span>✏️</span>
                            <span>このステップを編集</span>
                        </button>
                    @endcan
                </div>
                <button type="button" data-action="close-modal"
                    class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer">
                    閉じる
                </button>
            </div>
        </div>
    </div>
</div>

