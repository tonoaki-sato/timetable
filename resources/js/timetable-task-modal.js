import { createSingleModalsController } from './timetable-task-modal/single-modals.js';
import { createCommonCreateModalController } from './timetable-task-modal/common-create-modal.js';

/**
 * 作業タスク操作モーダル管理スクリプト (CSP完全準拠・Vanilla JS)
 * 詳細・追加・編集・共通一括登録モーダルの開閉、データバインディング、同一班内時間帯重複検知
 */
document.addEventListener('DOMContentLoaded', () => {
    initTimetableTaskModals();
});

function initTimetableTaskModals() {
    let singleModals = null;
    let commonCreateModal = null;

    const closeAllModals = () => {
        singleModals?.closeModals();
        commonCreateModal?.closeModal();
    };

    singleModals = createSingleModalsController({ closeAllModals });
    commonCreateModal = createCommonCreateModalController({ closeAllModals });

    singleModals.bindEvents();
    commonCreateModal.bindEvents();

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="close-modal"]')) {
            e.preventDefault();
            closeAllModals();
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

        const detailBtn = e.target.closest('[data-action="open-task-detail"]');
        if (detailBtn) {
            e.preventDefault();
            const taskId = detailBtn.dataset.taskId;
            if (taskId) singleModals.openDetailModal(taskId);
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

        const editBtn = e.target.closest('[data-action="open-task-edit"]');
        if (editBtn) {
            e.preventDefault();
            const taskId = editBtn.dataset.taskId;
            if (taskId) singleModals.openEditModal(taskId);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
}
