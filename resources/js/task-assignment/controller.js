import { confirmDialog } from '../utils/ui.js';
import {
    assignLanePool,
    bulkAssignStaff,
    fetchAssignModalData,
    removeAssignedStaff,
} from './api.js';
import {
    createAssignmentState,
    filterCandidates,
    resetStateForTask,
    selectAllEligible,
    selectShortageCandidates,
    toggleStaffSelection,
} from './state.js';

/**
 * 人員配置モーダル・ワークスペース共通コントローラー
 * API通信・状態更新・成功通知順序を一元管理する
 *
 * @param {{
 *   onLoading?: () => void,
 *   onLoaded?: (taskData: object) => void,
 *   onLoadError?: (message: string, taskId: number|string) => void,
 *   onCandidatesChanged?: () => void,
 *   onSelectionChanged?: () => void,
 *   onSubmittingChanged?: (isSubmitting: boolean) => void,
 *   onConflict?: (message: string) => void,
 *   onAlert?: (message: string, type: 'success'|'error'|'warning') => void,
 *   onMutated?: (taskData: object) => void,
 * }} callbacks
 */
export function createTaskAssignmentController(callbacks = {}) {
    const state = createAssignmentState();
    let isSubmitting = false;

    const getState = () => state;
    const isBusy = () => isSubmitting;

    const getFilteredCandidates = () => {
        if (!state.taskData) {
            return [];
        }
        return filterCandidates(state.taskData.candidate_staff, {
            searchQuery: state.searchQuery,
            statusFilter: state.statusFilter,
            affiliationFilter: state.affiliationFilter,
        });
    };

    const loadTask = async (taskId, { resetFilters = false } = {}) => {
        if (!taskId) {
            return;
        }
        resetStateForTask(state, taskId, { resetFilters });
        callbacks.onLoading?.();

        try {
            const data = await fetchAssignModalData(taskId);
            if (state.taskId !== taskId) {
                return;
            }
            state.taskData = data;
            callbacks.onLoaded?.(data);
        } catch (err) {
            if (state.taskId !== taskId) {
                return;
            }
            callbacks.onLoadError?.(err.message || 'データ読み込みに失敗しました', taskId);
        }
    };

    const reloadCurrentTask = async () => {
        if (!state.taskId) {
            return;
        }
        const targetId = state.taskId;
        const data = await fetchAssignModalData(targetId);
        if (state.taskId !== targetId) {
            return;
        }
        state.taskData = data;
        // 既にアサイン済みとなったIDは選択状態から除外
        const assignedIds = new Set((data.assignments || []).map((a) => a.staff_id));
        state.selectedStaffIds = state.selectedStaffIds.filter((id) => !assignedIds.has(id));
        callbacks.onLoaded?.(data);
    };

    const clear = () => {
        resetStateForTask(state, null, { resetFilters: true });
    };

    const toggleCandidate = (staffId) => {
        toggleStaffSelection(state, staffId);
        callbacks.onSelectionChanged?.();
    };

    const setSearchQuery = (query) => {
        state.searchQuery = query;
        callbacks.onCandidatesChanged?.();
    };

    const setStatusFilter = (status) => {
        state.statusFilter = status;
        callbacks.onCandidatesChanged?.();
    };

    const setAffiliationFilter = (affiliation) => {
        state.affiliationFilter = affiliation;
        callbacks.onCandidatesChanged?.();
    };

    const selectAllFiltered = () => {
        state.selectedStaffIds = selectAllEligible(state.selectedStaffIds, getFilteredCandidates());
        callbacks.onSelectionChanged?.();
    };

    const clearSelection = () => {
        state.selectedStaffIds = [];
        callbacks.onSelectionChanged?.();
    };

    const selectShortage = ({ useFilteredOnly = false } = {}) => {
        if (!state.taskData) {
            return { status: 'no_data', count: 0 };
        }
        const shortage = state.taskData.task?.shortage_count || 0;
        if (shortage <= 0) {
            return { status: 'already_satisfied', count: 0 };
        }

        const pool = useFilteredOnly
            ? getFilteredCandidates()
            : (state.taskData.candidate_staff || []);
        const picked = selectShortageCandidates(pool, shortage);

        if (picked.length === 0) {
            if (useFilteredOnly) {
                state.selectedStaffIds = [];
                callbacks.onSelectionChanged?.();
            }
            return { status: 'no_eligible', count: 0 };
        }

        state.selectedStaffIds = picked;
        callbacks.onSelectionChanged?.();
        return { status: 'selected', count: picked.length };
    };

    const submitBulkAssign = async ({ roleDescription = '', force = false } = {}) => {
        if (!state.taskId || state.selectedStaffIds.length === 0 || isSubmitting) {
            return;
        }

        isSubmitting = true;
        callbacks.onSubmittingChanged?.(true);

        try {
            const res = await bulkAssignStaff(state.taskId, {
                staffIds: state.selectedStaffIds,
                roleDescription,
                force,
            });

            if (!res.ok) {
                if (res.hasConflict) {
                    callbacks.onConflict?.(res.message || '時間帯が重複しているスタッフが含まれています');
                    return;
                }
                callbacks.onAlert?.(res.message || '配置処理に失敗しました', 'error');
                return;
            }

            state.selectedStaffIds = [];
            await reloadCurrentTask();
            callbacks.onAlert?.(res.message || 'スタッフをアサインしました。', 'success');
            if (state.taskData) {
                callbacks.onMutated?.(state.taskData);
            }
        } catch (err) {
            callbacks.onAlert?.(err.message || '通信エラーが発生しました', 'error');
        } finally {
            isSubmitting = false;
            callbacks.onSubmittingChanged?.(false);
        }
    };

    const executeAssignLanePool = async () => {
        if (!state.taskId || isSubmitting) {
            return;
        }

        isSubmitting = true;
        callbacks.onSubmittingChanged?.(true);

        try {
            const res = await assignLanePool(state.taskId);
            if (!res.ok) {
                callbacks.onAlert?.(res.message || '自動配置に失敗しました', 'error');
                return;
            }

            await reloadCurrentTask();
            callbacks.onAlert?.(res.message || '班メンバーを自動配置しました。', 'success');
            if (state.taskData) {
                callbacks.onMutated?.(state.taskData);
            }
        } catch (err) {
            callbacks.onAlert?.(err.message || '自動配置処理に失敗しました', 'error');
        } finally {
            isSubmitting = false;
            callbacks.onSubmittingChanged?.(false);
        }
    };

    const executeRemoveStaff = async (staffId, staffName = '') => {
        if (!state.taskId || !staffId || isSubmitting) {
            return;
        }

        const message = staffName
            ? `スタッフ「${staffName}」の配置を解除しますか？`
            : 'このスタッフの割り当てを解除しますか？';

        const confirmed = await confirmDialog(message, {
            variant: 'danger',
            title: '配置解除の確認',
            confirmText: '解除する',
        });
        if (!confirmed) {
            return;
        }

        isSubmitting = true;
        callbacks.onSubmittingChanged?.(true);

        try {
            const res = await removeAssignedStaff(state.taskId, staffId);
            if (!res.ok) {
                callbacks.onAlert?.(res.message || '解除に失敗しました', 'error');
                return;
            }

            await reloadCurrentTask();
            callbacks.onAlert?.(res.message || '配置を解除しました。', 'success');
            if (state.taskData) {
                callbacks.onMutated?.(state.taskData);
            }
        } catch (err) {
            callbacks.onAlert?.(err.message || '解除に失敗しました', 'error');
        } finally {
            isSubmitting = false;
            callbacks.onSubmittingChanged?.(false);
        }
    };

    return {
        getState,
        isBusy,
        getFilteredCandidates,
        loadTask,
        reloadCurrentTask,
        clear,
        toggleCandidate,
        setSearchQuery,
        setStatusFilter,
        setAffiliationFilter,
        selectAllFiltered,
        clearSelection,
        selectShortage,
        submitBulkAssign,
        executeAssignLanePool,
        executeRemoveStaff,
    };
}
