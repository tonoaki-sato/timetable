<!-- 組織詳細モーダル（閲覧専用・スクロールフリー設計） -->
<div id="organization-detail-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4" aria-modal="true" role="dialog">
    <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        <!-- ヘッダー -->
        <div class="flex items-center justify-between px-5 py-3.5 bg-slate-50 border-b border-slate-200">
            <div class="flex items-center space-x-2 min-w-0">
                <span class="text-xl">🏛️</span>
                <h3 class="text-base font-bold text-slate-900 truncate" id="detail-modal-title">組織の詳細</h3>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 transition cursor-pointer" data-close-detail-modal aria-label="閉じる">
                <span class="text-lg leading-none">✕</span>
            </button>
        </div>

        <!-- 本文 (コンパクトな2列カードレイアウト) -->
        <div class="p-5 space-y-4 text-xs sm:text-sm">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 bg-slate-50/70 rounded-xl border border-slate-100">
                <div>
                    <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">名前</span>
                    <span class="font-bold text-slate-900 text-sm" id="detail-modal-name">-</span>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">親組織</span>
                    <span class="text-slate-700 font-medium" id="detail-modal-parent">なし（最上位）</span>
                </div>
            </div>

            <div>
                <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">説明</span>
                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-100 text-slate-800 leading-relaxed font-medium" id="detail-modal-short-description">-</div>
            </div>

            <div>
                <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">詳細説明</span>
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 text-slate-700 whitespace-pre-wrap leading-relaxed text-xs" id="detail-modal-description">-</div>
            </div>

            <div>
                <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">担当</span>
                <div class="flex flex-wrap gap-1.5" id="detail-modal-staff-container">
                    <span class="text-slate-400 italic text-xs">担当スタッフは設定されていません</span>
                </div>
            </div>
        </div>

        <!-- フッター (閉じるボタンのみ) -->
        <div class="flex items-center justify-end px-5 py-3 bg-slate-50 border-t border-slate-200">
            <button type="button" class="px-4 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl shadow-xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer" data-close-detail-modal>
                閉じる
            </button>
        </div>
    </div>
</div>

