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

    initMyShiftPage();
});

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') || '' : '';
}

function initCopyUrlButton() {
    const copyUrlBtn = document.getElementById('copyUrlBtn');
    if (!copyUrlBtn) {
        return;
    }

    copyUrlBtn.addEventListener('click', async () => {
        const url = window.location.href;
        try {
            await navigator.clipboard.writeText(url);
            const originalHtml = copyUrlBtn.innerHTML;
            copyUrlBtn.innerHTML = '<span>✅</span><span>URLをコピーしました！</span>';
            setTimeout(() => {
                copyUrlBtn.innerHTML = originalHtml;
            }, 2500);
        } catch {
            window.prompt('以下のURLをコピーして保存してください:', url);
        }
    });
}

function initMyShiftPage() {
    let activeMainTab = 'shift';
    let activeLaneFilter = 'all';
    let incompleteOnly = false;
    let activeLeaderLaneId = null;
    const activeLeaderReqTabs = new Map();
    let isSyncing = false;
    let toastTimer = null;

    const initialLeaderTab = document.querySelector('.leaderLaneTabBtn[aria-selected="true"]');
    if (initialLeaderTab) {
        activeLeaderLaneId = initialLeaderTab.dataset.leaderLaneTab || null;
    }

    initProgressBars();
    initCheckInHandler();

    function showToast(message, isError = false) {
        const toast = document.getElementById('myshiftToast');
        const toastText = document.getElementById('myshiftToastText');
        if (!toast || !toastText) {
            return;
        }

        toastText.textContent = isError ? `⚠️ ${message}` : `✅ ${message}`;
        toast.classList.remove(
            'hidden',
            'bg-emerald-950/90',
            'border-emerald-500/50',
            'text-emerald-200',
            'bg-rose-950/90',
            'border-rose-500/50',
            'text-rose-200'
        );

        if (isError) {
            toast.classList.add('bg-rose-950/90', 'border-rose-500/50', 'text-rose-200');
        } else {
            toast.classList.add('bg-emerald-950/90', 'border-emerald-500/50', 'text-emerald-200');
        }

        if (toastTimer) {
            clearTimeout(toastTimer);
        }
        toastTimer = setTimeout(() => {
            toast.classList.add('hidden');
        }, 3500);
    }

    function initProgressBars() {
        document.querySelectorAll('.myshift-progress-bar[data-progress-percent]').forEach((bar) => {
            const raw = Number.parseInt(bar.dataset.progressPercent || '0', 10);
            const pct = Number.isNaN(raw) ? 0 : Math.max(0, Math.min(100, raw));
            bar.style.width = `${pct}%`;
        });
    }

    function initCheckInHandler() {
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
                    window.alert(errData?.message || 'チェックインの記録に失敗しました。電波の良い場所で再度お試しください。');
                    checkInBtn.disabled = false;
                    checkInBtn.innerHTML = originalHtml;
                }
            } catch (err) {
                console.error('Checkin error:', err);
                window.alert('通信エラーが発生しました。電波の良い場所で再度お試しください。');
                checkInBtn.disabled = false;
                checkInBtn.innerHTML = originalHtml;
            }
        });
    }

    function applyMainTab(tab) {
        const shiftPanel = document.getElementById('myshiftModePanel');
        const leaderPanel = document.getElementById('leaderModePanel');
        const buttons = document.querySelectorAll('.myshiftMainTabBtn');

        if (!leaderPanel || buttons.length === 0) {
            activeMainTab = 'shift';
            return;
        }

        activeMainTab = tab === 'leader' ? 'leader' : 'shift';

        if (shiftPanel) {
            shiftPanel.classList.toggle('hidden', activeMainTab !== 'shift');
        }
        leaderPanel.classList.toggle('hidden', activeMainTab !== 'leader');

        buttons.forEach((btn) => {
            const isSelected = btn.dataset.mainTab === activeMainTab;
            btn.setAttribute('aria-selected', isSelected ? 'true' : 'false');
            btn.classList.toggle('bg-indigo-600', isSelected && activeMainTab === 'shift');
            btn.classList.toggle('bg-amber-500', isSelected && activeMainTab === 'leader');
            btn.classList.toggle('text-slate-950', isSelected && activeMainTab === 'leader');
            btn.classList.toggle('text-white', isSelected && activeMainTab === 'shift');
            btn.classList.toggle('shadow-sm', isSelected);
            btn.classList.toggle('text-slate-300', !isSelected);
            btn.classList.toggle('hover:text-white', !isSelected);
            btn.classList.toggle('hover:bg-slate-800/80', !isSelected);
        });
    }

    function applyTimelineFilters() {
        const filterButtons = document.querySelectorAll('.myshiftLaneFilterBtn');
        if (filterButtons.length > 0) {
            const hasActiveLane = Array.from(filterButtons).some(
                (btn) => btn.dataset.laneFilter === activeLaneFilter
            );
            if (!hasActiveLane) {
                activeLaneFilter = 'all';
            }

            filterButtons.forEach((btn) => {
                const isSelected = btn.dataset.laneFilter === activeLaneFilter;
                btn.classList.toggle('bg-indigo-600', isSelected);
                btn.classList.toggle('text-white', isSelected);
                btn.classList.toggle('border-indigo-400', isSelected);
                btn.classList.toggle('bg-slate-900', !isSelected);
                btn.classList.toggle('text-slate-300', !isSelected);
                btn.classList.toggle('border-slate-700', !isSelected);
            });
        }

        const incompleteBtn = document.getElementById('myshiftIncompleteToggle');
        if (incompleteBtn) {
            incompleteBtn.setAttribute('aria-pressed', incompleteOnly ? 'true' : 'false');
            incompleteBtn.classList.toggle('bg-emerald-600', incompleteOnly);
            incompleteBtn.classList.toggle('text-white', incompleteOnly);
            incompleteBtn.classList.toggle('border-emerald-400', incompleteOnly);
            incompleteBtn.classList.toggle('bg-slate-900', !incompleteOnly);
            incompleteBtn.classList.toggle('text-slate-300', !incompleteOnly);
            incompleteBtn.classList.toggle('border-slate-700', !incompleteOnly);
        }

        const cards = document.querySelectorAll('#myshiftTimelineContainer [data-task-card]');
        let visibleCount = 0;

        cards.forEach((card) => {
            const cardLaneId = card.dataset.laneId || 'none';
            const isCompleted = card.dataset.completed === '1';

            const matchesLane = activeLaneFilter === 'all' || cardLaneId === activeLaneFilter;
            const matchesStatus = !incompleteOnly || !isCompleted;
            const visible = matchesLane && matchesStatus;

            card.classList.toggle('hidden', !visible);
            if (visible) {
                visibleCount += 1;
            }
        });

        document.querySelectorAll('#myshiftTimelineContainer .myshift-date-group').forEach((group) => {
            const hasVisibleCard = group.querySelector('[data-task-card]:not(.hidden)') !== null;
            group.classList.toggle('hidden', !hasVisibleCard);
        });

        const emptyBox = document.getElementById('myshiftFilteredEmpty');
        if (emptyBox) {
            emptyBox.classList.toggle('hidden', !(cards.length > 0 && visibleCount === 0));
        }
    }

    function applyLeaderLaneTab(laneId) {
        const tabs = document.querySelectorAll('.leaderLaneTabBtn');
        const panels = document.querySelectorAll('[data-leader-lane-panel]');
        if (panels.length === 0) {
            return;
        }

        const validIds = Array.from(panels).map((p) => p.dataset.leaderLanePanel);
        const resolvedId = laneId && validIds.includes(laneId) ? laneId : validIds[0];
        activeLeaderLaneId = resolvedId;

        panels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.dataset.leaderLanePanel !== resolvedId);
        });

        tabs.forEach((tab) => {
            const isSelected = tab.dataset.leaderLaneTab === resolvedId;
            tab.setAttribute('aria-selected', isSelected ? 'true' : 'false');
            tab.classList.toggle('bg-amber-500', isSelected);
            tab.classList.toggle('text-slate-950', isSelected);
            tab.classList.toggle('border-amber-400', isSelected);
            tab.classList.toggle('shadow-sm', isSelected);
            tab.classList.toggle('bg-slate-900', !isSelected);
            tab.classList.toggle('text-slate-300', !isSelected);
            tab.classList.toggle('border-slate-800', !isSelected);
        });
    }

    function applyLeaderReqTab(laneId, reqType) {
        const resolvedType = reqType === 'equipment' ? 'equipment' : 'staffing';
        activeLeaderReqTabs.set(laneId, resolvedType);

        document
            .querySelectorAll(`[data-leader-req-panel][data-lane-id="${laneId}"]`)
            .forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.leaderReqPanel !== resolvedType);
            });

        document
            .querySelectorAll(`.leaderReqTabBtn[data-lane-id="${laneId}"]`)
            .forEach((btn) => {
                const isSelected = btn.dataset.leaderReqTab === resolvedType;
                const isEquip = btn.dataset.leaderReqTab === 'equipment';

                btn.classList.remove(
                    'bg-amber-500/20',
                    'text-amber-200',
                    'border-amber-500/50',
                    'bg-sky-500/20',
                    'text-sky-200',
                    'border-sky-500/50',
                    'bg-slate-900/90',
                    'text-slate-400',
                    'border-slate-800'
                );

                if (isSelected && isEquip) {
                    btn.classList.add('bg-sky-500/20', 'text-sky-200', 'border-sky-500/50');
                } else if (isSelected) {
                    btn.classList.add('bg-amber-500/20', 'text-amber-200', 'border-amber-500/50');
                } else {
                    btn.classList.add('bg-slate-900/90', 'text-slate-400', 'border-slate-800');
                }
            });
    }

    // クリックイベント委譲（タブ・絞り込み・詳細アコーディオン・進捗更新ボタン）
    document.addEventListener('click', async (e) => {
        const mainTabBtn = e.target.closest('.myshiftMainTabBtn');
        if (mainTabBtn) {
            applyMainTab(mainTabBtn.dataset.mainTab || 'shift');
            return;
        }

        const laneFilterBtn = e.target.closest('.myshiftLaneFilterBtn');
        if (laneFilterBtn) {
            activeLaneFilter = laneFilterBtn.dataset.laneFilter || 'all';
            applyTimelineFilters();
            return;
        }

        if (e.target.closest('#myshiftIncompleteToggle')) {
            incompleteOnly = !incompleteOnly;
            applyTimelineFilters();
            return;
        }

        if (e.target.closest('#myshiftResetFiltersBtn')) {
            activeLaneFilter = 'all';
            incompleteOnly = false;
            applyTimelineFilters();
            return;
        }

        const leaderLaneBtn = e.target.closest('.leaderLaneTabBtn');
        if (leaderLaneBtn) {
            applyLeaderLaneTab(leaderLaneBtn.dataset.leaderLaneTab || null);
            return;
        }

        const leaderReqBtn = e.target.closest('.leaderReqTabBtn');
        if (leaderReqBtn) {
            const laneId = leaderReqBtn.dataset.laneId;
            const reqTab = leaderReqBtn.dataset.leaderReqTab;
            if (laneId && reqTab) {
                applyLeaderReqTab(laneId, reqTab);
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
                    showToast(`作業進捗を「${data.status_label || '更新済'}」にしました`);
                } else {
                    const err = await response.json().catch(() => ({}));
                    window.alert(err.message || 'ステータスの更新に失敗しました。');
                    statusBtn.disabled = false;
                    statusBtn.innerHTML = originalHtml;
                }
            } catch (err) {
                console.error('Status update error:', err);
                window.alert('通信エラーが発生しました。電波の良い場所でお試しください。');
                statusBtn.disabled = false;
                statusBtn.innerHTML = originalHtml;
            }
        }
    });

    // 班長コンソール: 班タスク全体ステータス変更（非同期JSON送信）
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
                showToast(`班タスクの進捗を「${data.status_label || '更新済'}」に更新しました`);
            } else {
                const err = await response.json().catch(() => ({}));
                showToast(err.message || '班タスクの更新に失敗しました。', true);
                select.disabled = false;
            }
        } catch (err) {
            console.error('Leader task update error:', err);
            showToast('通信エラーが発生しました。', true);
            select.disabled = false;
        }
    });

    // 班長コンソール: 人員応援・機材申請フォーム（非同期JSON送信）
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
                showToast(data.message || '管理者（本部）へ送信しました。');
            } else {
                const err = await response.json().catch(() => ({}));
                showToast(err.message || '申請の送信に失敗しました。入力内容をご確認ください。', true);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                }
            }
        } catch (err) {
            console.error('Lane request error:', err);
            showToast('通信エラーが発生しました。電波の良い場所で再度お試しください。', true);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            }
        }
    });

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

    async function fetchRealtimeTimeline({ skipLeaderPanelIfFocused = true } = {}) {
        if (isSyncing) {
            return;
        }
        isSyncing = true;

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
                    // 班長が入力中のフォーム内容が消えないよう、マイシフト側のみ更新
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
                applyMainTab(activeMainTab);
                applyTimelineFilters();
                applyLeaderLaneTab(activeLeaderLaneId);
                activeLeaderReqTabs.forEach((reqType, laneId) => {
                    applyLeaderReqTab(laneId, reqType);
                });
                initProgressBars();

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
            isSyncing = false;
        }
    }

    // 毎分00秒・30秒の境界タイミングで自動同期
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

    // タブ復帰・スリープ解除時に即時同期
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            fetchRealtimeTimeline({ skipLeaderPanelIfFocused: true });
        }
    });
}

