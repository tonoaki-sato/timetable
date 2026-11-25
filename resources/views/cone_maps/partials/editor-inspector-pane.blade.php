<!-- 3. 右ペイン: 常時表示プロパティ・インスペクタ & 機材在庫サマリー -->
<aside class="w-full lg:w-72 bg-white border border-slate-200 rounded-2xl p-3 shadow-2xs shrink-0 flex flex-col space-y-3 overflow-y-auto max-h-[78vh]">
    <div class="pb-2 border-b border-slate-100 flex items-center justify-between">
        <span class="text-xs font-bold text-slate-800">⚙️ インスペクタ・集計</span>
        <span id="cone-insp-badge" class="text-[10px] px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded font-bold">未選択</span>
    </div>

    <!-- A. 未選択時: 全体サマリー & 機材在庫比較 -->
    <div id="cone-insp-empty" class="space-y-3 text-xs">
        <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
            <div class="font-bold text-slate-700 flex items-center justify-between">
                <span>📊 配置数量サマリー</span>
            </div>
            <div class="flex justify-between items-baseline text-slate-700">
                <span>🔺 カラーコーン:</span>
                <span id="sum-cones-count" class="font-bold font-mono text-slate-900">0個</span>
            </div>
            <div id="sum-cones-breakdown" class="text-[10px] text-slate-500 text-right font-mono">
                単独 0個 / 連結 0個
            </div>
            <div class="flex justify-between items-baseline text-slate-700 pt-0.5 border-t border-slate-200/70">
                <span>⚖️ ウェイト (自動同数):</span>
                <span id="sum-weights-count" class="font-bold font-mono text-indigo-700">0個</span>
            </div>
            <div class="flex justify-between items-baseline text-slate-700">
                <span>➖ コーンバー (200cm):</span>
                <span id="sum-bars-count" class="font-bold font-mono text-amber-700">0本</span>
            </div>
            <div class="flex justify-between items-baseline text-slate-700 pt-0.5 border-t border-slate-200/70">
                <span>🟧 A型バリケード:</span>
                <span id="sum-barricades-type-a" class="font-bold font-mono text-orange-700">0台</span>
            </div>
            <div class="flex justify-between items-baseline text-slate-700">
                <span>🟩 樹脂製単管バリケード:</span>
                <span id="sum-barricades-resin-pipe" class="font-bold font-mono text-emerald-700">0台</span>
            </div>
            <div class="flex justify-between items-baseline text-slate-700 pt-0.5 border-t border-slate-200/70">
                <span>💬 注意事項 (吹き出し):</span>
                <span id="sum-callouts-count" class="font-bold font-mono text-slate-900">0件</span>
            </div>
        </div>

        <!-- 機材在庫比較 -->
        <div class="p-2.5 bg-sky-50/70 border border-sky-200 rounded-xl space-y-1.5">
            <div class="font-bold text-sky-900">📦 機材保有数との比較</div>
            <div id="cone-inventory-list" class="space-y-1 text-[11px]"></div>
        </div>

        <!-- 色別内訳 -->
        <div class="p-2.5 bg-white border border-slate-200 rounded-xl space-y-1">
            <div class="font-bold text-slate-700 text-[11px]">🎨 コーン色別内訳</div>
            <div id="cone-color-breakdown" class="grid grid-cols-2 gap-1 text-[11px]"></div>
        </div>
    </div>

    <!-- B. カラーコーン単体選択時 -->
    <div id="cone-insp-cone-form" class="hidden space-y-3 text-xs">
        <div class="p-2 bg-red-50/60 border border-red-200 rounded-lg">
            <div class="font-bold text-slate-800">🔺 カラーコーン（＋ウェイト1個）</div>
            <div class="text-[10px] text-slate-500">H70cm / 底面 38.8×38.8cm</div>
        </div>

        <div>
            <span class="block text-[11px] font-bold text-slate-700 mb-1">コーン色</span>
            <div class="flex items-center space-x-1.5">
                @foreach(\App\Enums\ConeColor::cases() as $coneColor)
                    <button type="button"
                        data-insp-cone-color="{{ $coneColor->value }}"
                        class="insp-cone-color-btn w-7 h-7 rounded-md border-2 border-white shadow-2xs cursor-pointer {{ match($coneColor) {
                            \App\Enums\ConeColor::Red => 'bg-red-600',
                            \App\Enums\ConeColor::Yellow => 'bg-yellow-500',
                            \App\Enums\ConeColor::Blue => 'bg-blue-600',
                            \App\Enums\ConeColor::Green => 'bg-emerald-600',
                            \App\Enums\ConeColor::Orange => 'bg-orange-500',
                        } }}"
                        title="{{ $coneColor->label() }}"></button>
                @endforeach
            </div>
        </div>

        <div>
            <label for="insp-cone-label" class="block text-[11px] font-bold text-slate-700 mb-1">識別ラベル（任意）</label>
            <input type="text" id="insp-cone-label" maxlength="50" placeholder="例: C01, 入口1"
                class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-lg">
        </div>

        <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg text-[11px] flex items-center justify-between">
            <span class="text-slate-600">接続中のコーンバー:</span>
            <span id="insp-cone-connected-bars" class="font-bold font-mono text-slate-900">0本</span>
        </div>

        <div>
            <label for="insp-cone-notes" class="block text-[11px] font-bold text-slate-700 mb-1">メモ</label>
            <textarea id="insp-cone-notes" rows="2" class="w-full px-2 py-1 text-xs bg-slate-50 border border-slate-300 rounded-lg"></textarea>
        </div>

        <button type="button" id="btn-cone-add-callout"
            class="w-full py-1.5 px-2 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-lg text-xs font-bold transition cursor-pointer">
            💬 このコーンに吹き出しを追加
        </button>
    </div>

    <!-- C. コーンバー単体選択時 -->
    <div id="cone-insp-bar-form" class="hidden space-y-3 text-xs">
        <div class="p-2 bg-amber-50/70 border border-amber-200 rounded-lg">
            <div class="font-bold text-slate-800">➖ コーンバー（コーン間連結）</div>
            <div class="text-[10px] text-slate-500">長さ 200cm / 太さ 3.4cm</div>
        </div>

        <div>
            <label for="insp-bar-style" class="block text-[11px] font-bold text-slate-700 mb-1">バー種別</label>
            <select id="insp-bar-style" class="w-full px-2 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-lg">
                @foreach(\App\Enums\ConeBarStyle::cases() as $barStyle)
                    <option value="{{ $barStyle->value }}">{{ $barStyle->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="p-2 bg-slate-50 border border-slate-200 rounded-lg space-y-1 text-[11px]">
            <div class="flex justify-between">
                <span class="text-slate-600">両端コーン間距離:</span>
                <span id="insp-bar-span-m" class="font-bold font-mono text-slate-900">0.00m</span>
            </div>
            <div id="insp-bar-span-warning" class="hidden text-[10px] text-red-600 font-bold">
                ⚠️ コーンバー標準長(2.00m)を超えています
            </div>
            <button type="button" id="btn-bar-fit-2m"
                class="hidden w-full mt-1 py-1 px-2 bg-white hover:bg-indigo-50 text-indigo-700 border border-indigo-300 rounded text-[11px] font-bold cursor-pointer">
                📏 終点コーンを 2.00m に補正
            </button>
        </div>

        <div>
            <label for="insp-bar-notes" class="block text-[11px] font-bold text-slate-700 mb-1">メモ</label>
            <textarea id="insp-bar-notes" rows="2" class="w-full px-2 py-1 text-xs bg-slate-50 border border-slate-300 rounded-lg"></textarea>
        </div>
    </div>

    <!-- D. バリケード単体選択時 -->
    <div id="cone-insp-barricade-form" class="hidden space-y-3 text-xs">
        <div class="p-2 bg-orange-50/70 border border-orange-200 rounded-lg">
            <div id="insp-barricade-title" class="font-bold text-slate-800">🟧 バリケード</div>
            <div id="insp-barricade-spec" class="text-[10px] text-slate-500">120×46×H80cm</div>
        </div>

        <div>
            <label for="insp-barricade-type" class="block text-[11px] font-bold text-slate-700 mb-1">バリケード種別</label>
            <select id="insp-barricade-type" class="w-full px-2 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-lg">
                @foreach(\App\Enums\BarricadeType::cases() as $bType)
                    <option value="{{ $bType->value }}">{{ $bType->label() }} ({{ $bType->specLabel() }})</option>
                @endforeach
            </select>
        </div>

        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label for="insp-barricade-rotation" class="text-[11px] font-bold text-slate-700">回転角度 (0〜359°)</label>
                <div class="flex items-center space-x-1">
                    <button type="button" id="btn-barricade-rot-15" class="px-1.5 py-0.5 bg-slate-100 hover:bg-indigo-100 rounded text-[10px] font-bold cursor-pointer">+15°</button>
                    <button type="button" id="btn-barricade-rot-45" class="px-1.5 py-0.5 bg-slate-100 hover:bg-indigo-100 rounded text-[10px] font-bold cursor-pointer">+45°</button>
                </div>
            </div>
            <input type="number" id="insp-barricade-rotation" min="0" max="359" step="1"
                class="w-full px-2.5 py-1.5 text-xs font-mono bg-slate-50 border border-slate-300 rounded-lg">
        </div>

        <div>
            <label for="insp-barricade-label" class="block text-[11px] font-bold text-slate-700 mb-1">識別ラベル（任意）</label>
            <input type="text" id="insp-barricade-label" maxlength="50" placeholder="例: B01, 支道封鎖"
                class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-lg">
        </div>

        <div>
            <label for="insp-barricade-notes" class="block text-[11px] font-bold text-slate-700 mb-1">メモ</label>
            <textarea id="insp-barricade-notes" rows="2" class="w-full px-2 py-1 text-xs bg-slate-50 border border-slate-300 rounded-lg"></textarea>
        </div>

        <button type="button" id="btn-barricade-add-callout"
            class="w-full py-1.5 px-2 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-lg text-xs font-bold transition cursor-pointer">
            💬 このバリケードに吹き出しを追加
        </button>
    </div>

    <!-- E. 吹き出し単体選択時 -->
    <div id="cone-insp-callout-form" class="hidden space-y-3 text-xs">
        <div class="p-2 bg-amber-50/70 border border-amber-200 rounded-lg">
            <div class="font-bold text-slate-800">💬 吹き出し（注意事項）</div>
            <div class="text-[10px] text-slate-500">枠と引出線先端(●)を独立ドラッグ可能</div>
        </div>

        <div>
            <label for="insp-callout-title" class="block text-[11px] font-bold text-slate-700 mb-1">見出し（任意）</label>
            <input type="text" id="insp-callout-title" maxlength="100" placeholder="例: 通行止め解除時注意"
                class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-lg">
        </div>

        <div>
            <label for="insp-callout-body" class="block text-[11px] font-bold text-slate-700 mb-1">注意事項本文（改行可）</label>
            <textarea id="insp-callout-body" rows="3"
                class="w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-lg"></textarea>
        </div>

        <div>
            <span class="block text-[11px] font-bold text-slate-700 mb-1">配色テーマ</span>
            <div class="grid grid-cols-2 gap-1.5">
                @foreach(\App\Enums\ConeCalloutColor::cases() as $cTheme)
                    <button type="button"
                        data-insp-callout-color="{{ $cTheme->value }}"
                        class="insp-callout-color-btn py-1 px-2 rounded-lg text-[11px] font-bold border cursor-pointer {{ match($cTheme) {
                            \App\Enums\ConeCalloutColor::Amber => 'bg-amber-50 text-amber-800 border-amber-400',
                            \App\Enums\ConeCalloutColor::Red => 'bg-red-50 text-red-800 border-red-400',
                            \App\Enums\ConeCalloutColor::Blue => 'bg-blue-50 text-blue-800 border-blue-400',
                            \App\Enums\ConeCalloutColor::Slate => 'bg-slate-100 text-slate-800 border-slate-400',
                        } }}">
                        {{ $cTheme->label() }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-3 gap-1.5">
            <div>
                <label for="insp-callout-font-size" class="block text-[10px] font-bold text-slate-700 mb-0.5">文字pt</label>
                <input type="number" id="insp-callout-font-size" step="0.5" min="3" max="24" placeholder="自動"
                    class="w-full px-1.5 py-1 text-xs bg-slate-50 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label for="insp-callout-width" class="block text-[10px] font-bold text-slate-700 mb-0.5">幅(pt)</label>
                <input type="number" id="insp-callout-width" step="2" min="20" max="300"
                    class="w-full px-1.5 py-1 text-xs font-mono bg-slate-50 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label for="insp-callout-height" class="block text-[10px] font-bold text-slate-700 mb-0.5">高さ(pt)</label>
                <input type="number" id="insp-callout-height" step="2" min="12" max="200"
                    class="w-full px-1.5 py-1 text-xs font-mono bg-slate-50 border border-slate-300 rounded-lg">
            </div>
        </div>

        <div id="insp-callout-target-wrap" class="hidden p-2 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-between text-[11px]">
            <span id="insp-callout-target-label" class="text-slate-700 font-bold truncate">対象へ追従中</span>
            <button type="button" id="btn-callout-unlink" class="text-red-600 hover:underline font-bold cursor-pointer shrink-0">紐づけ解除</button>
        </div>
    </div>

    <!-- F. 複数選択時 -->
    <div id="cone-insp-multi-panel" class="hidden space-y-3 text-xs">
        <p id="cone-insp-multi-count" class="font-bold text-indigo-700 bg-indigo-50 p-2 rounded-lg border border-indigo-100">
            0件を選択中
        </p>
        <div id="cone-insp-multi-colors" class="hidden space-y-1">
            <span class="block text-[11px] font-bold text-slate-700">選択中コーンの色を一括変更</span>
            <div class="flex items-center space-x-1.5">
                @foreach(\App\Enums\ConeColor::cases() as $coneColor)
                    <button type="button"
                        data-bulk-cone-color="{{ $coneColor->value }}"
                        class="bulk-cone-color-btn w-7 h-7 rounded-md border-2 border-white shadow-2xs cursor-pointer {{ match($coneColor) {
                            \App\Enums\ConeColor::Red => 'bg-red-600',
                            \App\Enums\ConeColor::Yellow => 'bg-yellow-500',
                            \App\Enums\ConeColor::Blue => 'bg-blue-600',
                            \App\Enums\ConeColor::Green => 'bg-emerald-600',
                            \App\Enums\ConeColor::Orange => 'bg-orange-500',
                        } }}"
                        title="{{ $coneColor->label() }}"></button>
                @endforeach
            </div>
        </div>
        <p class="text-[11px] text-slate-500">
            ドラッグで一括移動、上部ツールバーで左揃・上揃・2m整列、Deleteキーで一括削除できます。
        </p>
    </div>
</aside>
