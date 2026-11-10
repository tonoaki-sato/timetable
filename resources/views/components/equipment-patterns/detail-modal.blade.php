<!-- 搬出入パターン詳細モーダル (全ステップ一覧表示) -->
<div id="pattern-detail-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" data-action="close-modal"></div>

    <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
        <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden transform transition-all max-h-[calc(100vh-2.5rem)] flex flex-col">

            <!-- モーダルヘッダー (固定) -->
            <div class="shrink-0 px-5 py-3.5 bg-white border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center text-sm shadow-2xs shrink-0 font-bold">
                        🧩
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-slate-900 truncate flex items-center space-x-2">
                            <span>パターン詳細:</span>
                            <span id="detail_pattern_name" class="text-indigo-600"></span>
                        </h3>
                    </div>
                </div>

                <button type="button" data-action="close-modal"
                    class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition text-sm leading-none cursor-pointer shrink-0"
                    title="閉じる">
                    ✕
                </button>
            </div>

            <!-- モーダルボディ (縦スクロール可能) -->
            <div class="overflow-y-auto flex-1 p-5 space-y-4">
                <!-- 基本情報カード -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div>
                        <span class="block text-[10px] font-bold text-slate-500 mb-0.5">主な対象区分</span>
                        <span id="detail_pattern_target_type" class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold bg-white text-slate-700 border border-slate-200">
                        </span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="block text-[10px] font-bold text-slate-500 mb-0.5">パターンの説明・適用目安</span>
                        <p id="detail_pattern_description" class="text-slate-700 font-medium"></p>
                    </div>
                </div>

                <!-- 構成ステップ一覧 -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-slate-800 flex items-center space-x-1.5">
                            <span>📋</span>
                            <span>全構成ステップ一覧</span>
                            <span id="detail_steps_count" class="text-slate-400 font-mono text-[11px] font-normal"></span>
                        </h4>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full border-collapse text-left text-xs">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold select-none text-xs">
                                <tr>
                                    <th class="px-3.5 py-2.5 text-center whitespace-nowrap w-12">順序</th>
                                    <th class="px-3.5 py-2.5 whitespace-nowrap w-24">フェーズ</th>
                                    <th class="px-3.5 py-2.5 whitespace-nowrap w-28">種別</th>
                                    <th class="px-3.5 py-2.5 min-w-[160px]">ステップ表示名</th>
                                    <th class="px-3.5 py-2.5 min-w-[180px]">移動元 ➔ 移動先</th>
                                    <th class="px-3.5 py-2.5 whitespace-nowrap w-24">作業主体</th>
                                    <th class="px-3.5 py-2.5 text-center whitespace-nowrap w-14">立会</th>
                                </tr>
                            </thead>
                            <tbody id="detail_steps_tbody" class="divide-y divide-slate-100 bg-white">
                                <!-- JSにより動的生成 -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- モーダルフッター (固定) -->
            <div class="shrink-0 px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-end">
                <button type="button" data-action="close-modal"
                    class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer">
                    閉じる
                </button>
            </div>
        </div>
    </div>
</div>

