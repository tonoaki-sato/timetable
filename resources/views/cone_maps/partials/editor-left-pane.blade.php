<!-- 1. 左ペイン: 配置パレット & 注意事項一覧 -->
<aside class="w-full lg:w-64 bg-white border border-slate-200 rounded-2xl p-2.5 shadow-2xs shrink-0 flex flex-col justify-between space-y-2.5 max-h-[78vh]">
    <div class="flex flex-col space-y-2 overflow-hidden flex-1">
        <!-- タブ切替 -->
        <div class="grid grid-cols-2 gap-1 bg-slate-100 p-1 rounded-xl shrink-0">
            <button type="button" id="tab-btn-cone-palette"
                class="py-1 rounded-lg text-xs font-bold bg-white text-indigo-700 shadow-2xs transition cursor-pointer">
                🔺 配置パレット
            </button>
            <button type="button" id="tab-btn-cone-callouts"
                class="py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition cursor-pointer">
                💬 注意事項 (<span id="left-callout-count">{{ $callouts->count() }}</span>)
            </button>
        </div>

        <!-- タブ1: 配置パレット（5種類：単独コーンバー無し） -->
        <div id="tab-panel-cone-palette" class="overflow-y-auto pr-0.5 space-y-2.5 flex-1 text-xs">
            <p class="text-[10px] text-slate-500 leading-snug">
                パレットの部品を地図へドラッグ＆ドロップ、またはクリックで配置モードへ切り替えます。
            </p>

            <!-- 1. 単独カラーコーン（＋ウェイト） -->
            <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                <div class="cone-palette-item flex items-center space-x-2.5 p-1.5 bg-white hover:bg-indigo-50/70 border border-slate-200 hover:border-indigo-300 rounded-lg cursor-grab active:cursor-grabbing transition select-none"
                    draggable="true"
                    data-palette-kind="cone">
                    <svg viewBox="0 0 28 28" class="w-8 h-8 shrink-0 pointer-events-none">
                        <rect x="4" y="4" width="20" height="20" rx="2" fill="#dc2626" stroke="#0f172a" stroke-width="1.5"/>
                        <circle cx="14" cy="14" r="6" fill="none" stroke="#ffffff" stroke-width="1.5"/>
                        <circle cx="14" cy="14" r="2.2" fill="#ffffff"/>
                    </svg>
                    <div class="min-w-0 flex-1 pointer-events-none">
                        <div class="font-bold text-slate-800 truncate">カラーコーン (単独)</div>
                        <div class="text-[10px] text-slate-500">H70cm / 38.8×38.8cm</div>
                        <div class="text-[9px] font-bold text-indigo-600">＋ウェイト1個自動連動</div>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-slate-600">コーン色:</span>
                    <div id="palette-cone-colors" class="flex items-center space-x-1">
                        @foreach(\App\Enums\ConeColor::cases() as $coneColor)
                            <button type="button"
                                data-cone-color="{{ $coneColor->value }}"
                                class="palette-cone-color-btn w-5 h-5 rounded-md border-2 border-white shadow-2xs cursor-pointer transition {{ $coneColor === \App\Enums\ConeColor::Red ? 'ring-2 ring-indigo-600 ring-offset-1' : '' }} {{ match($coneColor) {
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
            </div>

            <!-- 2. カラーコーン連結（コーン列＋2mバー） -->
            <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                <button type="button" id="btn-palette-cone-line"
                    class="w-full flex items-center space-x-2.5 p-1.5 bg-white hover:bg-indigo-50/70 border border-slate-200 hover:border-indigo-300 rounded-lg text-left transition cursor-pointer">
                    <svg viewBox="0 0 36 22" class="w-8 h-6 shrink-0 pointer-events-none">
                        <line x1="7" y1="11" x2="29" y2="11" stroke="#eab308" stroke-width="3.5"/>
                        <line x1="12" y1="11" x2="17" y2="11" stroke="#0f172a" stroke-width="3.5"/>
                        <line x1="21" y1="11" x2="26" y2="11" stroke="#0f172a" stroke-width="3.5"/>
                        <rect x="2" y="6" width="10" height="10" rx="1.5" fill="#dc2626" stroke="#0f172a" stroke-width="1"/>
                        <rect x="24" y="6" width="10" height="10" rx="1.5" fill="#dc2626" stroke="#0f172a" stroke-width="1"/>
                    </svg>
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-slate-800 truncate">コーン＋バー連結配置</div>
                        <div class="text-[10px] text-slate-500">バー L200cm / φ3.4cm</div>
                        <div class="text-[9px] font-bold text-amber-700">バー本数＝コーン個数−1</div>
                    </div>
                </button>
                <div class="grid grid-cols-2 gap-1.5">
                    <div>
                        <label for="palette-bar-style" class="block text-[10px] font-bold text-slate-600 mb-0.5">バー種別</label>
                        <select id="palette-bar-style" class="w-full px-1.5 py-1 text-[11px] bg-white border border-slate-300 rounded-md">
                            @foreach(\App\Enums\ConeBarStyle::cases() as $barStyle)
                                <option value="{{ $barStyle->value }}">{{ $barStyle->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="palette-pitch-mode" class="block text-[10px] font-bold text-slate-600 mb-0.5">間隔方式</label>
                        <select id="palette-pitch-mode" class="w-full px-1.5 py-1 text-[11px] bg-white border border-slate-300 rounded-md">
                            <option value="even_max_2m">均等割 (≤2m)</option>
                            <option value="fixed_2m">2.00m 固定</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 3. A型バリケード / 4. 樹脂製単管バリケード -->
            <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                <div class="text-[10px] font-bold text-slate-500">単体バリケード（回転・連番複製可）</div>
                <div class="cone-palette-item flex items-center space-x-2.5 p-1.5 bg-white hover:bg-orange-50/70 border border-slate-200 hover:border-orange-300 rounded-lg cursor-grab active:cursor-grabbing transition select-none"
                    draggable="true"
                    data-palette-kind="barricade"
                    data-barricade-type="type_a">
                    <svg viewBox="0 0 36 24" class="w-8 h-6 shrink-0 pointer-events-none">
                        <rect x="2" y="3" width="32" height="18" rx="1.5" fill="#fff7ed" stroke="#ea580c" stroke-width="2"/>
                        <line x1="6" y1="3" x2="6" y2="21" stroke="#ea580c" stroke-width="1.5"/>
                        <line x1="30" y1="3" x2="30" y2="21" stroke="#ea580c" stroke-width="1.5"/>
                        <rect x="5" y="8" width="26" height="8" fill="#eab308" stroke="#0f172a" stroke-width="0.8"/>
                        <rect x="8" y="8" width="4" height="8" fill="#0f172a"/>
                        <rect x="16" y="8" width="4" height="8" fill="#0f172a"/>
                        <rect x="24" y="8" width="4" height="8" fill="#0f172a"/>
                    </svg>
                    <div class="min-w-0 flex-1 pointer-events-none">
                        <div class="font-bold text-slate-800 truncate">A型バリケード</div>
                        <div class="text-[10px] text-slate-500">120×46×H80cm</div>
                    </div>
                </div>

                <div class="cone-palette-item flex items-center space-x-2.5 p-1.5 bg-white hover:bg-emerald-50/70 border border-slate-200 hover:border-emerald-300 rounded-lg cursor-grab active:cursor-grabbing transition select-none"
                    draggable="true"
                    data-palette-kind="barricade"
                    data-barricade-type="resin_pipe">
                    <svg viewBox="0 0 36 24" class="w-8 h-6 shrink-0 pointer-events-none">
                        <rect x="5" y="2" width="5" height="20" rx="1" fill="#16a34a"/>
                        <rect x="26" y="2" width="5" height="20" rx="1" fill="#16a34a"/>
                        <rect x="2" y="9" width="32" height="6" rx="1" fill="#94a3b8" stroke="#334155" stroke-width="0.9"/>
                        <rect x="6" y="8" width="3" height="8" rx="0.5" fill="#eab308"/>
                        <rect x="27" y="8" width="3" height="8" rx="0.5" fill="#eab308"/>
                    </svg>
                    <div class="min-w-0 flex-1 pointer-events-none">
                        <div class="font-bold text-slate-800 truncate">樹脂製単管バリケード</div>
                        <div class="text-[10px] text-slate-500">120×64×H82cm</div>
                    </div>
                </div>
            </div>

            <!-- 5. 吹き出し（注意事項） -->
            <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                <div class="cone-palette-item flex items-center space-x-2.5 p-1.5 bg-white hover:bg-amber-50/70 border border-slate-200 hover:border-amber-300 rounded-lg cursor-grab active:cursor-grabbing transition select-none"
                    draggable="true"
                    data-palette-kind="callout">
                    <svg viewBox="0 0 32 24" class="w-8 h-6 shrink-0 pointer-events-none">
                        <line x1="5" y1="20" x2="12" y2="15" stroke="#d97706" stroke-width="1.8"/>
                        <circle cx="4.5" cy="20.5" r="2.2" fill="#d97706"/>
                        <rect x="9" y="3" width="20" height="13" rx="2" fill="#fffbeb" stroke="#d97706" stroke-width="1.5"/>
                        <line x1="12" y1="7.5" x2="26" y2="7.5" stroke="#78350f" stroke-width="1.5"/>
                        <line x1="12" y1="11.5" x2="22" y2="11.5" stroke="#78350f" stroke-width="1.2"/>
                    </svg>
                    <div class="min-w-0 flex-1 pointer-events-none">
                        <div class="font-bold text-slate-800 truncate">吹き出し（注意事項）</div>
                        <div class="text-[10px] text-slate-500">先端と枠を独立移動可</div>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-slate-600">吹き出し色:</span>
                    <div id="palette-callout-colors" class="flex items-center space-x-1">
                        @foreach(\App\Enums\ConeCalloutColor::cases() as $cTheme)
                            <button type="button"
                                data-callout-color="{{ $cTheme->value }}"
                                class="palette-callout-color-btn px-1.5 py-0.5 rounded text-[10px] font-bold border cursor-pointer {{ $cTheme === \App\Enums\ConeCalloutColor::Amber ? 'ring-2 ring-indigo-600' : '' }} {{ match($cTheme) {
                                    \App\Enums\ConeCalloutColor::Amber => 'bg-amber-50 text-amber-800 border-amber-400',
                                    \App\Enums\ConeCalloutColor::Red => 'bg-red-50 text-red-800 border-red-400',
                                    \App\Enums\ConeCalloutColor::Blue => 'bg-blue-50 text-blue-800 border-blue-400',
                                    \App\Enums\ConeCalloutColor::Slate => 'bg-slate-100 text-slate-800 border-slate-400',
                                } }}"
                                title="{{ $cTheme->label() }}">{{ mb_substr($cTheme->label(), 0, 2) }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- タブ2: 注意事項（吹き出し）一覧 -->
        <div id="tab-panel-cone-callouts" class="hidden flex-col space-y-2 overflow-hidden flex-1 text-xs">
            <p class="text-[10px] text-slate-500">クリックすると地図上の吹き出し位置へズーム移動します。</p>
            <div id="left-callouts-list" class="overflow-y-auto space-y-1.5 pr-0.5 flex-1"></div>
        </div>
    </div>

    <!-- 全クリアボタン -->
    <div class="pt-2 border-t border-slate-100 shrink-0">
        <button type="button" id="btn-clear-cone-map"
            class="w-full py-1.5 px-2 bg-slate-100 hover:bg-red-50 hover:text-red-700 hover:border-red-300 text-slate-600 border border-slate-200 rounded-lg text-[11px] font-bold transition cursor-pointer"
            title="この白地図上のカラーコーン・バー・バリケード・吹き出しをすべて削除します">
            🗑️ 全配置をクリア
        </button>
    </div>
</aside>
