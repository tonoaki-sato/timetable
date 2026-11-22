<!-- 1. 左ペイン: 図形スタンプ & 台帳リスト -->
<aside class="w-full lg:w-60 bg-white border border-slate-200 rounded-2xl p-2.5 shadow-2xs shrink-0 flex flex-col justify-between space-y-2.5 max-h-[78vh]">
    <div class="flex flex-col space-y-2 overflow-hidden flex-1">
        <!-- タブ切替 -->
        <div class="grid grid-cols-2 gap-1 bg-slate-100 p-1 rounded-xl shrink-0">
            <button type="button" id="tab-btn-shapes"
                class="py-1 rounded-lg text-xs font-bold bg-white text-indigo-700 shadow-2xs transition cursor-pointer">
                🎨 図形パレット
            </button>
            <button type="button" id="tab-btn-items"
                class="py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition cursor-pointer">
                📋 台帳 ({{ $layoutItems->count() }})
            </button>
        </div>

        <!-- タブ1: CAD図形パレット -->
        <div id="tab-panel-shapes" class="overflow-y-auto pr-0.5 space-y-2 flex-1">
            <div class="flex items-center justify-between gap-1 pb-1 border-b border-slate-100">
                <span class="text-[10px] font-bold text-slate-500">CAD・画像シンボル</span>
                <button type="button" id="btn-open-create-icon-modal"
                    class="px-2 py-0.5 bg-white border border-slate-300 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 text-slate-700 rounded-md text-[10px] font-bold shadow-2xs transition flex items-center space-x-1 cursor-pointer"
                    title="PNG画像から新しいアイコンを生成・追加">
                    <span>➕</span>
                    <span>PNGアイコン生成</span>
                </button>
            </div>
            <p class="text-[10px] text-slate-500 leading-snug">
                図形を白地図へドラッグ＆ドロップで配置（A3 1/400 実寸ベクター）
            </p>
            <div id="palette-shapes-grid" class="grid grid-cols-2 gap-1.5">
                @foreach($iconDefinitions as $def)
                    <div class="palette-item group relative p-2 bg-slate-50 hover:bg-indigo-50/70 border border-slate-200 hover:border-indigo-300 rounded-xl cursor-grab active:cursor-grabbing transition flex flex-col items-center text-center select-none"
                        draggable="true"
                        data-shape-type="{{ $def->icon_key }}"
                        data-default-width="{{ $def->default_width }}"
                        data-default-height="{{ $def->default_height }}"
                        data-default-color="{{ $def->default_color }}"
                        data-name="{{ $def->name }}"
                        data-category="{{ $def->category }}"
                        data-is-preset="{{ $def->is_preset ? '1' : '0' }}"
                        data-icon-id="{{ $def->id }}"
                        data-render-style="{{ $def->render_style }}"
                        data-asset-path="{{ $def->icon_asset_path ? asset($def->icon_asset_path) : '' }}"
                        data-is-rotatable="{{ $def->is_rotatable ? '1' : '0' }}"
                        data-is-resizable="{{ $def->is_resizable ? '1' : '0' }}"
                        data-rotation-step="{{ $def->rotation_step }}"
                        data-has-hygiene-circle="{{ $def->has_hygiene_circle ? '1' : '0' }}">
                        @if(! $def->is_preset)
                            <div class="absolute top-1 right-1 hidden group-hover:flex items-center space-x-0.5 z-10">
                                <button type="button" class="btn-edit-custom-icon px-1 py-0.5 bg-white/90 hover:bg-amber-100 border border-amber-300 text-amber-700 rounded text-[9px] font-bold shadow-2xs transition cursor-pointer" title="編集" data-icon-id="{{ $def->id }}">✏️</button>
                                <button type="button" class="btn-delete-custom-icon px-1 py-0.5 bg-white/90 hover:bg-red-100 border border-red-300 text-red-700 rounded text-[9px] font-bold shadow-2xs transition cursor-pointer" title="削除" data-icon-id="{{ $def->id }}">🗑️</button>
                            </div>
                        @endif
                        <div class="w-9 h-7 flex items-center justify-center pointer-events-none mb-1">
                            @if($def->render_style === 'image' && $def->icon_asset_path)
                                <img src="{{ asset($def->icon_asset_path) }}" alt="{{ $def->name }}" class="max-w-full max-h-full object-contain">
                            @elseif($def->icon_key === 'water_supply')
                                <svg viewBox="0 0 24 24" class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18"/>
                                    <rect x="6" y="6" width="12" height="12"/>
                                    <line x1="12" y1="6" x2="12" y2="18"/>
                                    <line x1="6" y1="12" x2="18" y2="12"/>
                                </svg>
                            @elseif($def->icon_key === 'kitchen_car')
                                <svg viewBox="0 0 32 20" class="w-8 h-5 text-orange-500" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="3" width="28" height="14"/>
                                    <line x1="8" y1="3" x2="8" y2="17"/>
                                </svg>
                            @elseif($def->icon_key === 'free_area')
                                <svg viewBox="0 0 32 22" class="w-8 h-5 text-slate-700" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="2" y="2" width="28" height="18" stroke-dasharray="3 2"/>
                                    <line x1="2" y1="16" x2="16" y2="2" stroke-width="0.8"/>
                                    <line x1="8" y1="20" x2="26" y2="2" stroke-width="0.8"/>
                                    <line x1="18" y1="20" x2="30" y2="8" stroke-width="0.8"/>
                                </svg>
                            @elseif($def->icon_key === 'area_note')
                                <svg viewBox="0 0 32 20" class="w-7 h-5 text-slate-600" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <line x1="4" y1="6" x2="28" y2="6"/>
                                    <line x1="4" y1="12" x2="22" y2="12"/>
                                </svg>
                            @else
                                <svg viewBox="0 0 28 24" class="w-7 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <rect x="2" y="2" width="24" height="20"/>
                                    <line x1="2" y1="2" x2="8" y2="8" stroke-width="1"/>
                                    <line x1="26" y1="2" x2="20" y2="8" stroke-width="1"/>
                                    <line x1="2" y1="22" x2="8" y2="16" stroke-width="1"/>
                                    <line x1="26" y1="22" x2="20" y2="16" stroke-width="1"/>
                                    <line x1="8" y1="12" x2="20" y2="12" stroke-width="1"/>
                                </svg>
                            @endif
                        </div>
                        <span class="text-[10px] font-bold text-slate-700 group-hover:text-indigo-700 leading-tight pointer-events-none truncate w-full">
                            {{ $def->name }}
                        </span>
                        <span class="text-[9px] text-slate-400 font-mono mt-0.5 pointer-events-none">
                            {{ round($def->default_width / $siteBaseMap->scale_ratio, 1) }}m×{{ round($def->default_height / $siteBaseMap->scale_ratio, 1) }}m
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- タブ2: ブース・設備台帳リスト -->
        <div id="tab-panel-items" class="hidden flex-col space-y-2 overflow-hidden flex-1">
            <input type="text" id="sidebar-item-search"
                class="w-full px-2.5 py-1 text-xs bg-slate-50 border border-slate-300 rounded-lg focus:ring-1 focus:ring-indigo-500"
                placeholder="コマ番号・店舗名で検索...">
            <label class="flex items-center space-x-1.5 text-[11px] text-slate-600 font-bold cursor-pointer">
                <input type="checkbox" id="sidebar-item-unplaced-only" class="rounded border-slate-300 text-indigo-600">
                <span>未配置のみ表示</span>
            </label>
            <div id="sidebar-layout-items-list" class="overflow-y-auto space-y-1 pr-0.5 flex-1">
                @foreach($layoutItems as $item)
                    <div class="sidebar-layout-item p-1.5 bg-slate-50 hover:bg-indigo-50 border border-slate-200 rounded-lg cursor-grab transition text-[11px]"
                        draggable="true"
                        data-layout-item-id="{{ $item->id }}"
                        data-code="{{ $item->code }}"
                        data-name="{{ $item->presenter()->displayName() }}"
                        data-item-type="{{ $item->item_type?->value ?? 'booth' }}"
                        data-vendor="{{ $item->vendor?->name ?? '' }}">
                        <div class="flex items-center justify-between">
                            <span class="font-bold font-mono text-slate-800">{{ $item->code }}</span>
                            <span class="item-status-badge text-[9px] px-1.5 py-0.2 rounded font-bold bg-slate-200 text-slate-600">未配置</span>
                        </div>
                        <div class="text-[10px] text-slate-600 truncate">{{ $item->presenter()->displayName() }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- 見本配置へ復元 & 全クリアボタン -->
    <div class="pt-2 border-t border-slate-100 shrink-0 space-y-1">
        <button type="button" id="btn-reset-preset"
            class="w-full py-1.5 px-2 bg-slate-100 hover:bg-amber-50 hover:text-amber-800 hover:border-amber-300 text-slate-600 border border-slate-200 rounded-lg text-[11px] font-bold transition cursor-pointer"
            title="添付PDFと同じ標準見本配置（全111件）を読み込みます">
            🔄 標準見本配置 (260927) に復元
        </button>
        <button type="button" id="btn-clear-placements"
            class="w-full py-1.5 px-2 bg-slate-100 hover:bg-red-50 hover:text-red-700 hover:border-red-300 text-slate-600 border border-slate-200 rounded-lg text-[11px] font-bold transition cursor-pointer"
            title="現在の全配置オブジェクトを削除し、白地図のみの初期状態に戻します">
            🗑️ 全配置をクリア
        </button>
    </div>
</aside>
