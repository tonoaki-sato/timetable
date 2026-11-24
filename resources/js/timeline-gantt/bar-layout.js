/**
 * 2層（定常トラック / スポットトラック）マルチトラック・ガントバー配置エンジン (CSSOM)
 */
export function layoutGanttRows({
    rootEl,
    startMin,
    pxPerMin,
    barHeight = 42,
    barGap = 4,
    zonePadding = 5,
    minZoneTracks = 1,
}) {
    if (!rootEl) return;

    const rows = rootEl.querySelectorAll('[data-gantt-row]');
    rows.forEach((row) => {
        if (row.classList.contains('hidden')) return;

        const leftRoutineSlot = row.querySelector('[data-left-zone="routine"]');
        const leftSpotSlot = row.querySelector('[data-left-zone="spot"]');
        const rightRoutineZone = row.querySelector('[data-track-zone="routine"]');
        const rightSpotZone = row.querySelector('[data-track-zone="spot"]');

        const layoutZone = (zoneEl, leftSlotEl) => {
            if (!zoneEl) return;
            const visibleBars = Array.from(zoneEl.querySelectorAll('[data-gantt-bar]'))
                .filter((b) => !b.classList.contains('hidden'));

            // 表示中バーだけの最大 subTrack または元データ段数を考慮してコンパクトに段詰め
            const subTrackCount = computeZoneHeightTracks(zoneEl, visibleBars, minZoneTracks);
            const zoneHeight = subTrackCount * barHeight + Math.max(0, subTrackCount - 1) * barGap + zonePadding * 2;

            zoneEl.style.height = `${zoneHeight}px`;
            if (leftSlotEl) {
                leftSlotEl.style.height = `${zoneHeight}px`;
            }

            visibleBars.forEach((bar) => {
                const sMin = parseInt(bar.dataset.startMin || '0', 10);
                const eMin = parseInt(bar.dataset.endMin || '0', 10);
                const subTrack = parseInt(bar.dataset.renderTrack ?? bar.dataset.subTrack ?? '0', 10);

                const left = Math.round((sMin - startMin) * pxPerMin);
                const width = Math.max(24, Math.round((eMin - sMin) * pxPerMin));
                const top = zonePadding + subTrack * (barHeight + barGap);

                bar.style.left = `${left}px`;
                bar.style.width = `${width}px`;
                bar.style.top = `${top}px`;
                bar.style.height = `${barHeight}px`;
            });
        };

        layoutZone(rightRoutineZone, leftRoutineSlot);
        layoutZone(rightSpotZone, leftSpotSlot);
    });
}

/**
 * 表示中バーに対して最小段数を再計算し data-render-track へ設定する
 */
function computeZoneHeightTracks(zoneEl, visibleBars, minTracks) {
    if (visibleBars.length === 0) {
        zoneEl.dataset.renderedTracks = String(minTracks);
        return minTracks;
    }

    const sorted = [...visibleBars].sort((a, b) => {
        const sA = parseInt(a.dataset.startMin || '0', 10);
        const sB = parseInt(b.dataset.startMin || '0', 10);
        if (sA !== sB) return sA - sB;
        const eA = parseInt(a.dataset.endMin || '0', 10);
        const eB = parseInt(b.dataset.endMin || '0', 10);
        return eA - eB;
    });

    const trackEnds = [];
    sorted.forEach((bar) => {
        const sMin = parseInt(bar.dataset.startMin || '0', 10);
        const eMin = Math.max(sMin + 1, parseInt(bar.dataset.endMin || '0', 10));

        let assigned = -1;
        for (let i = 0; i < trackEnds.length; i++) {
            if (trackEnds[i] <= sMin) {
                assigned = i;
                trackEnds[i] = eMin;
                break;
            }
        }
        if (assigned === -1) {
            assigned = trackEnds.length;
            trackEnds.push(eMin);
        }
        bar.dataset.renderTrack = String(assigned);
    });

    const total = Math.max(minTracks, trackEnds.length);
    zoneEl.dataset.renderedTracks = String(total);
    return total;
}
