import { showToast } from '../utils/ui.js';
import { findOverlapsWithLaneTasks } from './overlap.js';

/**
 * 共通タスク一括登録モーダル制御
 */
export function createCommonCreateModalController({ closeAllModals }) {
    const commonCreateModal = document.getElementById('task-common-create-modal');
    const commonCreateForm = document.getElementById('task-common-create-form');
    const commonTitleInput = document.getElementById('common-modal-title');
    const commonStartTimeInput = document.getElementById('common-modal-start-time');
    const commonEndTimeInput = document.getElementById('common-modal-end-time');
    const commonPeopleInput = document.getElementById('common-modal-people');
    const commonAtLeastInput = document.getElementById('common-modal-at-least');
    const commonCriticalInput = document.getElementById('common-modal-critical');
    const commonLocationInput = document.getElementById('common-modal-location');
    const commonStatusSelect = document.getElementById('common-modal-status');
    const commonAutoAssignLeadInput = document.getElementById('common-modal-auto-assign-lead');
    const commonContentInput = document.getElementById('common-modal-content');
    const commonSelectedCount = document.getElementById('common-modal-selected-count');
    const commonOverlapWarning = document.getElementById('common-modal-overlap-warning');
    const commonOverlapList = document.getElementById('common-modal-overlap-list');
    const commonNoLaneWarning = document.getElementById('common-modal-no-lane-warning');
    const commonServerErrors = document.getElementById('common-modal-server-errors');
    const commonSubmitBtn = document.getElementById('btn-submit-common-task');

    function closeModal() {
        commonCreateModal?.classList.add('hidden');
    }

    function getCommonLaneCheckboxes() {
        if (!commonCreateModal) {
            return [];
        }
        return Array.from(commonCreateModal.querySelectorAll('.common-lane-checkbox'));
    }

    function updateCommonSelectedCount() {
        const checkboxes = getCommonLaneCheckboxes();
        const checkedCount = checkboxes.filter(cb => cb.checked).length;

        if (commonSelectedCount) {
            commonSelectedCount.textContent = `${checkedCount} / ${checkboxes.length} 班選択中`;
        }
        if (commonNoLaneWarning) {
            commonNoLaneWarning.classList.toggle('hidden', checkedCount > 0);
        }

        return checkedCount;
    }

    function validateCommonCreateOverlap() {
        const checkboxes = getCommonLaneCheckboxes();
        const checkedCheckboxes = checkboxes.filter(cb => cb.checked);
        const hasNoLanes = checkedCheckboxes.length === 0;

        const start = commonStartTimeInput?.value || '';
        const end = commonEndTimeInput?.value || '';
        const status = commonStatusSelect?.value || 'not_started';
        const taskType = commonCreateModal?.querySelector('input[name="task_type"]:checked')?.value || 'spot';

        const conflicts = [];
        if (status !== 'cancelled' && start && end && start < end) {
            for (const cb of checkedCheckboxes) {
                const laneName = cb.dataset.laneName || '担当班';
                const { sameTypeMatches } = findOverlapsWithLaneTasks(cb.value, start, end, null, taskType);
                for (const task of sameTypeMatches) {
                    conflicts.push({ laneName, task });
                }
            }
        }

        const hasOverlap = conflicts.length > 0;

        if (commonOverlapList) {
            commonOverlapList.textContent = '';
            for (const item of conflicts) {
                const li = document.createElement('li');
                li.textContent = `【${item.laneName}】${item.task.title}（${item.task.start_time}〜${item.task.end_time}）`;
                commonOverlapList.appendChild(li);
            }
        }

        commonOverlapWarning?.classList.toggle('hidden', !hasOverlap);

        if (commonSubmitBtn) {
            commonSubmitBtn.disabled = hasNoLanes;
        }

        return { hasOverlap, hasNoLanes };
    }

    function openCommonCreateModal() {
        if (!commonCreateModal) return;

        closeAllModals();

        if (commonServerErrors) {
            commonServerErrors.classList.add('hidden');
        }
        getCommonLaneCheckboxes().forEach((cb) => {
            cb.checked = true;
        });

        if (commonTitleInput) commonTitleInput.value = '';
        if (commonStartTimeInput) commonStartTimeInput.value = '08:00';
        if (commonEndTimeInput) commonEndTimeInput.value = '08:30';
        if (commonPeopleInput) commonPeopleInput.value = '0';
        if (commonAtLeastInput) commonAtLeastInput.checked = false;
        if (commonCriticalInput) commonCriticalInput.checked = false;
        if (commonLocationInput) commonLocationInput.value = '';
        if (commonStatusSelect) commonStatusSelect.value = 'not_started';
        if (commonAutoAssignLeadInput) commonAutoAssignLeadInput.checked = true;
        if (commonContentInput) commonContentInput.value = '';

        updateCommonSelectedCount();
        validateCommonCreateOverlap();
        commonCreateModal.classList.remove('hidden');
        commonTitleInput?.focus();
    }

    function selectAllLanes(selected) {
        getCommonLaneCheckboxes().forEach((cb) => {
            cb.checked = selected;
        });
        updateCommonSelectedCount();
        validateCommonCreateOverlap();
    }

    function bindEvents() {
        commonCreateModal?.addEventListener('input', (e) => {
            if (e.target.matches('#common-modal-start-time, #common-modal-end-time')) {
                validateCommonCreateOverlap();
            }
        });
        commonCreateModal?.addEventListener('change', (e) => {
            if (e.target.matches('.common-lane-checkbox, #common-modal-status, input[name="task_type"]')) {
                updateCommonSelectedCount();
                validateCommonCreateOverlap();
            }
        });

        commonCreateForm?.addEventListener('submit', (e) => {
            const { hasNoLanes } = validateCommonCreateOverlap();
            if (hasNoLanes) {
                e.preventDefault();
                showToast('登録対象の担当班を1つ以上選択してください。', 'warning');
            }
        });

        if (commonCreateModal?.dataset.reopenOnError === '1') {
            updateCommonSelectedCount();
            validateCommonCreateOverlap();
            commonCreateModal.classList.remove('hidden');
        }
    }

    return {
        closeModal,
        openCommonCreateModal,
        selectAllLanes,
        bindEvents,
    };
}
