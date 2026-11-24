import { initGanttBarDrag } from './timeline-gantt/bar-drag.js';
import { layoutGanttRows } from './timeline-gantt/bar-layout.js';
import { initGanttRangeSelector } from './timeline-gantt/range-selector.js';
import { computePxPerMin, renderTimelineHeaderAndGuides } from './timeline-gantt/ruler.js';
import { getCsrfToken } from './utils/html.js';
import { confirmDialog, showToast } from './utils/ui.js';
import { applyStaffTimelineFiltersAndGrouping } from './staff-timeline/filters.js';
import { initStaffTimelineModals } from './staff-timeline/modals.js';

/**
 * スタッフ稼働タイムライン スクリプト (CSP完全準拠・バニラJS)
 */
document.addEventListener('DOMContentLoaded', () => {
    initStaffTimelinePage();
});

function initStaffTimelinePage() {
    const rootEl = document.getElementById('staff-gantt-root');
    const scrollEl = document.getElementById('staff-gantt-scroll');
    const rulerEl = document.getElementById('staff-gantt-ruler');
    const bandsEl = document.getElementById('staff-gantt-bands');
    const rowsContainer = document.getElementById('staff-gantt-rows');

    if (!rootEl || !scrollEl || !rulerEl || !rowsContainer) return;

    const timetableId = rootEl.dataset.timetableId;
    const startMin = parseInt(rootEl.dataset.boundStartMin || '420', 10);
    const endMin = parseInt(rootEl.dataset.boundEndMin || '1200', 10);
    const nowMin = parseInt(rootEl.dataset.nowMin || '-1', 10);
    const canCreate = rootEl.dataset.canCreate === '1';
    const canUpdate = rootEl.dataset.canUpdate === '1';

    let periodsData = { road_bands: [], festival_bands: [] };
    const periodsEl = document.getElementById('staff-timeline-periods-data');
    if (periodsEl?.textContent) {
        try {
            periodsData = JSON.parse(periodsEl.textContent);
        } catch (e) {
            // ignore
        }
    }

    const searchInput = document.getElementById('timeline-search-input');
    const leaderCheckbox = document.getElementById('timeline-leader-filter');
    const groupSelect = document.getElementById('timeline-group-select');

    const state = {
        zoomMode: '30m',
        pxPerMin: 2.6,
        searchQuery: '',
        includeLeaders: leaderCheckbox ? leaderCheckbox.checked : true,
        statusFilter: 'all',
        taskTypeFilter: 'all',
        groupMode: 'flat',
    };

    const modals = initStaffTimelineModals({ timetableId });

    const renderGantt = () => {
        const viewportWidth = Math.max(320, scrollEl.clientWidth - 260);
        state.pxPerMin = computePxPerMin(state.zoomMode, viewportWidth, endMin - startMin);

        const canvasContainers = Array.from(rootEl.querySelectorAll('[data-gantt-canvas]'));
        renderTimelineHeaderAndGuides({
            canvasContainers,
            rulerEl,
            bandsRowEl: bandsEl,
            startMin,
            endMin,
            pxPerMin: state.pxPerMin,
            zoomMode: state.zoomMode,
            roadBands: periodsData.road_bands || [],
            festivalBands: periodsData.festival_bands || [],
            nowMin: Number.isFinite(nowMin) ? nowMin : null,
            scrollContainer: scrollEl,
        });

        layoutGanttRows({
            rootEl,
            startMin,
            pxPerMin: state.pxPerMin,
        });
    };

    const applyAndRender = () => {
        applyStaffTimelineFiltersAndGrouping({
            rowsContainer,
            searchQuery: state.searchQuery,
            includeLeaders: state.includeLeaders,
            statusFilter: state.statusFilter,
            taskTypeFilter: state.taskTypeFilter,
            groupMode: state.groupMode,
        });
        renderGantt();
    };

    renderGantt();
    window.addEventListener('resize', () => {
        if (state.zoomMode === 'fit') {
            renderGantt();
        }
    });

    // 背景ドラッグ/クリックによる既存配置＆新規作成
    initGanttRangeSelector({
        rootEl,
        getBounds: () => ({ startMin, endMin }),
        getPxPerMin: () => state.pxPerMin,
        canInteract: () => canCreate || canUpdate,
        onSelectRange: ({ rowEl, zoneType, startMin: sMin, endMin: eMin, startTime, endTime }) => {
            let assignments = [];
            try {
                assignments = JSON.parse(rowEl.dataset.assignmentsJson || '[]');
            } catch (e) {
                assignments = [];
            }

            modals.openQuickAssignModal({
                staffId: parseInt(rowEl.dataset.staffId || '0', 10),
                staffName: rowEl.dataset.staffName || '',
                isLeader: rowEl.dataset.isLeader === '1',
                assignments,
                startMin: sMin,
                endMin: eMin,
                startTime,
                endTime,
                suggestedTaskType: zoneType === 'routine' ? 'routine' : 'spot',
            });
        },
    });

    // バークリック＆横ドラッグ時間更新
    initGanttBarDrag({
        rootEl,
        getBounds: () => ({ startMin, endMin }),
        getPxPerMin: () => state.pxPerMin,
        canEditBar: () => canUpdate,
        onBarClick: (barEl) => {
            modals.openBarActionModal(barEl);
        },
        onBarTimeChange: async ({ barEl, taskId, newStartTime, newEndTime, revert }) => {
            let meta = null;
            try {
                meta = JSON.parse(barEl.getAttribute('data-bar-meta') || 'null');
            } catch (e) {
                revert();
                return;
            }
            if (!meta) {
                revert();
                return;
            }

            if ((meta.assigned_count || 0) > 1) {
                const ok = await confirmDialog(
                    `「${meta.title}」には ${meta.assigned_count} 名のスタッフが配置されています。時間帯を ${newStartTime}〜${newEndTime} に変更すると全員のスケジュールが更新されます。よろしいですか？`,
                    { title: 'タスク時間帯変更の確認', confirmText: '全員の時間を変更' }
                );
                if (!ok) {
                    revert();
                    return;
                }
            }

            try {
                const res = await fetch(meta.update_url || `/timetable-tasks/${taskId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                    body: JSON.stringify({
                        task_type: meta.task_type,
                        title: meta.title,
                        location: meta.location || null,
                        start_time: newStartTime,
                        end_time: newEndTime,
                        required_people_count: meta.required_people_count ?? 1,
                        is_critical: Boolean(meta.is_critical),
                        status: meta.status || 'not_started',
                        content: meta.content || null,
                    }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || '時間の更新に失敗しました');
                }
                showToast(`「${meta.title}」を ${newStartTime}〜${newEndTime} に更新しました`, 'success');
                window.location.reload();
            } catch (err) {
                revert();
                showToast(err.message || '時間の更新に失敗しました', 'error');
            }
        },
    });

    // フィルターイベント群
    if (searchInput) {
        let timer = null;
        searchInput.addEventListener('input', (e) => {
            state.searchQuery = e.target.value.trim();
            clearTimeout(timer);
            timer = setTimeout(applyAndRender, 150);
        });
    }

    if (leaderCheckbox) {
        leaderCheckbox.addEventListener('change', (e) => {
            state.includeLeaders = e.target.checked;
            applyAndRender();
        });
    }

    if (groupSelect) {
        groupSelect.addEventListener('change', (e) => {
            state.groupMode = e.target.value || 'flat';
            applyAndRender();
        });
    }

    document.addEventListener('click', (e) => {
        // ステータスボタン
        const statusBtn = e.target.closest('[data-filter-status]');
        if (statusBtn) {
            state.statusFilter = statusBtn.dataset.filterStatus || 'all';
            document.querySelectorAll('[data-filter-status]').forEach((b) => {
                b.classList.remove('bg-indigo-600', 'bg-emerald-600', 'bg-amber-600', 'bg-sky-600', 'text-white', 'border-indigo-600', 'border-emerald-600', 'border-amber-600', 'border-sky-600');
                b.classList.add('bg-white', 'text-slate-700', 'border-slate-200');
            });

            if (state.statusFilter === 'all') statusBtn.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600');
            else if (state.statusFilter === 'assigned') statusBtn.classList.add('bg-emerald-600', 'text-white', 'border-emerald-600');
            else if (state.statusFilter === 'unassigned') statusBtn.classList.add('bg-amber-600', 'text-white', 'border-amber-600');
            else if (state.statusFilter === 'checked_in') statusBtn.classList.add('bg-sky-600', 'text-white', 'border-sky-600');
            statusBtn.classList.remove('bg-white', 'text-slate-700', 'border-slate-200');

            applyAndRender();
            return;
        }

        // 種別フィルター
        const typeBtn = e.target.closest('[data-staff-type-filter]');
        if (typeBtn) {
            state.taskTypeFilter = typeBtn.dataset.staffTypeFilter || 'all';
            document.querySelectorAll('.staff-type-btn').forEach((b) => {
                b.classList.remove('bg-indigo-600', 'text-white');
                b.classList.add('text-slate-600');
            });
            typeBtn.classList.add('bg-indigo-600', 'text-white');
            typeBtn.classList.remove('text-slate-600');
            applyAndRender();
            return;
        }

        // ズームボタン
        const zoomBtn = e.target.closest('[data-staff-zoom-mode]');
        if (zoomBtn) {
            state.zoomMode = zoomBtn.dataset.staffZoomMode || '30m';
            document.querySelectorAll('.staff-zoom-btn').forEach((b) => {
                b.classList.remove('bg-indigo-600', 'text-white', 'shadow-2xs');
                b.classList.add('text-slate-600');
            });
            zoomBtn.classList.add('bg-indigo-600', 'text-white', 'shadow-2xs');
            zoomBtn.classList.remove('text-slate-600');
            renderGantt();
            return;
        }

        // 現在時刻ジャンプ
        if (e.target.closest('#btn-staff-scroll-now')) {
            const offset = parseInt(scrollEl.dataset.nowOffsetPx || '-1', 10);
            if (offset >= 0) {
                scrollEl.scrollTo({ left: Math.max(0, offset - 180), behavior: 'smooth' });
            } else {
                showToast('現在時刻はタイムライン範囲外です', 'info');
            }
            return;
        }

        // 左ペイン「+ 配置/作成」ボタン
        const quickBtn = e.target.closest('[data-action="open-timeline-quick-assign"]');
        if (quickBtn) {
            const rowEl = quickBtn.closest('[data-gantt-row]');
            let assignments = [];
            try {
                assignments = JSON.parse(rowEl?.dataset.assignmentsJson || '[]');
            } catch (e2) {
                assignments = [];
            }

            modals.openQuickAssignModal({
                staffId: parseInt(quickBtn.dataset.staffId || '0', 10),
                staffName: quickBtn.dataset.staffName || '',
                isLeader: quickBtn.dataset.isLeader === '1',
                assignments,
                startMin: 0,
                endMin: 0,
                startTime: '09:00',
                endTime: '10:00',
                suggestedTaskType: 'spot',
            });
        }
    });
}
