<!-- 上部ツールバー -->
<div class="bg-white border border-slate-200 rounded-xl px-2.5 py-2 shadow-2xs flex flex-wrap items-center justify-between gap-2 text-xs">
    <!-- 左側: ツールモード / 履歴 / 編集 / 整列 -->
    <div class="flex flex-wrap items-center gap-1.5">
        <!-- 操作モード切替 -->
        <div class="inline-flex flex-wrap rounded-lg border border-slate-300 bg-slate-50 p-0.5 gap-0.5">
            <button type="button" id="btn-cone-mode-select"
                class="cone-mode-btn px-2 py-1 rounded-md font-bold bg-indigo-600 text-white transition cursor-pointer"
                data-mode="select"
                title="選択・移動モード (Vキー / 右クリック)">
                ↖ 選択
            </button>
            <button type="button" id="btn-cone-mode-pan"
                class="cone-mode-btn px-2 py-1 rounded-md font-bold text-slate-700 hover:bg-slate-200 transition cursor-pointer"
                data-mode="pan"
                title="手のひら移動モード (Hキー / Space押下 / 右クリック)">
                ✋ 手のひら
            </button>
            <button type="button" id="btn-cone-mode-cone"
                class="cone-mode-btn px-2 py-1 rounded-md font-bold text-slate-700 hover:bg-slate-200 transition cursor-pointer"
                data-mode="place_cone"
                title="クリックした位置にカラーコーン（＋ウェイト）を単独配置 (Cキー)">
                🔺 単独コーン
            </button>
            <button type="button" id="btn-cone-mode-cone-line"
                class="cone-mode-btn px-2 py-1 rounded-md font-bold text-slate-700 hover:bg-slate-200 transition cursor-pointer"
                data-mode="place_cone_line"
                title="始点と終点をクリックしてカラーコーン列＋2mコーンバーを一括配置 (Bキー)">
                🚧 コーン連結
            </button>
            <button type="button" id="btn-cone-mode-connect-bar"
                class="cone-mode-btn px-2 py-1 rounded-md font-bold text-slate-700 hover:bg-slate-200 transition cursor-pointer"
                data-mode="connect_bar"
                title="既存の2個のカラーコーンをクリックしてコーンバーで連結 (Lキー)">
                🔗 バー連結
            </button>
            <button type="button" id="btn-cone-mode-callout"
                class="cone-mode-btn px-2 py-1 rounded-md font-bold text-slate-700 hover:bg-slate-200 transition cursor-pointer"
                data-mode="place_callout"
                title="クリックした位置に吹き出し（注意事項）を配置 (Nキー)">
                💬 吹き出し
            </button>
        </div>

        <div class="h-4 w-px bg-slate-300"></div>

        <!-- Undo / Redo -->
        <button type="button" id="btn-cone-undo" disabled
            class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
            title="元に戻す (Ctrl+Z)">
            ↩ 戻す
        </button>
        <button type="button" id="btn-cone-redo" disabled
            class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
            title="やり直し (Ctrl+Y)">
            ↪ やり直し
        </button>

        <div class="h-4 w-px bg-slate-300"></div>

        <!-- 複製 / 回転 / 削除 -->
        <button type="button" id="btn-cone-duplicate" disabled
            class="px-2 py-1 bg-white border border-slate-300 hover:bg-indigo-50 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
            title="複製 (Ctrl+D)">
            📄 複製
        </button>
        <button type="button" id="btn-cone-rotate" disabled
            class="px-2 py-1 bg-white border border-slate-300 hover:bg-indigo-50 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
            title="バリケードを45°回転 (Rキー / Shift+Rで15°)">
            ↻ 45°
        </button>
        <button type="button" id="btn-cone-delete" disabled
            class="px-2 py-1 bg-red-50 border border-red-200 hover:bg-red-600 hover:text-white text-red-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
            title="選択要素を削除 (Deleteキー)">
            🗑️ 削除
        </button>

        <div class="h-4 w-px bg-slate-300"></div>

        <!-- 一括整列 (コーン2個以上選択時) -->
        <div class="inline-flex items-center space-x-1" title="コーン2個以上選択時に一括整列">
            <button type="button" id="btn-cone-align-left" disabled
                class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                title="X座標を左揃え">
                ⫷ 左揃
            </button>
            <button type="button" id="btn-cone-align-top" disabled
                class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                title="Y座標を上揃え">
                ⊤ 上揃
            </button>
            <button type="button" id="btn-cone-align-2m" disabled
                class="px-2 py-1 bg-white border border-slate-300 hover:bg-indigo-50 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                title="選択したコーンを2.00m等間隔に整列">
                📏 2m整列
            </button>
        </div>
    </div>

    <!-- 右側: 白地図 / 表示切替 / ブース参考 / 吸着 / ズーム / 保存状態 -->
    <div class="flex flex-wrap items-center gap-1.5">
        @can('update', App\Models\SiteBaseMap::class)
            <div class="inline-flex items-center space-x-1 bg-slate-50 border border-slate-300 rounded-lg px-2 py-0.5" title="使用する白地図マスターを切り替え">
                <span class="text-[11px] font-bold text-slate-600 shrink-0">🗺️</span>
                <select id="select-cone-base-map"
                    data-current-base-map-id="{{ $siteBaseMap->id }}"
                    aria-label="使用白地図マスター"
                    class="bg-transparent text-xs font-bold text-slate-800 focus:outline-none cursor-pointer max-w-[160px] sm:max-w-[200px]">
                    @foreach($availableBaseMaps as $bm)
                        <option value="{{ $bm->id }}" {{ $bm->id === $siteBaseMap->id ? 'selected' : '' }}>
                            {{ $bm->name }}@if(!$bm->is_active)（無効）@endif
                        </option>
                    @endforeach
                </select>
            </div>
        @else
            <span class="inline-flex items-center space-x-1 px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-[11px] font-bold text-slate-700">
                <span>🗺️</span>
                <span>{{ $siteBaseMap->name }}</span>
            </span>
        @endcan

        <button type="button" id="btn-toggle-emphasis"
            class="px-2.5 py-1 bg-indigo-600 text-white border border-indigo-600 rounded-lg font-bold shadow-2xs transition cursor-pointer"
            title="視認強調表示とA3 1/400厳密実寸表示を切り替え">
            🔍 表示: 視認強調
        </button>
        <button type="button" id="btn-toggle-booths"
            class="px-2.5 py-1 bg-white text-slate-600 border border-slate-300 rounded-lg font-bold shadow-2xs transition cursor-pointer"
            title="白地図エディタのブース配置を半透明参考レイヤーとして重ね表示">
            🎪 ブース参考 OFF
        </button>
        <button type="button" id="btn-cone-toggle-snap"
            class="px-2.5 py-1 bg-emerald-600 text-white border border-emerald-600 rounded-lg font-bold shadow-2xs transition cursor-pointer"
            title="歩道レール・他コーン中心・2m間隔スナップ ON/OFF">
            🧲 吸着 ON
        </button>

        <div class="h-4 w-px bg-slate-300"></div>

        <button type="button" id="btn-cone-zoom-out" class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 rounded-lg font-bold cursor-pointer" title="縮小">－</button>
        <span id="cone-zoom-level-text" class="px-2 py-1 font-mono font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-lg min-w-[50px] text-center">100%</span>
        <button type="button" id="btn-cone-zoom-in" class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 rounded-lg font-bold cursor-pointer" title="拡大">＋</button>
        <button type="button" id="btn-cone-zoom-100" class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 rounded-lg font-bold cursor-pointer" title="等倍">1:1</button>
        <button type="button" id="btn-cone-zoom-fit" class="px-2 py-1 bg-white border border-slate-300 hover:bg-indigo-600 hover:text-white rounded-lg font-bold cursor-pointer" title="全体表示">🔲 全体</button>

        <span id="cone-save-status-badge" class="px-2 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg font-bold text-[11px]">
            ✓ 保存済
        </span>
    </div>
</div>
