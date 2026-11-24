import { initQuickAssignModal } from './timetable-matrix/quick-assign-modal.js';
import { createCommonCreateModalController } from './timetable-task-modal/common-create-modal.js';
import { createSingleModalsController } from './timetable-task-modal/single-modals.js';
import { initGanttBarDrag } from './timeline-gantt/bar-drag.js';
import { layoutGanttRows } from './timeline-gantt/bar-layout.js';
import { initGanttRangeSelector } from './timeline-gantt/range-selector.js';
import { computePxPerMin, renderTimelineHeaderAndGuides } from './timeline-gantt/ruler.js';
import { confirmDialog, showToast } from './utils/ui.js';
import { escapeHtml, getCsrfToken } from './utils/html.js';
import { applyLaneTimelineFilters } from './lane-timeline/filters.js';

document.addEventListener('DOMContentLoaded', () => {
    initLaneTimelinePage();
});

function initLaneTimelinePage() {
    const rootEl = document.getElementById('lane-gantt-root');
    const scrollEl = document.getElementById('lane-gantt-scroll');
    const rulerEl = document.getElementById('lane-gantt-ruler');
    const bandsEl = document.getElementById('lane-gantt-bands');

    // モーダル群の初期化
    let singleModals = null;
    let commonCreateModal = null;

    const closeTaskModals = () => {
        singleModals?.closeModals();
        commonCreateModal?.closeModal();
    };

    singleModals = createSingleModalsController({ closeAllModals: closeTaskModals });
    commonCreateModal = createCommonCreateModalController({ closeAllModals: closeTaskModals });
    singleModals.bindEvents();
    commonCreateModal.bindEvents();

    initQuickAssignModal();

    if (!rootEl || !scrollEl || !rulerEl) return;

    const startMin = parseInt(rootEl.dataset.boundStartMin || '420', 10);
    const endMin = parseInt(rootEl.dataset.boundEndMin || '1200', 10);
    const nowMin = parseInt(rootEl.dataset.nowMin || '-1', 10);
    const canCreate = rootEl.dataset.canCreate === '1';
    const canUpdate = rootEl.dataset.canUpdate === '1';

    let periodsData = { road_bands: [], festival_bands: [] };
    const periodsEl = document.getElementById('lane-timeline-periods-data');
    if (periodsEl?.textContent) {
        try {
            periodsData = JSON.parse(periodsEl.textContent);
        } catch (e) {
            // ignore
        }
    }

    const state = {
        zoomMode: '30m',
        pxPerMin: 2.6,
        searchQuery: '',
        taskTypeFilter: 'all',
        shortageFilter: 'all',
        selectedLaneIds: new Set(
            Array.from(document.querySelectorAll('[data-lane-chip]')).map((el) => el.dataset.laneChip)
        ),
    };

    const renderGantt = () => {
        const viewportWidth = Math.max(320, scrollEl.clientWidth - 250);
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

    const applyFiltersAndLayout = () => {
        applyLaneTimelineFilters({
            rootEl,
            searchQuery: state.searchQuery,
            taskTypeFilter: state.taskTypeFilter,
            shortageFilter: state.shortageFilter,
            selectedLaneIds: state.selectedLaneIds,
        });
        renderGantt();
    };

    // 初回描画 & ウィンドウリサイズ追従
    renderGantt();
    window.addEventListener('resize', () => {
        if (state.zoomMode === 'fit') {
            renderGantt();
        }
    });

    // バーアクションモーダル
    const barActionModal = document.getElementById('laneBarActionModal');
    let activeBarMeta = null;

    const closeBarActionModal = () => {
        barActionModal?.classList.add('hidden');
        activeBarMeta = null;
    };

    const openBarActionModal = (barEl) => {
        if (!barActionModal) return;
        let meta = null;
        try {
            meta = JSON.parse(barEl.getAttribute('data-bar-meta') || 'null');
        } catch (e) {
            return;
        }
        if (!meta) return;
        activeBarMeta = meta;

        const isRoutine = meta.task_type === 'routine';
        const typeBadge = document.getElementById('laneBarTypeBadge');
        if (typeBadge) {
            typeBadge.textContent = `${meta.task_type_icon} ${meta.task_type_short}`;
            typeBadge.className = isRoutine
                ? 'px-1.5 py-0.2 rounded font-bold bg-teal-500/20 text-teal-200 border border-teal-400/40'
                : 'px-1.5 py-0.2 rounded font-bold bg-amber-500/20 text-amber-200 border border-amber-400/40';
        }
        const laneBadge = document.getElementById('laneBarLaneBadge');
        if (laneBadge) laneBadge.textContent = `${meta.lane_icon} ${meta.lane_name}`;

        const timeEl = document.getElementById('laneBarTimeLabel');
        if (timeEl) timeEl.textContent = `⏱️ ${meta.start_time}〜${meta.end_time}`;

        const titleEl = document.getElementById('lane-bar-modal-title');
        if (titleEl) titleEl.textContent = meta.title;

        const peopleEl = document.getElementById('laneBarPeopleLabel');
        if (peopleEl) {
            peopleEl.textContent = meta.is_satisfied
                ? `✔ 充足 (${meta.assigned_count}/${meta.required_people_count}名)`
                : `⚠️ 不足 ${meta.shortage_count}名 (${meta.assigned_count}/${meta.required_people_count}名)`;
        }

        const locEl = document.getElementById('laneBarLocationLabel');
        if (locEl) locEl.textContent = meta.location || '未指定';

        const assigneesEl = document.getElementById('laneBarAssignees');
        if (assigneesEl) {
            if (Array.isArray(meta.assignee_names) && meta.assignee_names.length > 0) {
                assigneesEl.innerHTML = meta.assignee_names.map((name) =>
                    `<span class="px-2 py-0.5 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 font-medium">${escapeHtml(name)}</span>`
                ).join('');
            } else {
                assigneesEl.innerHTML = '<span class="text-slate-400">まだスタッフが配置されていません。</span>';
            }
        }

        const contentWrap = document.getElementById('laneBarContentWrap');
        const contentEl = document.getElementById('laneBarContent');
        if (contentWrap && contentEl) {
            if (meta.content) {
                contentEl.textContent = meta.content;
                contentWrap.classList.remove('hidden');
            } else {
                contentWrap.classList.add('hidden');
            }
        }

        barActionModal.classList.remove('hidden');
    };

    // ガント背景ドラッグ・クリックでの新規タスク作成
    initGanttRangeSelector({
        rootEl,
        getBounds: () => ({ startMin, endMin }),
        getPxPerMin: () => state.pxPerMin,
        canInteract: () => canCreate,
        onSelectRange: ({ rowEl, zoneType, startTime, endTime }) => {
            const addBtn = rowEl.querySelector('[data-action="open-task-create"]');
            const laneId = rowEl.dataset.laneId;
            const laneName = addBtn?.dataset.laneName || '';
            const laneIcon = addBtn?.dataset.laneIcon || '📋';
            if (laneId) {
                singleModals.openCreateModal(laneId, laneName, laneIcon, startTime, endTime, zoneType);
            }
        },
    });

    // ガントバーのクリック・ドラッグ移動・リサイズ
    initGanttBarDrag({
        rootEl,
        getBounds: () => ({ startMin, endMin }),
        getPxPerMin: () => state.pxPerMin,
        canEditBar: () => canUpdate,
        onBarClick: (barEl) => {
            openBarActionModal(barEl);
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

            const tasksJsonEl = document.getElementById('lane-timeline-tasks-data');
            let taskFull = null;
            if (tasksJsonEl?.textContent) {
                try {
                    const list = JSON.parse(tasksJsonEl.textContent);
                    taskFull = list.find((t) => String(t.id) === String(taskId));
                } catch (e) {
                    // ignore
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
                        is_at_least: taskFull ? Boolean(taskFull.is_at_least) : false,
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
    const searchInput = document.getElementById('lane-timeline-search');
    if (searchInput) {
        let timer = null;
        searchInput.addEventListener('input', (e) => {
            state.searchQuery = e.target.value;
            clearTimeout(timer);
            timer = setTimeout(applyFiltersAndLayout, 120);
        });
    }

    document.addEventListener('click', async (e) => {
        // ズームボタン
        const zoomBtn = e.target.closest('.lane-zoom-btn');
        if (zoomBtn) {
            state.zoomMode = zoomBtn.dataset.zoomMode || '30m';
            document.querySelectorAll('.lane-zoom-btn').forEach((b) => {
                b.classList.remove('bg-indigo-600', 'text-white', 'shadow-2xs');
                b.classList.add('text-slate-600');
            });
            zoomBtn.classList.add('bg-indigo-600', 'text-white', 'shadow-2xs');
            zoomBtn.classList.remove('text-slate-600');
            renderGantt();
            return;
        }

        // 現在時刻ジャンプ
        if (e.target.closest('#btn-lane-scroll-now')) {
            const offset = parseInt(scrollEl.dataset.nowOffsetPx || '-1', 10);
            if (offset >= 0) {
                scrollEl.scrollTo({ left: Math.max(0, offset - 180), behavior: 'smooth' });
            } else {
                showToast('現在時刻はタイムライン範囲外です', 'info');
            }
            return;
        }

        // 種別フィルター
        const typeBtn = e.target.closest('[data-filter-task-type]');
        if (typeBtn) {
            state.taskTypeFilter = typeBtn.dataset.filterTaskType || 'all';
            document.querySelectorAll('.lane-type-btn').forEach((b) => {
                b.classList.remove('bg-indigo-600', 'text-white');
                b.classList.add('text-slate-600');
            });
            typeBtn.classList.add('bg-indigo-600', 'text-white');
            typeBtn.classList.remove('text-slate-600');
            applyFiltersAndLayout();
            return;
        }

        // 充足状態フィルター
        const shortageBtn = e.target.closest('[data-filter-shortage]');
        if (shortageBtn) {
            state.shortageFilter = shortageBtn.dataset.filterShortage || 'all';
            document.querySelectorAll('.lane-shortage-btn').forEach((b) => {
                b.classList.remove('bg-slate-800', 'text-white');
                b.classList.add('text-slate-600');
            });
            shortageBtn.classList.add('bg-slate-800', 'text-white');
            shortageBtn.classList.remove('text-slate-600');
            applyFiltersAndLayout();
            return;
        }

        // 班チップトグル
        const laneChip = e.target.closest('[data-lane-chip]');
        if (laneChip) {
            const lid = laneChip.dataset.laneChip;
            const checkEl = laneChip.querySelector('.chip-check');
            if (state.selectedLaneIds.has(lid)) {
                state.selectedLaneIds.delete(lid);
                laneChip.classList.add('opacity-50', 'bg-slate-100');
                laneChip.classList.remove('bg-white');
                if (checkEl) checkEl.textContent = '✕';
            } else {
                state.selectedLaneIds.add(lid);
                laneChip.classList.remove('opacity-50', 'bg-slate-100');
                laneChip.classList.add('bg-white');
                if (checkEl) checkEl.textContent = '✔';
            }
            applyFiltersAndLayout();
            return;
        }

        if (e.target.closest('[data-action="lane-select-all"]')) {
            document.querySelectorAll('[data-lane-chip]').forEach((chip) => {
                state.selectedLaneIds.add(chip.dataset.laneChip);
                chip.classList.remove('opacity-50', 'bg-slate-100');
                chip.classList.add('bg-white');
                const checkEl = chip.querySelector('.chip-check');
                if (checkEl) checkEl.textContent = '✔';
            });
            applyFiltersAndLayout();
            return;
        }

        if (e.target.closest('[data-action="lane-deselect-all"]')) {
            state.selectedLaneIds.clear();
            document.querySelectorAll('[data-lane-chip]').forEach((chip) => {
                chip.classList.add('opacity-50', 'bg-slate-100');
                chip.classList.remove('bg-white');
                const checkEl = chip.querySelector('.chip-check');
                if (checkEl) checkEl.textContent = '✕';
            });
            applyFiltersAndLayout();
            return;
        }

        // タスク追加・共通作成・モーダル閉じ
        if (e.target.closest('[data-action="close-modal"]')) {
            e.preventDefault();
            closeTaskModals();
            return;
        }

        if (e.target.closest('[data-action="open-common-task-create"]')) {
            e.preventDefault();
            commonCreateModal.openCommonCreateModal();
            return;
        }

        if (e.target.closest('[data-action="common-select-all-lanes"]')) {
            e.preventDefault();
            commonCreateModal.selectAllLanes(true);
            return;
        }

        if (e.target.closest('[data-action="common-clear-all-lanes"]')) {
            e.preventDefault();
            commonCreateModal.selectAllLanes(false);
            return;
        }

        const createBtn = e.target.closest('[data-action="open-task-create"]');
        if (createBtn) {
            e.preventDefault();
            singleModals.openCreateModal(
                createBtn.dataset.laneId,
                createBtn.dataset.laneName,
                createBtn.dataset.laneIcon,
                createBtn.dataset.suggestedStart
            );
            return;
        }

        // バーアクションモーダル操作
        if (e.target.closest('[data-action="close-bar-action-modal"]') || e.target === barActionModal) {
            closeBarActionModal();
            return;
        }

        if (e.target.closest('#laneBarQuickAssignBtn') && activeBarMeta) {
            const taskId = activeBarMeta.task_id;
            closeBarActionModal();
            const hiddenTrigger = document.createElement('button');
            hiddenTrigger.type = 'button';
            hiddenTrigger.dataset.action = 'open-quick-assign';
            hiddenTrigger.dataset.taskId = String(taskId);
            document.body.appendChild(hiddenTrigger);
            hiddenTrigger.click();
            hiddenTrigger.remove();
            return;
        }

        if (e.target.closest('#laneBarEditBtn') && activeBarMeta) {
            const taskId = activeBarMeta.task_id;
            closeBarActionModal();
            singleModals.openEditModal(taskId);
            return;
        }

        if (e.target.closest('#laneBarDeleteBtn') && activeBarMeta) {
            const meta = activeBarMeta;
            const confirmed = await confirmDialog(`作業タスク「${meta.title}」を削除しますか？`, {
                variant: 'danger',
                title: 'タスク削除の確認',
                confirmText: '削除する',
            });
            if (!confirmed) return;

            try {
                const res = await fetch(meta.destroy_url || `/timetable-tasks/${meta.task_id}`, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || '削除に失敗しました');
                closeBarActionModal();
                window.location.reload();
            } catch (err) {
                showToast(err.message || '削除に失敗しました', 'error');
            }
        }
    });

    // 班別タイムラインからのタスク作成・編集フォームはJSON送信して即リロードする（リダイレクト先のタスク一覧へ行かず班別タイムラインに留まる）
    const createForm = document.getElementById('task-create-form');
    if (createForm) {
        createForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(createForm);
            try {
                const res = await fetch(createForm.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                    body: formData,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || 'タスクの登録に失敗しました');
                }
                closeTaskModals();
                window.location.reload();
            } catch (err) {
                showToast(err.message || 'タスクの登録に失敗しました', 'error');
            }
        });
    }

    const editForm = document.getElementById('task-edit-form');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(editForm);
            try {
                const res = await fetch(editForm.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                    body: formData,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || 'タスクの更新に失敗しました');
                }
                closeTaskModals();
                window.location.reload();
            } catch (err) {
                showToast(err.message || 'タスクの更新に失敗しました', 'error');
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeTaskModals();
            closeBarActionModal();
        }
    });
}
