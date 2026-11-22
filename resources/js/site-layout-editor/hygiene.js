import { HYGIENE_RADIUS_PT } from './constants.js';
import { ensureLayers } from './svg-renderer.js';

/**
 * 20m 衛生サークル描画 & 赤枠ブース圏外チェックを実行する
 */
export function renderHygieneCirclesAndCheck(ctx) {
    const layers = ensureLayers(ctx);
    if (!layers) return;
    layers.hygiene.innerHTML = '';

    const { placements, showHygiene } = ctx.state;
    const { iconDefinitions } = ctx;
    const { hygieneStatus, hygieneWarningList } = ctx.dom;

    const waters = placements.filter((p) => {
        const def = iconDefinitions[p.shape_type];
        return p.shape_type === 'water_supply' || (def && def.has_hygiene_circle);
    });
    const redBooths = placements.filter((p) => {
        const def = iconDefinitions[p.shape_type];
        return p.color_theme === 'red' && p.shape_type !== 'area_note' && (!def || def.is_hygiene_target !== false);
    });

    if (showHygiene) {
        waters.forEach((w) => {
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
                const rad1 = ((startDeg - 90) * Math.PI) / 180;
                const rad2 = ((endDeg - 90) * Math.PI) / 180;
                const x1 = cx + HYGIENE_RADIUS_PT * Math.cos(rad1);
                const y1 = cy + HYGIENE_RADIUS_PT * Math.sin(rad1);
                const x2 = cx + HYGIENE_RADIUS_PT * Math.cos(rad2);
                const y2 = cy + HYGIENE_RADIUS_PT * Math.sin(rad2);
                const largeArc = endDeg - startDeg > 180 ? 1 : 0;
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute(
                    'd',
                    `M ${x1.toFixed(2)} ${y1.toFixed(2)} A ${HYGIENE_RADIUS_PT} ${HYGIENE_RADIUS_PT} 0 ${largeArc} 1 ${x2.toFixed(2)} ${y2.toFixed(2)}`,
                );
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
    redBooths.forEach((b) => {
        const bx = parseFloat(b.pos_x) + parseFloat(b.width) / 2;
        const by = parseFloat(b.pos_y) + parseFloat(b.height) / 2;
        const covered = waters.some((w) => {
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
            outOfRange.forEach((b) => {
                const li = document.createElement('li');
                li.textContent = `• ${b.display_text || '無題'} (X:${Math.round(b.pos_x)}, Y:${Math.round(b.pos_y)})`;
                hygieneWarningList.appendChild(li);
            });
        }
    }
}
