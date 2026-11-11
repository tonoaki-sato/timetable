/**
 * 作業タスク操作モーダル管理スクリプト (CSP完全準拠・Vanilla JS)
 * 詳細・追加・編集モーダルの開閉、データバインディング、同一班内時間帯重複検知
 */

document.addEventListener('DOMContentLoaded', () => {
    initTimetableTaskModals();
});

/**
 * 時刻文字列 (HH:MM) に指定分を加算した時刻文字列を返す
 */
function addMinutesToTime(timeStr, minutesToAdd = 60) {
    if (!timeStr || !/^\d{2}:\d{2}$/.test(timeStr)) {
        return '09:30';
    }
    const [hours, minutes] = timeStr.split(':').map(Number);
    const totalMinutes = Math.min(23 * 60 + 59, Math.max(0, hours * 60 + minutes + minutesToAdd));
    const newH = String(Math.floor(totalMinutes / 60)).padStart(2, '0');
    const newM = String(totalMinutes % 60).padStart(2, '0');
    return `${newH}:${newM}`;
}

function initTimetableTaskModals() {
    // モーダル要素
    const detailModal = document.getElementById('task-detail-modal');
    const createModal = document.getElementById('task-create-modal');
    const commonCreateModal = document.getElementById('task-common-create-modal');
    const editModal = document.getElementById('task-edit-modal');

    // 詳細モーダル内要素
    const detailTitle = document.getElementById('detail-task-title');
    const detailStatusBadge = document.getElementById('detail-task-status-badge');
    const detailCriticalBadge = document.getElementById('detail-task-critical-badge');
    const detailLane = document.getElementById('detail-task-lane');
    const detailTime = document.getElementById('detail-task-time');
    const detailLocation = document.getElementById('detail-task-location');
    const detailPeople = document.getElementById('detail-task-people');
    const detailContent = document.getElementById('detail-task-content');

    // 追加モーダル内要素
    const createForm = document.getElementById('task-create-form');
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

    // 編集モーダル内要素
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

    // 共通タスク一括登録モーダル内要素
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

    let currentEditingTaskId = null;

    /**
     * 一覧上の全タスクデータを取得
     * @returns {Array<Object>}
     */
    function getAllTasks() {
        const rows = document.querySelectorAll('[data-task-row]');
        const tasks = [];
        rows.forEach((row) => {
            const raw = row.getAttribute('data-task');
            if (raw) {
                try {
                    tasks.push(JSON.parse(raw));
                } catch (e) {
                    // ignore JSON parse error
                }
            }
        });
        return tasks;
    }

    /**
     * 同一レーン内の既存タスクと時間帯重複する一覧を取得
     * @returns {Array<Object>}
     */
    function findOverlapsWithLaneTasks(laneId, startTime, endTime, excludeTaskId = null) {
        if (!laneId || !startTime || !endTime || startTime >= endTime) {
            return [];
        }

        const allTasks = getAllTasks();
        const matches = [];
        for (const task of allTasks) {
            if (String(task.lane_id) !== String(laneId)) continue;
            if (excludeTaskId && String(task.id) === String(excludeTaskId)) continue;
            if (task.status === 'cancelled') continue;

            const tStart = task.start_time;
            const tEnd = task.end_time;
            if (!tStart || !tEnd) continue;

            // 重複判定: start < other.end && end > other.start
            if (startTime < tEnd && endTime > tStart) {
                matches.push(task);
            }
        }
        return matches;
    }

    /**
     * 同一レーン内の既存タスクと時間帯重複があるかを判定
     */
    function checkOverlapWithLaneTasks(laneId, startTime, endTime, excludeTaskId = null) {
        const matches = findOverlapsWithLaneTasks(laneId, startTime, endTime, excludeTaskId);
        return matches.length > 0 ? matches[0] : null;
    }

    /**
     * モーダルを閉じる
     */
    function closeAllModals() {
        detailModal?.classList.add('hidden');
        createModal?.classList.add('hidden');
        commonCreateModal?.classList.add('hidden');
        editModal?.classList.add('hidden');
        currentEditingTaskId = null;
    }

    /**
     * 詳細モーダルを開く
     */
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

        // ステータスバッジ
        if (detailStatusBadge) {
            detailStatusBadge.textContent = task.status_label || task.status;
            detailStatusBadge.className = 'px-2 py-0.5 rounded text-xs border font-medium ' + (
                task.status === 'completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                task.status === 'in_progress' ? 'bg-amber-50 text-amber-800 border-amber-300 font-bold' :
                task.status === 'cancelled' ? 'bg-slate-100 text-slate-400 border-slate-200 line-through' :
                'bg-slate-100 text-slate-600 border-slate-200'
            );
        }

        // 最優先バッジ
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

    /**
     * 追加モーダルを開く
     */
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

    /**
     * 追加モーダルのリアルタイム時間重複チェック
     */
    function validateCreateOverlap() {
        if (!createOverlapWarning || !createLaneIdInput || !createStartTimeInput || !createEndTimeInput) {
            return false;
        }

        const laneId = createLaneIdInput.value;
        const start = createStartTimeInput.value;
        const end = createEndTimeInput.value;

        const conflictingTask = checkOverlapWithLaneTasks(laneId, start, end);
        if (conflictingTask) {
            const warningSpan = createOverlapWarning.querySelector('.warning-text');
            if (warningSpan) {
                warningSpan.textContent = `既存タスク「${conflictingTask.title}」（${conflictingTask.start_time}〜${conflictingTask.end_time}）と時間帯が重複しています。`;
            }
            createOverlapWarning.classList.remove('hidden');
            createStartTimeInput.classList.add('border-rose-400', 'bg-rose-50/20');
            createEndTimeInput.classList.add('border-rose-400', 'bg-rose-50/20');
            return true;
        } else {
            createOverlapWarning.classList.add('hidden');
            createStartTimeInput.classList.remove('border-rose-400', 'bg-rose-50/20');
            createEndTimeInput.classList.remove('border-rose-400', 'bg-rose-50/20');
            return false;
        }
    }

    /**
     * 編集モーダルを開く
     */
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
        if (editLaneIdInput) editLaneIdInput.value = task.lane_id;
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

    /**
     * 編集モーダルのリアルタイム時間重複チェック
     */
    function validateEditOverlap() {
        if (!editOverlapWarning || !editLaneIdInput || !editStartTimeInput || !editEndTimeInput) {
            return false;
        }

        const laneId = editLaneIdInput.value;
        const start = editStartTimeInput.value;
        const end = editEndTimeInput.value;

        const conflictingTask = checkOverlapWithLaneTasks(laneId, start, end, currentEditingTaskId);
        if (conflictingTask) {
            const warningSpan = editOverlapWarning.querySelector('.warning-text');
            if (warningSpan) {
                warningSpan.textContent = `同一班のタスク「${conflictingTask.title}」（${conflictingTask.start_time}〜${conflictingTask.end_time}）と時間帯が重複しています。`;
            }
            editOverlapWarning.classList.remove('hidden');
            editStartTimeInput.classList.add('border-rose-400', 'bg-rose-50/20');
            editEndTimeInput.classList.add('border-rose-400', 'bg-rose-50/20');
            return true;
        } else {
            editOverlapWarning.classList.add('hidden');
            editStartTimeInput.classList.remove('border-rose-400', 'bg-rose-50/20');
            editEndTimeInput.classList.remove('border-rose-400', 'bg-rose-50/20');
            return false;
        }
    }

    /**
     * 共通タスクモーダル内の担当班チェックボックス一覧を取得
     * @returns {Array<HTMLInputElement>}
     */
    function getCommonLaneCheckboxes() {
        if (!commonCreateModal) {
            return [];
        }
        return Array.from(commonCreateModal.querySelectorAll('.common-lane-checkbox'));
    }

    /**
     * 共通タスクモーダルの選択中班数表示を更新
     */
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

    /**
     * 共通タスクモーダルの対象全班に対するリアルタイム重複判定
     * @returns {{ hasOverlap: boolean, hasNoLanes: boolean }}
     */
    function validateCommonCreateOverlap() {
        const checkboxes = getCommonLaneCheckboxes();
        const checkedCheckboxes = checkboxes.filter(cb => cb.checked);
        const hasNoLanes = checkedCheckboxes.length === 0;

        const start = commonStartTimeInput?.value || '';
        const end = commonEndTimeInput?.value || '';
        const status = commonStatusSelect?.value || 'not_started';

        const conflicts = [];
        if (status !== 'cancelled' && start && end && start < end) {
            for (const cb of checkedCheckboxes) {
                const laneName = cb.dataset.laneName || '担当班';
                const matches = findOverlapsWithLaneTasks(cb.value, start, end);
                for (const task of matches) {
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
        commonStartTimeInput?.classList.toggle('border-rose-400', hasOverlap);
        commonStartTimeInput?.classList.toggle('bg-rose-50/20', hasOverlap);
        commonEndTimeInput?.classList.toggle('border-rose-400', hasOverlap);
        commonEndTimeInput?.classList.toggle('bg-rose-50/20', hasOverlap);

        if (commonSubmitBtn) {
            commonSubmitBtn.disabled = hasOverlap || hasNoLanes;
        }

        return { hasOverlap, hasNoLanes };
    }

    /**
     * 共通タスク一括登録モーダルを開く
     */
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

    // イベント委譲（クリック）
    document.addEventListener('click', (e) => {
        // モーダルを閉じる
        if (e.target.closest('[data-action="close-modal"]')) {
            e.preventDefault();
            closeAllModals();
            return;
        }

        // 共通タスク一括登録モーダルを開く
        if (e.target.closest('[data-action="open-common-task-create"]')) {
            e.preventDefault();
            openCommonCreateModal();
            return;
        }

        // 共通タスクモーダル: 全班選択 / 全解除
        if (e.target.closest('[data-action="common-select-all-lanes"]')) {
            e.preventDefault();
            getCommonLaneCheckboxes().forEach((cb) => {
                cb.checked = true;
            });
            updateCommonSelectedCount();
            validateCommonCreateOverlap();
            return;
        }

        if (e.target.closest('[data-action="common-clear-all-lanes"]')) {
            e.preventDefault();
            getCommonLaneCheckboxes().forEach((cb) => {
                cb.checked = false;
            });
            updateCommonSelectedCount();
            validateCommonCreateOverlap();
            return;
        }

        // 詳細モーダルを開く
        const detailBtn = e.target.closest('[data-action="open-task-detail"]');
        if (detailBtn) {
            e.preventDefault();
            const taskId = detailBtn.dataset.taskId;
            if (taskId) openDetailModal(taskId);
            return;
        }

        // 追加モーダルを開く
        const createBtn = e.target.closest('[data-action="open-task-create"]');
        if (createBtn) {
            e.preventDefault();
            const laneId = createBtn.dataset.laneId;
            const laneName = createBtn.dataset.laneName;
            const laneIcon = createBtn.dataset.laneIcon;
            const suggestedStart = createBtn.dataset.suggestedStart;
            openCreateModal(laneId, laneName, laneIcon, suggestedStart);
            return;
        }

        // 編集モーダルを開く
        const editBtn = e.target.closest('[data-action="open-task-edit"]');
        if (editBtn) {
            e.preventDefault();
            const taskId = editBtn.dataset.taskId;
            if (taskId) openEditModal(taskId);
            return;
        }
    });

    // キーボード ESC キーでモーダルを閉じる
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });

    // 追加モーダルの入力監視
    createModal?.addEventListener('input', (e) => {
        if (e.target.matches('#create-modal-start-time, #create-modal-end-time')) {
            validateCreateOverlap();
        }
    });

    // 編集モーダルの入力監視
    editModal?.addEventListener('input', (e) => {
        if (e.target.matches('#edit-modal-start-time, #edit-modal-end-time')) {
            validateEditOverlap();
        }
    });

    // 共通タスクモーダルの入力・選択監視
    commonCreateModal?.addEventListener('input', (e) => {
        if (e.target.matches('#common-modal-start-time, #common-modal-end-time')) {
            validateCommonCreateOverlap();
        }
    });
    commonCreateModal?.addEventListener('change', (e) => {
        if (e.target.matches('.common-lane-checkbox, #common-modal-status')) {
            updateCommonSelectedCount();
            validateCommonCreateOverlap();
        }
    });

    // 追加モーダル送信時チェック
    createForm?.addEventListener('submit', (e) => {
        if (validateCreateOverlap()) {
            e.preventDefault();
            alert('既存タスクと時間帯が重複しています。開始時刻・終了時刻を確認してください。');
        }
    });

    // 編集モーダル送信時チェック
    editForm?.addEventListener('submit', (e) => {
        if (validateEditOverlap()) {
            e.preventDefault();
            alert('同一班の他タスクと時間帯が重複しています。開始時刻・終了時刻を確認してください。');
        }
    });

    // 共通タスクモーダル送信時チェック
    commonCreateForm?.addEventListener('submit', (e) => {
        const { hasOverlap, hasNoLanes } = validateCommonCreateOverlap();
        if (hasNoLanes) {
            e.preventDefault();
            alert('登録対象の担当班を1つ以上選択してください。');
            return;
        }
        if (hasOverlap) {
            e.preventDefault();
            alert('選択した担当班の中に既存タスクと時間帯が重複している班があります。対象班または時間帯を確認してください。');
        }
    });

    // サーバー側バリデーションエラー時のモーダル自動再表示
    if (commonCreateModal?.dataset.reopenOnError === '1') {
        updateCommonSelectedCount();
        validateCommonCreateOverlap();
        commonCreateModal.classList.remove('hidden');
    }
}

