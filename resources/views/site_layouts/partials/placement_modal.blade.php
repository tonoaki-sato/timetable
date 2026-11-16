{{-- オブジェクト設定モーダル (Phase 4) --}}
<div id="site-map-placement-modal"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs hidden transition-opacity duration-200">
    <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
        <!-- モーダルヘッダー -->
        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center space-x-2.5">
                <div id="modal-icon-preview-box" class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center p-1 shadow-2xs">
                    <img id="modal-icon-img" src="" alt="" class="max-w-full max-h-full object-contain">
                </div>
                <div>
                    <h3 id="modal-title" class="text-sm font-bold text-slate-800">
                        オブジェクト設定
                    </h3>
                    <p id="modal-subtitle" class="text-[11px] text-slate-500 font-mono">
                        寸法規格: --
                    </p>
                </div>
            </div>
            <button type="button" id="btn-modal-close"
                class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200/60 transition cursor-pointer"
                title="閉じる">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- モーダルボディ（入力フォーム） -->
        <form id="placement-modal-form" class="p-5 space-y-4">
            <input type="hidden" id="modal-placement-id" value="">

            <!-- 1. 中央表示テキスト -->
            <div>
                <label for="modal-display-text" class="block text-xs font-bold text-slate-700 mb-1">
                    表示テキスト（コマ番号・名称）
                </label>
                <div class="relative">
                    <input type="text" id="modal-display-text"
                        maxlength="50"
                        class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:bg-white transition"
                        placeholder="例: A01, B22, 本陣, 救護 等">
                </div>
                <p class="text-[10px] text-slate-400 mt-1">※ 白地図上のアイコン中央に描画される文字列です</p>
            </div>

            <!-- 2. カラーテーマ選択 (5色) -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                    <span>カラーテーマ（枠線・文字色）</span>
                    <span id="modal-color-status-badge" class="text-[10px] text-slate-500 font-normal"></span>
                </label>
                <div id="modal-color-palette" class="grid grid-cols-5 gap-2">
                    <label class="color-option flex flex-col items-center p-2 rounded-lg border border-slate-200 hover:border-red-400 cursor-pointer transition">
                        <input type="radio" name="modal_color" value="red" class="sr-only">
                        <span class="w-6 h-6 rounded-full bg-red-600 border border-red-700 shadow-2xs mb-1"></span>
                        <span class="text-[10px] font-bold text-slate-700">赤 (Red)</span>
                    </label>
                    <label class="color-option flex flex-col items-center p-2 rounded-lg border border-slate-200 hover:border-emerald-400 cursor-pointer transition">
                        <input type="radio" name="modal_color" value="green" class="sr-only">
                        <span class="w-6 h-6 rounded-full bg-emerald-600 border border-emerald-700 shadow-2xs mb-1"></span>
                        <span class="text-[10px] font-bold text-slate-700">緑 (Green)</span>
                    </label>
                    <label class="color-option flex flex-col items-center p-2 rounded-lg border border-slate-200 hover:border-slate-800 cursor-pointer transition">
                        <input type="radio" name="modal_color" value="black" class="sr-only">
                        <span class="w-6 h-6 rounded-full bg-slate-900 border border-black shadow-2xs mb-1"></span>
                        <span class="text-[10px] font-bold text-slate-700">黒 (Black)</span>
                    </label>
                    <label class="color-option flex flex-col items-center p-2 rounded-lg border border-slate-200 hover:border-blue-400 cursor-pointer transition">
                        <input type="radio" name="modal_color" value="blue" class="sr-only">
                        <span class="w-6 h-6 rounded-full bg-blue-600 border border-blue-700 shadow-2xs mb-1"></span>
                        <span class="text-[10px] font-bold text-slate-700">青 (Blue)</span>
                    </label>
                    <label class="color-option flex flex-col items-center p-2 rounded-lg border border-slate-200 hover:border-amber-400 cursor-pointer transition">
                        <input type="radio" name="modal_color" value="orange" class="sr-only">
                        <span class="w-6 h-6 rounded-full bg-amber-500 border border-amber-600 shadow-2xs mb-1"></span>
                        <span class="text-[10px] font-bold text-slate-700">橙 (Orange)</span>
                    </label>
                </div>
            </div>

            <!-- 3. 回転角度 (45度単位) -->
            <div>
                <label for="modal-rotation" class="block text-xs font-bold text-slate-700 mb-1 flex items-center justify-between">
                    <span>回転角度（45°刻み）</span>
                    <span id="modal-rotation-status-badge" class="text-[10px] text-slate-500 font-normal"></span>
                </label>
                <select id="modal-rotation"
                    class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:bg-white transition">
                    <option value="0">0°（標準向き）</option>
                    <option value="45">45°</option>
                    <option value="90">90°（直角横向き）</option>
                    <option value="135">135°</option>
                    <option value="180">180°（上下反転）</option>
                    <option value="225">225°</option>
                    <option value="270">270°（逆直角横向き）</option>
                    <option value="315">315°</option>
                </select>
            </div>

            <!-- 4. 既存ブース台帳（layout_items）との紐づけ -->
            <div>
                <label for="modal-layout-item-id" class="block text-xs font-bold text-slate-700 mb-1 flex items-center justify-between">
                    <span>出店ブース台帳との連動</span>
                    <span id="modal-item-status-badge" class="text-[10px] text-slate-500 font-normal"></span>
                </label>
                <select id="modal-layout-item-id"
                    class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:bg-white transition">
                    <option value="">（紐づけなし / 未指定）</option>
                    @foreach($layoutItems as $item)
                        <option value="{{ $item->id }}"
                            data-code="{{ $item->code }}"
                            data-name="{{ $item->name }}"
                            data-vendor="{{ $item->vendor?->name ?? '出店者未設定' }}"
                            data-fire="{{ $item->vendor?->has_fire ? '🔥火気使用' : '' }}">
                            {{ $item->code }}: {{ $item->name }} ({{ $item->vendor?->name ?? '出店者未定' }})
                        </option>
                    @endforeach
                </select>
                <p id="modal-vendor-preview" class="text-[11px] text-indigo-700 bg-indigo-50/70 p-2 rounded-lg mt-1.5 border border-indigo-100 hidden">
                    <!-- 選択された出店者詳細がここに展開されます -->
                </p>
            </div>

            <!-- 5. 備考・特記事項 -->
            <div>
                <label for="modal-notes" class="block text-xs font-bold text-slate-700 mb-1">
                    備考・申し送り事項
                </label>
                <textarea id="modal-notes" rows="2"
                    class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:bg-white transition"
                    placeholder="現場での特記事項（電源ドラム取り出し位置、消火器配置など）"></textarea>
            </div>

            <!-- モーダルフッター -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                <!-- 削除ボタン -->
                <button type="button" id="btn-modal-delete"
                    class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 cursor-pointer">
                    <span>🗑️</span>
                    <span>配置から削除</span>
                </button>

                <!-- キャンセル・保存ボタン -->
                <div class="flex items-center space-x-2">
                    <button type="button" id="btn-modal-cancel"
                        class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-bold transition cursor-pointer">
                        キャンセル
                    </button>
                    <button type="submit" id="btn-modal-save"
                        class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1 cursor-pointer">
                        <span>💾</span>
                        <span>設定を保存する</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
