import {
    BARRICADE_SPECS,
    BAR_SPEC,
    BAR_STYLES,
    CAD_FONT_FAMILY,
    CALLOUT_COLORS,
    CONE_COLORS,
    CONE_SPEC,
} from './constants.js';
import { selectionKey } from './context.js';
import { getSvgCoords } from './viewport.js';

const SVG_NS = 'http://www.w3.org/2000/svg';

export function createSvgEl(tag, attrs = {}) {
    const el = document.createElementNS(SVG_NS, tag);
    Object.entries(attrs).forEach(([k, v]) => el.setAttribute(k, String(v)));
    return el;
}

export function ensureLayers(ctx) {
    const svg = ctx.dom.svgContainer.querySelector('svg');
    if (!svg) return null;

    let root = svg.querySelector('#cone-map-placements-layer');
    if (!root) {
        root = createSvgEl('g', { id: 'cone-map-placements-layer' });
        svg.appendChild(root);

        [
            'layer-booth-reference',
            'layer-cone-bars',
            'layer-barricades',
            'layer-cones',
            'layer-callouts',
            'layer-overlays',
        ].forEach((id) => {
            root.appendChild(createSvgEl('g', { id }));
        });
    }

    return {
        booths: root.querySelector('#layer-booth-reference'),
        bars: root.querySelector('#layer-cone-bars'),
        barricades: root.querySelector('#layer-barricades'),
        cones: root.querySelector('#layer-cones'),
        callouts: root.querySelector('#layer-callouts'),
        overlays: root.querySelector('#layer-overlays'),
    };
}

export function findNodeElement(ctx, kind, id) {
    return ctx.dom.svgContainer?.querySelector(`g.cone-map-node[data-node-kind="${kind}"][data-node-id="${id}"]`) || null;
}

function renderBoothReferenceLayer(ctx, layer) {
    layer.innerHTML = '';
    if (!ctx.state.showBooths) return;

    ctx.state.boothPlacements.forEach((p) => {
        const x = parseFloat(p.pos_x) || 0;
        const y = parseFloat(p.pos_y) || 0;
        const w = parseFloat(p.width) || 13.86;
        const h = parseFloat(p.height) || 13.86;
        const rot = parseInt(p.rotation, 10) || 0;

        const g = createSvgEl('g', { 'pointer-events': 'none', opacity: '0.45' });
        if (rot !== 0) {
            g.setAttribute('transform', `rotate(${rot} ${x + w / 2} ${y + h / 2})`);
        }
        g.appendChild(
            createSvgEl('rect', {
                x,
                y,
                width: w,
                height: h,
                fill: '#e2e8f0',
                stroke: '#94a3b8',
                'stroke-width': '0.4',
            }),
        );
        if (p.display_text) {
            const t = createSvgEl('text', {
                x: x + w / 2,
                y: y + h / 2 + 1.5,
                'text-anchor': 'middle',
                'font-family': CAD_FONT_FAMILY,
                'font-size': '4px',
                fill: '#64748b',
            });
            t.textContent = p.display_text;
            g.appendChild(t);
        }
        layer.appendChild(g);
    });
}

function populateBarGroup(ctx, g, bar) {
    while (g.firstChild) g.removeChild(g.firstChild);

    const startCone = ctx.state.cones.find((c) => c.id === bar.start_cone_id);
    const endCone = ctx.state.cones.find((c) => c.id === bar.end_cone_id);
    if (!startCone || !endCone) return;

    const x1 = parseFloat(startCone.pos_x);
    const y1 = parseFloat(startCone.pos_y);
    const x2 = parseFloat(endCone.pos_x);
    const y2 = parseFloat(endCone.pos_y);

    const style = BAR_STYLES[bar.bar_style] || BAR_STYLES.yellow_black;
    const { scaleRatio } = ctx.config;
    const enhanced = ctx.state.emphasisMode;
    const realWidth = Math.max(0.24, BAR_SPEC.diameterM * scaleRatio);
    const strokeW = enhanced ? Math.max(2.0, realWidth * 8.0) : realWidth;
    const isSelected = ctx.state.selectedItems.has(selectionKey('bar', bar.id));

    // 掴みやすい透明ヒットライン
    g.appendChild(
        createSvgEl('line', {
            x1,
            y1,
            x2,
            y2,
            stroke: 'rgba(255,255,255,0.01)',
            'stroke-width': '6',
            'stroke-linecap': 'round',
        }),
    );

    // 白ハロー
    g.appendChild(
        createSvgEl('line', {
            x1,
            y1,
            x2,
            y2,
            stroke: '#ffffff',
            'stroke-width': String(strokeW + (enhanced ? 1.1 : 0.4)),
            'stroke-linecap': 'round',
            'pointer-events': 'none',
        }),
    );

    // 外枠輪郭（白地図同化防止）
    g.appendChild(
        createSvgEl('line', {
            x1,
            y1,
            x2,
            y2,
            stroke: '#1e293b',
            'stroke-width': String(strokeW + 0.35),
            'pointer-events': 'none',
        }),
    );

    // 2色ストライプ
    const dx = x2 - x1;
    const dy = y2 - y1;
    const dist = Math.hypot(dx, dy);
    const segments = Math.max(4, Math.min(20, Math.round(dist / 1.8)));

    for (let i = 0; i < segments; i++) {
        const t1 = i / segments;
        const t2 = (i + 1) / segments;
        g.appendChild(
            createSvgEl('line', {
                x1: (x1 + dx * t1).toFixed(2),
                y1: (y1 + dy * t1).toFixed(2),
                x2: (x1 + dx * t2).toFixed(2),
                y2: (y1 + dy * t2).toFixed(2),
                stroke: i % 2 === 0 ? style.primary : style.secondary,
                'stroke-width': String(strokeW),
                'pointer-events': 'none',
            }),
        );
    }

    // 2.00m 超過警告点線
    const distM = dist / scaleRatio;
    if (distM > 2.05) {
        g.appendChild(
            createSvgEl('line', {
                x1,
                y1,
                x2,
                y2,
                stroke: '#dc2626',
                'stroke-width': '0.8',
                'stroke-dasharray': '1.5 1.5',
                'pointer-events': 'none',
            }),
        );
    }

    if (isSelected) {
        g.appendChild(
            createSvgEl('line', {
                x1,
                y1,
                x2,
                y2,
                stroke: '#2563eb',
                'stroke-width': String(strokeW + 2.2),
                opacity: '0.4',
                'stroke-linecap': 'round',
                'pointer-events': 'none',
            }),
        );
    }
}

function populateBarricadeGroup(ctx, g, barricade) {
    while (g.firstChild) g.removeChild(g.firstChild);

    const spec = BARRICADE_SPECS[barricade.barricade_type] || BARRICADE_SPECS.type_a;
    const { scaleRatio } = ctx.config;
    const enhanced = ctx.state.emphasisMode;

    // 長辺(120cm)は常に実寸を維持し、強調時は奥行のみ見やすく拡張
    const w = spec.lengthM * scaleRatio;
    const realH = spec.depthM * scaleRatio;
    const h = enhanced ? realH * (barricade.barricade_type === 'type_a' ? 1.55 : 1.40) : realH;

    const cx = parseFloat(barricade.pos_x) || 0;
    const cy = parseFloat(barricade.pos_y) || 0;
    const x = cx - w / 2;
    const y = cy - h / 2;
    const rot = parseInt(barricade.rotation, 10) || 0;
    const isSelected = ctx.state.selectedItems.has(selectionKey('barricade', barricade.id));

    if (rot !== 0) {
        g.setAttribute('transform', `rotate(${rot} ${cx} ${cy})`);
    } else {
        g.removeAttribute('transform');
    }

    // 掴みやすい透明ヒット領域
    g.appendChild(
        createSvgEl('rect', {
            x: x - 1.5,
            y: y - 1.5,
            width: w + 3,
            height: h + 3,
            fill: 'rgba(255,255,255,0.01)',
        }),
    );

    if (barricade.barricade_type === 'resin_pipe') {
        // 樹脂製単管バリケード（緑樹脂脚×2＋シルバー単管パイプ＋黄色クランプ）
        const legW = Math.max(1.1, w * 0.16);
        g.appendChild(
            createSvgEl('rect', {
                x,
                y,
                width: w,
                height: h,
                fill: '#f0fdf4',
                stroke: 'none',
                'pointer-events': 'none',
            }),
        );
        g.appendChild(
            createSvgEl('rect', {
                x: x + w * 0.08,
                y,
                width: legW,
                height: h,
                rx: '0.3',
                fill: '#16a34a',
                'pointer-events': 'none',
            }),
        );
        g.appendChild(
            createSvgEl('rect', {
                x: x + w * 0.92 - legW,
                y,
                width: legW,
                height: h,
                rx: '0.3',
                fill: '#16a34a',
                'pointer-events': 'none',
            }),
        );

        const pipeH = Math.max(0.9, h * 0.26);
        const pipeY = cy - pipeH / 2;
        g.appendChild(
            createSvgEl('rect', {
                x,
                y: pipeY,
                width: w,
                height: pipeH,
                fill: '#94a3b8',
                stroke: '#334155',
                'stroke-width': '0.3',
                'pointer-events': 'none',
            }),
        );

        const clampW = Math.max(0.7, legW * 0.55);
        g.appendChild(
            createSvgEl('rect', {
                x: x + w * 0.08 + (legW - clampW) / 2,
                y: pipeY - 0.3,
                width: clampW,
                height: pipeH + 0.6,
                fill: '#eab308',
                'pointer-events': 'none',
            }),
        );
        g.appendChild(
            createSvgEl('rect', {
                x: x + w * 0.92 - legW + (legW - clampW) / 2,
                y: pipeY - 0.3,
                width: clampW,
                height: pipeH + 0.6,
                fill: '#eab308',
                'pointer-events': 'none',
            }),
        );
    } else {
        // A型バリケード（オレンジ枠＋開脚線＋中央黄黒トラ柄反射板帯）
        g.appendChild(
            createSvgEl('rect', {
                x,
                y,
                width: w,
                height: h,
                fill: '#fff7ed',
                stroke: '#ea580c',
                'stroke-width': enhanced ? '0.85' : '0.45',
                'pointer-events': 'none',
            }),
        );

        const legInset = w * 0.1;
        g.appendChild(
            createSvgEl('line', {
                x1: x + legInset,
                y1: y,
                x2: x + legInset,
                y2: y + h,
                stroke: '#ea580c',
                'stroke-width': '0.45',
                'pointer-events': 'none',
            }),
        );
        g.appendChild(
            createSvgEl('line', {
                x1: x + w - legInset,
                y1: y,
                x2: x + w - legInset,
                y2: y + h,
                stroke: '#ea580c',
                'stroke-width': '0.45',
                'pointer-events': 'none',
            }),
        );

        const bandH = h * 0.42;
        const bandY = y + (h - bandH) / 2;
        const bandX = x + legInset * 0.6;
        const bandW = w - legInset * 1.2;
        g.appendChild(
            createSvgEl('rect', {
                x: bandX,
                y: bandY,
                width: bandW,
                height: bandH,
                fill: '#eab308',
                stroke: '#0f172a',
                'stroke-width': '0.25',
                'pointer-events': 'none',
            }),
        );
        const stripes = 4;
        const step = bandW / stripes;
        for (let i = 0; i < stripes; i++) {
            if (i % 2 === 1) {
                g.appendChild(
                    createSvgEl('rect', {
                        x: bandX + i * step,
                        y: bandY,
                        width: step * 0.85,
                        height: bandH,
                        fill: '#0f172a',
                        'pointer-events': 'none',
                    }),
                );
            }
        }
    }

    if (barricade.label) {
        const lbl = createSvgEl('text', {
            x: cx,
            y: y - 1.2,
            'text-anchor': 'middle',
            'font-family': CAD_FONT_FAMILY,
            'font-size': '4.2px',
            'font-weight': 'bold',
            'text-rendering': 'geometricPrecision',
            'paint-order': 'stroke fill',
            stroke: '#ffffff',
            'stroke-width': '0.9',
            'stroke-linejoin': 'round',
            fill: '#0f172a',
            'pointer-events': 'none',
        });
        lbl.textContent = barricade.label;
        g.appendChild(lbl);
    }

    if (isSelected) {
        g.appendChild(
            createSvgEl('rect', {
                x: x - 1.0,
                y: y - 1.0,
                width: w + 2.0,
                height: h + 2.0,
                fill: 'rgba(37, 99, 235, 0.08)',
                stroke: '#2563eb',
                'stroke-width': '0.8',
                'stroke-dasharray': '2 1',
                'pointer-events': 'none',
            }),
        );
    }
}

function populateConeGroup(ctx, g, cone) {
    while (g.firstChild) g.removeChild(g.firstChild);

    const { scaleRatio } = ctx.config;
    const enhanced = ctx.state.emphasisMode;
    const realSize = CONE_SPEC.baseM * scaleRatio;
    const size = enhanced ? Math.max(5.4, realSize * 2.0) : realSize;
    const half = size / 2;

    const cx = parseFloat(cone.pos_x) || 0;
    const cy = parseFloat(cone.pos_y) || 0;
    const theme = CONE_COLORS[cone.color] || CONE_COLORS.red;
    const isSelected = ctx.state.selectedItems.has(selectionKey('cone', cone.id));
    const isPendingFirst = ctx.state.pendingBarFirstConeId === cone.id;

    // ヒット領域
    g.appendChild(
        createSvgEl('circle', {
            cx,
            cy,
            r: String(Math.max(4.5, half + 1.5)),
            fill: 'rgba(255,255,255,0.01)',
        }),
    );

    // 白ハロー
    g.appendChild(
        createSvgEl('rect', {
            x: cx - half - 0.6,
            y: cy - half - 0.6,
            width: size + 1.2,
            height: size + 1.2,
            rx: '0.5',
            fill: '#ffffff',
            'pointer-events': 'none',
        }),
    );

    // 底面正方形
    g.appendChild(
        createSvgEl('rect', {
            x: cx - half,
            y: cy - half,
            width: size,
            height: size,
            rx: '0.4',
            fill: theme.fill,
            stroke: theme.stroke,
            'stroke-width': enhanced ? '0.5' : '0.25',
            'pointer-events': 'none',
        }),
    );

    // 内接円＋頂点白ドット
    g.appendChild(
        createSvgEl('circle', {
            cx,
            cy,
            r: String((half * 0.62).toFixed(2)),
            fill: 'none',
            stroke: '#ffffff',
            'stroke-width': enhanced ? '0.55' : '0.3',
            'pointer-events': 'none',
        }),
    );
    g.appendChild(
        createSvgEl('circle', {
            cx,
            cy,
            r: String(Math.max(0.55, half * 0.22).toFixed(2)),
            fill: '#ffffff',
            'pointer-events': 'none',
        }),
    );

    if (cone.label) {
        const lbl = createSvgEl('text', {
            x: cx + half + 1.4,
            y: cy + 1.4,
            'font-family': CAD_FONT_FAMILY,
            'font-size': '4.2px',
            'font-weight': 'bold',
            'text-rendering': 'geometricPrecision',
            'paint-order': 'stroke fill',
            stroke: '#ffffff',
            'stroke-width': '0.9',
            'stroke-linejoin': 'round',
            fill: theme.fill,
            'pointer-events': 'none',
        });
        lbl.textContent = cone.label;
        g.appendChild(lbl);
    }

    if (isSelected || isPendingFirst) {
        g.appendChild(
            createSvgEl('circle', {
                cx,
                cy,
                r: String(half + 2.0),
                fill: 'none',
                stroke: isPendingFirst ? '#d97706' : '#2563eb',
                'stroke-width': '0.9',
                'stroke-dasharray': isPendingFirst ? 'none' : '1.8 1',
                'pointer-events': 'none',
            }),
        );
    }
}

function wrapSvgLines(text, maxWidthPx, fontSizePx) {
    const rawLines = String(text || '').split('\n');
    const result = [];
    const approxCharW = fontSizePx * 0.92;
    const maxChars = Math.max(4, Math.floor(maxWidthPx / approxCharW));

    rawLines.forEach((raw) => {
        if (!raw) {
            result.push('');
            return;
        }
        let buf = '';
        for (const ch of raw) {
            buf += ch;
            if (buf.length >= maxChars) {
                result.push(buf);
                buf = '';
            }
        }
        if (buf) result.push(buf);
    });
    return result;
}

function populateCalloutGroup(ctx, g, callout, hooks) {
    while (g.firstChild) g.removeChild(g.firstChild);

    const theme = CALLOUT_COLORS[callout.color_theme] || CALLOUT_COLORS.amber;
    const ax = parseFloat(callout.anchor_x) || 0;
    const ay = parseFloat(callout.anchor_y) || 0;
    const bx = parseFloat(callout.box_x) || 0;
    const by = parseFloat(callout.box_y) || 0;
    const bw = Math.max(20, parseFloat(callout.box_width) || 76);
    const bh = Math.max(12, parseFloat(callout.box_height) || 28);
    const isSelected = ctx.state.selectedItems.has(selectionKey('callout', callout.id));

    const edgeX = Math.max(bx, Math.min(ax, bx + bw));
    const edgeY = Math.max(by, Math.min(ay, by + bh));

    // 引出線（白フチ＋カラー線）
    g.appendChild(
        createSvgEl('line', {
            x1: ax,
            y1: ay,
            x2: edgeX,
            y2: edgeY,
            stroke: '#ffffff',
            'stroke-width': '2.0',
            'pointer-events': 'none',
        }),
    );
    g.appendChild(
        createSvgEl('line', {
            x1: ax,
            y1: ay,
            x2: edgeX,
            y2: edgeY,
            stroke: theme.border,
            'stroke-width': '0.95',
            'pointer-events': 'none',
        }),
    );

    // 吹き出しボックス本体
    g.appendChild(
        createSvgEl('rect', {
            x: bx,
            y: by,
            width: bw,
            height: bh,
            rx: '2',
            fill: theme.bg,
            stroke: isSelected ? '#2563eb' : theme.border,
            'stroke-width': isSelected ? '1.1' : '0.85',
        }),
    );

    // 左アクセントバー
    g.appendChild(
        createSvgEl('rect', {
            x: bx,
            y: by,
            width: '2.2',
            height: bh,
            rx: '0.8',
            fill: theme.border,
            'pointer-events': 'none',
        }),
    );

    // テキスト描画（白ハロー付き）
    const fs = callout.font_size ? parseFloat(callout.font_size) : 5.4;
    const lh = fs * 1.25;
    let curY = by + 2.2 + fs * 0.85;
    const textX = bx + 4.5;
    const maxTextW = Math.max(12, bw - 7.5);

    if (callout.title && callout.title.trim()) {
        const titleLines = wrapSvgLines(callout.title.trim(), maxTextW, fs);
        titleLines.forEach((line) => {
            if (curY > by + bh - 1.5) return;
            const t = createSvgEl('text', {
                x: textX,
                y: curY,
                'font-family': CAD_FONT_FAMILY,
                'font-size': `${fs}px`,
                'font-weight': 'bold',
                'text-rendering': 'geometricPrecision',
                'paint-order': 'stroke fill',
                stroke: '#ffffff',
                'stroke-width': '0.7',
                'stroke-linejoin': 'round',
                fill: theme.border,
                'pointer-events': 'none',
            });
            t.textContent = line;
            g.appendChild(t);
            curY += lh;
        });
    }

    const bodyLines = wrapSvgLines(callout.body || '', maxTextW, fs * 0.94);
    bodyLines.forEach((line) => {
        if (curY > by + bh - 1.5) return;
        const t = createSvgEl('text', {
            x: textX,
            y: curY,
            'font-family': CAD_FONT_FAMILY,
            'font-size': `${(fs * 0.94).toFixed(2)}px`,
            'font-weight': '600',
            'text-rendering': 'geometricPrecision',
            'paint-order': 'stroke fill',
            stroke: '#ffffff',
            'stroke-width': '0.6',
            'stroke-linejoin': 'round',
            fill: theme.text,
            'pointer-events': 'none',
        });
        t.textContent = line;
        g.appendChild(t);
        curY += lh * 0.95;
    });

    // 引出線先端アンカー（独立ドラッグ可能）
    const anchorCircle = createSvgEl('circle', {
        cx: ax,
        cy: ay,
        r: isSelected ? '2.4' : '1.9',
        fill: theme.border,
        stroke: '#ffffff',
        'stroke-width': '0.7',
    });
    anchorCircle.style.cursor = 'crosshair';
    anchorCircle.addEventListener('mousedown', (e) => {
        if (e.button !== 0 || ctx.state.toolMode === 'pan' || ctx.state.spacePressed) return;
        e.stopPropagation();
        hooks.onCalloutAnchorMouseDown(e, callout);
    });
    g.appendChild(anchorCircle);

    // 選択時リサイズハンドル
    if (isSelected && ctx.state.selectedItems.size === 1) {
        const resizeHandle = createSvgEl('circle', {
            cx: bx + bw,
            cy: by + bh,
            r: '2.0',
            fill: '#ffffff',
            stroke: '#2563eb',
            'stroke-width': '0.75',
        });
        resizeHandle.style.cursor = 'nwse-resize';
        resizeHandle.addEventListener('mousedown', (e) => {
            if (e.button !== 0) return;
            e.stopPropagation();
            const sc = getSvgCoords(ctx, e.clientX, e.clientY);
            ctx.state.calloutResizeId = callout.id;
            ctx.state.calloutResizeStartSvgX = sc.x;
            ctx.state.calloutResizeStartSvgY = sc.y;
            ctx.state.calloutResizeOrig = { bw, bh };
        });
        g.appendChild(resizeHandle);
    }
}

export function createConeMapRenderer(ctx, hooks) {
    function renderNode(kind, item) {
        const layers = ensureLayers(ctx);
        if (!layers) return;

        const targetLayer =
            kind === 'bar'
                ? layers.bars
                : kind === 'barricade'
                    ? layers.barricades
                    : kind === 'cone'
                        ? layers.cones
                        : layers.callouts;

        let g = findNodeElement(ctx, kind, item.id);
        const populate = () => {
            if (kind === 'bar') populateBarGroup(ctx, g, item);
            else if (kind === 'barricade') populateBarricadeGroup(ctx, g, item);
            else if (kind === 'cone') populateConeGroup(ctx, g, item);
            else populateCalloutGroup(ctx, g, item, hooks);
        };

        if (g) {
            if (g.parentNode !== targetLayer) targetLayer.appendChild(g);
            populate();
            return;
        }

        const itemId = item.id;
        const resolveCurrent = () => {
            const list =
                kind === 'bar'
                    ? ctx.state.bars
                    : kind === 'barricade'
                        ? ctx.state.barricades
                        : kind === 'cone'
                            ? ctx.state.cones
                            : ctx.state.callouts;
            return list.find((it) => it.id === itemId) || item;
        };

        g = createSvgEl('g', {
            class: 'cone-map-node cursor-pointer select-none',
            'data-node-kind': kind,
            'data-node-id': itemId,
        });
        populate();

        g.addEventListener('mousedown', (e) => hooks.onNodeMouseDown(e, kind, resolveCurrent()));
        g.addEventListener('dblclick', (e) => hooks.onNodeDblClick(e, kind, resolveCurrent()));
        g.addEventListener('mouseenter', (e) => hooks.onNodeMouseEnter(e, kind, resolveCurrent()));
        g.addEventListener('mousemove', (e) => hooks.onNodeMouseMove(e));
        g.addEventListener('mouseleave', () => hooks.onNodeMouseLeave());

        targetLayer.appendChild(g);
    }

    function renderConnectedBarsForCone(coneId) {
        ctx.state.bars
            .filter((b) => b.start_cone_id === coneId || b.end_cone_id === coneId)
            .forEach((b) => renderNode('bar', b));
    }

    function renderCalloutsForTarget(kind, targetId) {
        ctx.state.callouts
            .filter((c) => (kind === 'cone' ? c.target_cone_id === targetId : c.target_barricade_id === targetId))
            .forEach((c) => renderNode('callout', c));
    }

    function renderBoothReference() {
        const layers = ensureLayers(ctx);
        if (!layers) return;
        renderBoothReferenceLayer(ctx, layers.booths);
    }

    function renderAll() {
        const layers = ensureLayers(ctx);
        if (!layers) return;

        renderBoothReferenceLayer(ctx, layers.booths);
        layers.bars.innerHTML = '';
        layers.barricades.innerHTML = '';
        layers.cones.innerHTML = '';
        layers.callouts.innerHTML = '';

        ctx.state.bars.forEach((b) => renderNode('bar', b));
        ctx.state.barricades.forEach((b) => renderNode('barricade', b));
        ctx.state.cones.forEach((c) => renderNode('cone', c));
        ctx.state.callouts.forEach((c) => renderNode('callout', c));

        hooks.onAfterRenderAll();
    }

    function clearOverlays() {
        const layers = ensureLayers(ctx);
        if (layers) layers.overlays.innerHTML = '';
    }

    function renderLinePreview(points, barStyleKey) {
        const layers = ensureLayers(ctx);
        if (!layers) return;
        layers.overlays.innerHTML = '';

        const style = BAR_STYLES[barStyleKey] || BAR_STYLES.yellow_black;
        for (let i = 0; i < points.length - 1; i++) {
            layers.overlays.appendChild(
                createSvgEl('line', {
                    x1: points[i].x,
                    y1: points[i].y,
                    x2: points[i + 1].x,
                    y2: points[i + 1].y,
                    stroke: style.primary,
                    'stroke-width': '2.2',
                    'stroke-dasharray': '2 1',
                    'pointer-events': 'none',
                }),
            );
        }

        points.forEach((pt, idx) => {
            layers.overlays.appendChild(
                createSvgEl('circle', {
                    cx: pt.x,
                    cy: pt.y,
                    r: idx === 0 || idx === points.length - 1 ? '3.2' : '2.6',
                    fill: '#4f46e5',
                    stroke: '#ffffff',
                    'stroke-width': '0.9',
                    'pointer-events': 'none',
                }),
            );
        });
    }

    function drawGuideLine(x1, y1, x2, y2) {
        const layers = ensureLayers(ctx);
        if (!layers) return;
        layers.overlays.appendChild(
            createSvgEl('line', {
                x1,
                y1,
                x2,
                y2,
                stroke: '#06b6d4',
                'stroke-width': '0.6',
                'stroke-dasharray': '2 1',
                'pointer-events': 'none',
            }),
        );
    }

    return {
        renderNode,
        renderConnectedBarsForCone,
        renderCalloutsForTarget,
        renderBoothReference,
        renderAll,
        clearOverlays,
        renderLinePreview,
        drawGuideLine,
    };
}
