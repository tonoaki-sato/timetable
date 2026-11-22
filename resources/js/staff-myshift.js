import { createMyShiftState, initCopyUrlButton } from './staff-myshift/state.js';
import {
    applyLeaderLaneTab,
    applyLeaderReqTab,
    applyMainTab,
    applyTimelineFilters,
    initProgressBars,
} from './staff-myshift/filters.js';
import { createRealtimeSync } from './staff-myshift/realtime-sync.js';
import { initMyShiftActions } from './staff-myshift/actions.js';

/**
 * 現場マイタイムテーブル用スクリプト (ES Module / Vite)
 * CSP準拠（外部バンドル・イベント委譲・CSSOM制御）
 */
document.addEventListener('DOMContentLoaded', () => {
    initCopyUrlButton();

    const timelineRoot = document.getElementById('myshiftTimelineRoot');
    if (!timelineRoot) {
        return;
    }

    const state = createMyShiftState();
    initProgressBars();

    const sync = createRealtimeSync(state, {
        onSynced: () => {
            applyMainTab(state, state.activeMainTab);
            applyTimelineFilters(state);
            applyLeaderLaneTab(state, state.activeLeaderLaneId);
            state.activeLeaderReqTabs.forEach((reqType, laneId) => {
                applyLeaderReqTab(state, laneId, reqType);
            });
            initProgressBars();
        },
    });

    initMyShiftActions(state, sync);
    sync.startAutoSync();
});
