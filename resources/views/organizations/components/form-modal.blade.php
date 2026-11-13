<!-- 組織登録・編集モーダル（左右2カラム・スタッフDnD・スクロールフリー設計） -->
<div id="organization-form-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4" aria-modal="true" role="dialog">
    <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        <!-- フォームタグ -->
        <form id="organization-form" method="POST" action="{{ route('organizations.store') }}">
            @csrf
            <input type="hidden" name="_method" id="form-method-override" value="POST">
            <input type="hidden" name="id" id="form-unit-id" value="">

            <!-- モーダルヘッダー -->
            <div class="flex items-center justify-between px-5 py-3.5 bg-slate-50 border-b border-slate-200">
                <div class="flex items-center space-x-2 min-w-0">
                    <span class="text-xl">🏛️</span>
                    <h3 class="text-base font-bold text-slate-900 truncate" id="form-modal-title">組織・係の登録</h3>
                </div>
                <button type="button" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 transition cursor-pointer" data-close-form-modal aria-label="閉じる">
                    <span class="text-lg leading-none">✕</span>
                </button>
            </div>

            <!-- エラー表示枠 -->
            <div id="form-error-alert" class="hidden mx-5 mt-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs"></div>

            <!-- モーダル本文 (左右2カラムレイアウト) -->
            <div class="p-5 grid grid-cols-1 md:grid-cols-12 gap-5 text-xs sm:text-sm">
                <!-- 左カラム: 基本情報 (7カラム) -->
                <div class="md:col-span-7 space-y-3.5">
                    <div>
                        <label for="form-parent-id" class="block text-xs font-bold text-slate-700 mb-1">
                            親組織
                        </label>
                        <select id="form-parent-id" name="parent_id" class="w-full px-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-300 bg-white focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="">なし（最上位組織）</option>
                            @foreach($parentOptions as $option)
                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-400 mt-0.5">最上位（部門・役職など）にする場合は「なし」を選択</p>
                    </div>

                    <div>
                        <label for="form-name" class="block text-xs font-bold text-slate-700 mb-1">
                            名前 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="form-name" name="name" required placeholder="例: 設営・設備・企画" class="w-full px-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>

                    <div>
                        <label for="form-short-description" class="block text-xs font-bold text-slate-700 mb-1">
                            説明
                        </label>
                        <input type="text" id="form-short-description" name="short_description" placeholder="例: overcom（施工委託）" class="w-full px-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <p class="text-[11px] text-slate-400 mt-0.5">一覧画面に表示される短めの説明です</p>
                    </div>

                    <div>
                        <label for="form-description" class="block text-xs font-bold text-slate-700 mb-1">
                            詳細説明
                        </label>
                        <textarea id="form-description" name="description" rows="3" placeholder="所管業務の詳細、協力団体、特記事項など" class="w-full px-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition resize-none"></textarea>
                    </div>
                </div>

                <!-- 右カラム: 担当スタッフ（DnD並び替え枠） (5カラム) -->
                <div class="md:col-span-5 flex flex-col min-h-0 bg-slate-50/80 p-3.5 rounded-2xl border border-slate-200">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold text-slate-700">
                            担当スタッフ
                        </label>
                        <span class="text-[10px] text-slate-400">⠿ を掴んで並び替え</span>
                    </div>

                    <!-- スタッフリスト（DnDコンテナ） -->
                    <div id="form-staff-list" class="flex-1 space-y-2 min-h-36 max-h-56 overflow-y-auto pr-1">
                        <!-- スタッフ行テンプレート（JSで動的追加） -->
                    </div>

                    <div class="mt-2.5 pt-2 border-t border-slate-200">
                        <button type="button" id="btn-add-staff-row" class="w-full py-1.5 px-2.5 text-xs font-bold text-indigo-700 bg-indigo-50/80 hover:bg-indigo-100 rounded-xl border border-indigo-200 transition flex items-center justify-center space-x-1 cursor-pointer">
                            <span>➕</span>
                            <span>スタッフを追加</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- モーダルフッター -->
            <div class="flex items-center justify-end space-x-2 px-5 py-3.5 bg-slate-50 border-t border-slate-200">
                <button type="button" class="px-4 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl shadow-xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer" data-close-form-modal>
                    キャンセル
                </button>
                <button type="submit" id="btn-submit-form" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-xs transition flex items-center space-x-1 cursor-pointer">
                    <span id="btn-submit-text">保存する</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- スタッフ選択用オプションテンプレート（隠しDOM） -->
<template id="staff-options-template">
    <option value="">スタッフを選択...</option>
    @foreach($availableStaff as $staff)
        <option value="{{ $staff->id }}">{{ $staff->name }} ({{ $staff->kana ?? '' }})</option>
    @endforeach
</template>

