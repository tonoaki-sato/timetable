/**
 * スタッフ稼働タイムライン スクリプト (CSP完全準拠・バニラJS)
 */
document.addEventListener('DOMContentLoaded', () => {
    initStaffTimelineFilter();
    initStaffTimelineQuickAssign();
});

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * スタッフ行の絞り込みフィルター（名前・所属・ステータス）
 */
function initStaffTimelineFilter() {
    const searchInput = document.getElementById('timeline-search-input');
    const affiliationSelect = document.getElementById('timeline-affiliation-select');
    const filterButtons = document.querySelectorAll('[data-filter-status]');
    const rows = document.querySelectorAll('tr.staff-row');

    if (rows.length === 0) return;

    let currentSearch = '';
    let currentAffiliation = '';
    let currentStatus = 'all';

    function applyFilter() {
        const q = currentSearch.toLowerCase();

        rows.forEach(row => {
            const name = (row.dataset.staffName || '').toLowerCase();
            const kana = (row.dataset.staffKana || '').toLowerCase();
            const affiliation = row.dataset.staffAffiliation || '';
            const assignedCount = parseInt(row.dataset.assignedCount, 10) || 0;
            const isCheckedIn = row.dataset.checkedIn === '1';

            // 1. 検索マッチ
            const matchSearch = !q || name.includes(q) || kana.includes(q) || affiliation.toLowerCase().includes(q);

            // 2. 所属マッチ
            const matchAff = !currentAffiliation || affiliation === currentAffiliation;

            // 3. ステータスマッチ
            let matchStatus = true;
            if (currentStatus === 'assigned') {
                matchStatus = assignedCount > 0;
            } else if (currentStatus === 'unassigned') {
                matchStatus = assignedCount === 0;
            } else if (currentStatus === 'checked_in') {
                matchStatus = isCheckedIn;
            }

            if (matchSearch && matchAff && matchStatus) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            currentSearch = e.target.value.trim();
            applyFilter();
        });
    }

    if (affiliationSelect) {
        affiliationSelect.addEventListener('change', (e) => {
            currentAffiliation = e.target.value;
            applyFilter();
        });
    }

    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const status = btn.getAttribute('data-filter-status');
            currentStatus = status || 'all';

            // ボタンスタイル更新
            filterButtons.forEach(b => {
                b.classList.remove('bg-indigo-600', 'bg-emerald-600', 'bg-amber-600', 'bg-sky-600', 'text-white', 'border-indigo-600', 'border-emerald-600', 'border-amber-600', 'border-sky-600');
                b.classList.add('bg-white', 'text-slate-700', 'border-slate-200');
            });

            if (currentStatus === 'all') {
                btn.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600');
            } else if (currentStatus === 'assigned') {
                btn.classList.add('bg-emerald-600', 'text-white', 'border-emerald-600');
            } else if (currentStatus === 'unassigned') {
                btn.classList.add('bg-amber-600', 'text-white', 'border-amber-600');
            } else if (currentStatus === 'checked_in') {
                btn.classList.add('bg-sky-600', 'text-white', 'border-sky-600');
            }
            btn.classList.remove('bg-white', 'text-slate-700', 'border-slate-200');

            applyFilter();
        });
    });
}

/**
 * 空き時間枠からのタスク配置モーダル
 */
function initStaffTimelineQuickAssign() {
    const modal = document.getElementById('timelineQuickAssignModal');
    if (!modal) return;

    const dataContainer = document.getElementById('timeline-tasks-data');
    let tasksWithShortage = [];
    if (dataContainer && dataContainer.textContent) {
        try {
            tasksWithShortage = JSON.parse(dataContainer.textContent);
        } catch (e) {
            console.error('Failed to parse tasksWithShortage:', e);
        }
    }

    const staffNameEl = document.getElementById('timelineStaffName');
    const slotTimeEl = document.getElementById('timelineSlotTime');
    const taskListContainer = document.getElementById('timelineTaskList');
    const roleInput = document.getElementById('timelineRoleInput');
    const submitBtn = document.getElementById('timelineSubmitBtn');
    const errorEl = document.getElementById('timelineErrorMessage');

    let currentStaffId = null;
    let currentSlotTime = '';
    let selectedTaskId = null;
    let isSubmitting = false;

    function openModal(staffId, staffName, slotTime) {
        currentStaffId = staffId;
        currentSlotTime = slotTime;
        selectedTaskId = null;
        isSubmitting = false;

        if (staffNameEl) staffNameEl.textContent = staffName;
        if (slotTimeEl) slotTimeEl.textContent = slotTime;
        if (roleInput) roleInput.value = '担当';
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.add('hidden');
        }

        renderTaskList();
        updateSubmitBtn();

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        currentStaffId = null;
        currentSlotTime = '';
        selectedTaskId = null;
    }

    function renderTaskList() {
        if (!taskListContainer) return;

        const filtered = tasksWithShortage.filter(t => {
            const inTime = t.start_time <= currentSlotTime && currentSlotTime < t.end_time;
            const notAssigned = !t.assigned_staff_ids.includes(currentStaffId);
            return inTime && notAssigned;
        });

        if (filtered.length === 0) {
            taskListContainer.innerHTML = `
                <div class="p-8 text-center text-xs text-slate-400">
                    この時間帯（${escapeHtml(currentSlotTime)}）に不足しているタスクはありません。
                </div>
            `;
            return;
        }

        taskListContainer.innerHTML = filtered.map(t => `
            <label class="flex items-center justify-between p-3 hover:bg-white cursor-pointer transition text-xs ${selectedTaskId === t.id ? 'bg-indigo-50/80 font-bold border-l-4 border-l-indigo-600' : ''}">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <input type="radio" name="timeline_task_id" value="${t.id}" ${selectedTaskId === t.id ? 'checked' : ''}
                        class="timeline-task-radio text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                    <div class="truncate">
                        <div class="text-slate-900 font-bold">${escapeHtml(t.title)}</div>
                        <div class="text-[10px] text-slate-500">
                            <span>${escapeHtml(t.lane)}</span> ｜ <span class="font-mono">${escapeHtml(t.start_time)} 〜 ${escapeHtml(t.end_time)}</span>
                        </div>
                    </div>
                </div>
                <div class="shrink-0">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                        あと ${t.shortage_count} 名不足
                    </span>
                </div>
            </label>
        `).join('');
    }

    function updateSubmitBtn() {
        if (!submitBtn) return;
        if (selectedTaskId && !isSubmitting) {
            submitBtn.disabled = false;
            submitBtn.className = 'px-4 py-2 rounded-xl text-xs font-bold shadow transition flex items-center space-x-1.5 bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer';
        } else {
            submitBtn.disabled = true;
            submitBtn.className = 'px-4 py-2 rounded-xl text-xs font-bold shadow transition flex items-center space-x-1.5 bg-slate-200 text-slate-400 cursor-not-allowed';
        }
    }

    // イベント委譲
    document.addEventListener('click', (e) => {
        // 空き枠セルクリックでモーダルを開く
        const slotCell = e.target.closest('[data-action="open-timeline-quick-assign"]');
        if (slotCell) {
            const staffId = parseInt(slotCell.dataset.staffId, 10);
            const staffName = slotCell.dataset.staffName || '';
            const slotTime = slotCell.dataset.slotTime || '';
            if (staffId && slotTime) {
                openModal(staffId, staffName, slotTime);
            }
            return;
        }

        // モーダルを閉じる
        if (e.target.closest('[data-action="close-timeline-modal"]')) {
            closeModal();
            return;
        }

        if (e.target === modal) {
            closeModal();
            return;
        }
    });

    // タスクラジオ選択の変更
    if (taskListContainer) {
        taskListContainer.addEventListener('change', (e) => {
            if (e.target && e.target.classList.contains('timeline-task-radio')) {
                selectedTaskId = parseInt(e.target.value, 10);
                renderTaskList();
                updateSubmitBtn();
            }
        });
    }

    // 配置実行
    if (submitBtn) {
        submitBtn.addEventListener('click', async () => {
            if (!selectedTaskId || !currentStaffId || isSubmitting) return;

            isSubmitting = true;
            updateSubmitBtn();
            if (errorEl) {
                errorEl.textContent = '';
                errorEl.classList.add('hidden');
            }

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const role = roleInput ? roleInput.value : '担当';

                const res = await fetch(`/timetable-tasks/${selectedTaskId}/assign-bulk`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        staff_ids: [currentStaffId],
                        role_description: role,
                        force: 0
                    })
                });

                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || '配置処理に失敗しました');
                }

                closeModal();
                window.location.reload();
            } catch (err) {
                if (errorEl) {
                    errorEl.textContent = err.message || 'エラーが発生しました';
                    errorEl.classList.remove('hidden');
                }
            } finally {
                isSubmitting = false;
                updateSubmitBtn();
            }
        });
    }

    // Escキーで閉じる
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
}
