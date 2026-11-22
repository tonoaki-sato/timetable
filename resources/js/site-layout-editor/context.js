/**
 * 白地図配置エディタ 共有コンテキスト (DOM参照・設定・エディタ状態・通知ヘルパー)
 */

/**
 * script要素等の data-json 属性を安全にパースする
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

/**
 * エディタ全体の共有コンテキストを生成する
 */
export function createEditorContext(appEl) {
    const dom = {
        appEl,
        canvasContainer: document.getElementById('site-map-canvas-container'),
        viewport: document.getElementById('site-map-viewport'),
        svgContainer: document.getElementById('site-map-svg-container'),
        loadingEl: document.getElementById('canvas-loading'),
        marqueeBox: document.getElementById('marquee-selection-box'),
        inlineEditor: document.getElementById('inline-text-editor'),
        hoverTooltip: document.getElementById('node-hover-tooltip'),
        toastEl: document.getElementById('editor-toast'),
        toastMsgEl: document.getElementById('editor-toast-message'),
        toastUndoBtn: document.getElementById('editor-toast-undo'),

        // 上部ツールバー
        btnModeSelect: document.getElementById('btn-mode-select'),
        btnModePan: document.getElementById('btn-mode-pan'),
        btnUndo: document.getElementById('btn-undo'),
        btnRedo: document.getElementById('btn-redo'),
        btnDuplicate: document.getElementById('btn-duplicate'),
        btnRotateSelected: document.getElementById('btn-rotate-selected'),
        btnDeleteSelected: document.getElementById('btn-delete-selected'),
        btnAlignLeft: document.getElementById('btn-align-left'),
        btnAlignTop: document.getElementById('btn-align-top'),
        btnPackVert: document.getElementById('btn-pack-vert'),
        btnPackHoriz: document.getElementById('btn-pack-horiz'),
        btnToggleHygiene: document.getElementById('btn-toggle-hygiene'),
        btnToggleSnap: document.getElementById('btn-toggle-snap'),
        btnZoomOut: document.getElementById('btn-zoom-out'),
        btnZoomIn: document.getElementById('btn-zoom-in'),
        btnZoom100: document.getElementById('btn-zoom-100'),
        btnZoomFit: document.getElementById('btn-zoom-fit'),
        zoomText: document.getElementById('zoom-level-text'),
        saveStatusBadge: document.getElementById('save-status-badge'),

        // 左ペイン
        tabBtnShapes: document.getElementById('tab-btn-shapes'),
        tabBtnItems: document.getElementById('tab-btn-items'),
        tabPanelShapes: document.getElementById('tab-panel-shapes'),
        tabPanelItems: document.getElementById('tab-panel-items'),
        sidebarSearch: document.getElementById('sidebar-item-search'),
        sidebarUnplacedOnly: document.getElementById('sidebar-item-unplaced-only'),
        btnResetPreset: document.getElementById('btn-reset-preset'),
        btnClearPlacements: document.getElementById('btn-clear-placements'),
        selectTimetableBaseMap: document.getElementById('select-timetable-base-map'),

        // 右インスペクタ
        inspBadge: document.getElementById('inspector-selection-badge'),
        inspEmpty: document.getElementById('inspector-empty-state'),
        inspSingle: document.getElementById('inspector-single-form'),
        inspMulti: document.getElementById('inspector-multi-panel'),
        inspShapeName: document.getElementById('insp-shape-name'),
        inspDisplayText: document.getElementById('insp-display-text'),
        inspColorNote: document.getElementById('insp-color-note'),
        inspColorBtns: document.querySelectorAll('.insp-color-btn'),
        inspRotation: document.getElementById('insp-rotation'),
        inspFontSize: document.getElementById('insp-font-size'),
        inspRotationLocked: document.getElementById('insp-rotation-locked'),
        inspWidthM: document.getElementById('insp-width-m'),
        inspHeightM: document.getElementById('insp-height-m'),
        inspWaterOptions: document.getElementById('insp-water-options'),
        inspShowHygieneCircle: document.getElementById('insp-show-hygiene-circle'),
        inspArcMode: document.getElementById('insp-arc-mode'),
        inspLayoutItemId: document.getElementById('insp-layout-item-id'),
        inspNotes: document.getElementById('insp-notes'),
        inspMultiCount: document.getElementById('insp-multi-count'),
        bulkColorBtns: document.querySelectorAll('.bulk-color-btn'),

        // サマリー＆衛生チェック
        sumTotal: document.getElementById('summary-total-count'),
        sumRed: document.getElementById('summary-red-count'),
        sumGreen: document.getElementById('summary-green-count'),
        sumWater: document.getElementById('summary-water-count'),
        hygieneStatus: document.getElementById('hygiene-check-status'),
        hygieneWarningList: document.getElementById('hygiene-warning-list'),

        // カスタムPNGアイコン管理モーダル＆パレット
        btnOpenCreateIconModal: document.getElementById('btn-open-create-icon-modal'),
        modalCreateIcon: document.getElementById('modal-create-icon'),
        formCreateIcon: document.getElementById('form-create-icon'),
        createIconDropzone: document.getElementById('create-icon-dropzone'),
        createIconFile: document.getElementById('create-icon-file'),
        createIconPrompt: document.getElementById('create-icon-prompt'),
        createIconPreviewWrap: document.getElementById('create-icon-preview-wrap'),
        createIconPreviewImg: document.getElementById('create-icon-preview-img'),
        createIconFilename: document.getElementById('create-icon-filename'),
        createIconWidth: document.getElementById('create-icon-width'),
        createIconHeight: document.getElementById('create-icon-height'),
        createIconScaleHint: document.getElementById('create-icon-scale-hint'),
        createIconErrorMsg: document.getElementById('create-icon-error-msg'),
        btnSubmitCreateIcon: document.getElementById('btn-submit-create-icon'),

        modalEditIcon: document.getElementById('modal-edit-icon'),
        formEditIcon: document.getElementById('form-edit-icon'),
        editIconId: document.getElementById('edit-icon-id'),
        editIconName: document.getElementById('edit-icon-name'),
        editIconCategory: document.getElementById('edit-icon-category'),
        editIconWidth: document.getElementById('edit-icon-width'),
        editIconHeight: document.getElementById('edit-icon-height'),
        editIconScaleHint: document.getElementById('edit-icon-scale-hint'),
        editIconRotatable: document.getElementById('edit-icon-rotatable'),
        editIconResizable: document.getElementById('edit-icon-resizable'),
        editIconHygiene: document.getElementById('edit-icon-hygiene'),
        editIconCurrentImg: document.getElementById('edit-icon-current-img'),
        editIconFile: document.getElementById('edit-icon-file'),
        editIconErrorMsg: document.getElementById('edit-icon-error-msg'),
        btnSubmitEditIcon: document.getElementById('btn-submit-edit-icon'),

        modalDeleteIcon: document.getElementById('modal-delete-icon'),
        deleteIconId: document.getElementById('delete-icon-id'),
        deleteIconImg: document.getElementById('delete-icon-img'),
        deleteIconNameText: document.getElementById('delete-icon-name-text'),
        deleteIconSizeText: document.getElementById('delete-icon-size-text'),
        deleteIconUsageWarning: document.getElementById('delete-icon-usage-warning'),
        deleteIconUsageCount: document.getElementById('delete-icon-usage-count'),
        deleteIconErrorMsg: document.getElementById('delete-icon-error-msg'),
        btnSubmitDeleteIcon: document.getElementById('btn-submit-delete-icon'),

        paletteShapesGrid: document.getElementById('palette-shapes-grid'),
    };

    const config = {
        svgUrl: appEl.dataset.svgUrl,
        viewboxStr: appEl.dataset.viewbox || '0 0 842 1191',
        scaleRatio: parseFloat(appEl.dataset.scaleRatio) || 7.0866,
        storeUrl: appEl.dataset.storeUrl,
        bulkUrl: appEl.dataset.bulkUrl,
        resetUrl: appEl.dataset.resetUrl,
        clearUrl: appEl.dataset.clearUrl,
        baseMapUpdateUrl: appEl.dataset.baseMapUpdateUrl,
        iconStoreUrl: appEl.dataset.iconStoreUrl,
        iconBaseUrl: appEl.dataset.iconBaseUrl,
        csrfToken: appEl.dataset.csrfToken,
    };

    const initialPlacements = parseJsonAttr('initial-placements-data', []);
    const iconDefsList = parseJsonAttr('icon-definitions-data', []);
    const iconDefinitions = {};
    iconDefsList.forEach((d) => {
        iconDefinitions[d.icon_key] = d;
    });

    const state = {
        scale: 1.0,
        x: 0,
        y: 0,
        svgWidth: 842,
        svgHeight: 1191,
        toolMode: 'select', // 'select' | 'pan'
        spacePressed: false,
        showHygiene: true,
        snapEnabled: true,
        placements: initialPlacements,
        selectedIds: new Set(),
        // インタラクション状態
        isPanning: false,
        panStartX: 0,
        panStartY: 0,
        isMarquee: false,
        marqueeStartClientX: 0,
        marqueeStartClientY: 0,
        isMoving: false,
        moveStartSvgX: 0,
        moveStartSvgY: 0,
        moveOrigPositions: new Map(), // id -> {x, y, rot, locked}
        isResizing: false,
        resizeHandle: null,
        resizeId: null,
        resizeStartSvgX: 0,
        resizeStartSvgY: 0,
        resizeOrig: null,
        // 履歴・デバウンス・通知
        undoStack: [],
        redoStack: [],
        nudgeTimer: null,
        inspTimer: null,
        inspDisplayTextBefore: null,
        toastTimer: null,
    };

    const vbParts = config.viewboxStr.split(/\s+/).map(Number);
    if (vbParts.length === 4 && vbParts[2] > 0 && vbParts[3] > 0) {
        state.svgWidth = vbParts[2];
        state.svgHeight = vbParts[3];
    }

    return { dom, config, state, iconDefinitions };
}

/**
 * 右上保存ステータスバッジを更新する
 */
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

/**
 * エディタ下部のトースト通知を表示する
 */
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
