import { createTaskAssignmentController } from '../task-assignment/controller.js';
import {
    clearAlertBox,
    renderAlertBox,
    renderCandidateCardsHtml,
    renderCurrentAssignmentsHtml,
    renderTaskStatusBadgeHtml,
    updateSelectionButtons,
} from '../task-assignment/renderers.js';
import { escapeHtml } from '../utils/html.js';

const SUBMIT_ACTIVE_CLASS = 'px-4 py-2 rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-1.5 bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer shrink-0';
const SUBMIT_DISABLED_CLASS = 'px-4 py-2 rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-1.5 bg-slate-200 text-slate-400 cursor-not-allowed shrink-0';

/**
 * 人員事前配置ワークスペース 右ペイン（タスク詳細・候補管理・一括配置）
 *
 * @param {{ timetableId: string, onTaskMutated?: (taskData: object) => void }} options
 */
export function initDetailPane({ timetableId, onTaskMutated } = {}) {
    const loadingEl = document.getElementById('detail-loading');
    const contentEl = document.getElementById('detail-content');
    const alertArea = document.getElementById('assign-alert-area');
    const poolBtnWrapper = document.getElementById('detail-pool-btn-wrapper');
    const currentAssignmentsList = document.getElementById('current-assignments-list');
    const candidateStaffList = document.getElementById('candidate-staff-list');

    const showLoading = () => {
        if (loadingEl) {
            loadingEl.innerHTML = `
                <span class="inline-block animate-spin text-3xl mb-2">⏳</span>
                <p>タスクデータを読み込み中...</p>
            `;
            loadingEl.classList.remove('hidden');
        }
        if (contentEl) contentEl.classList.add('hidden');
    };

    const hideLoading = () => {
        if (loadingEl) loadingEl.classList.add('hidden');
        if (contentEl) contentEl.classList.remove('hidden');
    };

    const showLoadError = (message, taskId) => {
        if (!loadingEl) return;
        loadingEl.innerHTML = `
            <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-xs inline-block text-left">
                <p class="font-bold mb-1">エラーが発生しました</p>
                <p>${escapeHtml(message || '読み込みに失敗しました')}</p>
                <button type="button" data-action="retry-task" data-task-id="${escapeHtml(taskId)}"
                    class="mt-2 px-3 py-1 bg-white border border-red-300 rounded text-xs font-bold text-red-700 hover:bg-red-50 cursor-pointer">
                    再試行
                </button>
            </div>
        `;
        loadingEl.classList.remove('hidden');
    };

    const updateSelectionCounters = () => {
        const state = controller.getState();
        updateSelectionButtons({
            count: state.selectedStaffIds.length,
            countEl: document.getElementById('assign-selected-count'),
            submitCountEl: document.getElementById('assign-btn-count'),
            clearBtn: document.getElementById('btn-clear-selection'),
            submitBtn: document.getElementById('btn-submit-bulk-assign'),
            activeClass: SUBMIT_ACTIVE_CLASS,
            disabledClass: SUBMIT_DISABLED_CLASS,
            isSubmitting: controller.isBusy(),
        });
    };

    const renderCandidateStaff = () => {
        const state = controller.getState();
        const candidates = state.taskData ? (state.taskData.candidate_staff || []) : [];
        const totalLabel = document.getElementById('candidates-total-label');
        if (totalLabel) totalLabel.textContent = `（全 ${candidates.length} 名）`;
        if (!candidateStaffList) return;

        const filtered = controller.getFilteredCandidates();
        candidateStaffList.innerHTML = renderCandidateCardsHtml(filtered, state.selectedStaffIds, {
            variant: 'list',
            emptyMessage: '条件に一致する候補スタッフがいません。',
        });
    };

    const renderCurrentAssignments = (assignments) => {
        const countBadge = document.getElementById('current-assignments-count-badge');
        if (countBadge) countBadge.textContent = `${assignments.length}名`;
        if (!currentAssignmentsList) return;

        currentAssignmentsList.innerHTML = renderCurrentAssignmentsHtml(assignments, {
            emptyMessage: 'まだスタッフが配置されていません。下の一覧から選択してアサインしてください。',
            wrapInGrid: false,
        });
    };

    const renderRightPane = (taskData) => {
        if (!taskData) return;
        hideLoading();
        clearAlertBox(alertArea);

        const task = taskData.task;
        const assignments = taskData.assignments || [];

        const titleEl = document.getElementById('detail-task-title');
        const timeEl = document.getElementById('detail-task-time');
        const locationEl = document.getElementById('detail-task-location');
        const statusEl = document.getElementById('detail-task-status');
        const taskLink = document.getElementById('detail-task-link');

        const fullTitle = task.lane ? `${task.lane} ${task.title}` : task.title;
        if (titleEl) titleEl.textContent = fullTitle;
        if (timeEl) timeEl.textContent = `⏱️ ${task.start_time} 〜 ${task.end_time}`;
        if (locationEl) {
            if (task.location) {
                locationEl.textContent = `📍 ${task.location}`;
                locationEl.classList.remove('hidden');
            } else {
                locationEl.classList.add('hidden');
            }
        }
        if (statusEl) {
            statusEl.innerHTML = renderTaskStatusBadgeHtml(task);
        }

        if (poolBtnWrapper) {
            if (!task.is_satisfied && task.lane) {
                poolBtnWrapper.innerHTML = `
                    <div class="flex items-center space-x-1.5">
                        <button type="button" id="btn-assign-lane-pool"
                            title="「${escapeHtml(task.lane)}」の空き専属メンバーから不足分を自動配置"
                            class="px-2.5 py-1 bg-amber-400 hover:bg-amber-300 text-slate-950 rounded-lg text-[11px] font-bold transition flex items-center space-x-1 cursor-pointer shadow-xs">
                            <span>⚡</span>
                            <span>班から自動配置</span>
                        </button>
                        <span class="hidden sm:inline-block text-[10px] text-indigo-200/90 bg-slate-800/80 border border-slate-700 px-1.5 py-0.5 rounded" title="担当班の固定・所属スタッフから、重複のない空きメンバーを不足人数分だけ自動で配置します">
                            ※ 担当班の空き枠から自動補充
                        </span>
                    </div>
                `;
            } else {
                poolBtnWrapper.innerHTML = '';
            }
        }

        if (taskLink) {
            taskLink.href = `/timetable-tasks?timetable_id=${timetableId}#task-${task.id}`;
        }

        renderCurrentAssignments(assignments);
        renderCandidateStaff();
        updateSelectionCounters();
    };

    const getRoleDescription = () => document.getElementById('assign-role-description')?.value || '一般担当';

    const controller = createTaskAssignmentController({
        onLoading: showLoading,
        onLoaded: renderRightPane,
        onLoadError: showLoadError,
        onCandidatesChanged: renderCandidateStaff,
        onSelectionChanged: () => {
            renderCandidateStaff();
            updateSelectionCounters();
        },
        onSubmittingChanged: updateSelectionCounters,
        onConflict: (message) => {
            renderAlertBox(alertArea, {
                message,
                type: 'warning',
                allowForce: true,
                showCancel: true,
                conflictTitle: '時間帯が重複しているスタッフが含まれています',
            });
        },
        onAlert: (message, type) => {
            renderAlertBox(alertArea, {
                message,
                type,
                allowForce: false,
            });
        },
        onMutated: (taskData) => {
            onTaskMutated?.(taskData);
        },
    });

    // ローディング領域の再試行ボタン委譲
    loadingEl?.addEventListener('click', (e) => {
        const retryBtn = e.target.closest('[data-action="retry-task"]');
        if (retryBtn) {
            const tId = parseInt(retryBtn.getAttribute('data-task-id'), 10);
            if (tId) {
                controller.loadTask(tId, { resetFilters: false });
            }
        }
    });

    // ヘッダー自動配置ボタン委譲
    poolBtnWrapper?.addEventListener('click', (e) => {
        if (e.target.closest('#btn-assign-lane-pool')) {
            controller.executeAssignLanePool();
        }
    });

    // アラート領域ボタン委譲（閉じる・重複強制承認）
    alertArea?.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="dismiss-alert"]')) {
            clearAlertBox(alertArea);
            return;
        }
        if (e.target.closest('[data-action="submit-bulk-assign-force"]')) {
            controller.submitBulkAssign({ roleDescription: getRoleDescription(), force: true });
        }
    });

    // 現在配置スタッフの解除ボタン委譲
    currentAssignmentsList?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action="remove-staff"]');
        if (!btn) return;
        const staffId = parseInt(btn.getAttribute('data-staff-id'), 10);
        const staffName = btn.getAttribute('data-staff-name') || '';
        if (staffId) {
            controller.executeRemoveStaff(staffId, staffName);
        }
    });

    // 候補スタッフチェックボックス委譲
    candidateStaffList?.addEventListener('change', (e) => {
        if (e.target && e.target.matches('[data-staff-checkbox]')) {
            const staffId = parseInt(e.target.value, 10);
            if (staffId) {
                controller.toggleCandidate(staffId);
            }
        }
    });

    // 候補スタッフ絞り込みタブ
    const staffTabs = document.querySelectorAll('[data-staff-tab]');
    staffTabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            staffTabs.forEach((t) => {
                t.classList.remove('bg-slate-100', 'text-slate-900');
                t.classList.add('text-slate-600');
            });
            tab.classList.remove('text-slate-600');
            tab.classList.add('bg-slate-100', 'text-slate-900');

            controller.setStatusFilter(tab.getAttribute('data-staff-tab') || 'all');
        });
    });

    // 候補スタッフ検索 & 所属フィルター
    document.getElementById('staff-search-input')?.addEventListener('input', (e) => {
        controller.setSearchQuery(e.target.value);
    });

    document.getElementById('staff-affiliation-filter')?.addEventListener('change', (e) => {
        controller.setAffiliationFilter(e.target.value);
    });

    // アシスト操作: 不足分を自動選択
    document.getElementById('btn-select-shortage-auto')?.addEventListener('click', () => {
        const result = controller.selectShortage({ useFilteredOnly: false });
        if (result.status === 'already_satisfied') {
            renderAlertBox(alertArea, { message: 'このタスクはすでに人員が充足しています。', type: 'success' });
        } else if (result.status === 'no_eligible') {
            renderAlertBox(alertArea, {
                message: '現在空いているスタッフがいません。他班のスタッフや重複スタッフを個別に選択してください。',
                type: 'warning',
            });
        } else if (result.status === 'selected') {
            renderAlertBox(alertArea, {
                message: `空きスタッフから ${result.count} 名を自動選択しました。`,
                type: 'success',
            });
        }
    });

    document.getElementById('btn-select-all-filtered')?.addEventListener('click', () => {
        controller.selectAllFiltered();
    });

    document.getElementById('btn-clear-selection')?.addEventListener('click', () => {
        controller.clearSelection();
    });

    document.getElementById('btn-submit-bulk-assign')?.addEventListener('click', () => {
        controller.submitBulkAssign({ roleDescription: getRoleDescription(), force: false });
    });

    return {
        loadTask: (taskId) => controller.loadTask(taskId, { resetFilters: false }),
    };
}
