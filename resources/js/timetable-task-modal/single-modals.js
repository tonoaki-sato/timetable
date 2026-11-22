import {
    addMinutesToTime,
    findOverlapsWithLaneTasks,
    getAllTasks,
    updateSingleOverlapWarning,
} from './overlap.js';

/**
 * 詳細・個別追加・個別編集モーダル制御
 */
export function createSingleModalsController({ closeAllModals }) {
    const detailModal = document.getElementById('task-detail-modal');
    const createModal = document.getElementById('task-create-modal');
    const editModal = document.getElementById('task-edit-modal');

    const detailTitle = document.getElementById('detail-task-title');
    const detailStatusBadge = document.getElementById('detail-task-status-badge');
    const detailCriticalBadge = document.getElementById('detail-task-critical-badge');
    const detailLane = document.getElementById('detail-task-lane');
    const detailTime = document.getElementById('detail-task-time');
    const detailLocation = document.getElementById('detail-task-location');
    const detailPeople = document.getElementById('detail-task-people');
    const detailContent = document.getElementById('detail-task-content');

    const createLaneBadge = document.getElementById('create-modal-lane-badge');
    const createLaneIdInput = document.getElementById('create-modal-lane-id');
    const createTitleInput = document.getElementById('create-modal-title');
    const createStartTimeInput = document.getElementById('create-modal-start-time');
    const createEndTimeInput = document.getElementById('create-modal-end-time');
    const createPeopleInput = document.getElementById('create-modal-people');
    const createAtLeastInput = document.getElementById('create-modal-at-least');
    const createCriticalInput = document.getElementById('create-modal-critical');
    const createLocationInput = document.getElementById('create-modal-location');
    const createStatusSelect = document.getElementById('create-modal-status');
    const createContentInput = document.getElementById('create-modal-content');
    const createOverlapWarning = document.getElementById('create-modal-overlap-warning');

    const editForm = document.getElementById('task-edit-form');
    const editLaneBadge = document.getElementById('edit-modal-lane-badge');
    const editTaskIdInput = document.getElementById('edit-modal-task-id');
    const editLaneIdInput = document.getElementById('edit-modal-lane-id');
    const editTitleInput = document.getElementById('edit-modal-title');
    const editStartTimeInput = document.getElementById('edit-modal-start-time');
    const editEndTimeInput = document.getElementById('edit-modal-end-time');
    const editPeopleInput = document.getElementById('edit-modal-people');
    const editAtLeastInput = document.getElementById('edit-modal-at-least');
    const editCriticalInput = document.getElementById('edit-modal-critical');
    const editLocationInput = document.getElementById('edit-modal-location');
    const editStatusSelect = document.getElementById('edit-modal-status');
    const editContentInput = document.getElementById('edit-modal-content');
    const editOverlapWarning = document.getElementById('edit-modal-overlap-warning');

    let currentEditingTaskId = null;

    function closeModals() {
        detailModal?.classList.add('hidden');
        createModal?.classList.add('hidden');
        editModal?.classList.add('hidden');
        currentEditingTaskId = null;
    }

    function openDetailModal(taskId) {
        const allTasks = getAllTasks();
        const task = allTasks.find(t => String(t.id) === String(taskId));
        if (!task || !detailModal) return;

        closeAllModals();

        if (detailTitle) detailTitle.textContent = task.title;
        if (detailLane) detailLane.textContent = `${task.lane_icon || '📋'} ${task.lane_name || '担当班'}`;
        if (detailTime) detailTime.textContent = `${task.start_time} 〜 ${task.end_time}`;
        if (detailLocation) detailLocation.textContent = task.location || '（未指定）';
        if (detailPeople) {
            detailPeople.textContent = `${task.required_people_count}名${task.is_at_least ? ' 以上' : ''}`;
        }
        if (detailContent) {
            detailContent.textContent = task.content || '作業内容の記載はありません。';
            if (!task.content) {
                detailContent.classList.add('italic', 'text-slate-400');
            } else {
                detailContent.classList.remove('italic', 'text-slate-400');
            }
        }

        if (detailStatusBadge) {
            detailStatusBadge.textContent = task.status_label || task.status;
            detailStatusBadge.className = 'px-2 py-0.5 rounded text-xs border font-medium ' + (
                task.status === 'completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                task.status === 'in_progress' ? 'bg-amber-50 text-amber-800 border-amber-300 font-bold' :
                task.status === 'cancelled' ? 'bg-slate-100 text-slate-400 border-slate-200 line-through' :
                'bg-slate-100 text-slate-600 border-slate-200'
            );
        }

        if (detailCriticalBadge) {
            const defaultNotice = detailCriticalBadge.nextElementSibling;
            if (task.is_critical) {
                detailCriticalBadge.classList.remove('hidden');
                if (defaultNotice) defaultNotice.classList.add('hidden');
            } else {
                detailCriticalBadge.classList.add('hidden');
                if (defaultNotice) defaultNotice.classList.remove('hidden');
            }
        }

        const assignLink = document.getElementById('detail-modal-assign-link');
        if (assignLink && task.timetable_id) {
            assignLink.href = `/timetables/${task.timetable_id}/assignments?task_id=${task.id}`;
        }

        detailModal.classList.remove('hidden');
    }

    function validateCreateOverlap() {
        if (!createOverlapWarning || !createLaneIdInput || !createStartTimeInput || !createEndTimeInput) {
            return false;
        }

        const laneId = createLaneIdInput.value;
        const start = createStartTimeInput.value;
        const end = createEndTimeInput.value;
        const status = createStatusSelect?.value || 'not_started';

        const matches = status === 'cancelled'
            ? []
            : findOverlapsWithLaneTasks(laneId, start, end);

        return updateSingleOverlapWarning(createOverlapWarning, matches, {
            subject: '既存タスク',
            actionLabel: '登録',
        });
    }

    function openCreateModal(laneId, laneName, laneIcon, suggestedStart) {
        if (!createModal) return;

        closeAllModals();

        if (createLaneBadge) {
            createLaneBadge.textContent = `${laneIcon || '📋'} ${laneName || '担当班'}`;
        }
        if (createLaneIdInput) createLaneIdInput.value = laneId || '';
        if (createTitleInput) createTitleInput.value = '';
        if (createLocationInput) createLocationInput.value = '';

        const sStart = suggestedStart || '08:00';
        const sEnd = addMinutesToTime(sStart, sStart === '08:00' ? 90 : 60);
        if (createStartTimeInput) createStartTimeInput.value = sStart;
        if (createEndTimeInput) createEndTimeInput.value = sEnd;

        if (createPeopleInput) createPeopleInput.value = '2';
        if (createAtLeastInput) createAtLeastInput.checked = false;
        if (createCriticalInput) createCriticalInput.checked = false;
        if (createStatusSelect) createStatusSelect.value = 'not_started';
        if (createContentInput) createContentInput.value = '';

        validateCreateOverlap();
        createModal.classList.remove('hidden');
        createTitleInput?.focus();
    }

    function validateEditOverlap() {
        if (!editOverlapWarning || !editLaneIdInput || !editStartTimeInput || !editEndTimeInput) {
            return false;
        }

        const laneId = editLaneIdInput.value;
        const start = editStartTimeInput.value;
        const end = editEndTimeInput.value;
        const status = editStatusSelect?.value || 'not_started';

        const matches = status === 'cancelled'
            ? []
            : findOverlapsWithLaneTasks(laneId, start, end, currentEditingTaskId);

        return updateSingleOverlapWarning(editOverlapWarning, matches, {
            subject: '同一班のタスク',
            actionLabel: '保存',
        });
    }

    function openEditModal(taskId) {
        const allTasks = getAllTasks();
        const task = allTasks.find(t => String(t.id) === String(taskId));
        if (!task || !editModal) return;

        closeAllModals();
        currentEditingTaskId = task.id;

        if (editForm) {
            editForm.action = task.update_url || `/timetable-tasks/${task.id}`;
        }
        if (editTaskIdInput) editTaskIdInput.value = task.id;
        if (editLaneIdInput) editLaneIdInput.value = task.timetable_lane_id;
        if (editLaneBadge) {
            editLaneBadge.textContent = `${task.lane_icon || '📋'} ${task.lane_name || '担当班'}`;
        }

        if (editTitleInput) editTitleInput.value = task.title || '';
        if (editStartTimeInput) editStartTimeInput.value = task.start_time || '08:00';
        if (editEndTimeInput) editEndTimeInput.value = task.end_time || '09:30';
        if (editPeopleInput) editPeopleInput.value = task.required_people_count ?? 2;
        if (editAtLeastInput) editAtLeastInput.checked = Boolean(task.is_at_least);
        if (editCriticalInput) editCriticalInput.checked = Boolean(task.is_critical);
        if (editLocationInput) editLocationInput.value = task.location || '';
        if (editStatusSelect) editStatusSelect.value = task.status || 'not_started';
        if (editContentInput) editContentInput.value = task.content || '';

        validateEditOverlap();
        editModal.classList.remove('hidden');
        editTitleInput?.focus();
    }

    function bindEvents() {
        createModal?.addEventListener('input', (e) => {
            if (e.target.matches('#create-modal-start-time, #create-modal-end-time')) {
                validateCreateOverlap();
            }
        });
        createModal?.addEventListener('change', (e) => {
            if (e.target.matches('#create-modal-status')) {
                validateCreateOverlap();
            }
        });

        editModal?.addEventListener('input', (e) => {
            if (e.target.matches('#edit-modal-start-time, #edit-modal-end-time')) {
                validateEditOverlap();
            }
        });
        editModal?.addEventListener('change', (e) => {
            if (e.target.matches('#edit-modal-status')) {
                validateEditOverlap();
            }
        });
    }

    return {
        closeModals,
        openDetailModal,
        openCreateModal,
        openEditModal,
        bindEvents,
    };
}
