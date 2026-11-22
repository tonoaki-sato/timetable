/**
 * 現場マイタイムテーブル タブ切替・班別フィルター・進捗バー制御
 */
export function initProgressBars() {
    document.querySelectorAll('.myshift-progress-bar[data-progress-percent]').forEach((bar) => {
        const raw = Number.parseInt(bar.dataset.progressPercent || '0', 10);
        const pct = Number.isNaN(raw) ? 0 : Math.max(0, Math.min(100, raw));
        bar.style.width = `${pct}%`;
    });
}

export function applyMainTab(state, tab) {
    const shiftPanel = document.getElementById('myshiftModePanel');
    const leaderPanel = document.getElementById('leaderModePanel');
    const buttons = document.querySelectorAll('.myshiftMainTabBtn');

    if (!leaderPanel || buttons.length === 0) {
        state.activeMainTab = 'shift';
        return;
    }

    state.activeMainTab = tab === 'leader' ? 'leader' : 'shift';

    if (shiftPanel) {
        shiftPanel.classList.toggle('hidden', state.activeMainTab !== 'shift');
    }
    leaderPanel.classList.toggle('hidden', state.activeMainTab !== 'leader');

    buttons.forEach((btn) => {
        const isSelected = btn.dataset.mainTab === state.activeMainTab;
        btn.setAttribute('aria-selected', isSelected ? 'true' : 'false');
        btn.classList.toggle('bg-indigo-600', isSelected && state.activeMainTab === 'shift');
        btn.classList.toggle('bg-amber-500', isSelected && state.activeMainTab === 'leader');
        btn.classList.toggle('text-slate-950', isSelected && state.activeMainTab === 'leader');
        btn.classList.toggle('text-white', isSelected && state.activeMainTab === 'shift');
        btn.classList.toggle('shadow-sm', isSelected);
        btn.classList.toggle('text-slate-300', !isSelected);
        btn.classList.toggle('hover:text-white', !isSelected);
        btn.classList.toggle('hover:bg-slate-800/80', !isSelected);
    });
}

export function applyTimelineFilters(state) {
    const filterButtons = document.querySelectorAll('.myshiftLaneFilterBtn');
    if (filterButtons.length > 0) {
        const hasActiveLane = Array.from(filterButtons).some(
            (btn) => btn.dataset.laneFilter === state.activeLaneFilter
        );
        if (!hasActiveLane) {
            state.activeLaneFilter = 'all';
        }

        filterButtons.forEach((btn) => {
            const isSelected = btn.dataset.laneFilter === state.activeLaneFilter;
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
        incompleteBtn.setAttribute('aria-pressed', state.incompleteOnly ? 'true' : 'false');
        incompleteBtn.classList.toggle('bg-emerald-600', state.incompleteOnly);
        incompleteBtn.classList.toggle('text-white', state.incompleteOnly);
        incompleteBtn.classList.toggle('border-emerald-400', state.incompleteOnly);
        incompleteBtn.classList.toggle('bg-slate-900', !state.incompleteOnly);
        incompleteBtn.classList.toggle('text-slate-300', !state.incompleteOnly);
        incompleteBtn.classList.toggle('border-slate-700', !state.incompleteOnly);
    }

    const cards = document.querySelectorAll('#myshiftTimelineContainer [data-task-card]');
    let visibleCount = 0;

    cards.forEach((card) => {
        const cardLaneId = card.dataset.laneId || 'none';
        const isCompleted = card.dataset.completed === '1';

        const matchesLane = state.activeLaneFilter === 'all' || cardLaneId === state.activeLaneFilter;
        const matchesStatus = !state.incompleteOnly || !isCompleted;
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

export function applyLeaderLaneTab(state, laneId) {
    const tabs = document.querySelectorAll('.leaderLaneTabBtn');
    const panels = document.querySelectorAll('[data-leader-lane-panel]');
    if (panels.length === 0) {
        return;
    }

    const validIds = Array.from(panels).map((p) => p.dataset.leaderLanePanel);
    const resolvedId = laneId && validIds.includes(laneId) ? laneId : validIds[0];
    state.activeLeaderLaneId = resolvedId;

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

export function applyLeaderReqTab(state, laneId, reqType) {
    const resolvedType = reqType === 'equipment' ? 'equipment' : 'staffing';
    state.activeLeaderReqTabs.set(laneId, resolvedType);

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
