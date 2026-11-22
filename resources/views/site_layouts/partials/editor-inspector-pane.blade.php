<!-- 3. 右ペイン: 常時表示プロパティ・インスペクタ -->
<aside class="w-full lg:w-64 bg-white border border-slate-200 rounded-2xl p-3 shadow-2xs shrink-0 flex flex-col space-y-3 overflow-y-auto max-h-[78vh]">
    <div class="pb-2 border-b border-slate-100 flex items-center justify-between">
        <span class="text-xs font-bold text-slate-800">⚙️ プロパティ・インスペクタ</span>
        <span id="inspector-selection-badge" class="text-[10px] px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded font-bold">未選択</span>
    </div>

    <!-- A. 未選択時: 全体サマリー & 衛生20mチェック -->
    <div id="inspector-empty-state" class="space-y-3 text-xs">
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
            <div class="font-bold text-slate-700">📊 図面配置サマリー</div>
            <div class="flex justify-between text-slate-600"><span>総配置オブジェクト:</span><span id="summary-total-count" class="font-bold font-mono text-slate-900">0</span></div>
            <div class="flex justify-between text-red-600"><span>🔴 赤枠 (調理・販売):</span><span id="summary-red-count" class="font-bold font-mono">0</span></div>
            <div class="flex justify-between text-emerald-600"><span>🟢 緑枠 (販売のみ):</span><span id="summary-green-count" class="font-bold font-mono">0</span></div>
            <div class="flex justify-between text-blue-600"><span>💧 手洗い・洗浄設備:</span><span id="summary-water-count" class="font-bold font-mono">0</span></div>
        </div>

        <div id="hygiene-check-box" class="p-2.5 bg-sky-50 border border-sky-200 rounded-xl space-y-1">
            <div class="font-bold text-sky-900">🩺 食品衛生 20m圏チェック</div>
            <p id="hygiene-check-status" class="text-[11px] text-sky-800 leading-relaxed">検証中...</p>
            <ul id="hygiene-warning-list" class="text-[10px] text-red-700 font-mono space-y-0.5 max-h-32 overflow-y-auto"></ul>
        </div>

        <p class="text-[11px] text-slate-400 leading-relaxed">
            白地図上のアイコンをクリックすると、文字・色・角度・サイズ・台帳連動をここから即時編集できます。
        </p>
    </div>

    <!-- B. 単一選択時: インスペクタフォーム -->
    <div id="inspector-single-form" class="hidden space-y-3 text-xs">
        <div>
            <div class="text-[10px] font-bold text-slate-400">シンボル種別</div>
            <div id="insp-shape-name" class="font-bold text-slate-800">--</div>
        </div>

        <!-- 表示テキスト -->
        <div>
            <label for="insp-display-text" class="block text-[11px] font-bold text-slate-700 mb-1">表示テキスト（改行可）</label>
            <textarea id="insp-display-text" rows="2"
                class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:bg-white"></textarea>
        </div>

        <!-- カラーテーマ -->
        <div>
            <div class="flex items-center justify-between mb-1">
                <span class="text-[11px] font-bold text-slate-700">カラーテーマ</span>
                <span id="insp-color-note" class="text-[10px] text-slate-400"></span>
            </div>
            <div id="insp-color-buttons" class="grid grid-cols-6 gap-1.5">
                <button type="button" data-color="red" class="insp-color-btn h-6 rounded-md bg-red-600 border-2 border-transparent cursor-pointer" title="赤"></button>
                <button type="button" data-color="green" class="insp-color-btn h-6 rounded-md bg-emerald-600 border-2 border-transparent cursor-pointer" title="緑"></button>
                <button type="button" data-color="black" class="insp-color-btn h-6 rounded-md bg-slate-900 border-2 border-transparent cursor-pointer" title="黒"></button>
                <button type="button" data-color="cyan" class="insp-color-btn h-6 rounded-md bg-cyan-500 border-2 border-transparent cursor-pointer" title="水色"></button>
                <button type="button" data-color="orange" class="insp-color-btn h-6 rounded-md bg-orange-500 border-2 border-transparent cursor-pointer" title="橙"></button>
                <button type="button" data-color="blue" class="insp-color-btn h-6 rounded-md bg-blue-600 border-2 border-transparent cursor-pointer" title="青"></button>
            </div>
        </div>

        <!-- 角度 & 通りロック -->
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label for="insp-rotation" class="block text-[11px] font-bold text-slate-700 mb-1">回転角度</label>
                <select id="insp-rotation" class="w-full px-2 py-1 text-xs bg-slate-50 border border-slate-300 rounded-lg">
                    <option value="0">0°</option>
                    <option value="45">45°</option>
                    <option value="90">90°</option>
                    <option value="135">135°</option>
                    <option value="180">180°</option>
                    <option value="225">225°</option>
                    <option value="270">270°</option>
                    <option value="315">315°</option>
                </select>
            </div>
            <div>
                <label for="insp-font-size" class="block text-[11px] font-bold text-slate-700 mb-1">文字pt(空=自動)</label>
                <input type="number" id="insp-font-size" step="0.5" min="3" max="24"
                    class="w-full px-2 py-1 text-xs bg-slate-50 border border-slate-300 rounded-lg" placeholder="自動">
            </div>
        </div>

        <label class="flex items-center space-x-1.5 text-[11px] font-bold text-slate-600 cursor-pointer">
            <input type="checkbox" id="insp-rotation-locked" class="rounded border-slate-300 text-indigo-600">
            <span>通り沿い平行ロック (回転不可)</span>
        </label>

        <!-- 寸法 (pt / m) -->
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label for="insp-width-m" class="block text-[11px] font-bold text-slate-700 mb-1">幅 (m)</label>
                <input type="number" id="insp-width-m" step="0.1" min="0.5"
                    class="w-full px-2 py-1 text-xs font-mono bg-slate-50 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label for="insp-height-m" class="block text-[11px] font-bold text-slate-700 mb-1">奥行 (m)</label>
                <input type="number" id="insp-height-m" step="0.1" min="0.5"
                    class="w-full px-2 py-1 text-xs font-mono bg-slate-50 border border-slate-300 rounded-lg">
            </div>
        </div>

        <!-- 給水20m円設定 (water_supply時のみ表示) -->
        <div id="insp-water-options" class="hidden p-2 bg-sky-50 border border-sky-200 rounded-lg space-y-1.5">
            <label class="flex items-center space-x-1.5 text-[11px] font-bold text-sky-900 cursor-pointer">
                <input type="checkbox" id="insp-show-hygiene-circle" class="rounded border-sky-300 text-sky-600">
                <span>20m衛生サークルを描画</span>
            </label>
            <div>
                <label for="insp-arc-mode" class="block text-[10px] font-bold text-sky-800 mb-0.5">円弧向き</label>
                <select id="insp-arc-mode" class="w-full px-2 py-1 text-[11px] bg-white border border-sky-300 rounded">
                    <option value="0,360">全円 (360°)</option>
                    <option value="90,270">左半円 (通り西側向け)</option>
                    <option value="-90,90">右半円 (通り東側向け)</option>
                </select>
            </div>
        </div>

        <!-- 台帳紐づけ -->
        <div>
            <label for="insp-layout-item-id" class="block text-[11px] font-bold text-slate-700 mb-1">台帳データ連動</label>
            <select id="insp-layout-item-id" class="w-full px-2 py-1 text-xs bg-slate-50 border border-slate-300 rounded-lg">
                <option value="">（未紐づけ）</option>
                @foreach($layoutItems as $item)
                    <option value="{{ $item->id }}"
                        data-code="{{ $item->code }}"
                        data-name="{{ $item->presenter()->displayName() }}"
                        data-vendor="{{ $item->vendor?->name ?? '' }}">
                        {{ $item->code }}: {{ $item->presenter()->displayName() }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- 備考 -->
        <div>
            <label for="insp-notes" class="block text-[11px] font-bold text-slate-700 mb-1">メモ・特記事項</label>
            <textarea id="insp-notes" rows="2" class="w-full px-2 py-1 text-xs bg-slate-50 border border-slate-300 rounded-lg" placeholder="申し送り事項等"></textarea>
        </div>
    </div>

    <!-- C. 複数選択時: 一括操作パネル -->
    <div id="inspector-multi-panel" class="hidden space-y-3 text-xs">
        <p id="insp-multi-count" class="font-bold text-indigo-700 bg-indigo-50 p-2 rounded-lg border border-indigo-100">
            0件を選択中
        </p>
        <div>
            <span class="block text-[11px] font-bold text-slate-700 mb-1">一括カラー変更</span>
            <div class="grid grid-cols-5 gap-1.5">
                <button type="button" data-bulk-color="red" class="bulk-color-btn h-6 rounded-md bg-red-600 cursor-pointer" title="すべて赤に"></button>
                <button type="button" data-bulk-color="green" class="bulk-color-btn h-6 rounded-md bg-emerald-600 cursor-pointer" title="すべて緑に"></button>
                <button type="button" data-bulk-color="black" class="bulk-color-btn h-6 rounded-md bg-slate-900 cursor-pointer" title="すべて黒に"></button>
                <button type="button" data-bulk-color="cyan" class="bulk-color-btn h-6 rounded-md bg-cyan-500 cursor-pointer" title="すべて水色に"></button>
                <button type="button" data-bulk-color="orange" class="bulk-color-btn h-6 rounded-md bg-orange-500 cursor-pointer" title="すべて橙に"></button>
            </div>
        </div>
        <p class="text-[11px] text-slate-500">
            上部ツールバーの「左揃」「上揃」「縦密着」「横密着」ボタンで一括整列できます。
        </p>
    </div>
</aside>
