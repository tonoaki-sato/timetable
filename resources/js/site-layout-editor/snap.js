import { STREET_RAILS_X } from './constants.js';
import { createSvgEl, ensureLayers } from './svg-renderer.js';

/**
 * 通り沿い歩道レール範囲内か判定する
 */
export function isAlongStreet(x, _y, w) {
    const cx = x + w / 2;
    return (cx >= 305 && cx <= 372) || (cx >= 676 && cx <= 744);
}

/**
 * スナップ補助線をクリアする
 */
export function clearGuideLines(ctx) {
    const layers = ensureLayers(ctx);
    if (layers) layers.overlays.innerHTML = '';
}

/**
 * 補助線を描画する
 */
export function drawGuideLine(ctx, x1, y1, x2, y2) {
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

/**
 * 通り沿いレールおよび隣接ノード辺へのスマートスナップ計算
 */
export function computeSnap(ctx, movingId, rawX, rawY, w, h) {
    clearGuideLines(ctx);
    if (!ctx.state.snapEnabled) return { x: rawX, y: rawY };

    let sx = rawX;
    let sy = rawY;
    const thresh = 2.0;

    // 1. 通り沿い4レール X 座標スナップ
    for (const railX of STREET_RAILS_X) {
        if (Math.abs(rawX - railX) <= thresh) {
            sx = railX;
            drawGuideLine(ctx, railX, 80, railX, 1100);
            break;
        }
    }

    // 2. 他ノードの辺・密着スナップ
    for (const other of ctx.state.placements) {
        if (other.id === movingId || ctx.state.selectedIds.has(other.id)) continue;
        const ox = parseFloat(other.pos_x);
        const oy = parseFloat(other.pos_y);
        const ow = parseFloat(other.width);
        const oh = parseFloat(other.height);
        if (Math.abs(oy - rawY) > 80 && Math.abs(ox - rawX) > 80) continue;

        if (Math.abs(sx - ox) <= thresh) {
            sx = ox;
            drawGuideLine(ctx, ox, Math.min(sy, oy) - 10, ox, Math.max(sy + h, oy + oh) + 10);
        } else if (Math.abs(sx - (ox + ow)) <= thresh) {
            sx = ox + ow;
            drawGuideLine(ctx, ox + ow, Math.min(sy, oy) - 10, ox + ow, Math.max(sy + h, oy + oh) + 10);
        } else if (Math.abs(sx + w - ox) <= thresh) {
            sx = ox - w;
            drawGuideLine(ctx, ox, Math.min(sy, oy) - 10, ox, Math.max(sy + h, oy + oh) + 10);
        }

        if (Math.abs(sy - oy) <= thresh) {
            sy = oy;
            drawGuideLine(ctx, Math.min(sx, ox) - 10, oy, Math.max(sx + w, ox + ow) + 10, oy);
        } else if (Math.abs(sy - (oy + oh)) <= thresh) {
            sy = oy + oh;
            drawGuideLine(ctx, Math.min(sx, ox) - 10, oy + oh, Math.max(sx + w, ox + ow) + 10, oy + oh);
        } else if (Math.abs(sy + h - oy) <= thresh) {
            sy = oy - h;
            drawGuideLine(ctx, Math.min(sx, ox) - 10, oy, Math.max(sx + w, ox + ow) + 10, oy);
        }
    }

    return { x: Math.round(sx * 100) / 100, y: Math.round(sy * 100) / 100 };
}
