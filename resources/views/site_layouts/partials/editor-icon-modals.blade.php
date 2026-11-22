{{-- モーダル 1: PNG画像からアイコン新規生成 --}}
<div id="modal-create-icon" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs hidden p-4" role="dialog" aria-modal="true" aria-labelledby="modal-create-icon-title">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-lg p-5 space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 id="modal-create-icon-title" class="text-sm font-bold text-slate-800 flex items-center space-x-2">
                <span>➕</span>
                <span>PNG画像からアイコン生成</span>
            </h3>
            <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-600 font-bold text-lg px-2 py-0.5 rounded-lg cursor-pointer" data-modal="modal-create-icon">✕</button>
        </div>

        <form id="form-create-icon" class="space-y-3.5 text-xs">
            <!-- ファイルD&Dアップロード領域 -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">PNG画像ファイル <span class="text-red-500">*</span></label>
                <div id="create-icon-dropzone" class="border-2 border-dashed border-slate-300 hover:border-indigo-400 rounded-xl p-4 text-center cursor-pointer bg-slate-50 hover:bg-indigo-50/40 transition">
                    <input type="file" id="create-icon-file" name="png_file" accept="image/png" class="hidden">
                    <div id="create-icon-prompt" class="space-y-1">
                        <div class="text-2xl">🖼️</div>
                        <p class="font-bold text-slate-700">PNG画像をドラッグ＆ドロップ または クリックして選択</p>
                        <p class="text-[10px] text-slate-400">透過PNG推奨 (最大 5MB)</p>
                    </div>
                    <div id="create-icon-preview-wrap" class="hidden flex flex-col items-center space-y-2">
                        <div class="w-16 h-16 border border-slate-200 rounded-lg p-1 bg-white/80 flex items-center justify-center">
                            <img id="create-icon-preview-img" src="" alt="プレビュー" class="max-w-full max-h-full object-contain">
                        </div>
                        <span id="create-icon-filename" class="font-mono text-[10px] text-slate-600 truncate max-w-xs"></span>
                    </div>
                </div>
            </div>

            <!-- アイコン名称 & 分類 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div>
                    <label for="create-icon-name" class="block text-[11px] font-bold text-slate-700 mb-1">アイコン名称 <span class="text-red-500">*</span></label>
                    <input type="text" id="create-icon-name" name="name" required
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg focus:ring-1 focus:ring-indigo-500 font-bold"
                        placeholder="例: 本部救護テント">
                </div>
                <div>
                    <label for="create-icon-category" class="block text-[11px] font-bold text-slate-700 mb-1">分類 <span class="text-red-500">*</span></label>
                    <select id="create-icon-category" name="category" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg focus:ring-1 focus:ring-indigo-500 font-bold">
                        <option value="booth">通りブース (booth)</option>
                        <option value="tent">テント (tent)</option>
                        <option value="facility">設備 (facility)</option>
                        <option value="vehicle">車両・キッチンカー (vehicle)</option>
                        <option value="area">エリア (area)</option>
                        <option value="note">注記 (note)</option>
                        <option value="other" selected>その他 (other)</option>
                    </select>
                </div>
            </div>

            <!-- 実世界寸法 (幅m × 奥行m) & 縮尺換算ヒント -->
            <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-800">規定寸法 (実寸メートル)</span>
                    <span id="create-icon-scale-hint" class="font-mono text-[10px] text-indigo-600 font-bold">A3 1/400換算: 14.17pt × 14.17pt</span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="create-icon-width" class="block text-[10px] font-bold text-slate-600 mb-0.5">幅 (m)</label>
                        <input type="number" id="create-icon-width" name="width_meters" step="0.1" min="0.5" max="50.0" value="2.0" required
                            class="w-full px-2 py-1 bg-white border border-slate-300 rounded font-mono text-center font-bold">
                    </div>
                    <div>
                        <label for="create-icon-height" class="block text-[10px] font-bold text-slate-600 mb-0.5">奥行 (m)</label>
                        <input type="number" id="create-icon-height" name="height_meters" step="0.1" min="0.5" max="50.0" value="2.0" required
                            class="w-full px-2 py-1 bg-white border border-slate-300 rounded font-mono text-center font-bold">
                    </div>
                </div>
            </div>

            <!-- オプション設定 -->
            <div class="space-y-1.5 pt-1">
                <label class="flex items-center space-x-2 text-[11px] font-bold text-slate-700 cursor-pointer">
                    <input type="checkbox" id="create-icon-rotatable" name="is_rotatable" value="1" checked class="rounded border-slate-300 text-indigo-600">
                    <span>回転可能 (45°刻みで回転)</span>
                </label>
                <label class="flex items-center space-x-2 text-[11px] font-bold text-slate-700 cursor-pointer">
                    <input type="checkbox" id="create-icon-resizable" name="is_resizable" value="1" class="rounded border-slate-300 text-indigo-600">
                    <span>リサイズ可能 (四隅ハンドルで手動拡縮)</span>
                </label>
                <label class="flex items-center space-x-2 text-[11px] font-bold text-slate-700 cursor-pointer">
                    <input type="checkbox" id="create-icon-hygiene" name="has_hygiene_circle" value="1" class="rounded border-slate-300 text-indigo-600">
                    <span>給水20m衛生サークルを展開 (手洗い・給水シンボル用)</span>
                </label>
            </div>

            <div id="create-icon-error-msg" class="hidden p-2 rounded-lg bg-red-50 border border-red-200 text-red-700 text-[11px] font-bold"></div>

            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                <button type="button" class="btn-close-modal px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-bold transition cursor-pointer" data-modal="modal-create-icon">キャンセル</button>
                <button type="submit" id="btn-submit-create-icon" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-xs transition flex items-center space-x-1 cursor-pointer">
                    <span>➕</span>
                    <span>アイコンを生成して登録</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- モーダル 2: アイコン編集 --}}
<div id="modal-edit-icon" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs hidden p-4" role="dialog" aria-modal="true" aria-labelledby="modal-edit-icon-title">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-lg p-5 space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 id="modal-edit-icon-title" class="text-sm font-bold text-slate-800 flex items-center space-x-2">
                <span>✏️</span>
                <span>アイコン編集</span>
            </h3>
            <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-600 font-bold text-lg px-2 py-0.5 rounded-lg cursor-pointer" data-modal="modal-edit-icon">✕</button>
        </div>

        <form id="form-edit-icon" class="space-y-3.5 text-xs">
            <input type="hidden" id="edit-icon-id" name="icon_id">

            <!-- 現在画像サムネイル & 差し替えファイル選択 -->
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">アイコン画像 (差し替える場合のみ選択)</label>
                <div class="flex items-center space-x-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <div class="w-14 h-14 border border-slate-300 rounded-lg p-1 bg-white flex items-center justify-center shrink-0">
                        <img id="edit-icon-current-img" src="" alt="現在画像" class="max-w-full max-h-full object-contain">
                    </div>
                    <div class="flex-1 space-y-1">
                        <input type="file" id="edit-icon-file" name="png_file" accept="image/png" class="text-[11px] file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                        <p class="text-[10px] text-slate-400">未選択時は現在の画像が維持されます</p>
                    </div>
                </div>
            </div>

            <!-- アイコン名称 & 分類 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div>
                    <label for="edit-icon-name" class="block text-[11px] font-bold text-slate-700 mb-1">アイコン名称 <span class="text-red-500">*</span></label>
                    <input type="text" id="edit-icon-name" name="name" required
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg focus:ring-1 focus:ring-indigo-500 font-bold">
                </div>
                <div>
                    <label for="edit-icon-category" class="block text-[11px] font-bold text-slate-700 mb-1">分類 <span class="text-red-500">*</span></label>
                    <select id="edit-icon-category" name="category" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-lg focus:ring-1 focus:ring-indigo-500 font-bold">
                        <option value="booth">通りブース (booth)</option>
                        <option value="tent">テント (tent)</option>
                        <option value="facility">設備 (facility)</option>
                        <option value="vehicle">車両・キッチンカー (vehicle)</option>
                        <option value="area">エリア (area)</option>
                        <option value="note">注記 (note)</option>
                        <option value="other">その他 (other)</option>
                    </select>
                </div>
            </div>

            <!-- 実世界寸法 (幅m × 奥行m) & 縮尺換算ヒント -->
            <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-800">規定寸法 (実寸メートル)</span>
                    <span id="edit-icon-scale-hint" class="font-mono text-[10px] text-indigo-600 font-bold"></span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="edit-icon-width" class="block text-[10px] font-bold text-slate-600 mb-0.5">幅 (m)</label>
                        <input type="number" id="edit-icon-width" name="width_meters" step="0.1" min="0.5" max="50.0" required
                            class="w-full px-2 py-1 bg-white border border-slate-300 rounded font-mono text-center font-bold">
                    </div>
                    <div>
                        <label for="edit-icon-height" class="block text-[10px] font-bold text-slate-600 mb-0.5">奥行 (m)</label>
                        <input type="number" id="edit-icon-height" name="height_meters" step="0.1" min="0.5" max="50.0" required
                            class="w-full px-2 py-1 bg-white border border-slate-300 rounded font-mono text-center font-bold">
                    </div>
                </div>
            </div>

            <!-- オプション設定 -->
            <div class="space-y-1.5 pt-1">
                <label class="flex items-center space-x-2 text-[11px] font-bold text-slate-700 cursor-pointer">
                    <input type="checkbox" id="edit-icon-rotatable" name="is_rotatable" value="1" class="rounded border-slate-300 text-indigo-600">
                    <span>回転可能 (45°刻みで回転)</span>
                </label>
                <label class="flex items-center space-x-2 text-[11px] font-bold text-slate-700 cursor-pointer">
                    <input type="checkbox" id="edit-icon-resizable" name="is_resizable" value="1" class="rounded border-slate-300 text-indigo-600">
                    <span>リサイズ可能 (四隅ハンドルで手動拡縮)</span>
                </label>
                <label class="flex items-center space-x-2 text-[11px] font-bold text-slate-700 cursor-pointer">
                    <input type="checkbox" id="edit-icon-hygiene" name="has_hygiene_circle" value="1" class="rounded border-slate-300 text-indigo-600">
                    <span>給水20m衛生サークルを展開</span>
                </label>
            </div>

            <div id="edit-icon-error-msg" class="hidden p-2 rounded-lg bg-red-50 border border-red-200 text-red-700 text-[11px] font-bold"></div>

            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                <button type="button" class="btn-close-modal px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-bold transition cursor-pointer" data-modal="modal-edit-icon">キャンセル</button>
                <button type="submit" id="btn-submit-edit-icon" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-xs transition flex items-center space-x-1 cursor-pointer">
                    <span>💾</span>
                    <span>変更を保存</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- モーダル 3: アイコン削除確認 --}}
<div id="modal-delete-icon" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs hidden p-4" role="dialog" aria-modal="true" aria-labelledby="modal-delete-icon-title">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md p-5 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 id="modal-delete-icon-title" class="text-sm font-bold text-red-600 flex items-center space-x-2">
                <span>🗑️</span>
                <span>カスタムアイコンの削除</span>
            </h3>
            <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-600 font-bold text-lg px-2 py-0.5 rounded-lg cursor-pointer" data-modal="modal-delete-icon">✕</button>
        </div>

        <div class="space-y-3 text-xs">
            <input type="hidden" id="delete-icon-id">
            <div class="flex items-center space-x-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                <div class="w-12 h-12 border border-slate-300 rounded-lg p-1 bg-white flex items-center justify-center shrink-0">
                    <img id="delete-icon-img" src="" alt="削除対象アイコン" class="max-w-full max-h-full object-contain">
                </div>
                <div class="truncate">
                    <p id="delete-icon-name-text" class="font-bold text-slate-800 text-sm truncate"></p>
                    <p id="delete-icon-size-text" class="text-[11px] font-mono text-slate-500"></p>
                </div>
            </div>

            <!-- 配置件数に応じた警告メッセージ -->
            <div id="delete-icon-usage-warning" class="hidden p-3 bg-amber-50 border border-amber-200 rounded-xl space-y-1 text-amber-800">
                <p class="font-bold flex items-center space-x-1">
                    <span>⚠️</span>
                    <span>白地図上で配置されています</span>
                </p>
                <p class="text-[11px] leading-relaxed">
                    現在、白地図上に <span id="delete-icon-usage-count" class="font-bold text-red-600 text-xs">0</span> 箇所の配置があります。<br>
                    このアイコンを削除すると、白地図上の関連配置も同時に削除されます。
                </p>
            </div>

            <p class="text-slate-600">このカスタムアイコンをパレットから削除します。この操作は取り消せません。</p>

            <div id="delete-icon-error-msg" class="hidden p-2 rounded-lg bg-red-50 border border-red-200 text-red-700 text-[11px] font-bold"></div>
        </div>

        <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100 text-xs">
            <button type="button" class="btn-close-modal px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-bold transition cursor-pointer" data-modal="modal-delete-icon">キャンセル</button>
            <button type="button" id="btn-submit-delete-icon" class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg font-bold shadow-xs transition flex items-center space-x-1 cursor-pointer">
                <span>🗑️</span>
                <span>削除を実行</span>
            </button>
        </div>
    </div>
</div>
