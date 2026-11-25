<x-layouts.app :title="$timetable->name . ' カラーコーン・バリケード配置エディタ (A3 1/400)'">
<div class="max-w-[1600px] mx-auto px-3 sm:px-5 py-3 space-y-2.5">

    <!-- ページヘッダー -->
    <x-page-header
        :title="$timetable->name . ' カラーコーン・バリケード配置エディタ'"
        icon="🔺"
        :description="'カラーコーン（＋ウェイト）・コーンバー連結・A型/樹脂製単管バリケード・吹き出し注意事項をA3 1/400図面上で配置管理します'">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>⏱️</span>
                <span>運行情報一覧</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>📅</span>
                <span>{{ $timetable->name }}</span>
            </a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('timetables.layouts.index', $timetable) }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>🗺️</span>
                <span>配置計画</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">🔺 カラーコーン・バリケード配置</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button variant="header-action" id="btn-cone-export-pdf"
                :href="route('timetables.cone-maps.pdf', ['timetable' => $timetable, 'emphasis' => 1, 'show_booths' => 0])"
                data-base-pdf-url="{{ route('timetables.cone-maps.pdf', $timetable) }}">
                <span>📥</span>
                <span>PDFダウンロード (A3 1/400)</span>
            </x-ui.button>
            <x-ui.button variant="header-action" :href="route('timetables.site-maps.editor', $timetable)">
                <span>🎨</span>
                <span>白地図エディタ（ブース）</span>
            </x-ui.button>
            <x-ui.button variant="header-action" :href="route('timetables.layouts.index', $timetable)">
                <span>📊</span>
                <span>配置計画サマリー</span>
            </x-ui.button>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="cone-maps" />

    <!-- エディタ全体コンテナ -->
    <div id="cone-map-editor-app"
        class="flex flex-col space-y-2"
        data-base-map-id="{{ $siteBaseMap->id }}"
        data-svg-url="{{ asset($siteBaseMap->svg_path) }}"
        data-scale-ratio="{{ $siteBaseMap->scale_ratio }}"
        data-viewbox="{{ $siteBaseMap->viewbox }}"
        data-can-edit="{{ auth()->user()?->can('update', App\Models\SiteBaseMap::class) ? '1' : '0' }}"
        data-data-url="{{ route('timetables.cone-maps.data.index', $timetable) }}"
        data-clear-url="{{ route('timetables.cone-maps.clear', $timetable) }}"
        data-base-map-update-url="{{ route('timetables.cone-maps.base-map.update', $timetable) }}"
        data-cones-url="{{ route('timetables.cone-maps.cones.store', $timetable) }}"
        data-cones-bulk-url="{{ route('timetables.cone-maps.cones.bulk-update', $timetable) }}"
        data-bars-url="{{ route('timetables.cone-maps.bars.store', $timetable) }}"
        data-cone-lines-url="{{ route('timetables.cone-maps.cone-lines.store', $timetable) }}"
        data-barricades-url="{{ route('timetables.cone-maps.barricades.store', $timetable) }}"
        data-barricades-bulk-url="{{ route('timetables.cone-maps.barricades.bulk-update', $timetable) }}"
        data-callouts-url="{{ route('timetables.cone-maps.callouts.store', $timetable) }}"
        data-csrf-token="{{ csrf_token() }}">

        @include('cone_maps.partials.editor-toolbar', [
            'siteBaseMap' => $siteBaseMap,
            'availableBaseMaps' => $availableBaseMaps,
        ])

        <!-- 3ペイン・メインワークスペース -->
        <div class="flex flex-col lg:flex-row gap-2.5 items-stretch">

            @include('cone_maps.partials.editor-left-pane')

            <!-- 2. 中央ペイン: 白地図SVGベクターキャンバス -->
            <main class="flex-1 min-w-0 relative bg-slate-200/80 border border-slate-300 rounded-2xl shadow-inner overflow-hidden h-[78vh] min-h-[600px]">
                <div id="cone-map-canvas-container" class="relative w-full h-full overflow-hidden select-none cursor-default">
                    <!-- ローディング -->
                    <div id="cone-canvas-loading" class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-white/90 backdrop-blur-xs transition-opacity duration-300">
                        <div class="w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                        <p class="mt-3 text-xs font-bold text-slate-600">A3 1/400 ベクター白地図を読み込み中...</p>
                    </div>

                    <!-- ベクター直接スケーリング・ビューポート -->
                    <div id="cone-map-viewport" class="absolute top-0 left-0 origin-top-left">
                        <div id="cone-map-svg-container" class="relative inline-block shadow-xl bg-white antialiased"></div>
                    </div>

                    <!-- 範囲選択ラバーバンド矩形 -->
                    <div id="cone-marquee-box" class="hidden absolute z-30 border-2 border-indigo-600 bg-indigo-500/15 pointer-events-none"></div>

                    <!-- インライン吹き出し本文入力オーバーレイ -->
                    <textarea id="cone-inline-callout-editor" rows="3"
                        class="hidden absolute z-40 px-2 py-1 text-xs font-bold bg-white border-2 border-amber-500 rounded-lg shadow-lg focus:outline-none resize-none"></textarea>
                </div>

                <!-- 連結プレビュー／操作ヒントバナー -->
                <div id="cone-mode-banner" class="hidden absolute top-3 left-1/2 -translate-x-1/2 z-30 px-3.5 py-1.5 bg-indigo-900/90 text-white rounded-xl text-xs font-bold shadow-lg pointer-events-none"></div>

                <!-- ホバーツールチップ -->
                <div id="cone-hover-tooltip" class="hidden absolute z-30 px-2.5 py-1.5 bg-slate-900/90 text-white rounded-lg text-[11px] shadow-lg pointer-events-none max-w-xs"></div>

                <!-- トースト通知 -->
                <div id="cone-editor-toast" class="hidden absolute bottom-10 left-1/2 -translate-x-1/2 z-40 px-4 py-2 bg-slate-900/90 text-white rounded-xl text-xs font-bold shadow-xl flex items-center space-x-3">
                    <span id="cone-editor-toast-message"></span>
                    <button type="button" id="cone-editor-toast-undo" class="hidden text-indigo-300 hover:text-indigo-200 underline cursor-pointer">元に戻す</button>
                </div>

                <!-- ヘルプチップ -->
                <div class="absolute bottom-2.5 right-3 z-10 px-2.5 py-1 bg-slate-900/75 text-white rounded-lg text-[10px] font-medium pointer-events-none flex items-center space-x-2">
                    <span>右クリック: 選択/手のひら切替</span>
                    <span class="text-slate-400">|</span>
                    <span>R: 45°回転 (Shift+R: 15°)</span>
                    <span class="text-slate-400">|</span>
                    <span>Ctrl+D: 複製</span>
                    <span class="text-slate-400">|</span>
                    <span>Space+ドラッグ: 移動</span>
                    <span class="text-slate-400">|</span>
                    <span>矢印: 微調整</span>
                </div>
            </main>

            @include('cone_maps.partials.editor-inspector-pane')
        </div>
    </div>
</div>

{{-- 初期データの安全な埋め込み (JSON) --}}
<div id="initial-cones-data" class="hidden" data-json="{{ json_encode($cones) }}"></div>
<div id="initial-bars-data" class="hidden" data-json="{{ json_encode($bars) }}"></div>
<div id="initial-barricades-data" class="hidden" data-json="{{ json_encode($barricades) }}"></div>
<div id="initial-callouts-data" class="hidden" data-json="{{ json_encode($callouts) }}"></div>
<div id="initial-booth-placements-data" class="hidden" data-json="{{ json_encode($boothPlacements) }}"></div>
<div id="initial-summary-data" class="hidden" data-json="{{ json_encode($summary->toArray()) }}"></div>

{{-- 厳格な CSP 対応: 専用スクリプトをスロット末尾で @vite 経由読み込み --}}
@vite(['resources/js/cone-map-editor.js'])
</x-layouts.app>
