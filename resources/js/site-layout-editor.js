/**
 * 白地図配置エディタ (Site Layout Editor) - 3ペイン CADベクタードローツール
 * A3 1/400 (842pt × 1191pt) 精密ベクター編集・スマートスナップ・20m衛生チェック・PDF出力連動
 */

document.addEventListener('DOMContentLoaded', () => {
    const appEl = document.getElementById('site-layout-editor-app');
    if (!appEl) return;

    // --- DOM参照 ---
    const canvasContainer = document.getElementById('site-map-canvas-container');
    const viewport = document.getElementById('site-map-viewport');
    const svgContainer = document.getElementById('site-map-svg-container');
    const loadingEl = document.getElementById('canvas-loading');
    const marqueeBox = document.getElementById('marquee-selection-box');
    const inlineEditor = document.getElementById('inline-text-editor');
    const hoverTooltip = document.getElementById('node-hover-tooltip');
    const toastEl = document.getElementById('editor-toast');
    const toastMsgEl = document.getElementById('editor-toast-message');
    const toastUndoBtn = document.getElementById('editor-toast-undo');

    // 上部ツールバー
    const btnModeSelect = document.getElementById('btn-mode-select');
    const btnModePan = document.getElementById('btn-mode-pan');
    const btnUndo = document.getElementById('btn-undo');
    const btnRedo = document.getElementById('btn-redo');
    const btnDuplicate = document.getElementById('btn-duplicate');
    const btnRotateSelected = document.getElementById('btn-rotate-selected');
    const btnDeleteSelected = document.getElementById('btn-delete-selected');
    const btnAlignLeft = document.getElementById('btn-align-left');
    const btnAlignTop = document.getElementById('btn-align-top');
    const btnPackVert = document.getElementById('btn-pack-vert');
    const btnPackHoriz = document.getElementById('btn-pack-horiz');
    const btnToggleHygiene = document.getElementById('btn-toggle-hygiene');
    const btnToggleSnap = document.getElementById('btn-toggle-snap');
    const btnZoomOut = document.getElementById('btn-zoom-out');
    const btnZoomIn = document.getElementById('btn-zoom-in');
    const btnZoom100 = document.getElementById('btn-zoom-100');
    const btnZoomFit = document.getElementById('btn-zoom-fit');
    const zoomText = document.getElementById('zoom-level-text');
    const saveStatusBadge = document.getElementById('save-status-badge');

    // 左ペイン
    const tabBtnShapes = document.getElementById('tab-btn-shapes');
    const tabBtnItems = document.getElementById('tab-btn-items');
    const tabPanelShapes = document.getElementById('tab-panel-shapes');
    const tabPanelItems = document.getElementById('tab-panel-items');
    const sidebarSearch = document.getElementById('sidebar-item-search');
    const sidebarUnplacedOnly = document.getElementById('sidebar-item-unplaced-only');
    const btnResetPreset = document.getElementById('btn-reset-preset');
    const btnClearPlacements = document.getElementById('btn-clear-placements');

    // 右インスペクタ
    const inspBadge = document.getElementById('inspector-selection-badge');
    const inspEmpty = document.getElementById('inspector-empty-state');
    const inspSingle = document.getElementById('inspector-single-form');
    const inspMulti = document.getElementById('inspector-multi-panel');
    const inspShapeName = document.getElementById('insp-shape-name');
    const inspDisplayText = document.getElementById('insp-display-text');
    const inspColorNote = document.getElementById('insp-color-note');
    const inspColorBtns = document.querySelectorAll('.insp-color-btn');
    const inspRotation = document.getElementById('insp-rotation');
    const inspFontSize = document.getElementById('insp-font-size');
    const inspRotationLocked = document.getElementById('insp-rotation-locked');
    const inspWidthM = document.getElementById('insp-width-m');
    const inspHeightM = document.getElementById('insp-height-m');
    const inspWaterOptions = document.getElementById('insp-water-options');
    const inspShowHygieneCircle = document.getElementById('insp-show-hygiene-circle');
    const inspArcMode = document.getElementById('insp-arc-mode');
    const inspLayoutItemId = document.getElementById('insp-layout-item-id');
    const inspNotes = document.getElementById('insp-notes');
    const inspMultiCount = document.getElementById('insp-multi-count');
    const bulkColorBtns = document.querySelectorAll('.bulk-color-btn');

    // サマリー＆衛生チェック
    const sumTotal = document.getElementById('summary-total-count');
    const sumRed = document.getElementById('summary-red-count');
    const sumGreen = document.getElementById('summary-green-count');
    const sumWater = document.getElementById('summary-water-count');
    const hygieneStatus = document.getElementById('hygiene-check-status');
    const hygieneWarningList = document.getElementById('hygiene-warning-list');

    // Dataset URLs
    const svgUrl = appEl.dataset.svgUrl;
    const viewboxStr = appEl.dataset.viewbox || '0 0 842 1191';
    const scaleRatio = parseFloat(appEl.dataset.scaleRatio) || 7.0866;
    const storeUrl = appEl.dataset.storeUrl;
    const bulkUrl = appEl.dataset.bulkUrl;
    const resetUrl = appEl.dataset.resetUrl;
    const clearUrl = appEl.dataset.clearUrl;
    const iconStoreUrl = appEl.dataset.iconStoreUrl;
    const iconBaseUrl = appEl.dataset.iconBaseUrl;
    const csrfToken = appEl.dataset.csrfToken;

    // 初期JSONパース
    const parseJsonAttr = (id, fallback) => {
        const el = document.getElementById(id);
        if (!el || !el.dataset.json) return fallback;
        try {
            return JSON.parse(el.dataset.json);
        } catch (e) {
            console.error(`JSON parse failed for ${id}:`, e);
            return fallback;
        }
    };

    const initialPlacements = parseJsonAttr('initial-placements-data', []);
    const iconDefsList = parseJsonAttr('icon-definitions-data', []);
    const iconDefinitions = {};
    iconDefsList.forEach(d => { iconDefinitions[d.icon_key] = d; });

    // カラーマップ（添付PDF準拠）
    const COLOR_MAP = {
        red: { stroke: '#dc2626', text: '#dc2626' },
        green: { stroke: '#16a34a', text: '#16a34a' },
        black: { stroke: '#0f172a', text: '#0f172a' },
        cyan: { stroke: '#06b6d4', text: '#0891b2' },
        orange: { stroke: '#f97316', text: '#ea580c' },
        blue: { stroke: '#2563eb', text: '#2563eb' },
    };

    // 通り沿い歩道4レールX座標 (A3 1/400 pt)
    const STREET_RAILS_X = [309.72, 353.94, 681.36, 725.58];
    const HYGIENE_RADIUS_PT = 138.0; // 20m
    const CAD_FONT_FAMILY = "'BIZ UDPGothic', 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', 'Yu Gothic UI', 'Meiryo', 'Noto Sans JP', sans-serif";

    // エディタ状態
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
        moveOrigPositions: new Map(), // id -> {x, y}
        isResizing: false,
        resizeHandle: null,
        resizeId: null,
        resizeStartSvgX: 0,
        resizeStartSvgY: 0,
        resizeOrig: null,
        // 履歴
        undoStack: [],
        redoStack: [],
        nudgeTimer: null,
        inspTimer: null,
        toastTimer: null,
    };

    const vbParts = viewboxStr.split(/\s+/).map(Number);
    if (vbParts.length === 4 && vbParts[2] > 0 && vbParts[3] > 0) {
        state.svgWidth = vbParts[2];
        state.svgHeight = vbParts[3];
    }

    // --- ユーティリティ ---
    function setSaveStatus(status) {
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

    function showToast(msg, undoCallback = null) {
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
        clearTimeout(state.toastTimer);
        state.toastTimer = setTimeout(() => toastEl.classList.add('hidden'), 3500);
    }

    function snapToDevicePixel(val) {
        const dpr = window.devicePixelRatio || 1;
        return Math.round(val * dpr) / dpr;
    }

    function updateTransform() {
        const pxX = snapToDevicePixel(state.x);
        const pxY = snapToDevicePixel(state.y);
        const scaledW = snapToDevicePixel(state.svgWidth * state.scale);
        const scaledH = snapToDevicePixel(state.svgHeight * state.scale);

        viewport.style.transform = 'none';
        viewport.style.left = `${pxX}px`;
        viewport.style.top = `${pxY}px`;

        const svg = svgContainer.querySelector('svg');
        if (svg) {
            svg.setAttribute('width', `${scaledW}`);
            svg.setAttribute('height', `${scaledH}`);
            svg.style.width = `${scaledW}px`;
            svg.style.height = `${scaledH}px`;
        }

        if (zoomText) zoomText.textContent = `${Math.round(state.scale * 100)}%`;
    }

    function fitToScreen() {
        const rect = canvasContainer.getBoundingClientRect();
        if (!rect.width || !rect.height) return;
        const pad = 20;
        const s = Math.min((rect.width - pad * 2) / state.svgWidth, (rect.height - pad * 2) / state.svgHeight, 2.0);
        state.scale = Math.max(0.25, Math.min(s, 16.0));
        state.x = (rect.width - state.svgWidth * state.scale) / 2;
        state.y = (rect.height - state.svgHeight * state.scale) / 2;
        updateTransform();
    }

    function zoomAround(factor, clientX, clientY) {
        const rect = canvasContainer.getBoundingClientRect();
        const cx = clientX - rect.left;
        const cy = clientY - rect.top;
        const oldS = state.scale;
        const newS = Math.max(0.25, Math.min(oldS * factor, 16.0));
        const svgX = (cx - state.x) / oldS;
        const svgY = (cy - state.y) / oldS;
        state.scale = newS;
        state.x = cx - svgX * newS;
        state.y = cy - svgY * newS;
        updateTransform();
    }

    function getSvgCoords(clientX, clientY) {
        const svg = svgContainer.querySelector('svg');
        if (!svg) {
            const rect = canvasContainer.getBoundingClientRect();
            return { x: (clientX - rect.left - state.x) / state.scale, y: (clientY - rect.top - state.y) / state.scale };
        }
        const pt = svg.createSVGPoint();
        pt.x = clientX;
        pt.y = clientY;
        const ctm = svg.getScreenCTM();
        if (!ctm) {
            const rect = canvasContainer.getBoundingClientRect();
            return { x: (clientX - rect.left - state.x) / state.scale, y: (clientY - rect.top - state.y) / state.scale };
        }
        const res = pt.matrixTransform(ctm.inverse());
        return { x: res.x, y: res.y };
    }

    function isAlongStreet(x, y, w) {
        const cx = x + w / 2;
        return (cx >= 305 && cx <= 372) || (cx >= 676 && cx <= 744);
    }

    // --- SVG サブレイヤー管理 (Z-Order 自動階層化) ---
    function ensureLayers() {
        const svg = svgContainer.querySelector('svg');
        if (!svg) return null;
        let root = svg.querySelector('#site-map-placements-layer');
        if (!root) {
            root = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            root.setAttribute('id', 'site-map-placements-layer');
            svg.appendChild(root);

            ['layer-hygiene-circles', 'layer-free-areas', 'layer-booths', 'layer-facilities', 'layer-overlays'].forEach(id => {
                const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                g.setAttribute('id', id);
                root.appendChild(g);
            });
        }
        return {
            hygiene: root.querySelector('#layer-hygiene-circles'),
            areas: root.querySelector('#layer-free-areas'),
            booths: root.querySelector('#layer-booths'),
            facilities: root.querySelector('#layer-facilities'),
            overlays: root.querySelector('#layer-overlays'),
        };
    }

    function getTargetLayerFor(placement, layers) {
        if (placement.shape_type === 'free_area' || placement.shape_type === 'area_note') return layers.areas;
        if (placement.shape_type === 'water_supply') return layers.facilities;
        return layers.booths;
    }

    // --- 20m 衛生サークル描画 & 圏外チェック ---
    function renderHygieneCirclesAndCheck() {
        const layers = ensureLayers();
        if (!layers) return;
        layers.hygiene.innerHTML = '';

        const waters = state.placements.filter(p => {
            const def = iconDefinitions[p.shape_type];
            return p.shape_type === 'water_supply' || (def && def.has_hygiene_circle);
        });
        const redBooths = state.placements.filter(p => {
            const def = iconDefinitions[p.shape_type];
            return p.color_theme === 'red' && p.shape_type !== 'area_note' && (!def || def.is_hygiene_target !== false);
        });

        if (state.showHygiene) {
            waters.forEach(w => {
                if (w.show_hygiene_circle === false) return;
                const cx = parseFloat(w.pos_x) + parseFloat(w.width) / 2;
                const cy = parseFloat(w.pos_y) + parseFloat(w.height) / 2;
                const startDeg = parseInt(w.arc_start_angle ?? 0, 10);
                let endDeg = parseInt(w.arc_end_angle ?? 360, 10);
                if (endDeg <= startDeg) endDeg = startDeg + 360;

                if (endDeg - startDeg >= 360) {
                    const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    circle.setAttribute('cx', cx);
                    circle.setAttribute('cy', cy);
                    circle.setAttribute('r', HYGIENE_RADIUS_PT);
                    circle.setAttribute('fill', 'rgba(37, 99, 235, 0.04)');
                    circle.setAttribute('stroke', '#2563eb');
                    circle.setAttribute('stroke-width', '0.5');
                    circle.setAttribute('stroke-dasharray', '3 2');
                    circle.setAttribute('pointer-events', 'none');
                    layers.hygiene.appendChild(circle);
                } else {
                    const rad1 = (startDeg - 90) * Math.PI / 180;
                    const rad2 = (endDeg - 90) * Math.PI / 180;
                    const x1 = cx + HYGIENE_RADIUS_PT * Math.cos(rad1);
                    const y1 = cy + HYGIENE_RADIUS_PT * Math.sin(rad1);
                    const x2 = cx + HYGIENE_RADIUS_PT * Math.cos(rad2);
                    const y2 = cy + HYGIENE_RADIUS_PT * Math.sin(rad2);
                    const largeArc = (endDeg - startDeg) > 180 ? 1 : 0;
                    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    path.setAttribute('d', `M ${x1.toFixed(2)} ${y1.toFixed(2)} A ${HYGIENE_RADIUS_PT} ${HYGIENE_RADIUS_PT} 0 ${largeArc} 1 ${x2.toFixed(2)} ${y2.toFixed(2)}`);
                    path.setAttribute('fill', 'none');
                    path.setAttribute('stroke', '#2563eb');
                    path.setAttribute('stroke-width', '0.5');
                    path.setAttribute('stroke-dasharray', '3 2');
                    path.setAttribute('pointer-events', 'none');
                    layers.hygiene.appendChild(path);
                }
            });
        }

        // 圏外赤枠ブース判定
        const outOfRange = [];
        redBooths.forEach(b => {
            const bx = parseFloat(b.pos_x) + parseFloat(b.width) / 2;
            const by = parseFloat(b.pos_y) + parseFloat(b.height) / 2;
            const covered = waters.some(w => {
                const wx = parseFloat(w.pos_x) + parseFloat(w.width) / 2;
                const wy = parseFloat(w.pos_y) + parseFloat(w.height) / 2;
                return Math.hypot(bx - wx, by - wy) <= HYGIENE_RADIUS_PT;
            });
            if (!covered) outOfRange.push(b);
        });

        if (hygieneStatus && hygieneWarningList) {
            hygieneWarningList.innerHTML = '';
            if (waters.length === 0) {
                hygieneStatus.textContent = '手洗い設備が配置されていません。';
            } else if (outOfRange.length === 0) {
                hygieneStatus.textContent = `✓ 全 ${redBooths.length} 件の赤枠ブースが20m圏内に収まっています。`;
            } else {
                hygieneStatus.textContent = `⚠️ 20m圏外の赤枠ブースが ${outOfRange.length} 件あります：`;
                outOfRange.forEach(b => {
                    const li = document.createElement('li');
                    li.textContent = `• ${b.display_text || '無題'} (X:${Math.round(b.pos_x)}, Y:${Math.round(b.pos_y)})`;
                    hygieneWarningList.appendChild(li);
                });
            }
        }
    }

    // --- 純粋SVG CADベクター描画 (DOMインプレース更新でdblclick 100%保証) ---
    function createSvgEl(tag, attrs = {}) {
        const el = document.createElementNS('http://www.w3.org/2000/svg', tag);
        Object.entries(attrs).forEach(([k, v]) => el.setAttribute(k, String(v)));
        return el;
    }

    function buildHatchPathD(x, y, w, h, step) {
        const parts = [];
        for (let d = -h; d <= w; d += step) {
            let x1 = x + d, y1 = y + h;
            let x2 = x + d + h, y2 = y;
            if (x1 < x) { y1 -= (x - x1); x1 = x; }
            if (x2 > x + w) { y2 += (x2 - (x + w)); x2 = x + w; }
            if (x1 <= x + w && x2 >= x && y1 >= y && y2 <= y + h) {
                parts.push(`M ${x1.toFixed(2)} ${y1.toFixed(2)} L ${x2.toFixed(2)} ${y2.toFixed(2)}`);
            }
        }
        return parts.join(' ');
    }

    function populatePlacementSvgGroup(g, placement) {
        // 中身のみ再構築し、<g> 要素自体はDOM上に維持する（dblclickイベントを絶対に壊さない）
        while (g.firstChild) g.removeChild(g.firstChild);

        const x = parseFloat(placement.pos_x) || 0;
        const y = parseFloat(placement.pos_y) || 0;
        const w = parseFloat(placement.width) || 13.86;
        const h = parseFloat(placement.height) || 13.86;
        const rot = parseInt(placement.rotation, 10) || 0;
        const shape = placement.shape_type;
        const theme = COLOR_MAP[placement.color_theme] || COLOR_MAP.red;
        const isSelected = state.selectedIds.has(placement.id);

        if (rot !== 0) {
            g.setAttribute('transform', `rotate(${rot} ${x + w / 2} ${y + h / 2})`);
        } else {
            g.removeAttribute('transform');
        }

        // ヒット領域（クリック・ドラッグ掴みやすさ用透明背景）
        g.appendChild(createSvgEl('rect', { x, y, width: w, height: h, fill: 'rgba(255,255,255,0.01)' }));

        const def = iconDefinitions[shape];

        if (def && def.render_style === 'image' && def.icon_asset_path) {
            const assetUrl = def.icon_asset_path.startsWith('/') ? def.icon_asset_path : `/${def.icon_asset_path}`;
            const imgEl = createSvgEl('image', {
                href: assetUrl,
                x,
                y,
                width: w,
                height: h,
                preserveAspectRatio: 'none',
            });
            g.appendChild(imgEl);
            if (placement.display_text && placement.display_text.trim()) {
                renderTextWithMask(g, placement, x, y, w, h, theme.text);
            }
        } else if (shape === 'water_supply') {
            g.appendChild(createSvgEl('rect', { x, y, width: w, height: h, fill: '#ffffff', stroke: '#2563eb', 'stroke-width': '0.55' }));
            g.appendChild(createSvgEl('rect', { x: x + 1.1, y: y + 1.1, width: Math.max(1, w - 2.2), height: Math.max(1, h - 2.2), fill: 'none', stroke: '#2563eb', 'stroke-width': '0.4' }));
            g.appendChild(createSvgEl('line', { x1: x + w / 2, y1: y + 1.1, x2: x + w / 2, y2: y + h - 1.1, stroke: '#2563eb', 'stroke-width': '0.4' }));
            g.appendChild(createSvgEl('line', { x1: x + 1.1, y1: y + h / 2, x2: x + w - 1.1, y2: y + h / 2, stroke: '#2563eb', 'stroke-width': '0.4' }));
        } else if (shape === 'area_note') {
            const lines = (placement.display_text || '').split('\n');
            const fs = placement.font_size ? parseFloat(placement.font_size) : 6.0;
            const lh = fs * 1.25;
            g.appendChild(createSvgEl('rect', { x, y, width: w, height: Math.max(h, lines.length * lh), fill: 'rgba(255,255,255,0.85)', stroke: isSelected ? '#2563eb' : 'none', 'stroke-width': '0.4' }));
            lines.forEach((line, idx) => {
                const tEl = createSvgEl('text', {
                    x: x + 1, y: y + fs + idx * lh,
                    'font-family': CAD_FONT_FAMILY,
                    'font-size': `${fs}px`,
                    'font-weight': 'bold',
                    'text-rendering': 'geometricPrecision',
                    'paint-order': 'stroke fill',
                    stroke: '#ffffff',
                    'stroke-width': '0.9',
                    'stroke-linejoin': 'round',
                    fill: theme.text,
                });
                tEl.textContent = line;
                g.appendChild(tEl);
            });
        } else if (shape === 'free_area') {
            const hatchD = buildHatchPathD(x, y, w, h, 4.0);
            if (hatchD) g.appendChild(createSvgEl('path', { d: hatchD, fill: 'none', stroke: theme.stroke, 'stroke-width': '0.16', opacity: '0.75' }));
            g.appendChild(createSvgEl('rect', { x, y, width: w, height: h, fill: 'none', stroke: theme.stroke, 'stroke-width': '0.5' }));
            renderTextWithMask(g, placement, x, y, w, h, theme.text);
        } else if (shape === 'kitchen_car') {
            g.appendChild(createSvgEl('rect', { x, y, width: w, height: h, fill: '#ffffff', stroke: theme.stroke, 'stroke-width': '0.9' }));
            g.appendChild(createSvgEl('line', { x1: x + w * 0.18, y1: y, x2: x + w * 0.18, y2: y + h, stroke: theme.stroke, 'stroke-width': '0.45' }));
            renderTextWithMask(g, placement, x, y, w, h, theme.text);
        } else {
            // CADテント・ブース（ハッチング + 外枠 + 寄棟屋根稜線 + 四隅支柱 + 白抜き文字）
            const hatchD = buildHatchPathD(x, y, w, h, 3.2);
            if (hatchD) g.appendChild(createSvgEl('path', { d: hatchD, fill: 'none', stroke: theme.stroke, 'stroke-width': '0.14', opacity: '0.65' }));
            g.appendChild(createSvgEl('rect', { x, y, width: w, height: h, fill: 'none', stroke: theme.stroke, 'stroke-width': '0.84' }));

            const inset = Math.min(w, h) * 0.25;
            const x1 = x + inset, y1 = y + inset, x2 = x + w - inset, y2 = y + h - inset;
            let roofD = `M ${x} ${y} L ${x1} ${y1} M ${x + w} ${y} L ${x2} ${y1} M ${x} ${y + h} L ${x1} ${y2} M ${x + w} ${y + h} L ${x2} ${y2}`;
            if (w > h * 1.15) roofD += ` M ${x1} ${y + h / 2} L ${x2} ${y + h / 2}`;
            else if (h > w * 1.15) roofD += ` M ${x + w / 2} ${y1} L ${x + w / 2} ${y2}`;
            else roofD += ` M ${x} ${y} L ${x + w} ${y + h} M ${x + w} ${y} L ${x} ${y + h}`;
            g.appendChild(createSvgEl('path', { d: roofD, fill: 'none', stroke: theme.stroke, 'stroke-width': '0.35', opacity: '0.85' }));

            const p = 0.7;
            [[x, y], [x + w - p, y], [x, y + h - p], [x + w - p, y + h - p]].forEach(([px, py]) => {
                g.appendChild(createSvgEl('rect', { x: px, y: py, width: p, height: p, fill: theme.stroke }));
            });

            renderTextWithMask(g, placement, x, y, w, h, theme.text);
        }

        // 選択時ハイライト枠 & リサイズハンドル
        if (isSelected) {
            g.appendChild(createSvgEl('rect', {
                x: x - 0.75, y: y - 0.75, width: w + 1.5, height: h + 1.5,
                fill: 'rgba(37, 99, 235, 0.08)', stroke: '#2563eb', 'stroke-width': '0.85', 'stroke-dasharray': '2 1', 'pointer-events': 'none',
            }));

            const def = iconDefinitions[shape];
            const isResizable = state.selectedIds.size === 1 && ((def && def.is_resizable) || shape === 'free_area' || shape === 'kitchen_car');
            const corners = [
                { type: 'tl', cx: x, cy: y, cursor: 'nwse-resize' },
                { type: 'tr', cx: x + w, cy: y, cursor: 'nesw-resize' },
                { type: 'br', cx: x + w, cy: y + h, cursor: 'nwse-resize' },
                { type: 'bl', cx: x, cy: y + h, cursor: 'nesw-resize' },
            ];
            corners.forEach(c => {
                const circle = createSvgEl('circle', {
                    cx: c.cx, cy: c.cy, r: isResizable ? '1.7' : '1.2',
                    fill: '#ffffff', stroke: '#2563eb', 'stroke-width': isResizable ? '0.7' : '0.5',
                });
                if (isResizable) {
                    circle.style.cursor = c.cursor;
                    circle.addEventListener('mousedown', (e) => {
                        if (e.button !== 0) return;
                        e.stopPropagation();
                        state.isResizing = true;
                        state.resizeHandle = c.type;
                        state.resizeId = placement.id;
                        const sc = getSvgCoords(e.clientX, e.clientY);
                        state.resizeStartSvgX = sc.x;
                        state.resizeStartSvgY = sc.y;
                        state.resizeOrig = { x, y, w, h, rot };
                    });
                } else {
                    circle.setAttribute('pointer-events', 'none');
                }
                g.appendChild(circle);
            });
        }
    }

    function renderTextWithMask(g, placement, x, y, w, h, colorHex) {
        const text = (placement.display_text || '').trim();
        if (!text) return;

        const lines = text.split('\n');
        const isBoothCode = /^[AB]\d+$/.test(text);
        let fs = placement.font_size ? parseFloat(placement.font_size) : (isBoothCode ? 6.2 : Math.min(5.5, Math.max(3.4, (w - 2) / Math.max(1, ...lines.map(l => l.length * 0.7)))));
        if (!placement.font_size && lines.length > 1) {
            fs = Math.min(fs, (h - 2) / (lines.length * 1.25));
        }

        const lh = fs * 1.18;
        const totalH = lh * lines.length;
        const estMaxW = Math.max(...lines.map(l => l.length * fs * (/[A-Za-z0-9]/.test(l) ? 0.62 : 0.92)));
        const maskW = Math.min(w - 1.4, estMaxW + 1.6);
        const maskH = Math.min(h - 1.4, totalH + 1.0);

        g.appendChild(createSvgEl('rect', {
            x: x + (w - maskW) / 2, y: y + (h - maskH) / 2,
            width: Math.max(1, maskW), height: Math.max(1, maskH),
            fill: '#ffffff', 'pointer-events': 'none',
        }));

        const startY = y + (h - totalH) / 2 + fs * 0.85;
        lines.forEach((line, idx) => {
            const tEl = createSvgEl('text', {
                x: x + w / 2, y: startY + idx * lh,
                'text-anchor': 'middle',
                'font-family': CAD_FONT_FAMILY,
                'font-size': `${fs.toFixed(1)}px`,
                'font-weight': 'bold',
                'text-rendering': 'geometricPrecision',
                'paint-order': 'stroke fill',
                stroke: '#ffffff',
                'stroke-width': '0.65',
                'stroke-linejoin': 'round',
                fill: colorHex,
                'pointer-events': 'none',
            });
            tEl.textContent = line;
            g.appendChild(tEl);
        });
    }

    function renderPlacementNode(placement) {
        const layers = ensureLayers();
        if (!layers) return;
        const targetLayer = getTargetLayerFor(placement, layers);

        const existing = svgContainer.querySelector(`g.site-map-node[data-placement-id="${placement.id}"]`);
        if (existing) {
            if (existing.parentNode !== targetLayer) targetLayer.appendChild(existing);
            populatePlacementSvgGroup(existing, placement);
            return;
        }

        const g = createSvgEl('g', {
            class: 'site-map-node cursor-pointer select-none',
            'data-placement-id': placement.id,
        });
        populatePlacementSvgGroup(g, placement);

        // イベントバインド（1回のみ付与）
        g.addEventListener('mousedown', (e) => {
            if (e.button !== 0 || state.toolMode === 'pan' || state.spacePressed) return;
            e.stopPropagation();

            const pid = placement.id;
            if (e.shiftKey) {
                toggleSelection(pid);
            } else if (!state.selectedIds.has(pid)) {
                setSingleSelection(pid);
            }

            state.isMoving = true;
            const sc = getSvgCoords(e.clientX, e.clientY);
            state.moveStartSvgX = sc.x;
            state.moveStartSvgY = sc.y;
            state.moveOrigPositions.clear();
            state.selectedIds.forEach(sid => {
                const sp = state.placements.find(p => p.id === sid);
                if (sp) state.moveOrigPositions.set(sid, { x: parseFloat(sp.pos_x), y: parseFloat(sp.pos_y), rot: sp.rotation, locked: sp.is_rotation_locked });
            });
        });

        g.addEventListener('dblclick', (e) => {
            e.stopPropagation();
            if (state.toolMode === 'pan' || state.spacePressed) return;
            openInlineTextEditor(placement);
        });

        g.addEventListener('mouseenter', (e) => showHoverTooltip(e, placement));
        g.addEventListener('mousemove', (e) => moveHoverTooltip(e));
        g.addEventListener('mouseleave', () => hideHoverTooltip());

        targetLayer.appendChild(g);
    }

    function renderAllPlacements() {
        const layers = ensureLayers();
        if (!layers) return;
        layers.areas.innerHTML = '';
        layers.booths.innerHTML = '';
        layers.facilities.innerHTML = '';
        state.placements.forEach(p => renderPlacementNode(p));
        renderHygieneCirclesAndCheck();
        updateSummaryAndInspector();
        syncSidebarLayoutItems();
    }

    // --- ホバーツールチップ ---
    function showHoverTooltip(e, p) {
        if (!hoverTooltip || state.isMoving || state.isResizing) return;
        const def = iconDefinitions[p.shape_type];
        const wM = (parseFloat(p.width) / scaleRatio).toFixed(1);
        const hM = (parseFloat(p.height) / scaleRatio).toFixed(1);
        const vendor = p.layout_item?.vendor?.name ? ` | ${p.layout_item.vendor.name}` : '';
        hoverTooltip.textContent = `${p.display_text || '(無題)'}${vendor} [${def ? def.name : p.shape_type} ${wM}m×${hM}m]`;
        hoverTooltip.classList.remove('hidden');
        moveHoverTooltip(e);
    }
    function moveHoverTooltip(e) {
        if (!hoverTooltip || hoverTooltip.classList.contains('hidden')) return;
        const rect = canvasContainer.getBoundingClientRect();
        hoverTooltip.style.left = `${e.clientX - rect.left + 12}px`;
        hoverTooltip.style.top = `${e.clientY - rect.top + 12}px`;
    }
    function hideHoverTooltip() {
        if (hoverTooltip) hoverTooltip.classList.add('hidden');
    }

    // --- ダブルクリック・インラインテキスト編集 ---
    function openInlineTextEditor(placement) {
        if (!inlineEditor) return;
        const svg = svgContainer.querySelector('svg');
        if (!svg) return;

        const pt = svg.createSVGPoint();
        pt.x = parseFloat(placement.pos_x) + parseFloat(placement.width) / 2;
        pt.y = parseFloat(placement.pos_y) + parseFloat(placement.height) / 2;
        const screenPt = pt.matrixTransform(svg.getScreenCTM());
        const rect = canvasContainer.getBoundingClientRect();

        inlineEditor.value = placement.display_text || '';
        inlineEditor.style.width = '110px';
        inlineEditor.style.left = `${screenPt.x - rect.left - 55}px`;
        inlineEditor.style.top = `${screenPt.y - rect.top - 22}px`;
        inlineEditor.classList.remove('hidden');
        inlineEditor.focus();
        inlineEditor.select();

        const commit = async () => {
            inlineEditor.classList.add('hidden');
            inlineEditor.onblur = null;
            inlineEditor.onkeydown = null;
            const nextVal = inlineEditor.value.trim();
            if (nextVal !== (placement.display_text || '')) {
                await applySingleUpdate(placement, { display_text: nextVal }, true);
            }
        };

        inlineEditor.onblur = commit;
        inlineEditor.onkeydown = (ev) => {
            if (ev.key === 'Enter' && !ev.shiftKey) {
                ev.preventDefault();
                commit();
            } else if (ev.key === 'Escape') {
                inlineEditor.classList.add('hidden');
                inlineEditor.onblur = null;
            }
        };
    }

    // --- 選択管理 ---
    function setSingleSelection(id) {
        const prev = new Set(state.selectedIds);
        state.selectedIds.clear();
        if (id !== null) state.selectedIds.add(id);
        refreshChangedNodes(prev, state.selectedIds);
        updateSummaryAndInspector();
    }

    function toggleSelection(id) {
        const prev = new Set(state.selectedIds);
        if (state.selectedIds.has(id)) state.selectedIds.delete(id);
        else state.selectedIds.add(id);
        refreshChangedNodes(prev, state.selectedIds);
        updateSummaryAndInspector();
    }

    function clearSelection() {
        if (state.selectedIds.size === 0) return;
        const prev = new Set(state.selectedIds);
        state.selectedIds.clear();
        refreshChangedNodes(prev, state.selectedIds);
        updateSummaryAndInspector();
    }

    function refreshChangedNodes(prevSet, nextSet) {
        const allChanged = new Set([...prevSet, ...nextSet]);
        allChanged.forEach(id => {
            const p = state.placements.find(item => item.id === id);
            if (p) renderPlacementNode(p);
        });
    }

    // --- サマリー & 右インスペクタ同期 ---
    function updateSummaryAndInspector() {
        const total = state.placements.length;
        const red = state.placements.filter(p => p.color_theme === 'red').length;
        const green = state.placements.filter(p => p.color_theme === 'green').length;
        const water = state.placements.filter(p => p.shape_type === 'water_supply').length;
        if (sumTotal) sumTotal.textContent = total;
        if (sumRed) sumRed.textContent = red;
        if (sumGreen) sumGreen.textContent = green;
        if (sumWater) sumWater.textContent = water;

        const selCount = state.selectedIds.size;
        if (btnDuplicate) btnDuplicate.disabled = selCount !== 1;
        if (btnDeleteSelected) btnDeleteSelected.disabled = selCount === 0;
        if (btnAlignLeft) btnAlignLeft.disabled = selCount < 2;
        if (btnAlignTop) btnAlignTop.disabled = selCount < 2;
        if (btnPackVert) btnPackVert.disabled = selCount < 2;
        if (btnPackHoriz) btnPackHoriz.disabled = selCount < 2;

        if (btnUndo) btnUndo.disabled = state.undoStack.length === 0;
        if (btnRedo) btnRedo.disabled = state.redoStack.length === 0;

        if (selCount === 0) {
            if (inspBadge) inspBadge.textContent = '未選択';
            if (inspEmpty) inspEmpty.classList.remove('hidden');
            if (inspSingle) inspSingle.classList.add('hidden');
            if (inspMulti) inspMulti.classList.add('hidden');
            if (btnRotateSelected) btnRotateSelected.disabled = true;
            return;
        }

        if (selCount > 1) {
            if (inspBadge) inspBadge.textContent = `${selCount}件選択`;
            if (inspEmpty) inspEmpty.classList.add('hidden');
            if (inspSingle) inspSingle.classList.add('hidden');
            if (inspMulti) inspMulti.classList.remove('hidden');
            if (inspMultiCount) inspMultiCount.textContent = `${selCount} 件のオブジェクトを選択中`;
            if (btnRotateSelected) btnRotateSelected.disabled = true;
            return;
        }

        // 単一選択時
        const sid = [...state.selectedIds][0];
        const p = state.placements.find(item => item.id === sid);
        if (!p) return;

        const def = iconDefinitions[p.shape_type];
        if (inspBadge) inspBadge.textContent = p.display_text || '1件選択';
        if (inspEmpty) inspEmpty.classList.add('hidden');
        if (inspSingle) inspSingle.classList.remove('hidden');
        if (inspMulti) inspMulti.classList.add('hidden');

        if (inspShapeName) inspShapeName.textContent = def ? def.name : p.shape_type;
        if (inspDisplayText) inspDisplayText.value = p.display_text || '';
        if (inspFontSize) inspFontSize.value = p.font_size || '';
        if (inspNotes) inspNotes.value = p.notes || '';
        if (inspWidthM) {
            inspWidthM.value = (parseFloat(p.width) / scaleRatio).toFixed(2);
            inspWidthM.disabled = !(def && def.is_resizable);
        }
        if (inspHeightM) {
            inspHeightM.value = (parseFloat(p.height) / scaleRatio).toFixed(2);
            inspHeightM.disabled = !(def && def.is_resizable);
        }

        // カラーボタン制御
        const supported = def && Array.isArray(def.supported_colors) ? def.supported_colors : ['red', 'green', 'black', 'cyan', 'orange', 'blue'];
        if (inspColorNote) inspColorNote.textContent = supported.length <= 1 ? '固定色' : '';
        inspColorBtns.forEach(btn => {
            const c = btn.dataset.color;
            const allowed = supported.includes(c);
            btn.disabled = !allowed;
            btn.classList.toggle('opacity-20', !allowed);
            btn.classList.toggle('cursor-not-allowed', !allowed);
            const active = c === p.color_theme;
            btn.classList.toggle('ring-2', active);
            btn.classList.toggle('ring-indigo-600', active);
            btn.classList.toggle('ring-offset-1', active);
        });

        // 回転制御 (正しいプロパティ名 is_rotatable を使用!)
        const rotatable = (def ? def.is_rotatable : true) && !p.is_rotation_locked;
        if (btnRotateSelected) btnRotateSelected.disabled = !rotatable;
        if (inspRotation) {
            inspRotation.disabled = !rotatable;
            inspRotation.value = String((Math.round((parseInt(p.rotation, 10) || 0) / 45) * 45) % 360);
        }
        if (inspRotationLocked) inspRotationLocked.checked = Boolean(p.is_rotation_locked);

        // 給水オプション
        if (inspWaterOptions) {
            if (p.shape_type === 'water_supply') {
                inspWaterOptions.classList.remove('hidden');
                if (inspShowHygieneCircle) inspShowHygieneCircle.checked = p.show_hygiene_circle !== false;
                if (inspArcMode) inspArcMode.value = `${p.arc_start_angle ?? 0},${p.arc_end_angle ?? 360}`;
            } else {
                inspWaterOptions.classList.add('hidden');
            }
        }

        // 台帳連動セレクト
        if (inspLayoutItemId) {
            inspLayoutItemId.value = p.layout_item_id || '';
            const usedIds = new Set(state.placements.filter(other => other.id !== p.id && other.layout_item_id).map(other => String(other.layout_item_id)));
            Array.from(inspLayoutItemId.options).forEach(opt => {
                if (!opt.value) return;
                const baseLabel = opt.dataset.baseLabel || opt.textContent.replace(' 【配置済】', '');
                opt.dataset.baseLabel = baseLabel;
                opt.textContent = usedIds.has(opt.value) ? `${baseLabel} 【配置済】` : baseLabel;
            });
        }
    }

    function syncSidebarLayoutItems() {
        const usedIds = new Set(state.placements.filter(p => p.layout_item_id).map(p => String(p.layout_item_id)));
        const kw = (sidebarSearch?.value || '').trim().toLowerCase();
        const unplacedOnly = Boolean(sidebarUnplacedOnly?.checked);

        document.querySelectorAll('.sidebar-layout-item').forEach(el => {
            const id = el.dataset.layoutItemId;
            const placed = usedIds.has(id);
            const badge = el.querySelector('.item-status-badge');
            if (badge) {
                badge.textContent = placed ? '配置済' : '未配置';
                badge.className = placed
                    ? 'item-status-badge text-[9px] px-1.5 py-0.2 rounded font-bold bg-emerald-100 text-emerald-700'
                    : 'item-status-badge text-[9px] px-1.5 py-0.2 rounded font-bold bg-slate-200 text-slate-600';
            }
            const textMatch = !kw || (el.dataset.code || '').toLowerCase().includes(kw) || (el.dataset.name || '').toLowerCase().includes(kw);
            const filterMatch = !unplacedOnly || !placed;
            el.classList.toggle('hidden', !(textMatch && filterMatch));
        });
    }

    // --- スマートスナップ & ガイド線 ---
    function clearGuideLines() {
        const layers = ensureLayers();
        if (layers) layers.overlays.innerHTML = '';
    }

    function drawGuideLine(x1, y1, x2, y2) {
        const layers = ensureLayers();
        if (!layers) return;
        layers.overlays.appendChild(createSvgEl('line', {
            x1, y1, x2, y2, stroke: '#06b6d4', 'stroke-width': '0.6', 'stroke-dasharray': '2 1', 'pointer-events': 'none',
        }));
    }

    function computeSnap(movingId, rawX, rawY, w, h) {
        clearGuideLines();
        if (!state.snapEnabled) return { x: rawX, y: rawY };

        let sx = rawX, sy = rawY;
        const thresh = 2.0;

        // 1. 通り沿い4レール X 座標スナップ
        for (const railX of STREET_RAILS_X) {
            if (Math.abs(rawX - railX) <= thresh) {
                sx = railX;
                drawGuideLine(railX, 80, railX, 1100);
                break;
            }
        }

        // 2. 他ノードの辺・密着スナップ
        for (const other of state.placements) {
            if (other.id === movingId || state.selectedIds.has(other.id)) continue;
            const ox = parseFloat(other.pos_x), oy = parseFloat(other.pos_y);
            const ow = parseFloat(other.width), oh = parseFloat(other.height);
            if (Math.abs(oy - rawY) > 80 && Math.abs(ox - rawX) > 80) continue;

            if (Math.abs(sx - ox) <= thresh) { sx = ox; drawGuideLine(ox, Math.min(sy, oy) - 10, ox, Math.max(sy + h, oy + oh) + 10); }
            else if (Math.abs(sx - (ox + ow)) <= thresh) { sx = ox + ow; drawGuideLine(ox + ow, Math.min(sy, oy) - 10, ox + ow, Math.max(sy + h, oy + oh) + 10); }
            else if (Math.abs((sx + w) - ox) <= thresh) { sx = ox - w; drawGuideLine(ox, Math.min(sy, oy) - 10, ox, Math.max(sy + h, oy + oh) + 10); }

            if (Math.abs(sy - oy) <= thresh) { sy = oy; drawGuideLine(Math.min(sx, ox) - 10, oy, Math.max(sx + w, ox + ow) + 10, oy); }
            else if (Math.abs(sy - (oy + oh)) <= thresh) { sy = oy + oh; drawGuideLine(Math.min(sx, ox) - 10, oy + oh, Math.max(sx + w, ox + ow) + 10, oy + oh); }
            else if (Math.abs((sy + h) - oy) <= thresh) { sy = oy - h; drawGuideLine(Math.min(sx, ox) - 10, oy, Math.max(sx + w, ox + ow) + 10, oy); }
        }

        return { x: Math.round(sx * 100) / 100, y: Math.round(sy * 100) / 100 };
    }

    // --- Undo / Redo ヒストリー ---
    function pushHistory(action) {
        state.undoStack.push(action);
        if (state.undoStack.length > 30) state.undoStack.shift();
        state.redoStack = [];
        updateSummaryAndInspector();
    }

    async function performUndo() {
        const action = state.undoStack.pop();
        if (!action) return;
        state.redoStack.push(action);
        await applyHistoryAction(action, true);
        updateSummaryAndInspector();
    }

    async function performRedo() {
        const action = state.redoStack.pop();
        if (!action) return;
        state.undoStack.push(action);
        await applyHistoryAction(action, false);
        updateSummaryAndInspector();
    }

    async function applyHistoryAction(action, isUndo) {
        if (action.type === 'update') {
            const items = isUndo ? action.before : action.after;
            await apiBulkUpdate(items);
        } else if (action.type === 'delete') {
            if (isUndo) {
                const created = await apiCreatePlacement(action.snapshot);
                if (created) action.restoredId = created.id;
            } else {
                const targetId = action.restoredId || action.snapshot.id;
                await apiDeletePlacement(targetId, false);
            }
        } else if (action.type === 'create') {
            if (isUndo) {
                await apiDeletePlacement(action.created.id, false);
            } else {
                const reCreated = await apiCreatePlacement(action.created);
                if (reCreated) action.created = reCreated;
            }
        }
    }

    // --- API 通信層 ---
    async function apiCreatePlacement(payload, recordHistory = false) {
        setSaveStatus('saving');
        try {
            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(payload),
            });
            if (!res.ok) throw new Error('作成に失敗しました');
            const data = await res.json();
            if (data.success && data.placement) {
                state.placements.push(data.placement);
                renderPlacementNode(data.placement);
                setSingleSelection(data.placement.id);
                renderHygieneCirclesAndCheck();
                syncSidebarLayoutItems();
                if (recordHistory) pushHistory({ type: 'create', created: { ...data.placement } });
                setSaveStatus('saved');
                return data.placement;
            }
        } catch (err) {
            console.error(err);
            setSaveStatus('error');
        }
        return null;
    }

    async function applySingleUpdate(placement, patch, recordHistory = true) {
        const before = [{ id: placement.id, pos_x: placement.pos_x, pos_y: placement.pos_y, width: placement.width, height: placement.height, rotation: placement.rotation, is_rotation_locked: placement.is_rotation_locked, display_text: placement.display_text, color_theme: placement.color_theme }];
        Object.assign(placement, patch);
        renderPlacementNode(placement);
        renderHygieneCirclesAndCheck();
        updateSummaryAndInspector();

        setSaveStatus('saving');
        try {
            const res = await fetch(`${storeUrl}/${placement.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(patch),
            });
            if (!res.ok) throw new Error('更新失敗');
            const data = await res.json();
            if (data.placement) Object.assign(placement, data.placement);
            if (recordHistory) {
                const after = [{ id: placement.id, pos_x: placement.pos_x, pos_y: placement.pos_y, width: placement.width, height: placement.height, rotation: placement.rotation, is_rotation_locked: placement.is_rotation_locked, display_text: placement.display_text, color_theme: placement.color_theme }];
                pushHistory({ type: 'update', before, after });
            }
            renderPlacementNode(placement);
            syncSidebarLayoutItems();
            setSaveStatus('saved');
        } catch (err) {
            console.error(err);
            setSaveStatus('error');
        }
    }

    async function apiBulkUpdate(items, recordHistory = false, beforeSnapshot = null) {
        if (!items || items.length === 0) return;
        setSaveStatus('saving');
        try {
            const res = await fetch(bulkUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ items }),
            });
            if (!res.ok) throw new Error('一括更新失敗');
            const data = await res.json();
            if (data.success && Array.isArray(data.placements)) {
                state.placements = data.placements;
                renderAllPlacements();
                if (recordHistory && beforeSnapshot) {
                    pushHistory({ type: 'update', before: beforeSnapshot, after: items });
                }
            }
            setSaveStatus('saved');
        } catch (err) {
            console.error(err);
            setSaveStatus('error');
        }
    }

    async function apiDeletePlacement(id, recordHistory = true) {
        const target = state.placements.find(p => p.id === id);
        if (!target) return;
        const snapshot = { ...target };

        setSaveStatus('saving');
        try {
            const res = await fetch(`${storeUrl}/${id}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });
            if (!res.ok) throw new Error('削除失敗');
            state.placements = state.placements.filter(p => p.id !== id);
            state.selectedIds.delete(id);
            const node = svgContainer.querySelector(`g.site-map-node[data-placement-id="${id}"]`);
            if (node) node.remove();
            renderHygieneCirclesAndCheck();
            updateSummaryAndInspector();
            syncSidebarLayoutItems();
            if (recordHistory) {
                pushHistory({ type: 'delete', snapshot });
                showToast(`「${snapshot.display_text || 'オブジェクト'}」を削除しました`, performUndo);
            }
            setSaveStatus('saved');
        } catch (err) {
            console.error(err);
            setSaveStatus('error');
        }
    }

    async function deleteSelectedPlacements() {
        const ids = [...state.selectedIds];
        for (const id of ids) {
            await apiDeletePlacement(id, true);
        }
    }

    // --- 連番複製 (Ctrl+D) ---
    function incrementLabel(text) {
        const m = String(text || '').match(/^(.*?)(\d+)$/);
        if (!m) return text;
        const prefix = m[1];
        const numStr = m[2];
        const nextNum = String(parseInt(numStr, 10) + 1).padStart(numStr.length, '0');
        return `${prefix}${nextNum}`;
    }

    async function duplicateSelected() {
        if (state.selectedIds.size !== 1) return;
        const src = state.placements.find(p => p.id === [...state.selectedIds][0]);
        if (!src) return;

        const w = parseFloat(src.width), h = parseFloat(src.height);
        const isVerticalColumn = isAlongStreet(parseFloat(src.pos_x), parseFloat(src.pos_y), w);
        const newX = isVerticalColumn ? parseFloat(src.pos_x) : parseFloat(src.pos_x) + w;
        const newY = isVerticalColumn ? parseFloat(src.pos_y) + h : parseFloat(src.pos_y);

        await apiCreatePlacement({
            shape_type: src.shape_type,
            display_text: incrementLabel(src.display_text),
            color_theme: src.color_theme,
            pos_x: Math.round(newX * 100) / 100,
            pos_y: Math.round(newY * 100) / 100,
            width: w,
            height: h,
            rotation: src.rotation,
            is_rotation_locked: src.is_rotation_locked,
            font_size: src.font_size,
        }, true);
    }

    // --- 一括整列・密着配置 ---
    async function alignSelected(mode) {
        const nodes = state.placements.filter(p => state.selectedIds.has(p.id));
        if (nodes.length < 2) return;

        const before = nodes.map(p => ({ id: p.id, pos_x: p.pos_x, pos_y: p.pos_y }));
        const items = [];

        if (mode === 'left') {
            const minX = Math.min(...nodes.map(n => parseFloat(n.pos_x)));
            nodes.forEach(n => items.push({ id: n.id, pos_x: minX, pos_y: n.pos_y }));
        } else if (mode === 'top') {
            const minY = Math.min(...nodes.map(n => parseFloat(n.pos_y)));
            nodes.forEach(n => items.push({ id: n.id, pos_x: n.pos_x, pos_y: minY }));
        } else if (mode === 'pack_vert') {
            const sorted = [...nodes].sort((a, b) => parseFloat(a.pos_y) - parseFloat(b.pos_y));
            const baseX = parseFloat(sorted[0].pos_x);
            let curY = parseFloat(sorted[0].pos_y);
            sorted.forEach(n => {
                items.push({ id: n.id, pos_x: baseX, pos_y: Math.round(curY * 100) / 100 });
                curY += parseFloat(n.height);
            });
        } else if (mode === 'pack_horiz') {
            const sorted = [...nodes].sort((a, b) => parseFloat(a.pos_x) - parseFloat(b.pos_x));
            let curX = parseFloat(sorted[0].pos_x);
            const baseY = parseFloat(sorted[0].pos_y);
            sorted.forEach(n => {
                items.push({ id: n.id, pos_x: Math.round(curX * 100) / 100, pos_y: baseY });
                curX += parseFloat(n.width);
            });
        }

        await apiBulkUpdate(items, true, before);
        showToast('整列を適用しました');
    }

    // --- 白地図ロード ---
    async function loadSvgMap() {
        try {
            const res = await fetch(svgUrl, { cache: 'no-cache' });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const svgText = await res.text();
            svgContainer.innerHTML = svgText;

            const svg = svgContainer.querySelector('svg');
            if (svg) {
                svg.setAttribute('width', `${state.svgWidth}`);
                svg.setAttribute('height', `${state.svgHeight}`);
                svg.setAttribute('shape-rendering', 'geometricPrecision');
                svg.setAttribute('text-rendering', 'geometricPrecision');
                svg.style.display = 'block';
                svg.style.maxWidth = 'none';
                svg.style.fontFamily = CAD_FONT_FAMILY;
            }

            if (loadingEl) {
                loadingEl.style.opacity = '0';
                setTimeout(() => { loadingEl.style.display = 'none'; }, 250);
            }
            renderAllPlacements();
            fitToScreen();
        } catch (err) {
            console.error(err);
            if (loadingEl) loadingEl.textContent = `白地図の読み込みに失敗しました: ${err.message}`;
        }
    }

    // --- ツールモード切替 ---
    function setToolMode(mode) {
        state.toolMode = mode;
        const isPan = mode === 'pan' || state.spacePressed;
        canvasContainer.style.cursor = isPan ? 'grab' : 'default';
        if (btnModeSelect && btnModePan) {
            btnModeSelect.className = mode === 'select' ? 'px-2 py-1 rounded-md font-bold bg-indigo-600 text-white transition cursor-pointer' : 'px-2 py-1 rounded-md font-bold text-slate-700 hover:bg-slate-200 transition cursor-pointer';
            btnModePan.className = mode === 'pan' ? 'px-2 py-1 rounded-md font-bold bg-indigo-600 text-white transition cursor-pointer' : 'px-2 py-1 rounded-md font-bold text-slate-700 hover:bg-slate-200 transition cursor-pointer';
        }
    }

    // --- マウスイベント ---
    canvasContainer.addEventListener('mousedown', (e) => {
        if (e.button !== 0) return;
        if (state.toolMode === 'pan' || state.spacePressed) {
            state.isPanning = true;
            state.panStartX = e.clientX - state.x;
            state.panStartY = e.clientY - state.y;
            canvasContainer.style.cursor = 'grabbing';
            return;
        }
        // 背景クリック ➔ 範囲選択(ラバーバンド)開始
        state.isMarquee = true;
        state.marqueeStartClientX = e.clientX;
        state.marqueeStartClientY = e.clientY;
        if (!e.shiftKey) clearSelection();
    });

    window.addEventListener('mousemove', (e) => {
        if (state.isPanning) {
            state.x = e.clientX - state.panStartX;
            state.y = e.clientY - state.panStartY;
            updateTransform();
            return;
        }

        if (state.isMarquee && marqueeBox) {
            const rect = canvasContainer.getBoundingClientRect();
            const x1 = Math.min(state.marqueeStartClientX, e.clientX) - rect.left;
            const y1 = Math.min(state.marqueeStartClientY, e.clientY) - rect.top;
            const w = Math.abs(e.clientX - state.marqueeStartClientX);
            const h = Math.abs(e.clientY - state.marqueeStartClientY);
            if (w > 4 || h > 4) {
                marqueeBox.style.left = `${x1}px`;
                marqueeBox.style.top = `${y1}px`;
                marqueeBox.style.width = `${w}px`;
                marqueeBox.style.height = `${h}px`;
                marqueeBox.classList.remove('hidden');
            }
            return;
        }

        // リサイズ中 (回転考慮の逆行列変換)
        if (state.isResizing && state.resizeOrig) {
            const sc = getSvgCoords(e.clientX, e.clientY);
            const dx = sc.x - state.resizeStartSvgX;
            const dy = sc.y - state.resizeStartSvgY;
            const target = state.placements.find(p => p.id === state.resizeId);
            if (!target) return;

            const rad = -(state.resizeOrig.rot * Math.PI) / 180;
            const ldx = dx * Math.cos(rad) - dy * Math.sin(rad);
            const ldy = dx * Math.sin(rad) + dy * Math.cos(rad);
            const minS = 7.0866;

            let { x, y, w, h } = state.resizeOrig;
            if (state.resizeHandle.includes('r')) w = Math.max(minS, w + ldx);
            if (state.resizeHandle.includes('b')) h = Math.max(minS, h + ldy);
            if (state.resizeHandle.includes('l')) {
                const nw = Math.max(minS, w - ldx);
                x += (w - nw);
                w = nw;
            }
            if (state.resizeHandle.includes('t')) {
                const nh = Math.max(minS, h - ldy);
                y += (h - nh);
                h = nh;
            }

            target.pos_x = Math.round(x * 100) / 100;
            target.pos_y = Math.round(y * 100) / 100;
            target.width = Math.round(w * 100) / 100;
            target.height = Math.round(h * 100) / 100;
            renderPlacementNode(target);
            updateSummaryAndInspector();
            return;
        }

        // ノード移動中
        if (state.isMoving) {
            const sc = getSvgCoords(e.clientX, e.clientY);
            let dx = sc.x - state.moveStartSvgX;
            let dy = sc.y - state.moveStartSvgY;

            if (state.selectedIds.size === 1) {
                const sid = [...state.selectedIds][0];
                const target = state.placements.find(p => p.id === sid);
                const orig = state.moveOrigPositions.get(sid);
                if (target && orig) {
                    const snapped = e.altKey
                        ? { x: orig.x + dx, y: orig.y + dy }
                        : computeSnap(sid, orig.x + dx, orig.y + dy, parseFloat(target.width), parseFloat(target.height));
                    target.pos_x = Math.round(snapped.x * 100) / 100;
                    target.pos_y = Math.round(snapped.y * 100) / 100;
                    renderPlacementNode(target);
                    if (target.shape_type === 'water_supply') renderHygieneCirclesAndCheck();
                }
            } else {
                state.selectedIds.forEach(sid => {
                    const target = state.placements.find(p => p.id === sid);
                    const orig = state.moveOrigPositions.get(sid);
                    if (target && orig) {
                        target.pos_x = Math.round((orig.x + dx) * 100) / 100;
                        target.pos_y = Math.round((orig.y + dy) * 100) / 100;
                        renderPlacementNode(target);
                    }
                });
            }
        }
    });

    window.addEventListener('mouseup', async (e) => {
        if (state.isPanning) {
            state.isPanning = false;
            canvasContainer.style.cursor = (state.toolMode === 'pan' || state.spacePressed) ? 'grab' : 'default';
            return;
        }

        if (state.isMarquee) {
            state.isMarquee = false;
            if (marqueeBox) marqueeBox.classList.add('hidden');
            const w = Math.abs(e.clientX - state.marqueeStartClientX);
            const h = Math.abs(e.clientY - state.marqueeStartClientY);
            if (w > 5 || h > 5) {
                const p1 = getSvgCoords(state.marqueeStartClientX, state.marqueeStartClientY);
                const p2 = getSvgCoords(e.clientX, e.clientY);
                const rx1 = Math.min(p1.x, p2.x), rx2 = Math.max(p1.x, p2.x);
                const ry1 = Math.min(p1.y, p2.y), ry2 = Math.max(p1.y, p2.y);
                const prev = new Set(state.selectedIds);
                state.placements.forEach(p => {
                    const px = parseFloat(p.pos_x), py = parseFloat(p.pos_y), pw = parseFloat(p.width), ph = parseFloat(p.height);
                    if (px + pw >= rx1 && px <= rx2 && py + ph >= ry1 && py <= ry2) {
                        state.selectedIds.add(p.id);
                    }
                });
                refreshChangedNodes(prev, state.selectedIds);
                updateSummaryAndInspector();
            }
            return;
        }

        if (state.isResizing) {
            const target = state.placements.find(p => p.id === state.resizeId);
            const orig = state.resizeOrig;
            state.isResizing = false;
            state.resizeOrig = null;
            if (target && orig && (target.width !== orig.w || target.height !== orig.h || target.pos_x !== orig.x || target.pos_y !== orig.y)) {
                await applySingleUpdate(target, { pos_x: target.pos_x, pos_y: target.pos_y, width: target.width, height: target.height }, true);
            }
            return;
        }

        if (state.isMoving) {
            state.isMoving = false;
            clearGuideLines();
            const updates = [];
            const before = [];
            state.selectedIds.forEach(sid => {
                const target = state.placements.find(p => p.id === sid);
                const orig = state.moveOrigPositions.get(sid);
                if (target && orig && (target.pos_x !== orig.x || target.pos_y !== orig.y)) {
                    before.push({ id: sid, pos_x: orig.x, pos_y: orig.y, rotation: orig.rot, is_rotation_locked: orig.locked });
                    const inStreet = isAlongStreet(target.pos_x, target.pos_y, parseFloat(target.width));
                    const nextLocked = inStreet;
                    const nextRot = inStreet ? 0 : target.rotation;
                    target.is_rotation_locked = nextLocked;
                    target.rotation = nextRot;
                    updates.push({ id: sid, pos_x: target.pos_x, pos_y: target.pos_y, rotation: nextRot, is_rotation_locked: nextLocked });
                }
            });
            if (updates.length === 1) {
                const target = state.placements.find(p => p.id === updates[0].id);
                if (target) await applySingleUpdate(target, updates[0], true);
            } else if (updates.length > 1) {
                await apiBulkUpdate(updates, true, before);
            }
        }
    });

    canvasContainer.addEventListener('wheel', (e) => {
        e.preventDefault();
        zoomAround(e.deltaY < 0 ? 1.15 : 0.85, e.clientX, e.clientY);
    }, { passive: false });

    // --- キーボードショートカット ---
    window.addEventListener('keydown', (e) => {
        const tag = document.activeElement?.tagName?.toLowerCase();
        const isInput = tag === 'input' || tag === 'textarea' || tag === 'select';
        if (isInput) return;

        if (e.code === 'Space' && !state.spacePressed) {
            e.preventDefault();
            state.spacePressed = true;
            canvasContainer.style.cursor = 'grab';
        } else if (e.key === 'v' || e.key === 'V') {
            setToolMode('select');
        } else if (e.key === 'h' || e.key === 'H') {
            setToolMode('pan');
        } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z' && !e.shiftKey) {
            e.preventDefault();
            performUndo();
        } else if ((e.ctrlKey || e.metaKey) && (e.key.toLowerCase() === 'y' || (e.shiftKey && e.key.toLowerCase() === 'z'))) {
            e.preventDefault();
            performRedo();
        } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'd') {
            e.preventDefault();
            duplicateSelected();
        } else if (e.key === 'Delete' || e.key === 'Backspace') {
            if (state.selectedIds.size > 0) {
                e.preventDefault();
                deleteSelectedPlacements();
            }
        } else if (e.key === 'r' || e.key === 'R') {
            if (state.selectedIds.size === 1) {
                const p = state.placements.find(item => item.id === [...state.selectedIds][0]);
                const def = p ? iconDefinitions[p.shape_type] : null;
                if (p && !p.is_rotation_locked && (!def || def.is_rotatable)) {
                    e.preventDefault();
                    applySingleUpdate(p, { rotation: (parseInt(p.rotation, 10) + 45) % 360 });
                }
            }
        } else if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key) && state.selectedIds.size > 0) {
            e.preventDefault();
            const step = e.shiftKey ? 3.54 : 0.5;
            const dx = e.key === 'ArrowLeft' ? -step : (e.key === 'ArrowRight' ? step : 0);
            const dy = e.key === 'ArrowUp' ? -step : (e.key === 'ArrowDown' ? step : 0);
            state.selectedIds.forEach(sid => {
                const p = state.placements.find(item => item.id === sid);
                if (p) {
                    p.pos_x = Math.round((parseFloat(p.pos_x) + dx) * 100) / 100;
                    p.pos_y = Math.round((parseFloat(p.pos_y) + dy) * 100) / 100;
                    renderPlacementNode(p);
                }
            });
            renderHygieneCirclesAndCheck();
            clearTimeout(state.nudgeTimer);
            state.nudgeTimer = setTimeout(() => {
                const items = [...state.selectedIds].map(sid => {
                    const p = state.placements.find(item => item.id === sid);
                    return p ? { id: p.id, pos_x: p.pos_x, pos_y: p.pos_y } : null;
                }).filter(Boolean);
                apiBulkUpdate(items, false);
            }, 350);
        } else if (e.key === 'Escape') {
            clearSelection();
        }
    });

    window.addEventListener('keyup', (e) => {
        if (e.code === 'Space') {
            state.spacePressed = false;
            canvasContainer.style.cursor = state.toolMode === 'pan' ? 'grab' : 'default';
        }
    });

    // --- ツールバー & 左ペインバインド ---
    if (btnModeSelect) btnModeSelect.addEventListener('click', () => setToolMode('select'));
    if (btnModePan) btnModePan.addEventListener('click', () => setToolMode('pan'));
    if (btnUndo) btnUndo.addEventListener('click', performUndo);
    if (btnRedo) btnRedo.addEventListener('click', performRedo);
    if (btnDuplicate) btnDuplicate.addEventListener('click', duplicateSelected);
    if (btnDeleteSelected) btnDeleteSelected.addEventListener('click', deleteSelectedPlacements);
    if (btnRotateSelected) {
        btnRotateSelected.addEventListener('click', () => {
            if (state.selectedIds.size !== 1) return;
            const p = state.placements.find(item => item.id === [...state.selectedIds][0]);
            if (p && !p.is_rotation_locked) applySingleUpdate(p, { rotation: (parseInt(p.rotation, 10) + 45) % 360 });
        });
    }
    if (btnAlignLeft) btnAlignLeft.addEventListener('click', () => alignSelected('left'));
    if (btnAlignTop) btnAlignTop.addEventListener('click', () => alignSelected('top'));
    if (btnPackVert) btnPackVert.addEventListener('click', () => alignSelected('pack_vert'));
    if (btnPackHoriz) btnPackHoriz.addEventListener('click', () => alignSelected('pack_horiz'));

    if (btnToggleHygiene) {
        btnToggleHygiene.addEventListener('click', () => {
            state.showHygiene = !state.showHygiene;
            btnToggleHygiene.textContent = state.showHygiene ? '💧 20m衛生圏 ON' : '💧 20m衛生圏 OFF';
            btnToggleHygiene.className = state.showHygiene
                ? 'px-2.5 py-1 bg-sky-600 text-white border border-sky-600 rounded-lg font-bold shadow-2xs transition cursor-pointer'
                : 'px-2.5 py-1 bg-white text-slate-600 border border-slate-300 rounded-lg font-bold shadow-2xs transition cursor-pointer';
            renderHygieneCirclesAndCheck();
        });
    }

    if (btnToggleSnap) {
        btnToggleSnap.addEventListener('click', () => {
            state.snapEnabled = !state.snapEnabled;
            btnToggleSnap.textContent = state.snapEnabled ? '🧲 吸着 ON' : '🧲 吸着 OFF';
            btnToggleSnap.className = state.snapEnabled
                ? 'px-2.5 py-1 bg-emerald-600 text-white border border-emerald-600 rounded-lg font-bold shadow-2xs transition cursor-pointer'
                : 'px-2.5 py-1 bg-white text-slate-600 border border-slate-300 rounded-lg font-bold shadow-2xs transition cursor-pointer';
        });
    }

    if (btnZoomIn) btnZoomIn.addEventListener('click', () => { const r = canvasContainer.getBoundingClientRect(); zoomAround(1.25, r.left + r.width / 2, r.top + r.height / 2); });
    if (btnZoomOut) btnZoomOut.addEventListener('click', () => { const r = canvasContainer.getBoundingClientRect(); zoomAround(0.8, r.left + r.width / 2, r.top + r.height / 2); });
    if (btnZoom100) btnZoom100.addEventListener('click', () => { const r = canvasContainer.getBoundingClientRect(); state.scale = 1.0; state.x = (r.width - state.svgWidth) / 2; state.y = (r.height - state.svgHeight) / 2; updateTransform(); });
    if (btnZoomFit) btnZoomFit.addEventListener('click', fitToScreen);

    // 左ペインタブ切替
    if (tabBtnShapes && tabBtnItems) {
        tabBtnShapes.addEventListener('click', () => {
            tabPanelShapes.classList.remove('hidden');
            tabPanelItems.classList.add('hidden');
            tabPanelItems.classList.remove('flex');
            tabBtnShapes.className = 'py-1 rounded-lg text-xs font-bold bg-white text-indigo-700 shadow-2xs transition cursor-pointer';
            tabBtnItems.className = 'py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition cursor-pointer';
        });
        tabBtnItems.addEventListener('click', () => {
            tabPanelShapes.classList.add('hidden');
            tabPanelItems.classList.remove('hidden');
            tabPanelItems.classList.add('flex');
            tabBtnItems.className = 'py-1 rounded-lg text-xs font-bold bg-white text-indigo-700 shadow-2xs transition cursor-pointer';
            tabBtnShapes.className = 'py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition cursor-pointer';
        });
    }

    if (sidebarSearch) sidebarSearch.addEventListener('input', syncSidebarLayoutItems);
    if (sidebarUnplacedOnly) sidebarUnplacedOnly.addEventListener('change', syncSidebarLayoutItems);

    if (btnResetPreset) {
        btnResetPreset.addEventListener('click', async () => {
            if (!confirm('現在の配置をリセットし、標準見本配置（260927版・全111件）に復元しますか？')) return;
            setSaveStatus('saving');
            try {
                const res = await fetch(resetUrl, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken } });
                if (!res.ok) throw new Error('復元に失敗しました');
                const data = await res.json();
                if (data.success && Array.isArray(data.placements)) {
                    state.placements = data.placements;
                    state.selectedIds.clear();
                    state.undoStack = [];
                    state.redoStack = [];
                    renderAllPlacements();
                    syncInspector();
                    syncSidebarLayoutItems();
                    updateHygieneWarnings();
                    showToast('標準見本配置へ復元しました');
                }
                setSaveStatus('saved');
            } catch (err) {
                console.error(err);
                setSaveStatus('error');
            }
        });
    }

    if (btnClearPlacements) {
        btnClearPlacements.addEventListener('click', async () => {
            if (state.placements.length === 0) {
                showToast('配置オブジェクトはありません');
                return;
            }
            if (!confirm(`現在の全配置オブジェクト（${state.placements.length}件）をすべて削除し、白地図のみの初期状態に戻しますか？`)) return;
            setSaveStatus('saving');
            try {
                const res = await fetch(clearUrl, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken } });
                if (!res.ok) throw new Error('クリアに失敗しました');
                const data = await res.json();
                if (data.success) {
                    state.placements = [];
                    state.selectedIds.clear();
                    state.undoStack = [];
                    state.redoStack = [];
                    renderAllPlacements();
                    syncInspector();
                    syncSidebarLayoutItems();
                    updateHygieneWarnings();
                    showToast('すべての配置オブジェクトをクリアしました');
                }
                setSaveStatus('saved');
            } catch (err) {
                console.error(err);
                setSaveStatus('error');
            }
        });
    }

    // --- 右インスペクタ双方向イベント ---
    const getSingleSelected = () => state.selectedIds.size === 1 ? state.placements.find(p => p.id === [...state.selectedIds][0]) : null;

    if (inspDisplayText) {
        inspDisplayText.addEventListener('input', () => {
            const p = getSingleSelected();
            if (!p) return;
            p.display_text = inspDisplayText.value;
            renderPlacementNode(p);
            clearTimeout(state.inspTimer);
            state.inspTimer = setTimeout(() => applySingleUpdate(p, { display_text: inspDisplayText.value }, true), 350);
        });
    }

    inspColorBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const p = getSingleSelected();
            if (p && !btn.disabled) applySingleUpdate(p, { color_theme: btn.dataset.color }, true);
        });
    });

    bulkColorBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const color = btn.dataset.bulkColor;
            const before = [...state.selectedIds].map(id => {
                const p = state.placements.find(item => item.id === id);
                return p ? { id: p.id, color_theme: p.color_theme } : null;
            }).filter(Boolean);
            const items = before.map(b => ({ id: b.id, color_theme: color }));
            apiBulkUpdate(items, true, before);
        });
    });

    if (inspRotation) inspRotation.addEventListener('change', () => { const p = getSingleSelected(); if (p) applySingleUpdate(p, { rotation: parseInt(inspRotation.value, 10) }, true); });
    if (inspRotationLocked) inspRotationLocked.addEventListener('change', () => { const p = getSingleSelected(); if (p) applySingleUpdate(p, { is_rotation_locked: inspRotationLocked.checked }, true); });
    if (inspFontSize) inspFontSize.addEventListener('change', () => { const p = getSingleSelected(); if (p) applySingleUpdate(p, { font_size: inspFontSize.value ? parseFloat(inspFontSize.value) : null }, true); });
    if (inspWidthM) inspWidthM.addEventListener('change', () => { const p = getSingleSelected(); if (p) applySingleUpdate(p, { width: Math.max(7.09, Math.round(parseFloat(inspWidthM.value) * scaleRatio * 100) / 100) }, true); });
    if (inspHeightM) inspHeightM.addEventListener('change', () => { const p = getSingleSelected(); if (p) applySingleUpdate(p, { height: Math.max(7.09, Math.round(parseFloat(inspHeightM.value) * scaleRatio * 100) / 100) }, true); });
    if (inspShowHygieneCircle) inspShowHygieneCircle.addEventListener('change', () => { const p = getSingleSelected(); if (p) applySingleUpdate(p, { show_hygiene_circle: inspShowHygieneCircle.checked }, true); });
    if (inspArcMode) {
        inspArcMode.addEventListener('change', () => {
            const p = getSingleSelected();
            if (!p) return;
            const [s, e] = inspArcMode.value.split(',').map(Number);
            applySingleUpdate(p, { arc_start_angle: s, arc_end_angle: e }, true);
        });
    }
    if (inspLayoutItemId) {
        inspLayoutItemId.addEventListener('change', () => {
            const p = getSingleSelected();
            if (!p) return;
            const val = inspLayoutItemId.value ? parseInt(inspLayoutItemId.value, 10) : null;
            const opt = inspLayoutItemId.options[inspLayoutItemId.selectedIndex];
            const patch = { layout_item_id: val };
            if (val && opt?.dataset.code && (!p.display_text || p.display_text === 'A00' || p.display_text === 'テント')) {
                patch.display_text = opt.dataset.code;
            }
            applySingleUpdate(p, patch, true);
        });
    }
    if (inspNotes) inspNotes.addEventListener('change', () => { const p = getSingleSelected(); if (p) applySingleUpdate(p, { notes: inspNotes.value }, true); });

    // --- パレット & 台帳からのドラッグ＆ドロップ配置 ---
    function bindPaletteItem(item) {
        item.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('application/json', JSON.stringify({
                kind: 'palette',
                shape_type: item.dataset.shapeType,
                default_width: parseFloat(item.dataset.defaultWidth) || 13.86,
                default_height: parseFloat(item.dataset.defaultHeight) || 13.86,
                color_theme: item.dataset.defaultColor || 'red',
            }));
            e.dataTransfer.effectAllowed = 'copy';
        });

        const btnEdit = item.querySelector('.btn-edit-custom-icon');
        if (btnEdit) {
            btnEdit.addEventListener('click', (e) => {
                e.stopPropagation();
                openEditIconModal(item.dataset.shapeType);
            });
        }

        const btnDelete = item.querySelector('.btn-delete-custom-icon');
        if (btnDelete) {
            btnDelete.addEventListener('click', (e) => {
                e.stopPropagation();
                openDeleteIconModal(item.dataset.shapeType);
            });
        }
    }

    document.querySelectorAll('.palette-item').forEach(item => bindPaletteItem(item));

    document.querySelectorAll('.sidebar-layout-item').forEach(item => {
        item.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('application/json', JSON.stringify({
                kind: 'layout_item',
                layout_item_id: parseInt(item.dataset.layoutItemId, 10),
                code: item.dataset.code,
                item_type: item.dataset.itemType,
            }));
            e.dataTransfer.effectAllowed = 'copy';
        });
        // 配置済アイテムをクリックするとその位置にズーム＆フォーカス
        item.addEventListener('click', () => {
            const lid = parseInt(item.dataset.layoutItemId, 10);
            const found = state.placements.find(p => p.layout_item_id === lid);
            if (!found) return;
            setSingleSelection(found.id);
            const rect = canvasContainer.getBoundingClientRect();
            state.scale = 3.0;
            state.x = rect.width / 2 - (parseFloat(found.pos_x) + parseFloat(found.width) / 2) * state.scale;
            state.y = rect.height / 2 - (parseFloat(found.pos_y) + parseFloat(found.height) / 2) * state.scale;
            updateTransform();
        });
    });

    canvasContainer.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    });

    canvasContainer.addEventListener('drop', async (e) => {
        e.preventDefault();
        const raw = e.dataTransfer.getData('application/json');
        if (!raw) return;

        let data;
        try { data = JSON.parse(raw); } catch { return; }

        const sc = getSvgCoords(e.clientX, e.clientY);
        let shapeType = data.shape_type || 'booth_standard';
        let w = data.default_width || 13.86;
        let h = data.default_height || 13.86;
        let color = data.color_theme || 'red';
        let displayText = '';
        let layoutItemId = null;

        if (data.kind === 'layout_item') {
            layoutItemId = data.layout_item_id;
            displayText = data.code || '';
            if (data.item_type === 'facility') {
                shapeType = 'water_supply';
                w = 6.24;
                h = 6.24;
                color = 'blue';
            }
        }

        let posX = sc.x - w / 2;
        let posY = sc.y - h / 2;
        if (state.snapEnabled) {
            const snapped = computeSnap(null, posX, posY, w, h);
            posX = snapped.x;
            posY = snapped.y;
            clearGuideLines();
        }

        const inStreet = isAlongStreet(posX, posY, w);
        await apiCreatePlacement({
            shape_type: shapeType,
            layout_item_id: layoutItemId,
            display_text: displayText,
            color_theme: color,
            pos_x: Math.round(posX * 100) / 100,
            pos_y: Math.round(posY * 100) / 100,
            width: w,
            height: h,
            rotation: 0,
            is_rotation_locked: inStreet,
        }, true);
    });

    // --- カスタムPNGアイコン管理 (生成・編集・削除・パレット連携) ---
    const btnOpenCreateIconModal = document.getElementById('btn-open-create-icon-modal');
    const modalCreateIcon = document.getElementById('modal-create-icon');
    const formCreateIcon = document.getElementById('form-create-icon');
    const createIconDropzone = document.getElementById('create-icon-dropzone');
    const createIconFile = document.getElementById('create-icon-file');
    const createIconPrompt = document.getElementById('create-icon-prompt');
    const createIconPreviewWrap = document.getElementById('create-icon-preview-wrap');
    const createIconPreviewImg = document.getElementById('create-icon-preview-img');
    const createIconFilename = document.getElementById('create-icon-filename');
    const createIconWidth = document.getElementById('create-icon-width');
    const createIconHeight = document.getElementById('create-icon-height');
    const createIconScaleHint = document.getElementById('create-icon-scale-hint');
    const createIconErrorMsg = document.getElementById('create-icon-error-msg');
    const btnSubmitCreateIcon = document.getElementById('btn-submit-create-icon');

    const modalEditIcon = document.getElementById('modal-edit-icon');
    const formEditIcon = document.getElementById('form-edit-icon');
    const editIconId = document.getElementById('edit-icon-id');
    const editIconName = document.getElementById('edit-icon-name');
    const editIconCategory = document.getElementById('edit-icon-category');
    const editIconWidth = document.getElementById('edit-icon-width');
    const editIconHeight = document.getElementById('edit-icon-height');
    const editIconScaleHint = document.getElementById('edit-icon-scale-hint');
    const editIconRotatable = document.getElementById('edit-icon-rotatable');
    const editIconResizable = document.getElementById('edit-icon-resizable');
    const editIconHygiene = document.getElementById('edit-icon-hygiene');
    const editIconCurrentImg = document.getElementById('edit-icon-current-img');
    const editIconFile = document.getElementById('edit-icon-file');
    const editIconErrorMsg = document.getElementById('edit-icon-error-msg');
    const btnSubmitEditIcon = document.getElementById('btn-submit-edit-icon');

    const modalDeleteIcon = document.getElementById('modal-delete-icon');
    const deleteIconId = document.getElementById('delete-icon-id');
    const deleteIconImg = document.getElementById('delete-icon-img');
    const deleteIconNameText = document.getElementById('delete-icon-name-text');
    const deleteIconSizeText = document.getElementById('delete-icon-size-text');
    const deleteIconUsageWarning = document.getElementById('delete-icon-usage-warning');
    const deleteIconUsageCount = document.getElementById('delete-icon-usage-count');
    const deleteIconErrorMsg = document.getElementById('delete-icon-error-msg');
    const btnSubmitDeleteIcon = document.getElementById('btn-submit-delete-icon');

    const paletteShapesGrid = document.getElementById('palette-shapes-grid');

    // 汎用モーダルクローズ
    document.querySelectorAll('.btn-close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.dataset.modal;
            if (modalId) {
                const modal = document.getElementById(modalId);
                if (modal) modal.classList.add('hidden');
            } else {
                [modalCreateIcon, modalEditIcon, modalDeleteIcon].forEach(m => m?.classList.add('hidden'));
            }
        });
    });

    // 縮尺ヒント更新ヘルパー
    function updateScaleHintText(wInput, hInput, hintEl) {
        if (!wInput || !hInput || !hintEl) return;
        const wM = parseFloat(wInput.value) || 0;
        const hM = parseFloat(hInput.value) || 0;
        const wPt = (wM * scaleRatio).toFixed(2);
        const hPt = (hM * scaleRatio).toFixed(2);
        hintEl.textContent = `A3 1/400換算: ${wPt}pt × ${hPt}pt`;
    }

    if (createIconWidth && createIconHeight && createIconScaleHint) {
        createIconWidth.addEventListener('input', () => updateScaleHintText(createIconWidth, createIconHeight, createIconScaleHint));
        createIconHeight.addEventListener('input', () => updateScaleHintText(createIconWidth, createIconHeight, createIconScaleHint));
    }
    if (editIconWidth && editIconHeight && editIconScaleHint) {
        editIconWidth.addEventListener('input', () => updateScaleHintText(editIconWidth, editIconHeight, editIconScaleHint));
        editIconHeight.addEventListener('input', () => updateScaleHintText(editIconWidth, editIconHeight, editIconScaleHint));
    }

    // --- 新規生成モーダル開閉 & ファイルドロップ ---
    if (btnOpenCreateIconModal) {
        btnOpenCreateIconModal.addEventListener('click', () => {
            if (formCreateIcon) formCreateIcon.reset();
            if (createIconPrompt) createIconPrompt.classList.remove('hidden');
            if (createIconPreviewWrap) createIconPreviewWrap.classList.add('hidden');
            if (createIconErrorMsg) createIconErrorMsg.classList.add('hidden');
            if (createIconWidth) createIconWidth.value = '2.0';
            if (createIconHeight) createIconHeight.value = '2.0';
            updateScaleHintText(createIconWidth, createIconHeight, createIconScaleHint);
            if (modalCreateIcon) modalCreateIcon.classList.remove('hidden');
        });
    }

    function processSelectedPngFile(file) {
        if (!file) return;
        if (file.type !== 'image/png' && !file.name.toLowerCase().endsWith('.png')) {
            if (createIconErrorMsg) {
                createIconErrorMsg.textContent = 'PNG形式の画像ファイルのみアップロード可能です。';
                createIconErrorMsg.classList.remove('hidden');
            }
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            if (createIconErrorMsg) {
                createIconErrorMsg.textContent = 'ファイルサイズが上限 (5MB) を超えています。';
                createIconErrorMsg.classList.remove('hidden');
            }
            return;
        }
        if (createIconErrorMsg) createIconErrorMsg.classList.add('hidden');

        const reader = new FileReader();
        reader.onload = (e) => {
            const dataUrl = e.target.result;
            const img = new Image();
            img.onload = () => {
                const nw = img.naturalWidth;
                const nh = img.naturalHeight;
                if (nw > 0 && nh > 0) {
                    const aspect = nw / nh;
                    const suggestedW = 2.0;
                    let suggestedH = Math.round((suggestedW / aspect) * 10) / 10;
                    if (suggestedH < 0.5) suggestedH = 0.5;
                    if (suggestedH > 20.0) suggestedH = 20.0;
                    if (createIconWidth) createIconWidth.value = suggestedW.toFixed(1);
                    if (createIconHeight) createIconHeight.value = suggestedH.toFixed(1);
                    updateScaleHintText(createIconWidth, createIconHeight, createIconScaleHint);
                }
            };
            img.src = dataUrl;

            if (createIconPreviewImg) createIconPreviewImg.src = dataUrl;
            if (createIconFilename) createIconFilename.textContent = file.name;
            if (createIconPrompt) createIconPrompt.classList.add('hidden');
            if (createIconPreviewWrap) createIconPreviewWrap.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }

    if (createIconDropzone && createIconFile) {
        createIconDropzone.addEventListener('click', () => createIconFile.click());
        createIconFile.addEventListener('change', () => {
            if (createIconFile.files && createIconFile.files[0]) {
                processSelectedPngFile(createIconFile.files[0]);
            }
        });
        createIconDropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            createIconDropzone.classList.add('border-indigo-500', 'bg-indigo-50/60');
        });
        createIconDropzone.addEventListener('dragleave', () => {
            createIconDropzone.classList.remove('border-indigo-500', 'bg-indigo-50/60');
        });
        createIconDropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            createIconDropzone.classList.remove('border-indigo-500', 'bg-indigo-50/60');
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                createIconFile.files = e.dataTransfer.files;
                processSelectedPngFile(e.dataTransfer.files[0]);
            }
        });
    }

    // 新規アイコン作成送信
    if (formCreateIcon) {
        formCreateIcon.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!createIconFile || !createIconFile.files || !createIconFile.files[0]) {
                if (createIconErrorMsg) {
                    createIconErrorMsg.textContent = 'PNG画像ファイルを選択してください。';
                    createIconErrorMsg.classList.remove('hidden');
                }
                return;
            }

            if (btnSubmitCreateIcon) btnSubmitCreateIcon.disabled = true;
            if (createIconErrorMsg) createIconErrorMsg.classList.add('hidden');

            try {
                const formData = new FormData(formCreateIcon);
                const res = await fetch(iconStoreUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    const msg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'アイコンの生成に失敗しました。');
                    if (createIconErrorMsg) {
                        createIconErrorMsg.textContent = msg;
                        createIconErrorMsg.classList.remove('hidden');
                    }
                    return;
                }

                addIconToPalette(data.icon);
                modalCreateIcon?.classList.add('hidden');
                formCreateIcon.reset();
                showToast(`アイコン「${data.icon.name}」を生成・追加しました`);
            } catch (err) {
                console.error('Failed to create icon:', err);
                if (createIconErrorMsg) {
                    createIconErrorMsg.textContent = '通信エラーが発生しました。時間をおいて再試行してください。';
                    createIconErrorMsg.classList.remove('hidden');
                }
            } finally {
                if (btnSubmitCreateIcon) btnSubmitCreateIcon.disabled = false;
            }
        });
    }

    // --- パレット動的反映ヘルパー ---
    function addIconToPalette(iconDef) {
        if (!paletteShapesGrid || !iconDef) return;
        iconDefinitions[iconDef.icon_key] = iconDef;

        const assetUrl = iconDef.icon_asset_path ? (iconDef.icon_asset_path.startsWith('/') ? iconDef.icon_asset_path : `/${iconDef.icon_asset_path}`) : '';
        const wM = (parseFloat(iconDef.default_width) / scaleRatio).toFixed(1);
        const hM = (parseFloat(iconDef.default_height) / scaleRatio).toFixed(1);

        const card = document.createElement('div');
        card.className = 'palette-item group relative p-2 bg-slate-50 hover:bg-indigo-50/70 border border-slate-200 hover:border-indigo-300 rounded-xl cursor-grab active:cursor-grabbing transition flex flex-col items-center text-center select-none';
        card.draggable = true;
        card.dataset.shapeType = iconDef.icon_key;
        card.dataset.defaultWidth = iconDef.default_width;
        card.dataset.defaultHeight = iconDef.default_height;
        card.dataset.defaultColor = iconDef.default_color || 'red';
        card.dataset.name = iconDef.name;
        card.dataset.category = iconDef.category;
        card.dataset.isPreset = '0';
        card.dataset.iconId = iconDef.id;
        card.dataset.renderStyle = iconDef.render_style;
        card.dataset.assetPath = assetUrl;
        card.dataset.isRotatable = iconDef.is_rotatable ? '1' : '0';
        card.dataset.isResizable = iconDef.is_resizable ? '1' : '0';
        card.dataset.rotationStep = iconDef.rotation_step || '45';
        card.dataset.hasHygieneCircle = iconDef.has_hygiene_circle ? '1' : '0';

        card.innerHTML = `
            <div class="absolute top-1 right-1 hidden group-hover:flex items-center space-x-0.5 z-10">
                <button type="button" class="btn-edit-custom-icon px-1 py-0.5 bg-white/90 hover:bg-amber-100 border border-amber-300 text-amber-700 rounded text-[9px] font-bold shadow-2xs transition cursor-pointer" title="編集" data-icon-id="${iconDef.id}">✏️</button>
                <button type="button" class="btn-delete-custom-icon px-1 py-0.5 bg-white/90 hover:bg-red-100 border border-red-300 text-red-700 rounded text-[9px] font-bold shadow-2xs transition cursor-pointer" title="削除" data-icon-id="${iconDef.id}">🗑️</button>
            </div>
            <div class="w-9 h-7 flex items-center justify-center pointer-events-none mb-1">
                <img src="${assetUrl}" alt="${iconDef.name}" class="max-w-full max-h-full object-contain">
            </div>
            <span class="text-[10px] font-bold text-slate-700 group-hover:text-indigo-700 leading-tight pointer-events-none truncate w-full">
                ${iconDef.name}
            </span>
            <span class="text-[9px] text-slate-400 font-mono mt-0.5 pointer-events-none">
                ${wM}m×${hM}m
            </span>
        `;

        paletteShapesGrid.appendChild(card);
        bindPaletteItem(card);
    }

    function updateIconInPalette(iconDef) {
        if (!iconDef) return;
        iconDefinitions[iconDef.icon_key] = iconDef;

        const card = document.querySelector(`.palette-item[data-shape-type="${iconDef.icon_key}"]`);
        if (card) {
            const assetUrl = iconDef.icon_asset_path ? (iconDef.icon_asset_path.startsWith('/') ? iconDef.icon_asset_path : `/${iconDef.icon_asset_path}`) : '';
            const wM = (parseFloat(iconDef.default_width) / scaleRatio).toFixed(1);
            const hM = (parseFloat(iconDef.default_height) / scaleRatio).toFixed(1);

            card.dataset.defaultWidth = iconDef.default_width;
            card.dataset.defaultHeight = iconDef.default_height;
            card.dataset.name = iconDef.name;
            card.dataset.category = iconDef.category;
            card.dataset.assetPath = assetUrl;
            card.dataset.isRotatable = iconDef.is_rotatable ? '1' : '0';
            card.dataset.isResizable = iconDef.is_resizable ? '1' : '0';
            card.dataset.hasHygieneCircle = iconDef.has_hygiene_circle ? '1' : '0';

            const nameSpan = card.querySelector('span.text-slate-700');
            if (nameSpan) nameSpan.textContent = iconDef.name;

            const sizeSpan = card.querySelector('span.text-slate-400');
            if (sizeSpan) sizeSpan.textContent = `${wM}m×${hM}m`;

            const imgEl = card.querySelector('img');
            if (imgEl && assetUrl) imgEl.src = assetUrl;
        }

        // 白地図上の既存配置ノードを最新情報でインプレース再描画
        state.placements.forEach(p => {
            if (p.shape_type === iconDef.icon_key) {
                const g = document.getElementById(`placement-node-${p.id}`);
                if (g) populatePlacementSvgGroup(g, p);
            }
        });
        renderHygieneCirclesAndCheck();
    }

    function removeIconFromPalette(shapeType) {
        const card = document.querySelector(`.palette-item[data-shape-type="${shapeType}"]`);
        if (card) card.remove();

        delete iconDefinitions[shapeType];

        // 白地図上の配置ノードを削除
        const toRemove = state.placements.filter(p => p.shape_type === shapeType);
        toRemove.forEach(p => {
            const g = document.getElementById(`placement-node-${p.id}`);
            if (g) g.remove();
            state.selectedIds.delete(p.id);
        });
        state.placements = state.placements.filter(p => p.shape_type !== shapeType);

        renderHygieneCirclesAndCheck();
        updateSummary();
        updateSelectionUI();
    }

    // --- 編集モーダル操作 ---
    function openEditIconModal(shapeType) {
        const def = iconDefinitions[shapeType];
        if (!def || def.is_preset) return;

        if (editIconId) editIconId.value = def.id;
        if (editIconName) editIconName.value = def.name;
        if (editIconCategory) editIconCategory.value = def.category;
        if (editIconWidth) editIconWidth.value = (parseFloat(def.default_width) / scaleRatio).toFixed(1);
        if (editIconHeight) editIconHeight.value = (parseFloat(def.default_height) / scaleRatio).toFixed(1);
        if (editIconRotatable) editIconRotatable.checked = Boolean(def.is_rotatable);
        if (editIconResizable) editIconResizable.checked = Boolean(def.is_resizable);
        if (editIconHygiene) editIconHygiene.checked = Boolean(def.has_hygiene_circle);

        const assetUrl = def.icon_asset_path ? (def.icon_asset_path.startsWith('/') ? def.icon_asset_path : `/${def.icon_asset_path}`) : '';
        if (editIconCurrentImg) editIconCurrentImg.src = assetUrl;
        if (editIconFile) editIconFile.value = '';
        if (editIconErrorMsg) editIconErrorMsg.classList.add('hidden');

        updateScaleHintText(editIconWidth, editIconHeight, editIconScaleHint);
        modalEditIcon?.classList.remove('hidden');
    }

    if (formEditIcon) {
        formEditIcon.addEventListener('submit', async (e) => {
            e.preventDefault();
            const iconId = editIconId?.value;
            if (!iconId) return;

            if (btnSubmitEditIcon) btnSubmitEditIcon.disabled = true;
            if (editIconErrorMsg) editIconErrorMsg.classList.add('hidden');

            try {
                const formData = new FormData(formEditIcon);
                const res = await fetch(`${iconBaseUrl}/${iconId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    const msg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'アイコンの更新に失敗しました。');
                    if (editIconErrorMsg) {
                        editIconErrorMsg.textContent = msg;
                        editIconErrorMsg.classList.remove('hidden');
                    }
                    return;
                }

                updateIconInPalette(data.icon);
                modalEditIcon?.classList.add('hidden');
                showToast(`アイコン「${data.icon.name}」を更新しました`);
            } catch (err) {
                console.error('Failed to update icon:', err);
                if (editIconErrorMsg) {
                    editIconErrorMsg.textContent = '通信エラーが発生しました。時間をおいて再試行してください。';
                    editIconErrorMsg.classList.remove('hidden');
                }
            } finally {
                if (btnSubmitEditIcon) btnSubmitEditIcon.disabled = false;
            }
        });
    }

    // --- 削除モーダル操作 ---
    async function openDeleteIconModal(shapeType) {
        const def = iconDefinitions[shapeType];
        if (!def || def.is_preset) return;

        if (deleteIconId) deleteIconId.value = def.id;
        if (deleteIconNameText) deleteIconNameText.textContent = def.name;
        const wM = (parseFloat(def.default_width) / scaleRatio).toFixed(1);
        const hM = (parseFloat(def.default_height) / scaleRatio).toFixed(1);
        if (deleteIconSizeText) deleteIconSizeText.textContent = `${wM}m × ${hM}m (${def.category})`;

        const assetUrl = def.icon_asset_path ? (def.icon_asset_path.startsWith('/') ? def.icon_asset_path : `/${def.icon_asset_path}`) : '';
        if (deleteIconImg) deleteIconImg.src = assetUrl;

        if (deleteIconUsageWarning) deleteIconUsageWarning.classList.add('hidden');
        if (deleteIconErrorMsg) deleteIconErrorMsg.classList.add('hidden');
        modalDeleteIcon?.classList.remove('hidden');

        try {
            const res = await fetch(`${iconBaseUrl}/${def.id}/usage`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (res.ok && data.success && data.usage_count > 0) {
                if (deleteIconUsageCount) deleteIconUsageCount.textContent = data.usage_count;
                if (deleteIconUsageWarning) deleteIconUsageWarning.classList.remove('hidden');
            }
        } catch (err) {
            console.warn('Failed to fetch icon usage count:', err);
        }
    }

    if (btnSubmitDeleteIcon) {
        btnSubmitDeleteIcon.addEventListener('click', async () => {
            const iconId = deleteIconId?.value;
            if (!iconId) return;

            btnSubmitDeleteIcon.disabled = true;
            if (deleteIconErrorMsg) deleteIconErrorMsg.classList.add('hidden');

            try {
                const res = await fetch(`${iconBaseUrl}/${iconId}?force=1`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    const msg = data.message || 'アイコンの削除に失敗しました。';
                    if (deleteIconErrorMsg) {
                        deleteIconErrorMsg.textContent = msg;
                        deleteIconErrorMsg.classList.remove('hidden');
                    }
                    return;
                }

                let targetKey = null;
                for (const [key, d] of Object.entries(iconDefinitions)) {
                    if (d.id == iconId) {
                        targetKey = key;
                        break;
                    }
                }
                if (targetKey) {
                    removeIconFromPalette(targetKey);
                }

                modalDeleteIcon?.classList.add('hidden');
                showToast('カスタムアイコンを削除しました');
            } catch (err) {
                console.error('Failed to delete icon:', err);
                if (deleteIconErrorMsg) {
                    deleteIconErrorMsg.textContent = '通信エラーが発生しました。時間をおいて再試行してください。';
                    deleteIconErrorMsg.classList.remove('hidden');
                }
            } finally {
                btnSubmitDeleteIcon.disabled = false;
            }
        });
    }

    loadSvgMap();
});
