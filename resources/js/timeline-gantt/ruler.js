import { escapeHtml } from '../utils/html.js';
import { minutesToHhmm } from './utils.js';

/**
 * ズームモードに応じた 1分あたりのピクセル数を算出する
 *
 * @param {'15m'|'30m'|'60m'|'fit'} zoomMode
 * @param {number} viewportWidth
 * @param {number} totalMinutes
 * @returns {number}
 */
export function computePxPerMin(zoomMode, viewportWidth, totalMinutes) {
    const safeTotal = Math.max(60, totalMinutes || 60);
    if (zoomMode === '15m') return 4.0;
    if (zoomMode === '60m') return 1.5;
    if (zoomMode === 'fit') {
        const available = Math.max(320, (viewportWidth || 960) - 8);
        return Math.max(0.85, available / safeTotal);
    }
    return 2.6; // '30m' default (78px / 30min)
}

/**
 * 時刻ルーラー・規制/開催バンド・背景縦グリッド線・現在時刻縦ラインを描画する (CSSOM使用・CSP完全準拠)
 */
export function renderTimelineHeaderAndGuides({
    canvasContainers,
    rulerEl,
    bandsRowEl,
    gridGuidesLayerClass = 'gantt-grid-guides',
    startMin,
    endMin,
    pxPerMin,
    zoomMode,
    roadBands = [],
    festivalBands = [],
    nowMin = null,
    scrollContainer = null,
}) {
    const totalMinutes = Math.max(60, endMin - startMin);
    const totalWidth = Math.round(totalMinutes * pxPerMin);

    // 全キャンバス幅を同期
    canvasContainers.forEach((el) => {
        el.style.width = `${totalWidth}px`;
        el.style.minWidth = `${totalWidth}px`;
    });

    // 1. 時刻ルーラー描画
    if (rulerEl) {
        rulerEl.style.width = `${totalWidth}px`;
        rulerEl.innerHTML = '';

        const labelStep = (zoomMode === '60m' || (zoomMode === 'fit' && pxPerMin < 1.6)) ? 60 : 30;
        const tickStep = zoomMode === '15m' ? 15 : 30;

        for (let m = startMin; m < endMin; m += tickStep) {
            const leftPx = Math.round((m - startMin) * pxPerMin);
            const tickWidth = Math.round(tickStep * pxPerMin);
            const isHour = m % 60 === 0;
            const showLabel = m % labelStep === 0;

            const tick = document.createElement('div');
            tick.className = `absolute top-0 bottom-0 border-l select-none flex items-center pl-1 ${
                isHour ? 'border-slate-500/80 text-white font-bold' : 'border-slate-700/80 text-slate-300 font-medium'
            }`;
            tick.style.left = `${leftPx}px`;
            tick.style.width = `${tickWidth}px`;

            if (showLabel) {
                const span = document.createElement('span');
                span.className = 'font-mono text-[11px] leading-none';
                span.textContent = minutesToHhmm(m);
                tick.appendChild(span);
            }

            rulerEl.appendChild(tick);
        }

        // 現在時刻ピル（ルーラー上）
        if (nowMin !== null && nowMin >= startMin && nowMin <= endMin) {
            const nowLeft = Math.round((nowMin - startMin) * pxPerMin);
            const nowBadge = document.createElement('div');
            nowBadge.className = 'absolute top-0.5 -translate-x-1/2 z-30 px-1.5 py-0.5 rounded-full bg-red-600 text-white font-mono text-[9px] font-bold shadow pointer-events-none';
            nowBadge.style.left = `${nowLeft}px`;
            nowBadge.textContent = minutesToHhmm(nowMin);
            rulerEl.appendChild(nowBadge);
        }
    }

    // 2. 道路規制帯 & まつり開催帯描画
    if (bandsRowEl) {
        const hasAnyBands = roadBands.length > 0 || festivalBands.length > 0;
        bandsRowEl.classList.toggle('hidden', !hasAnyBands);
        bandsRowEl.style.width = `${totalWidth}px`;
        bandsRowEl.innerHTML = '';

        if (hasAnyBands) {
            const renderBandTrack = (bands, type) => {
                if (bands.length === 0) return;
                const track = document.createElement('div');
                track.className = 'relative h-5 border-b border-slate-200 bg-slate-50/90';

                bands.forEach((b) => {
                    const left = Math.round((b.start_min - startMin) * pxPerMin);
                    const width = Math.max(18, Math.round((b.end_min - b.start_min) * pxPerMin));
                    const isRoad = type === 'road';
                    const cls = isRoad
                        ? (b.status === 'closed' ? 'bg-red-100 text-red-800 border-red-300' : 'bg-amber-100 text-amber-800 border-amber-300')
                        : 'bg-emerald-100 text-emerald-800 border-emerald-300';
                    const prefix = isRoad ? '🚧' : '🏮';

                    const pill = document.createElement('div');
                    pill.className = `absolute top-0.5 bottom-0.5 rounded border text-[10px] font-bold px-1.5 flex items-center truncate ${cls}`;
                    pill.style.left = `${left}px`;
                    pill.style.width = `${width}px`;
                    pill.title = `${b.label} (${minutesToHhmm(b.start_min)}〜${minutesToHhmm(b.end_min)})`;
                    pill.innerHTML = `<span class="truncate">${prefix} ${escapeHtml(b.label)} (${minutesToHhmm(b.start_min)}〜${minutesToHhmm(b.end_min)})</span>`;
                    track.appendChild(pill);
                });

                bandsRowEl.appendChild(track);
            };

            renderBandTrack(roadBands, 'road');
            renderBandTrack(festivalBands, 'festival');
        }
    }

    // 3. 各行背景の縦グリッド線 & 現在時刻赤縦ラインを描画
    const guideStep = zoomMode === '15m' ? 15 : 30;
    const guideContainers = document.querySelectorAll(`.${gridGuidesLayerClass}`);
    guideContainers.forEach((layer) => {
        layer.innerHTML = '';
        for (let m = startMin; m < endMin; m += guideStep) {
            const leftPx = Math.round((m - startMin) * pxPerMin);
            const isHour = m % 60 === 0;
            const isHalf = m % 30 === 0;
            const line = document.createElement('div');
            line.className = `absolute top-0 bottom-0 border-l pointer-events-none ${
                isHour ? 'border-slate-200/90' : (isHalf ? 'border-slate-200/50' : 'border-slate-100/70')
            }`;
            line.style.left = `${leftPx}px`;
            layer.appendChild(line);
        }

        if (nowMin !== null && nowMin >= startMin && nowMin <= endMin) {
            const nowLeft = Math.round((nowMin - startMin) * pxPerMin);
            const nowLine = document.createElement('div');
            nowLine.className = 'absolute top-0 bottom-0 w-0.5 bg-red-500/75 pointer-events-none z-10';
            nowLine.style.left = `${nowLeft}px`;
            layer.appendChild(nowLine);
        }
    });

    if (scrollContainer && nowMin !== null && nowMin >= startMin && nowMin <= endMin) {
        scrollContainer.dataset.nowOffsetPx = String(Math.round((nowMin - startMin) * pxPerMin));
    }
}
