import { clamp, minutesToHhmm, snapMinutes } from './utils.js';

/**
 * 背景トラックのクリック＆ドラッグ範囲選択によるタスク作成/配置トリガー
 */
export function initGanttRangeSelector({
    rootEl,
    getBounds,
    getPxPerMin,
    canInteract = () => true,
    onSelectRange,
}) {
    if (!rootEl) return;

    let activeDrag = null;

    const removeMarquee = () => {
        if (activeDrag?.marqueeEl) {
            activeDrag.marqueeEl.remove();
        }
        activeDrag = null;
    };

    const getMinutesAtClientX = (zoneEl, clientX) => {
        const rect = zoneEl.getBoundingClientRect();
        const bounds = getBounds();
        const pxPerMin = getPxPerMin();
        const offsetX = clamp(clientX - rect.left, 0, rect.width);
        const rawMin = bounds.startMin + offsetX / pxPerMin;
        return clamp(snapMinutes(rawMin, 15), bounds.startMin, bounds.endMin);
    };

    const updateMarquee = (drag, currentMin) => {
        const bounds = getBounds();
        const pxPerMin = getPxPerMin();

        const startM = Math.min(drag.anchorMin, currentMin);
        const endM = Math.max(drag.anchorMin + 15, currentMin);
        const clampedEnd = Math.min(bounds.endMin, endM);

        const left = Math.round((startM - bounds.startMin) * pxPerMin);
        const width = Math.max(12, Math.round((clampedEnd - startM) * pxPerMin));

        drag.marqueeEl.style.left = `${left}px`;
        drag.marqueeEl.style.width = `${width}px`;
        drag.labelEl.textContent = `${minutesToHhmm(startM)}〜${minutesToHhmm(clampedEnd)}`;
    };

    rootEl.addEventListener('pointerdown', (e) => {
        if (e.button !== 0 || !canInteract()) return;
        if (e.target.closest('[data-gantt-bar]')) return;

        const zoneEl = e.target.closest('[data-track-zone]');
        const rowEl = zoneEl?.closest('[data-gantt-row]');
        if (!zoneEl || !rowEl) return;

        const anchorMin = getMinutesAtClientX(zoneEl, e.clientX);
        const bounds = getBounds();
        const clampedAnchor = Math.min(anchorMin, bounds.endMin - 15);

        const marqueeEl = document.createElement('div');
        marqueeEl.className = 'gantt-marquee-selection absolute top-1 bottom-1 rounded-lg bg-indigo-500/20 border-2 border-dashed border-indigo-600 z-20 pointer-events-none flex items-center justify-center';
        const labelEl = document.createElement('span');
        labelEl.className = 'px-1.5 py-0.5 rounded bg-indigo-600 text-white font-mono text-[10px] font-bold shadow-xs whitespace-nowrap';
        marqueeEl.appendChild(labelEl);
        zoneEl.appendChild(marqueeEl);

        activeDrag = {
            pointerId: e.pointerId,
            startX: e.clientX,
            zoneEl,
            rowEl,
            zoneType: zoneEl.dataset.trackZone || 'spot',
            anchorMin: clampedAnchor,
            currentMin: clampedAnchor + 15,
            moved: false,
            marqueeEl,
            labelEl,
        };

        updateMarquee(activeDrag, clampedAnchor + 15);
    });

    document.addEventListener('pointermove', (e) => {
        if (!activeDrag || e.pointerId !== activeDrag.pointerId) return;
        const dx = Math.abs(e.clientX - activeDrag.startX);
        if (dx > 5) {
            activeDrag.moved = true;
        }
        const curMin = getMinutesAtClientX(activeDrag.zoneEl, e.clientX);
        activeDrag.currentMin = curMin;
        updateMarquee(activeDrag, curMin);
    });

    document.addEventListener('pointerup', (e) => {
        if (!activeDrag || e.pointerId !== activeDrag.pointerId) return;

        const drag = activeDrag;
        const bounds = getBounds();
        let finalStart;
        let finalEnd;

        if (!drag.moved) {
            // 単クリック時: クリックした30分/15分境界を開始とし、デフォルト長を提案
            finalStart = clamp(Math.floor(drag.anchorMin / 30) * 30, bounds.startMin, bounds.endMin - 30);
            const defaultDuration = drag.zoneType === 'routine' ? 120 : 60;
            finalEnd = clamp(finalStart + defaultDuration, finalStart + 30, bounds.endMin);
        } else {
            finalStart = Math.min(drag.anchorMin, drag.currentMin);
            finalEnd = Math.max(drag.anchorMin, drag.currentMin);
            if (finalEnd - finalStart < 15) {
                finalEnd = Math.min(bounds.endMin, finalStart + 30);
            }
        }

        removeMarquee();

        onSelectRange?.({
            rowEl: drag.rowEl,
            zoneType: drag.zoneType,
            startMin: finalStart,
            endMin: finalEnd,
            startTime: minutesToHhmm(finalStart),
            endTime: minutesToHhmm(finalEnd),
            isDrag: drag.moved,
        });
    });

    document.addEventListener('pointercancel', () => {
        removeMarquee();
    });
}
