import { CAD_FONT_FAMILY, COLOR_MAP } from './constants.js';
import { getSvgCoords } from './viewport.js';

const SVG_NS = 'http://www.w3.org/2000/svg';

/**
 * SVG名前空間の要素を生成する
 */
export function createSvgEl(tag, attrs = {}) {
    const el = document.createElementNS(SVG_NS, tag);
    Object.entries(attrs).forEach(([k, v]) => el.setAttribute(k, String(v)));
    return el;
}

/**
 * SVG サブレイヤー (Z-Order 自動階層化) を確保して返す
 */
export function ensureLayers(ctx) {
    const svg = ctx.dom.svgContainer.querySelector('svg');
    if (!svg) return null;
    let root = svg.querySelector('#site-map-placements-layer');
    if (!root) {
        root = document.createElementNS(SVG_NS, 'g');
        root.setAttribute('id', 'site-map-placements-layer');
        svg.appendChild(root);

        ['layer-hygiene-circles', 'layer-free-areas', 'layer-booths', 'layer-facilities', 'layer-overlays'].forEach((id) => {
            const g = document.createElementNS(SVG_NS, 'g');
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

/**
 * 配置オブジェクトの種別に応じた描画レイヤーを選択する
 */
export function getTargetLayerFor(placement, layers) {
    if (placement.shape_type === 'free_area' || placement.shape_type === 'area_note') return layers.areas;
    if (placement.shape_type === 'water_supply') return layers.facilities;
    return layers.booths;
}

/**
 * 配置IDに対応するSVG <g> ノードを共通セレクタで検索する
 */
export function findPlacementNodeElement(ctx, placementId) {
    return ctx.dom.svgContainer?.querySelector(`g.site-map-node[data-placement-id="${placementId}"]`) || null;
}

/**
 * CADテント/フリーエリア用の斜線ハッチングパスを生成する
 */
export function buildHatchPathD(x, y, w, h, step) {
    const parts = [];
    for (let d = -h; d <= w; d += step) {
        let x1 = x + d;
        let y1 = y + h;
        let x2 = x + d + h;
        let y2 = y;
        if (x1 < x) {
            y1 -= x - x1;
            x1 = x;
        }
        if (x2 > x + w) {
            y2 += x2 - (x + w);
            x2 = x + w;
        }
        if (x1 <= x + w && x2 >= x && y1 >= y && y2 <= y + h) {
            parts.push(`M ${x1.toFixed(2)} ${y1.toFixed(2)} L ${x2.toFixed(2)} ${y2.toFixed(2)}`);
        }
    }
    return parts.join(' ');
}

/**
 * 白マスク矩形＋白ハロー付きCADテキストを描画する
 */
export function renderTextWithMask(g, placement, x, y, w, h, colorHex) {
    const text = (placement.display_text || '').trim();
    if (!text) return;

    const lines = text.split('\n');
    const isBoothCode = /^[AB]\d+$/.test(text);
    let fs = placement.font_size
        ? parseFloat(placement.font_size)
        : isBoothCode
            ? 6.2
            : Math.min(5.5, Math.max(3.4, (w - 2) / Math.max(1, ...lines.map((l) => l.length * 0.7))));
    if (!placement.font_size && lines.length > 1) {
        fs = Math.min(fs, (h - 2) / (lines.length * 1.25));
    }

    const lh = fs * 1.18;
    const totalH = lh * lines.length;
    const estMaxW = Math.max(...lines.map((l) => l.length * fs * (/[A-Za-z0-9]/.test(l) ? 0.62 : 0.92)));
    const maskW = Math.min(w - 1.4, estMaxW + 1.6);
    const maskH = Math.min(h - 1.4, totalH + 1.0);

    g.appendChild(
        createSvgEl('rect', {
            x: x + (w - maskW) / 2,
            y: y + (h - maskH) / 2,
            width: Math.max(1, maskW),
            height: Math.max(1, maskH),
            fill: '#ffffff',
            'pointer-events': 'none',
        }),
    );

    const startY = y + (h - totalH) / 2 + fs * 0.85;
    lines.forEach((line, idx) => {
        const tEl = createSvgEl('text', {
            x: x + w / 2,
            y: startY + idx * lh,
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

/**
 * 配置ノード <g> の子要素のみをインプレース再構築する
 * (<g>自体はDOM上に維持してバインド済みイベントリスナーを保護する)
 */
export function populatePlacementSvgGroup(ctx, g, placement) {
    while (g.firstChild) g.removeChild(g.firstChild);

    const x = parseFloat(placement.pos_x) || 0;
    const y = parseFloat(placement.pos_y) || 0;
    const w = parseFloat(placement.width) || 13.86;
    const h = parseFloat(placement.height) || 13.86;
    const rot = parseInt(placement.rotation, 10) || 0;
    const shape = placement.shape_type;
    const theme = COLOR_MAP[placement.color_theme] || COLOR_MAP.red;
    const isSelected = ctx.state.selectedIds.has(placement.id);

    if (rot !== 0) {
        g.setAttribute('transform', `rotate(${rot} ${x + w / 2} ${y + h / 2})`);
    } else {
        g.removeAttribute('transform');
    }

    // ヒット領域（クリック・ドラッグ掴みやすさ用透明背景）
    g.appendChild(createSvgEl('rect', { x, y, width: w, height: h, fill: 'rgba(255,255,255,0.01)' }));

    const def = ctx.iconDefinitions[shape];

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
        g.appendChild(
            createSvgEl('rect', {
                x: x + 1.1,
                y: y + 1.1,
                width: Math.max(1, w - 2.2),
                height: Math.max(1, h - 2.2),
                fill: 'none',
                stroke: '#2563eb',
                'stroke-width': '0.4',
            }),
        );
        g.appendChild(createSvgEl('line', { x1: x + w / 2, y1: y + 1.1, x2: x + w / 2, y2: y + h - 1.1, stroke: '#2563eb', 'stroke-width': '0.4' }));
        g.appendChild(createSvgEl('line', { x1: x + 1.1, y1: y + h / 2, x2: x + w - 1.1, y2: y + h / 2, stroke: '#2563eb', 'stroke-width': '0.4' }));
    } else if (shape === 'area_note') {
        const lines = (placement.display_text || '').split('\n');
        const fs = placement.font_size ? parseFloat(placement.font_size) : 6.0;
        const lh = fs * 1.25;
        g.appendChild(
            createSvgEl('rect', {
                x,
                y,
                width: w,
                height: Math.max(h, lines.length * lh),
                fill: 'rgba(255,255,255,0.85)',
                stroke: isSelected ? '#2563eb' : 'none',
                'stroke-width': '0.4',
            }),
        );
        lines.forEach((line, idx) => {
            const tEl = createSvgEl('text', {
                x: x + 1,
                y: y + fs + idx * lh,
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
        const x1 = x + inset;
        const y1 = y + inset;
        const x2 = x + w - inset;
        const y2 = y + h - inset;
        let roofD = `M ${x} ${y} L ${x1} ${y1} M ${x + w} ${y} L ${x2} ${y1} M ${x} ${y + h} L ${x1} ${y2} M ${x + w} ${y + h} L ${x2} ${y2}`;
        if (w > h * 1.15) roofD += ` M ${x1} ${y + h / 2} L ${x2} ${y + h / 2}`;
        else if (h > w * 1.15) roofD += ` M ${x + w / 2} ${y1} L ${x + w / 2} ${y2}`;
        else roofD += ` M ${x} ${y} L ${x + w} ${y + h} M ${x + w} ${y} L ${x} ${y + h}`;
        g.appendChild(createSvgEl('path', { d: roofD, fill: 'none', stroke: theme.stroke, 'stroke-width': '0.35', opacity: '0.85' }));

        const p = 0.7;
        [
            [x, y],
            [x + w - p, y],
            [x, y + h - p],
            [x + w - p, y + h - p],
        ].forEach(([px, py]) => {
            g.appendChild(createSvgEl('rect', { x: px, y: py, width: p, height: p, fill: theme.stroke }));
        });

        renderTextWithMask(g, placement, x, y, w, h, theme.text);
    }

    // 選択時ハイライト枠 & リサイズハンドル
    if (isSelected) {
        g.appendChild(
            createSvgEl('rect', {
                x: x - 0.75,
                y: y - 0.75,
                width: w + 1.5,
                height: h + 1.5,
                fill: 'rgba(37, 99, 235, 0.08)',
                stroke: '#2563eb',
                'stroke-width': '0.85',
                'stroke-dasharray': '2 1',
                'pointer-events': 'none',
            }),
        );

        const isResizable = ctx.state.selectedIds.size === 1 && ((def && def.is_resizable) || shape === 'free_area' || shape === 'kitchen_car');
        const corners = [
            { type: 'tl', cx: x, cy: y, cursor: 'nwse-resize' },
            { type: 'tr', cx: x + w, cy: y, cursor: 'nesw-resize' },
            { type: 'br', cx: x + w, cy: y + h, cursor: 'nwse-resize' },
            { type: 'bl', cx: x, cy: y + h, cursor: 'nesw-resize' },
        ];
        corners.forEach((c) => {
            const circle = createSvgEl('circle', {
                cx: c.cx,
                cy: c.cy,
                r: isResizable ? '1.7' : '1.2',
                fill: '#ffffff',
                stroke: '#2563eb',
                'stroke-width': isResizable ? '0.7' : '0.5',
            });
            if (isResizable) {
                circle.style.cursor = c.cursor;
                circle.addEventListener('mousedown', (e) => {
                    if (e.button !== 0) return;
                    e.stopPropagation();
                    ctx.state.isResizing = true;
                    ctx.state.resizeHandle = c.type;
                    ctx.state.resizeId = placement.id;
                    const sc = getSvgCoords(ctx, e.clientX, e.clientY);
                    ctx.state.resizeStartSvgX = sc.x;
                    ctx.state.resizeStartSvgY = sc.y;
                    ctx.state.resizeOrig = { x, y, w, h, rot };
                });
            } else {
                circle.setAttribute('pointer-events', 'none');
            }
            g.appendChild(circle);
        });
    }
}

/**
 * 配置ノード単体・全体のSVG描画コントローラを作成する
 */
export function createSvgRenderer(ctx, hooks) {
    function renderPlacementNode(placement) {
        const layers = ensureLayers(ctx);
        if (!layers) return;
        const targetLayer = getTargetLayerFor(placement, layers);

        const existing = findPlacementNodeElement(ctx, placement.id);
        if (existing) {
            if (existing.parentNode !== targetLayer) targetLayer.appendChild(existing);
            populatePlacementSvgGroup(ctx, existing, placement);
            return;
        }

        const pid = placement.id;
        const resolveCurrent = () => ctx.state.placements.find((item) => item.id === pid) || placement;

        const g = createSvgEl('g', {
            class: 'site-map-node cursor-pointer select-none',
            'data-placement-id': pid,
        });
        populatePlacementSvgGroup(ctx, g, placement);

        // イベントバインド（1回のみ付与）
        g.addEventListener('mousedown', (e) => {
            hooks.onNodeMouseDown(e, resolveCurrent());
        });

        g.addEventListener('dblclick', (e) => {
            hooks.onNodeDblClick(e, resolveCurrent());
        });

        g.addEventListener('mouseenter', (e) => hooks.onNodeMouseEnter(e, resolveCurrent()));
        g.addEventListener('mousemove', (e) => hooks.onNodeMouseMove(e));
        g.addEventListener('mouseleave', () => hooks.onNodeMouseLeave());

        targetLayer.appendChild(g);
    }

    function renderAllPlacements() {
        const layers = ensureLayers(ctx);
        if (!layers) return;
        layers.areas.innerHTML = '';
        layers.booths.innerHTML = '';
        layers.facilities.innerHTML = '';
        ctx.state.placements.forEach((p) => renderPlacementNode(p));
        hooks.onAfterRenderAll();
    }

    return {
        renderPlacementNode,
        renderAllPlacements,
    };
}
