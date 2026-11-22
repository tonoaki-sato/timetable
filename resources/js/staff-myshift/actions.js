import { getCsrfToken } from './state.js';
import {
    applyLeaderLaneTab,
    applyLeaderReqTab,
    applyMainTab,
    applyTimelineFilters,
} from './filters.js';

/**
 * 現場マイタイムテーブル 非同期操作・イベント委譲（出勤受付・進捗更新・申請送信）
 */
function initCheckInHandler(state, fetchRealtimeTimeline) {
    const checkInBtn = document.getElementById('checkInBtn');
    if (!checkInBtn) {
        return;
    }

    checkInBtn.addEventListener('click', async () => {
        const endpoint = checkInBtn.dataset.endpoint;
        const timetableId = Number(checkInBtn.dataset.timetableId);
        if (!endpoint || !Number.isInteger(timetableId) || timetableId <= 0) {
            return;
        }

        checkInBtn.disabled = true;
        const originalHtml = checkInBtn.innerHTML;
        checkInBtn.innerHTML = '<span>⏳</span><span>受付中...</span>';

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    Accept: 'application/json',
                },
                body: JSON.stringify({ timetable_id: timetableId }),
            });

            if (response.ok) {
                const data = await response.json();
                const timeStr =
                    data.time ||
                    new Date().toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' });

                const container = checkInBtn.parentElement;
                if (container) {
                    container.innerHTML = `
                        <div class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                            <span>✅</span>
                            <span>受付済 (${timeStr})</span>
                        </div>
                    `;
                }
                await fetchRealtimeTimeline({ skipLeaderPanelIfFocused: true });
            } else {
                const errData = await response.json().catch(() => null);
                state.showToast(errData?.message || 'チェックインの記録に失敗しました。電波の良い場所で再度お試しください。', true);
                checkInBtn.disabled = false;
                checkInBtn.innerHTML = originalHtml;
            }
        } catch (err) {
            console.error('Checkin error:', err);
            state.showToast('通信エラーが発生しました。電波の良い場所で再度お試しください。', true);
            checkInBtn.disabled = false;
            checkInBtn.innerHTML = originalHtml;
        }
    });
}

export function initMyShiftActions(state, { fetchRealtimeTimeline }) {
    initCheckInHandler(state, fetchRealtimeTimeline);

    document.addEventListener('click', async (e) => {
        const mainTabBtn = e.target.closest('.myshiftMainTabBtn');
        if (mainTabBtn) {
            applyMainTab(state, mainTabBtn.dataset.mainTab || 'shift');
            return;
        }

        const laneFilterBtn = e.target.closest('.myshiftLaneFilterBtn');
        if (laneFilterBtn) {
            state.activeLaneFilter = laneFilterBtn.dataset.laneFilter || 'all';
            applyTimelineFilters(state);
            return;
        }

        if (e.target.closest('#myshiftIncompleteToggle')) {
            state.incompleteOnly = !state.incompleteOnly;
            applyTimelineFilters(state);
            return;
        }

        if (e.target.closest('#myshiftResetFiltersBtn')) {
            state.activeLaneFilter = 'all';
            state.incompleteOnly = false;
            applyTimelineFilters(state);
            return;
        }

        const leaderLaneBtn = e.target.closest('.leaderLaneTabBtn');
        if (leaderLaneBtn) {
            applyLeaderLaneTab(state, leaderLaneBtn.dataset.leaderLaneTab || null);
            return;
        }

        const leaderReqBtn = e.target.closest('.leaderReqTabBtn');
        if (leaderReqBtn) {
            const laneId = leaderReqBtn.dataset.laneId;
            const reqTab = leaderReqBtn.dataset.leaderReqTab;
            if (laneId && reqTab) {
                applyLeaderReqTab(state, laneId, reqTab);
            }
            return;
        }

        const toggleBtn = e.target.closest('.taskDetailToggleBtn');
        if (toggleBtn) {
            const card = toggleBtn.closest('[data-task-card]');
            if (!card) {
                return;
            }
            const panel = card.querySelector('.taskDetailPanel');
            const icon = toggleBtn.querySelector('svg');
            if (panel) {
                const willOpen = panel.classList.contains('hidden');
                panel.classList.toggle('hidden');
                toggleBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                if (icon) {
                    icon.classList.toggle('rotate-180', willOpen);
                }
            }
            return;
        }

        const statusBtn = e.target.closest('.assignmentStatusBtn');
        if (statusBtn) {
            const endpoint = statusBtn.dataset.endpoint;
            const status = statusBtn.dataset.status;
            if (!endpoint || !status) {
                return;
            }

            statusBtn.disabled = true;
            const originalHtml = statusBtn.innerHTML;
            statusBtn.innerHTML = '<span>⏳</span>';

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ status }),
                });

                if (response.ok) {
                    const data = await response.json().catch(() => ({}));
                    await fetchRealtimeTimeline({ skipLeaderPanelIfFocused: false });
                    state.showToast(`作業進捗を「${data.status_label || '更新済'}」にしました`);
                } else {
                    const err = await response.json().catch(() => ({}));
                    state.showToast(err.message || 'ステータスの更新に失敗しました。', true);
                    statusBtn.disabled = false;
                    statusBtn.innerHTML = originalHtml;
                }
            } catch (err) {
                console.error('Status update error:', err);
                state.showToast('通信エラーが発生しました。電波の良い場所でお試しください。', true);
                statusBtn.disabled = false;
                statusBtn.innerHTML = originalHtml;
            }
        }
    });

    document.addEventListener('change', async (e) => {
        const select = e.target;
        if (!select || !select.matches('select[data-leader-task-select]')) {
            return;
        }

        const endpoint = select.dataset.endpoint;
        if (!endpoint) {
            return;
        }

        select.disabled = true;

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    Accept: 'application/json',
                },
                body: JSON.stringify({ status: select.value }),
            });

            if (response.ok) {
                const data = await response.json().catch(() => ({}));
                await fetchRealtimeTimeline({ skipLeaderPanelIfFocused: false });
                state.showToast(`班タスクの進捗を「${data.status_label || '更新済'}」に更新しました`);
            } else {
                const err = await response.json().catch(() => ({}));
                state.showToast(err.message || '班タスクの更新に失敗しました。', true);
                select.disabled = false;
            }
        } catch (err) {
            console.error('Leader task update error:', err);
            state.showToast('通信エラーが発生しました。', true);
            select.disabled = false;
        }
    });

    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (!form || !form.matches('form[data-async-lane-request]')) {
            return;
        }

        e.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn ? submitBtn.textContent : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = '送信中...';
        }

        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    Accept: 'application/json',
                },
                body: formData,
            });

            if (response.ok) {
                const data = await response.json().catch(() => ({}));
                form.reset();
                await fetchRealtimeTimeline({ skipLeaderPanelIfFocused: false });
                state.showToast(data.message || '管理者（本部）へ送信しました。');
            } else {
                const err = await response.json().catch(() => ({}));
                state.showToast(err.message || '申請の送信に失敗しました。入力内容をご確認ください。', true);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                }
            }
        } catch (err) {
            console.error('Lane request error:', err);
            state.showToast('通信エラーが発生しました。電波の良い場所で再度お試しください。', true);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            }
        }
    });
}
