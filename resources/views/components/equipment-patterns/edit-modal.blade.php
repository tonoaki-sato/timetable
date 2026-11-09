@props(['locations' => collect()])

<!-- 搬出入パターン編集モーダル (独立コンポーネント) -->
<div id="pattern-edit-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" data-action="close-modal"></div>

    <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
        <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden transform transition-all max-h-[calc(100vh-2.5rem)] flex flex-col">

            <!-- モーダルヘッダー (固定) -->
            <div class="shrink-0 px-5 py-3.5 bg-white border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center text-sm shadow-2xs shrink-0 font-bold">
                        ✏️
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-slate-900 truncate">
                            搬出入パターンの編集
                        </h3>
                    </div>
                </div>

                <button type="button" data-action="close-modal"
                    class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition text-sm leading-none cursor-pointer shrink-0"
                    title="閉じる">
                    ✕
                </button>
            </div>

            <!-- 入力フォーム (縦スクロール可能ボディ) -->
            <form id="pattern-edit-form" action="" method="POST" class="flex-1 flex flex-col min-h-0">
                @csrf
                @method('PUT')

                <div class="overflow-y-auto flex-1 p-5 space-y-4">
                    <!-- 基本属性: パターン名 & 主な対象区分 -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label for="edit_pattern_name" class="block text-[11px] font-bold text-slate-700 mb-1">
                                パターン名 <span class="text-rose-500">*</span>
                            </label>
                            <input type="text"
                                id="edit_pattern_name"
                                name="name"
                                required
                                placeholder="例: カラーコーン標準（前日配備・当日回収）"
                                class="w-full px-3 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        </div>

                        <div>
                            <label for="edit_pattern_target_type" class="block text-[11px] font-bold text-slate-700 mb-1">
                                主な対象区分 <span class="text-rose-500">*</span>
                            </label>
                            <select id="edit_pattern_target_type"
                                name="target_type"
                                required
                                class="w-full px-2.5 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition cursor-pointer">
                                @foreach(\App\Models\EquipmentLogisticsPattern::TARGET_TYPES as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- パターンの説明 -->
                    <div>
                        <label for="edit_pattern_description" class="block text-[11px] font-bold text-slate-700 mb-1">
                            パターンの説明・適用目安（任意）
                        </label>
                        <input type="text"
                            id="edit_pattern_description"
                            name="description"
                            placeholder="例: 番所地下倉庫から前日搬出、交通規制開始前に配備し終了直後に撤収"
                            class="w-full px-3 py-2 bg-slate-50/60 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    </div>

                    <!-- 構成ステップ一覧ヘッダー -->
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-800 flex items-center space-x-1.5">
                                <span>📋</span>
                                <span>構成ステップ一覧</span>
                                <span class="text-rose-500">*</span>
                            </label>
                            <button type="button"
                                data-action="add-pattern-step"
                                data-container="#edit-pattern-steps-container"
                                data-template="#tpl-pattern-step-row"
                                class="inline-flex items-center space-x-1 px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition cursor-pointer">
                                <span>➕</span>
                                <span>ステップを追加</span>
                            </button>
                        </div>

                        <!-- ステップ行コンテナ -->
                        <div id="edit-pattern-steps-container" class="space-y-3"></div>
                    </div>
                </div>

                <!-- モーダルフッター (固定) -->
                <div class="shrink-0 px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-2">
                    <button type="button"
                        data-action="close-modal"
                        class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer">
                        キャンセル
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                        変更を保存する
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

