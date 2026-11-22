import { createTaskAssignmentController } from '../task-assignment/controller.js';
import {
    clearAlertBox,
    renderAlertBox,
    renderCandidateCardsHtml,
    renderCurrentAssignmentsHtml,
    renderTaskStatusBadgeHtml,
    updateSelectionButtons,
} from '../task-assignment/renderers.js';
import { extractAffiliations } from '../task-assignment/state.js';
import { escapeHtml } from '../utils/html.js';

const SUBMIT_ACTIVE_CLASS = 'px-4 py-2 rounded-lg text-xs font-bold shadow-sm transition flex items-center space-x-1 bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer shrink-0';
const SUBMIT_DISABLED_CLASS = 'px-4 py-2 rounded-lg text-xs font-bold shadow-sm transition flex items-center space-x-1 bg-slate-200 text-slate-400 cursor-not-allowed shrink-0';

/**
 * 進行表クイックアサイン スライドオーバーモーダル (CSP完全準拠)
 */
export function initQuickAssignModal() {
    const modal = document.getElementById('quickAssignModal');
    const bodyEl = document.getElementById('quickAssignModalBody');
    if (!modal || !bodyEl) return;

    const getAlertArea = () => document.getElementById('quickAssignAlertArea');

    const renderLoading = () => {
        bodyEl.innerHTML = `
            <div class="py-16 text-center text-slate-400 text-xs">
                <span class="inline-block animate-spin text-2xl mb-2">⏳</span>
                <p>データを読み込み中...</p>
            </div>
        `;
    };

    const renderError = (msg) => {
        bodyEl.innerHTML = `
            <div class="p-4 m-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-xs">
                <p class="font-bold mb-1">エラーが発生しました</p>
                <p>${escapeHtml(msg)}</p>
                <button type="button" data-action="retry-quick-assign" class="mt-3 px-3 py-1 bg-white border border-red-300 rounded text-xs text-red-700 font-bold hover:bg-red-50 cursor-pointer">再試行</button>
            </div>
        `;
    };

    const updateButtons = () => {
        const state = controller.getState();
        updateSelectionButtons({
            count: state.selectedStaffIds.length,
            countEl: document.getElementById('quickAssignSelectedCount'),
            submitCountEl: document.getElementById('quickAssignSubmitCount'),
            clearBtn: document.getElementById('quickAssignClearBtn'),
            submitBtn: document.getElementById('quickAssignSubmitBtn'),
            activeClass: SUBMIT_ACTIVE_CLASS,
            disabledClass: SUBMIT_DISABLED_CLASS,
            isSubmitting: controller.isBusy(),
        });
    };

    const renderCandidateList = () => {
        const container = document.getElementById('quickAssignCandidateListContainer');
        const state = controller.getState();
        if (!container || !state.taskData) return;

        const filtered = controller.getFilteredCandidates();
        container.innerHTML = renderCandidateCardsHtml(filtered, state.selectedStaffIds, {
            variant: 'grid',
            emptyMessage: '該当するスタッフがいません。',
        });
    };

    const renderContent = (taskData) => {
        if (!taskData) return;
        const state = controller.getState();
        const task = taskData.task;
        const assignments = taskData.assignments || [];
        const candidates = taskData.candidate_staff || [];
        const affiliations = extractAffiliations(candidates);

        // 1. ヘッダー情報と人員充足バッジ・自動配置ボタンの更新
        const headerTitle = document.getElementById('quickAssignTaskTitle');
        const headerTime = document.getElementById('quickAssignTaskTime');
        const headerLane = document.getElementById('quickAssignTaskLane');
        const headerLocation = document.getElementById('quickAssignTaskLocation');
        const headerStatus = document.getElementById('quickAssignHeaderStatus');
        const headerPoolBtn = document.getElementById('quickAssignHeaderPoolBtn');
        const detailLink = document.getElementById('quickAssignDetailLink');

        if (headerTitle) headerTitle.textContent = task.title;
        if (headerTime) headerTime.textContent = `⏱️ ${task.start_time} 〜 ${task.end_time}`;
        if (headerLane) headerLane.textContent = `🏷️ ${task.lane || '班未設定'}`;
        if (headerLocation) {
            if (task.location) {
                headerLocation.textContent = `📍 ${task.location}`;
                headerLocation.classList.remove('hidden');
            } else {
                headerLocation.classList.add('hidden');
            }
        }
        if (headerStatus) {
            headerStatus.innerHTML = renderTaskStatusBadgeHtml(task);
        }
        if (headerPoolBtn) {
            headerPoolBtn.innerHTML = (!task.is_satisfied && task.lane)
                ? `<button type="button" data-action="assign-lane-pool" title="この班の空きメンバーから不足分を自動配置" class="px-2.5 py-1 bg-amber-400 hover:bg-amber-300 text-slate-950 rounded-lg text-[11px] font-bold transition flex items-center space-x-1 cursor-pointer shadow-xs">
                    <span>⚡</span>
                    <span>「${escapeHtml(task.lane)}」自動配置</span>
                   </button>`
                : '';
        }
        if (detailLink) detailLink.href = `/timetable-tasks/${task.id}`;

        // 2. 現在配置中のスタッフ一覧（班長は解除不可）
        const assignedListHtml = renderCurrentAssignmentsHtml(assignments, {
            emptyMessage: 'まだスタッフが配置されていません。下の一覧から選択して配置してください。',
            wrapInGrid: true,
        });

        const affiliationOptionsHtml = affiliations.map((aff) =>
            `<option value="${escapeHtml(aff)}" ${state.affiliationFilter === aff ? 'selected' : ''}>${escapeHtml(aff)}</option>`
        ).join('');

        const selectedCount = state.selectedStaffIds.length;

        bodyEl.innerHTML = `
            <!-- 上部固定領域: アラート & 現在配置中のスタッフ -->
            <div class="px-4 pt-3 pb-2.5 border-b border-slate-200 space-y-2.5 shrink-0 bg-white">
                <div id="quickAssignAlertArea" class="hidden"></div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <h4 class="text-xs font-bold text-slate-700 flex items-center space-x-1">
                            <span>👥</span>
                            <span>現在配置中のスタッフ (${assignments.length}名)</span>
                        </h4>
                    </div>
                    ${assignedListHtml}
                </div>
            </div>

            <!-- 中央ヘッダー & フィルターバー (固定) -->
            <div class="px-4 pt-2.5 pb-2 bg-slate-50/80 border-b border-slate-200 space-y-2 shrink-0">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h4 class="text-xs font-bold text-slate-800 flex items-center space-x-1">
                        <span>➕</span>
                        <span>スタッフを追加配置する</span>
                        <span class="text-[10px] font-normal text-slate-400">（全 ${candidates.length} 名）</span>
                    </h4>
                    <div class="flex items-center space-x-1.5">
                        <span class="text-[11px] text-slate-600 font-medium">
                            選択中: <strong id="quickAssignSelectedCount" class="text-indigo-600 font-mono text-xs">${selectedCount}</strong> 名
                        </span>
                        ${(!task.is_satisfied && task.shortage_count > 0) ? `
                        <button type="button" data-action="select-shortage-auto"
                            class="px-2 py-0.5 bg-amber-100 hover:bg-amber-200 text-amber-900 border border-amber-300 rounded text-[11px] font-bold transition cursor-pointer">
                            ⚡ 不足${task.shortage_count}名を自動選択
                        </button>` : ''}
                        <button type="button" data-action="select-all-filtered"
                            class="px-2 py-0.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded text-[11px] font-medium transition cursor-pointer">
                            表示中を全選択
                        </button>
                        <button type="button" id="quickAssignClearBtn" data-action="clear-selection"
                            class="${selectedCount > 0 ? '' : 'hidden'} px-2 py-0.5 bg-white hover:bg-slate-100 text-slate-500 border border-slate-200 rounded text-[11px] font-medium transition cursor-pointer">
                            解除
                        </button>
                    </div>
                </div>

                <!-- 検索・状態・所属フィルター (3カラム) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5">
                    <input type="text" id="quickAssignSearchInput" value="${escapeHtml(state.searchQuery)}" placeholder="🔍 氏名・カナ・所属で検索..."
                        class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <select id="quickAssignStatusSelect"
                        class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="all" ${state.statusFilter === 'all' ? 'selected' : ''}>すべての状態</option>
                        <option value="recommended" ${state.statusFilter === 'recommended' ? 'selected' : ''}>🌟 推奨（同班＆空き）</option>
                        <option value="surplus" ${state.statusFilter === 'surplus' ? 'selected' : ''}>✨ 他班の余剰スタッフ（融通可）</option>
                        <option value="available" ${state.statusFilter === 'available' ? 'selected' : ''}>🟢 空きスタッフのみ</option>
                        <option value="checked_in" ${state.statusFilter === 'checked_in' ? 'selected' : ''}>📍 現場到着済のみ</option>
                    </select>
                    <select id="quickAssignAffiliationSelect"
                        class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">すべての所属団体</option>
                        ${affiliationOptionsHtml}
                    </select>
                </div>
            </div>

            <!-- 候補スタッフ一覧（独立スクロール領域・2列高密度グリッド） -->
            <div class="flex-1 min-h-0 overflow-y-auto bg-slate-50/50 p-3">
                <div id="quickAssignCandidateListContainer" class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                </div>
            </div>

            <!-- 下部常時表示アクションバー（担当役割 & 配置するボタンが隠れない固定枠） -->
            <div class="px-4 py-2.5 bg-white border-t border-slate-200 flex items-center gap-2 shrink-0 shadow-[0_-2px_10px_rgba(0,0,0,0.03)]">
                <input type="text" id="quickAssignRoleInput" placeholder="担当役割（任意、例: チーフ、案内、運搬）"
                    class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <button type="button" id="quickAssignSubmitBtn" data-action="submit-bulk-assign"
                    class="${selectedCount > 0 ? SUBMIT_ACTIVE_CLASS : SUBMIT_DISABLED_CLASS}"
                    ${selectedCount === 0 ? 'disabled' : ''}>
                    <span>➕</span>
                    <span>選択した <strong id="quickAssignSubmitCount">${selectedCount}</strong> 名を配置する</span>
                </button>
            </div>
        `;

        renderCandidateList();
        updateButtons();
    };

    const controller = createTaskAssignmentController({
        onLoading: renderLoading,
        onLoaded: renderContent,
        onLoadError: renderError,
        onCandidatesChanged: renderCandidateList,
        onSelectionChanged: () => {
            updateButtons();
            renderCandidateList();
        },
        onSubmittingChanged: updateButtons,
        onConflict: (message) => {
            renderAlertBox(getAlertArea(), {
                message,
                type: 'error',
                allowForce: true,
            });
        },
        onAlert: (message, type) => {
            renderAlertBox(getAlertArea(), {
                message,
                type,
                allowForce: false,
            });
        },
    });

    const openModal = async (taskId) => {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        await controller.loadTask(taskId, { resetFilters: true });
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        controller.clear();
    };

    // フィルター入力・セレクトの単一回イベント委譲
    bodyEl.addEventListener('input', (e) => {
        if (e.target.id === 'quickAssignSearchInput') {
            controller.setSearchQuery(e.target.value);
        }
    });

    bodyEl.addEventListener('change', (e) => {
        if (e.target.id === 'quickAssignStatusSelect') {
            controller.setStatusFilter(e.target.value);
        } else if (e.target.id === 'quickAssignAffiliationSelect') {
            controller.setAffiliationFilter(e.target.value);
        }
    });

    // クリックイベント委譲
    document.addEventListener('click', async (e) => {
        const openBtn = e.target.closest('[data-action="open-quick-assign"]');
        if (openBtn) {
            const taskId = openBtn.getAttribute('data-task-id');
            if (taskId) {
                await openModal(taskId);
            }
            return;
        }

        if (e.target.closest('[data-action="close-quick-assign"]') || e.target === modal) {
            closeModal();
            return;
        }

        if (e.target.closest('[data-action="dismiss-alert"]')) {
            clearAlertBox(getAlertArea());
            return;
        }

        if (e.target.closest('[data-action="retry-quick-assign"]')) {
            const currentId = controller.getState().taskId;
            if (currentId) {
                await controller.loadTask(currentId, { resetFilters: false });
            }
            return;
        }

        const candidateRow = e.target.closest('[data-action="toggle-candidate"]');
        if (candidateRow) {
            if (candidateRow.dataset.assigned === '1') return;
            const staffId = parseInt(candidateRow.dataset.staffId, 10);
            if (staffId) {
                controller.toggleCandidate(staffId);
            }
            return;
        }

        if (e.target.closest('[data-action="select-shortage-auto"]')) {
            controller.selectShortage({ useFilteredOnly: true });
            return;
        }

        if (e.target.closest('[data-action="select-all-filtered"]')) {
            controller.selectAllFiltered();
            return;
        }

        if (e.target.closest('[data-action="clear-selection"]')) {
            controller.clearSelection();
            return;
        }

        if (e.target.closest('[data-action="submit-bulk-assign"]')) {
            const roleInput = document.getElementById('quickAssignRoleInput');
            await controller.submitBulkAssign({
                roleDescription: roleInput ? roleInput.value : '',
                force: false,
            });
            return;
        }

        if (e.target.closest('[data-action="submit-bulk-assign-force"]')) {
            const roleInput = document.getElementById('quickAssignRoleInput');
            await controller.submitBulkAssign({
                roleDescription: roleInput ? roleInput.value : '',
                force: true,
            });
            return;
        }

        if (e.target.closest('[data-action="assign-lane-pool"]')) {
            await controller.executeAssignLanePool();
            return;
        }

        const removeBtn = e.target.closest('#quickAssignModal [data-action="remove-staff"]');
        if (removeBtn) {
            const staffId = parseInt(removeBtn.getAttribute('data-staff-id'), 10);
            const staffName = removeBtn.getAttribute('data-staff-name') || '';
            if (staffId) {
                await controller.executeRemoveStaff(staffId, staffName);
            }
            return;
        }

        if (e.target.closest('[data-action="reload-matrix"]')) {
            closeModal();
            window.location.reload();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
}
