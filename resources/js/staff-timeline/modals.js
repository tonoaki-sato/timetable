import { escapeHtml, getCsrfToken } from '../utils/html.js';
import { confirmDialog, showToast } from '../utils/ui.js';

/**
 * スタッフタイムライン モーダル制御（2タブ配置/新規作成 ＋ バー詳細/配置解除）
 */
export function initStaffTimelineModals({ timetableId }) {
    const modal = document.getElementById('timelineQuickAssignModal');
    const barActionModal = document.getElementById('staffBarActionModal');
    if (!modal) return { openQuickAssignModal: () => {}, openBarActionModal: () => {} };

    let allTasks = [];
    const dataContainer = document.getElementById('timeline-tasks-data');
    if (dataContainer?.textContent) {
        try {
            allTasks = JSON.parse(dataContainer.textContent);
        } catch (e) {
            // ignore
        }
    }

    const staffNameEl = document.getElementById('timelineStaffName');
    const staffBadgeEl = document.getElementById('timelineStaffBadge');
    const slotTimeEl = document.getElementById('timelineSlotTime');
    const taskListContainer = document.getElementById('timelineTaskList');
    const submitBtn = document.getElementById('timelineSubmitBtn');
    const errorEl = document.getElementById('timelineErrorMessage');
    const errorTextEl = document.getElementById('timelineErrorText');
    const forceWrap = document.getElementById('timelineForceConfirmWrap');
    const forceBtn = document.getElementById('timelineForceSubmitBtn');
    const showSatisfiedToggle = document.getElementById('timelineShowSatisfiedToggle');

    const panelExisting = document.getElementById('timelinePanelExisting');
    const quickCreateForm = document.getElementById('timelineQuickCreateForm');
    const createStartInput = document.getElementById('timelineQuickCreateStart');
    const createEndInput = document.getElementById('timelineQuickCreateEnd');
    const createTitleInput = document.getElementById('timelineQuickCreateTitle');
    const createTypeSpotRadio = document.getElementById('timelineQuickCreateTypeSpot');
    const createTypeRoutineRadio = document.getElementById('timelineQuickCreateTypeRoutine');

    let currentStaffId = null;
    let currentStaffName = '';
    let currentStaffAssignments = [];
    let currentStartMin = 0;
    let currentEndMin = 0;
    let currentStartTime = '';
    let currentEndTime = '';
    let activeTab = 'existing';
    let selectedTaskId = null;
    let isSubmitting = false;

    const showModalError = (msg, allowForce = false) => {
        if (!errorEl || !errorTextEl) return;
        errorTextEl.textContent = msg;
        errorEl.classList.remove('hidden');
        if (forceWrap) {
            forceWrap.classList.toggle('hidden', !allowForce);
        }
    };

    const clearModalError = () => {
        if (errorEl) errorEl.classList.add('hidden');
        if (errorTextEl) errorTextEl.textContent = '';
        if (forceWrap) forceWrap.classList.add('hidden');
    };

    const switchTab = (tab) => {
        activeTab = tab;
        clearModalError();
        document.querySelectorAll('.timeline-modal-tab').forEach((btn) => {
            const active = btn.dataset.assignTab === tab;
            btn.classList.toggle('bg-white', active);
            btn.classList.toggle('text-indigo-700', active);
            btn.classList.toggle('shadow-2xs', active);
            btn.classList.toggle('text-slate-600', !active);
        });
        panelExisting?.classList.toggle('hidden', tab !== 'existing');
        quickCreateForm?.classList.toggle('hidden', tab !== 'create');
    };

    const detectConflictKind = (candidateTask) => {
        const cStart = candidateTask.start_min;
        const cEnd = candidateTask.end_min;
        const cType = candidateTask.task_type || 'spot';

        let sameTypeConflict = null;
        let diffTypeConcurrent = null;

        for (const a of currentStaffAssignments) {
            if (a.start_min < cEnd && a.end_min > cStart) {
                if ((a.task_type || 'spot') === cType) {
                    sameTypeConflict = a;
                    break;
                }
                if (!diffTypeConcurrent) {
                    diffTypeConcurrent = a;
                }
            }
        }

        return { sameTypeConflict, diffTypeConcurrent };
    };

    const updateSubmitBtn = () => {
        if (!submitBtn) return;
        if (selectedTaskId && !isSubmitting) {
            submitBtn.disabled = false;
            submitBtn.className = 'px-4 py-2 rounded-xl text-xs font-bold shadow transition flex items-center space-x-1.5 bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer';
        } else {
            submitBtn.disabled = true;
            submitBtn.className = 'px-4 py-2 rounded-xl text-xs font-bold shadow transition flex items-center space-x-1.5 bg-slate-200 text-slate-400 cursor-not-allowed';
        }
    };

    const renderTaskList = () => {
        if (!taskListContainer) return;

        const includeSatisfied = Boolean(showSatisfiedToggle?.checked);
        const filtered = allTasks.filter((t) => {
            const overlapsRange = currentStartMin > 0 && currentEndMin > 0
                ? (t.start_min < currentEndMin && t.end_min > currentStartMin)
                : true;
            const notAssigned = !(t.assigned_staff_ids || []).includes(currentStaffId);
            const matchShortage = includeSatisfied ? true : t.shortage_count > 0;
            return overlapsRange && notAssigned && matchShortage;
        });

        if (filtered.length === 0) {
            taskListContainer.innerHTML = `
                <div class="p-6 text-center text-xs text-slate-400 space-y-1.5">
                    <p>この時間帯（${escapeHtml(currentStartTime)}〜${escapeHtml(currentEndTime)}）に該当する${includeSatisfied ? '' : '人員不足の'}タスクはありません。</p>
                    <p class="text-[11px] text-indigo-600 font-bold">「➕ 新規タスクを作成して配置」タブから新しく追加することもできます。</p>
                </div>
            `;
            return;
        }

        taskListContainer.innerHTML = filtered.map((t) => {
            const { sameTypeConflict, diffTypeConcurrent } = detectConflictKind(t);
            let overlapBadge = '';
            if (sameTypeConflict) {
                overlapBadge = `<span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-red-100 text-red-700 border border-red-200" title="同種重複: ${escapeHtml(sameTypeConflict.title)}">⚠️ 同種重複</span>`;
            } else if (diffTypeConcurrent) {
                overlapBadge = `<span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-teal-100 text-teal-800 border border-teal-200" title="${escapeHtml(diffTypeConcurrent.title)}と並行配置可">✅ 並行配置可</span>`;
            }

            const shortageBadge = t.shortage_count > 0
                ? `<span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">あと${t.shortage_count}名</span>`
                : `<span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">充足(${t.assigned_count})</span>`;

            return `
                <label class="flex items-center justify-between gap-2 p-3 hover:bg-white cursor-pointer transition text-xs ${selectedTaskId === t.id ? 'bg-indigo-50/80 font-bold border-l-4 border-l-indigo-600' : ''}">
                    <div class="flex items-center space-x-2.5 min-w-0">
                        <input type="radio" name="timeline_task_id" value="${t.id}" ${selectedTaskId === t.id ? 'checked' : ''}
                            class="timeline-task-radio text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                        <div class="min-w-0">
                            <div class="text-slate-900 font-bold flex items-center gap-1.5 flex-wrap">
                                <span>${escapeHtml(t.task_type_icon)}</span>
                                <span>${escapeHtml(t.lane)}</span>
                                <span class="font-mono text-indigo-700">${escapeHtml(t.start_time)}〜${escapeHtml(t.end_time)}</span>
                            </div>
                            <div class="text-[11px] text-slate-600 mt-0.5 truncate">${escapeHtml(t.title)}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        ${overlapBadge}
                        ${shortageBadge}
                    </div>
                </label>
            `;
        }).join('');
    };

    const openQuickAssignModal = ({
        staffId,
        staffName,
        isLeader,
        assignments = [],
        startMin = 0,
        endMin = 0,
        startTime = '',
        endTime = '',
        suggestedTaskType = 'spot',
    }) => {
        currentStaffId = staffId;
        currentStaffName = staffName;
        currentStaffAssignments = assignments;
        currentStartMin = startMin;
        currentEndMin = endMin;
        currentStartTime = startTime || '09:00';
        currentEndTime = endTime || '10:00';
        selectedTaskId = null;
        isSubmitting = false;

        if (staffNameEl) staffNameEl.textContent = staffName;
        if (staffBadgeEl) {
            if (isLeader) {
                staffBadgeEl.className = 'px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300 shrink-0';
                staffBadgeEl.textContent = '班長';
            } else {
                staffBadgeEl.className = 'px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200 shrink-0';
                staffBadgeEl.textContent = '一般';
            }
        }
        if (slotTimeEl) {
            slotTimeEl.textContent = startTime && endTime ? `${startTime}〜${endTime}` : '全時間帯';
        }
        if (createStartInput) createStartInput.value = currentStartTime;
        if (createEndInput) createEndInput.value = currentEndTime;
        if (createTitleInput) createTitleInput.value = '';
        if (createTypeRoutineRadio && createTypeSpotRadio) {
            const isRoutine = suggestedTaskType === 'routine';
            createTypeRoutineRadio.checked = isRoutine;
            createTypeSpotRadio.checked = !isRoutine;
        }

        clearModalError();
        switchTab('existing');
        renderTaskList();
        updateSubmitBtn();

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        currentStaffId = null;
        selectedTaskId = null;
        clearModalError();
    };

    const executeExistingAssign = async (force = false) => {
        if (!selectedTaskId || !currentStaffId || isSubmitting) return;
        isSubmitting = true;
        updateSubmitBtn();
        clearModalError();

        try {
            const res = await fetch(`/timetable-tasks/${selectedTaskId}/assign-bulk`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    staff_ids: [currentStaffId],
                    force: force ? 1 : 0,
                }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                if (res.status === 409 && data.has_conflict) {
                    showModalError(data.message || '同種別のタスクと時間帯が重複しています。', true);
                    return;
                }
                throw new Error(data.message || '配置処理に失敗しました');
            }
            closeModal();
            window.location.reload();
        } catch (err) {
            showModalError(err.message || '配置処理に失敗しました', false);
        } finally {
            isSubmitting = false;
            updateSubmitBtn();
        }
    };

    const executeQuickCreateAndAssign = async (forceAssign = false) => {
        if (!quickCreateForm || !currentStaffId || isSubmitting) return;
        isSubmitting = true;
        clearModalError();

        const formData = new FormData(quickCreateForm);
        const payload = {
            timetable_id: parseInt(timetableId, 10),
            timetable_lane_id: parseInt(formData.get('timetable_lane_id'), 10),
            task_type: formData.get('task_type') || 'spot',
            title: String(formData.get('title') || '').trim(),
            start_time: formData.get('start_time'),
            end_time: formData.get('end_time'),
            required_people_count: parseInt(formData.get('required_people_count') || '1', 10),
            location: formData.get('location') || null,
            status: 'not_started',
            auto_assign_lead: false,
            assign_staff_id: currentStaffId,
            force_assign: forceAssign ? 1 : 0,
        };

        try {
            const res = await fetch('/timetable-tasks', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                if (res.status === 409 && data.has_conflict) {
                    showModalError(data.message || '同種別のタスクと時間帯が重複しています。', true);
                    return;
                }
                throw new Error(data.message || 'タスクの作成・配置に失敗しました');
            }
            closeModal();
            window.location.reload();
        } catch (err) {
            showModalError(err.message || 'タスクの作成・配置に失敗しました', false);
        } finally {
            isSubmitting = false;
        }
    };

    // バークリック詳細・配置解除モーダル
    let activeBarContext = null;

    const closeBarActionModal = () => {
        barActionModal?.classList.add('hidden');
        activeBarContext = null;
    };

    const openBarActionModal = (barEl) => {
        if (!barActionModal) return;
        const rowEl = barEl.closest('[data-gantt-row]');
        let meta = null;
        try {
            meta = JSON.parse(barEl.getAttribute('data-bar-meta') || 'null');
        } catch (e) {
            return;
        }
        if (!meta || !rowEl) return;

        activeBarContext = {
            taskId: meta.task_id,
            title: meta.title,
            staffId: parseInt(rowEl.dataset.staffId || '0', 10),
            staffName: rowEl.dataset.staffName || '',
        };

        const isRoutine = meta.task_type === 'routine';
        const typeBadge = document.getElementById('staffBarTypeBadge');
        if (typeBadge) {
            typeBadge.textContent = `${meta.task_type_icon} ${meta.task_type_short}`;
            typeBadge.className = isRoutine
                ? 'px-1.5 py-0.2 rounded font-bold bg-teal-500/20 text-teal-200 border border-teal-400/40'
                : 'px-1.5 py-0.2 rounded font-bold bg-amber-500/20 text-amber-200 border border-amber-400/40';
        }
        const laneBadge = document.getElementById('staffBarLaneBadge');
        if (laneBadge) laneBadge.textContent = `${meta.lane_icon} ${meta.lane_name}`;

        const timeLabel = document.getElementById('staffBarTimeLabel');
        if (timeLabel) timeLabel.textContent = `⏱️ ${meta.start_time}〜${meta.end_time}`;

        const titleEl = document.getElementById('staff-bar-modal-title');
        if (titleEl) titleEl.textContent = meta.title;

        const staffEl = document.getElementById('staffBarStaffName');
        if (staffEl) staffEl.textContent = activeBarContext.staffName;

        const locEl = document.getElementById('staffBarLocation');
        if (locEl) locEl.textContent = meta.location || '未指定';

        const assigneesEl = document.getElementById('staffBarAssignees');
        if (assigneesEl) {
            if (Array.isArray(meta.assignee_names) && meta.assignee_names.length > 0) {
                assigneesEl.innerHTML = meta.assignee_names.map((name) =>
                    `<span class="px-2 py-0.5 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 font-medium">${escapeHtml(name)}</span>`
                ).join('');
            } else {
                assigneesEl.innerHTML = '<span class="text-slate-400">なし</span>';
            }
        }

        const wsLink = document.getElementById('staffBarWorkspaceLink');
        if (wsLink) {
            wsLink.href = `/timetables/${timetableId}/assignments?task_id=${meta.task_id}`;
        }

        barActionModal.classList.remove('hidden');
    };

    // イベント配線
    taskListContainer?.addEventListener('change', (e) => {
        if (e.target?.classList.contains('timeline-task-radio')) {
            selectedTaskId = parseInt(e.target.value, 10);
            renderTaskList();
            updateSubmitBtn();
        }
    });

    showSatisfiedToggle?.addEventListener('change', () => {
        selectedTaskId = null;
        renderTaskList();
        updateSubmitBtn();
    });

    submitBtn?.addEventListener('click', () => executeExistingAssign(false));
    forceBtn?.addEventListener('click', () => {
        if (activeTab === 'existing') {
            executeExistingAssign(true);
        } else {
            executeQuickCreateAndAssign(true);
        }
    });

    quickCreateForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        executeQuickCreateAndAssign(false);
    });

    document.addEventListener('click', async (e) => {
        const tabBtn = e.target.closest('.timeline-modal-tab');
        if (tabBtn) {
            switchTab(tabBtn.dataset.assignTab || 'existing');
            return;
        }

        if (e.target.closest('[data-action="close-timeline-modal"]') || e.target === modal) {
            closeModal();
            return;
        }

        if (e.target.closest('[data-action="close-staff-bar-modal"]') || e.target === barActionModal) {
            closeBarActionModal();
            return;
        }

        if (e.target.closest('#staffBarUnassignBtn') && activeBarContext) {
            const ctx = activeBarContext;
            const confirmed = await confirmDialog(
                `${ctx.staffName} さんを「${ctx.title}」の配置から解除しますか？`,
                { variant: 'danger', title: '配置解除の確認', confirmText: '解除する' }
            );
            if (!confirmed) return;

            try {
                const res = await fetch(`/timetable-tasks/${ctx.taskId}/staff/${ctx.staffId}`, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || '配置の解除に失敗しました');
                }
                closeBarActionModal();
                window.location.reload();
            } catch (err) {
                showToast(err.message || '配置の解除に失敗しました', 'error');
            }
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (!modal.classList.contains('hidden')) closeModal();
            if (barActionModal && !barActionModal.classList.contains('hidden')) closeBarActionModal();
        }
    });

    return {
        openQuickAssignModal,
        openBarActionModal,
    };
}
