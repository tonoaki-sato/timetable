/**
 * 現場マイタイムテーブル リアルタイムHTML同期・アコーディオン展開状態復元
 */
function collectOpenAssignmentIds() {
    const openIds = new Set();
    document.querySelectorAll('#myshiftTimelineContainer [data-task-card]').forEach((card) => {
        const panel = card.querySelector('.taskDetailPanel');
        if (panel && !panel.classList.contains('hidden') && card.dataset.assignmentId) {
            openIds.add(card.dataset.assignmentId);
        }
    });
    return openIds;
}

function restoreOpenAssignmentIds(openIds) {
    if (openIds.size === 0) {
        return;
    }
    document.querySelectorAll('#myshiftTimelineContainer [data-task-card]').forEach((card) => {
        if (card.dataset.assignmentId && openIds.has(card.dataset.assignmentId)) {
            const panel = card.querySelector('.taskDetailPanel');
            const btn = card.querySelector('.taskDetailToggleBtn');
            const icon = btn ? btn.querySelector('svg') : null;
            if (panel) {
                panel.classList.remove('hidden');
            }
            if (btn) {
                btn.setAttribute('aria-expanded', 'true');
            }
            if (icon) {
                icon.classList.add('rotate-180');
            }
        }
    });
}

export function createRealtimeSync(state, { onSynced }) {
    async function fetchRealtimeTimeline({ skipLeaderPanelIfFocused = true } = {}) {
        if (state.isSyncing) {
            return;
        }
        state.isSyncing = true;

        const syncText = document.getElementById('myshiftSyncText');
        if (syncText) {
            syncText.textContent = '同期中...';
        }

        try {
            const response = await fetch(window.location.href, {
                method: 'GET',
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const text = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(text, 'text/html');

                const openIds = collectOpenAssignmentIds();

                const currentLeaderPanel = document.getElementById('leaderModePanel');
                const activeEl = document.activeElement;
                const leaderInputFocused =
                    skipLeaderPanelIfFocused &&
                    currentLeaderPanel &&
                    activeEl &&
                    currentLeaderPanel.contains(activeEl) &&
                    activeEl.matches('input, textarea, select');

                if (leaderInputFocused) {
                    const newShiftPanel = doc.getElementById('myshiftModePanel');
                    const currentShiftPanel = document.getElementById('myshiftModePanel');
                    if (newShiftPanel && currentShiftPanel) {
                        currentShiftPanel.innerHTML = newShiftPanel.innerHTML;
                    }
                } else {
                    const newSyncRoot = doc.getElementById('myshiftSyncRoot');
                    const currentSyncRoot = document.getElementById('myshiftSyncRoot');
                    if (newSyncRoot && currentSyncRoot) {
                        currentSyncRoot.innerHTML = newSyncRoot.innerHTML;
                    }
                }

                restoreOpenAssignmentIds(openIds);
                onSynced();

                const updatedSyncText = document.getElementById('myshiftSyncText');
                if (updatedSyncText) {
                    const now = new Date();
                    const timeStr = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
                    updatedSyncText.textContent = `${timeStr} 同期済`;
                }
            }
        } catch (err) {
            console.warn('Realtime sync warning:', err);
            const updatedSyncText = document.getElementById('myshiftSyncText');
            if (updatedSyncText) {
                updatedSyncText.textContent = '再同期待機中';
            }
        } finally {
            state.isSyncing = false;
        }
    }

    function startAutoSync() {
        let lastTriggeredSlot = null;
        setInterval(() => {
            if (document.hidden) {
                return;
            }

            const now = new Date();
            const sec = now.getSeconds();

            if (sec === 0 || sec === 30) {
                const slotKey = `${now.getHours()}:${now.getMinutes()}:${sec}`;
                if (lastTriggeredSlot !== slotKey) {
                    lastTriggeredSlot = slotKey;
                    fetchRealtimeTimeline({ skipLeaderPanelIfFocused: true });
                }
            }
        }, 1000);

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                fetchRealtimeTimeline({ skipLeaderPanelIfFocused: true });
            }
        });
    }

    return { fetchRealtimeTimeline, startAutoSync };
}
