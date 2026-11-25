/**
 * カラーコーン・バリケード配置エディタ 共有コンテキスト
 */

export function parseJsonAttr(id, fallback) {
    const el = document.getElementById(id);
    if (!el || !el.dataset.json) return fallback;
    try {
        return JSON.parse(el.dataset.json);
    } catch (e) {
        console.error(`JSON parse failed for ${id}:`, e);
        return fallback;
    }
}

export function createConeMapContext(appEl) {
    const dom = {
        appEl,
        canvasContainer: document.getElementById('cone-map-canvas-container'),
        viewport: document.getElementById('cone-map-viewport'),
        svgContainer: document.getElementById('cone-map-svg-container'),
        loadingEl: document.getElementById('cone-canvas-loading'),
        marqueeBox: document.getElementById('cone-marquee-box'),
        inlineCalloutEditor: document.getElementById('cone-inline-callout-editor'),
        modeBanner: document.getElementById('cone-mode-banner'),
        hoverTooltip: document.getElementById('cone-hover-tooltip'),
        toastEl: document.getElementById('cone-editor-toast'),
        toastMsgEl: document.getElementById('cone-editor-toast-message'),
        toastUndoBtn: document.getElementById('cone-editor-toast-undo'),
        btnExportPdf: document.getElementById('btn-cone-export-pdf'),

        // ツールバー
        modeButtons: document.querySelectorAll('.cone-mode-btn'),
        btnModeSelect: document.getElementById('btn-cone-mode-select'),
        btnModePan: document.getElementById('btn-cone-mode-pan'),
        btnUndo: document.getElementById('btn-cone-undo'),
        btnRedo: document.getElementById('btn-cone-redo'),
        btnDuplicate: document.getElementById('btn-cone-duplicate'),
        btnRotate: document.getElementById('btn-cone-rotate'),
        btnDelete: document.getElementById('btn-cone-delete'),
        btnAlignLeft: document.getElementById('btn-cone-align-left'),
        btnAlignTop: document.getElementById('btn-cone-align-top'),
        btnAlign2m: document.getElementById('btn-cone-align-2m'),
        selectBaseMap: document.getElementById('select-cone-base-map'),
        btnToggleEmphasis: document.getElementById('btn-toggle-emphasis'),
        btnToggleBooths: document.getElementById('btn-toggle-booths'),
        btnToggleSnap: document.getElementById('btn-cone-toggle-snap'),
        btnZoomOut: document.getElementById('btn-cone-zoom-out'),
        btnZoomIn: document.getElementById('btn-cone-zoom-in'),
        btnZoom100: document.getElementById('btn-cone-zoom-100'),
        btnZoomFit: document.getElementById('btn-cone-zoom-fit'),
        zoomText: document.getElementById('cone-zoom-level-text'),
        saveStatusBadge: document.getElementById('cone-save-status-badge'),

        // 左ペイン
        tabBtnPalette: document.getElementById('tab-btn-cone-palette'),
        tabBtnCallouts: document.getElementById('tab-btn-cone-callouts'),
        tabPanelPalette: document.getElementById('tab-panel-cone-palette'),
        tabPanelCallouts: document.getElementById('tab-panel-cone-callouts'),
        leftCalloutCount: document.getElementById('left-callout-count'),
        leftCalloutsList: document.getElementById('left-callouts-list'),
        paletteConeColorBtns: document.querySelectorAll('.palette-cone-color-btn'),
        btnPaletteConeLine: document.getElementById('btn-palette-cone-line'),
        paletteBarStyle: document.getElementById('palette-bar-style'),
        palettePitchMode: document.getElementById('palette-pitch-mode'),
        paletteCalloutColorBtns: document.querySelectorAll('.palette-callout-color-btn'),
        paletteItems: document.querySelectorAll('.cone-palette-item'),
        btnClearAll: document.getElementById('btn-clear-cone-map'),

        // 右インスペクタ
        inspBadge: document.getElementById('cone-insp-badge'),
        inspEmpty: document.getElementById('cone-insp-empty'),
        inspConeForm: document.getElementById('cone-insp-cone-form'),
        inspBarForm: document.getElementById('cone-insp-bar-form'),
        inspBarricadeForm: document.getElementById('cone-insp-barricade-form'),
        inspCalloutForm: document.getElementById('cone-insp-callout-form'),
        inspMultiPanel: document.getElementById('cone-insp-multi-panel'),

        // サマリー
        sumConesCount: document.getElementById('sum-cones-count'),
        sumConesBreakdown: document.getElementById('sum-cones-breakdown'),
        sumWeightsCount: document.getElementById('sum-weights-count'),
        sumBarsCount: document.getElementById('sum-bars-count'),
        sumBarricadesTypeA: document.getElementById('sum-barricades-type-a'),
        sumBarricadesResinPipe: document.getElementById('sum-barricades-resin-pipe'),
        sumCalloutsCount: document.getElementById('sum-callouts-count'),
        inventoryList: document.getElementById('cone-inventory-list'),
        colorBreakdown: document.getElementById('cone-color-breakdown'),

        // コーンフォーム
        inspConeColorBtns: document.querySelectorAll('.insp-cone-color-btn'),
        inspConeLabel: document.getElementById('insp-cone-label'),
        inspConeConnectedBars: document.getElementById('insp-cone-connected-bars'),
        inspConeNotes: document.getElementById('insp-cone-notes'),
        btnConeAddCallout: document.getElementById('btn-cone-add-callout'),

        // バーフォーム
        inspBarStyle: document.getElementById('insp-bar-style'),
        inspBarSpanM: document.getElementById('insp-bar-span-m'),
        inspBarSpanWarning: document.getElementById('insp-bar-span-warning'),
        btnBarFit2m: document.getElementById('btn-bar-fit-2m'),
        inspBarNotes: document.getElementById('insp-bar-notes'),

        // バリケードフォーム
        inspBarricadeTitle: document.getElementById('insp-barricade-title'),
        inspBarricadeSpec: document.getElementById('insp-barricade-spec'),
        inspBarricadeType: document.getElementById('insp-barricade-type'),
        inspBarricadeRotation: document.getElementById('insp-barricade-rotation'),
        btnBarricadeRot15: document.getElementById('btn-barricade-rot-15'),
        btnBarricadeRot45: document.getElementById('btn-barricade-rot-45'),
        inspBarricadeLabel: document.getElementById('insp-barricade-label'),
        inspBarricadeNotes: document.getElementById('insp-barricade-notes'),
        btnBarricadeAddCallout: document.getElementById('btn-barricade-add-callout'),

        // 吹き出しフォーム
        inspCalloutTitle: document.getElementById('insp-callout-title'),
        inspCalloutBody: document.getElementById('insp-callout-body'),
        inspCalloutColorBtns: document.querySelectorAll('.insp-callout-color-btn'),
        inspCalloutFontSize: document.getElementById('insp-callout-font-size'),
        inspCalloutWidth: document.getElementById('insp-callout-width'),
        inspCalloutHeight: document.getElementById('insp-callout-height'),
        inspCalloutTargetWrap: document.getElementById('insp-callout-target-wrap'),
        inspCalloutTargetLabel: document.getElementById('insp-callout-target-label'),
        btnCalloutUnlink: document.getElementById('btn-callout-unlink'),

        // 複数選択
        inspMultiCount: document.getElementById('cone-insp-multi-count'),
        inspMultiColors: document.getElementById('cone-insp-multi-colors'),
        bulkConeColorBtns: document.querySelectorAll('.bulk-cone-color-btn'),
    };

    const config = {
        svgUrl: appEl.dataset.svgUrl,
        viewboxStr: appEl.dataset.viewbox || '0 0 842 1191',
        scaleRatio: parseFloat(appEl.dataset.scaleRatio) || 7.0866,
        canEdit: appEl.dataset.canEdit === '1',
        dataUrl: appEl.dataset.dataUrl,
        clearUrl: appEl.dataset.clearUrl,
        baseMapUpdateUrl: appEl.dataset.baseMapUpdateUrl,
        conesUrl: appEl.dataset.conesUrl,
        conesBulkUrl: appEl.dataset.conesBulkUrl,
        barsUrl: appEl.dataset.barsUrl,
        coneLinesUrl: appEl.dataset.coneLinesUrl,
        barricadesUrl: appEl.dataset.barricadesUrl,
        barricadesBulkUrl: appEl.dataset.barricadesBulkUrl,
        calloutsUrl: appEl.dataset.calloutsUrl,
        csrfToken: appEl.dataset.csrfToken,
    };

    const state = {
        scale: 1.0,
        x: 0,
        y: 0,
        svgWidth: 842,
        svgHeight: 1191,
        toolMode: 'select', // 'select' | 'pan' | 'place_cone' | 'place_cone_line' | 'connect_bar' | 'place_callout'
        spacePressed: false,
        emphasisMode: true,
        showBooths: false,
        snapEnabled: true,

        // パレット設定
        selectedConeColor: 'red',
        selectedBarStyle: 'yellow_black',
        selectedPitchMode: 'even_max_2m',
        selectedCalloutColor: 'amber',

        // データ
        cones: parseJsonAttr('initial-cones-data', []),
        bars: parseJsonAttr('initial-bars-data', []),
        barricades: parseJsonAttr('initial-barricades-data', []),
        callouts: parseJsonAttr('initial-callouts-data', []),
        boothPlacements: parseJsonAttr('initial-booth-placements-data', []),
        summary: parseJsonAttr('initial-summary-data', {}),

        // 選択状態 (Map<"cone:1" | "bar:1" | "barricade:1" | "callout:1", {kind, id}>)
        selectedItems: new Map(),

        // インタラクション一時状態
        isPanning: false,
        panStartX: 0,
        panStartY: 0,
        isMarquee: false,
        marqueeStartClientX: 0,
        marqueeStartClientY: 0,
        isMoving: false,
        moveStartSvgX: 0,
        moveStartSvgY: 0,
        moveOrigins: new Map(),
        calloutAnchorDragId: null,
        calloutResizeId: null,
        calloutResizeStartSvgX: 0,
        calloutResizeStartSvgY: 0,
        calloutResizeOrig: null,

        // 連結配置 / バー連結一時状態
        pendingLineStart: null, // {x, y, startConeId}
        pendingBarFirstConeId: null,

        // 履歴・デバウンス
        undoStack: [],
        redoStack: [],
        nudgeTimer: null,
        toastTimer: null,
    };

    const vbParts = config.viewboxStr.split(/\s+/).map(Number);
    if (vbParts.length === 4 && vbParts[2] > 0 && vbParts[3] > 0) {
        state.svgWidth = vbParts[2];
        state.svgHeight = vbParts[3];
    }

    return { dom, config, state };
}

export function selectionKey(kind, id) {
    return `${kind}:${id}`;
}

export function setSaveStatus(ctx, status) {
    const { saveStatusBadge } = ctx.dom;
    if (!saveStatusBadge) return;
    if (status === 'saving') {
        saveStatusBadge.textContent = '⏳ 保存中...';
        saveStatusBadge.className = 'px-2 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-lg font-bold text-[11px]';
    } else if (status === 'error') {
        saveStatusBadge.textContent = '⚠️ 保存エラー';
        saveStatusBadge.className = 'px-2 py-1 bg-red-50 text-red-700 border border-red-200 rounded-lg font-bold text-[11px]';
    } else {
        saveStatusBadge.textContent = '✓ 保存済';
        saveStatusBadge.className = 'px-2 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg font-bold text-[11px]';
    }
}

export function showToast(ctx, msg, undoCallback = null) {
    const { toastEl, toastMsgEl, toastUndoBtn } = ctx.dom;
    if (!toastEl || !toastMsgEl) return;
    toastMsgEl.textContent = msg;
    if (toastUndoBtn) {
        if (undoCallback) {
            toastUndoBtn.classList.remove('hidden');
            toastUndoBtn.onclick = () => {
                toastEl.classList.add('hidden');
                undoCallback();
            };
        } else {
            toastUndoBtn.classList.add('hidden');
        }
    }
    toastEl.classList.remove('hidden');
    clearTimeout(ctx.state.toastTimer);
    ctx.state.toastTimer = setTimeout(() => toastEl.classList.add('hidden'), 3500);
}
