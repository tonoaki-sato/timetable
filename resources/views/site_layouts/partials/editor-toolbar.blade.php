<!-- 上部ドローツール・アクションバー -->
<div class="bg-white border border-slate-200 rounded-xl px-2.5 py-2 shadow-2xs flex flex-wrap items-center justify-between gap-2 text-xs">
    <!-- 左側: ツールモード / 履歴 / オブジェクト操作 / 整列 -->
    <div class="flex flex-wrap items-center gap-1.5">
        <!-- モード切替 -->
        <div class="inline-flex rounded-lg border border-slate-300 bg-slate-50 p-0.5">
            <button type="button" id="btn-mode-select"
                class="px-2 py-1 rounded-md font-bold bg-indigo-600 text-white transition cursor-pointer"
                title="選択・移動モード (Vキー / 右クリック)">
                ↖ 選択
            </button>
            <button type="button" id="btn-mode-pan"
                class="px-2 py-1 rounded-md font-bold text-slate-700 hover:bg-slate-200 transition cursor-pointer"
                title="手のひら移動モード (Hキー / Space押下 / 右クリック)">
                ✋ 手のひら
            </button>
        </div>

        <div class="h-4 w-px bg-slate-300"></div>

        <!-- Undo / Redo -->
        <button type="button" id="btn-undo" disabled
            class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
            title="元に戻す (Ctrl+Z)">
            ↩ 戻す
        </button>
        <button type="button" id="btn-redo" disabled
            class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
            title="やり直し (Ctrl+Y)">
            ↪ やり直し
        </button>

        <div class="h-4 w-px bg-slate-300"></div>

        <!-- 複製 / 回転 / 削除 -->
        <button type="button" id="btn-duplicate" disabled
            class="px-2 py-1 bg-white border border-slate-300 hover:bg-indigo-50 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
            title="連番複製 (Ctrl+D)">
            📄 複製
        </button>
        <button type="button" id="btn-rotate-selected" disabled
            class="px-2 py-1 bg-white border border-slate-300 hover:bg-indigo-50 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
            title="45°回転 (Rキー)">
            ↻ 45°
        </button>
        <button type="button" id="btn-delete-selected" disabled
            class="px-2 py-1 bg-red-50 border border-red-200 hover:bg-red-600 hover:text-white text-red-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
            title="削除 (Deleteキー)">
            🗑️ 削除
        </button>

        <div class="h-4 w-px bg-slate-300"></div>

        <!-- 一括整列 (2個以上選択時有効) -->
        <div class="inline-flex items-center space-x-1" title="2個以上選択時に一括整列">
            <button type="button" id="btn-align-left" disabled
                class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                title="左揃え">
                ⫷ 左揃
            </button>
            <button type="button" id="btn-align-top" disabled
                class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                title="上揃え">
                ⊤ 上揃
            </button>
            <button type="button" id="btn-pack-vert" disabled
                class="px-2 py-1 bg-white border border-slate-300 hover:bg-indigo-50 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                title="縦に隙間なく密着連結">
                ⇕ 縦密着
            </button>
            <button type="button" id="btn-pack-horiz" disabled
                class="px-2 py-1 bg-white border border-slate-300 hover:bg-indigo-50 text-slate-700 rounded-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                title="横に隙間なく密着連結">
                ⇔ 横密着
            </button>
        </div>
    </div>

    <!-- 右側: 使用白地図 / 衛生20m圏 / スナップ / ズーム / 保存状態 -->
    <div class="flex flex-wrap items-center gap-1.5">
        @can('update', App\Models\SiteBaseMap::class)
            <div class="inline-flex items-center space-x-1 bg-slate-50 border border-slate-300 rounded-lg px-2 py-0.5" title="この運行日程で使用する白地図マスターを切り替え">
                <span class="text-[11px] font-bold text-slate-600 shrink-0">🗺️</span>
                <select id="select-timetable-base-map"
                    data-current-base-map-id="{{ $siteBaseMap->id }}"
                    aria-label="使用白地図マスター"
                    class="bg-transparent text-xs font-bold text-slate-800 focus:outline-none cursor-pointer max-w-[180px] sm:max-w-[220px]">
                    @foreach($availableBaseMaps as $bm)
                        <option value="{{ $bm->id }}" {{ $bm->id === $siteBaseMap->id ? 'selected' : '' }}>
                            {{ $bm->name }}@if(!$bm->is_active)（無効）@endif
                        </option>
                    @endforeach
                </select>
            </div>
        @else
            <span id="current-base-map-badge" class="inline-flex items-center space-x-1 px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-[11px] font-bold text-slate-700">
                <span>🗺️</span>
                <span>{{ $siteBaseMap->name }}</span>
            </span>
        @endcan

        <button type="button" id="btn-toggle-hygiene"
            class="px-2.5 py-1 bg-sky-600 text-white border border-sky-600 rounded-lg font-bold shadow-2xs transition cursor-pointer"
            title="給水設備の半径20m衛生チェック円を表示/非表示">
            💧 20m衛生圏 ON
        </button>
        <button type="button" id="btn-toggle-snap"
            class="px-2.5 py-1 bg-emerald-600 text-white border border-emerald-600 rounded-lg font-bold shadow-2xs transition cursor-pointer"
            title="歩道レール・隣接ブースへの自動吸着ON/OFF">
            🧲 吸着 ON
        </button>

        <div class="h-4 w-px bg-slate-300"></div>

        <button type="button" id="btn-zoom-out" class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 rounded-lg font-bold cursor-pointer" title="縮小">－</button>
        <span id="zoom-level-text" class="px-2 py-1 font-mono font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-lg min-w-[50px] text-center">100%</span>
        <button type="button" id="btn-zoom-in" class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 rounded-lg font-bold cursor-pointer" title="拡大">＋</button>
        <button type="button" id="btn-zoom-100" class="px-2 py-1 bg-white border border-slate-300 hover:bg-slate-100 rounded-lg font-bold cursor-pointer" title="等倍">1:1</button>
        <button type="button" id="btn-zoom-fit" class="px-2 py-1 bg-white border border-slate-300 hover:bg-indigo-600 hover:text-white rounded-lg font-bold cursor-pointer" title="全体表示">🔲 全体</button>

        <span id="save-status-badge" class="px-2 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg font-bold text-[11px]">
            ✓ 保存済
        </span>
    </div>
</div>
