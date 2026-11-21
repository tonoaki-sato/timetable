/**
 * 運行日別担当班編成画面スクリプト (CSP完全準拠・バニラJS)
 */
document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('timetable-lanes-app');
    if (!app) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const reorderUrl = app.dataset.reorderUrl || '';

    initModalCloseHandlers();
    initAddModal();
    initDetailModal();
    initEditModal();
    initDeleteModal();
    initSortableRows(reorderUrl, csrfToken);
});

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function openModal(modal) {
    if (!modal) return;
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeModal(modal) {
    if (!modal) return;
    modal.classList.add('hidden');
    const visibleModals = document.querySelectorAll('[role="dialog"]:not(.hidden)');
    if (visibleModals.length === 0) {
        document.body.classList.remove('overflow-hidden');
    }
}

function parsePayload(raw) {
    if (!raw) return null;
    try {
        return JSON.parse(raw);
    } catch (e) {
        console.error('Failed to parse timetable lane payload:', e);
        return null;
    }
}

function initModalCloseHandlers() {
    document.addEventListener('click', (e) => {
        const closeTrigger = e.target.closest('[data-close-target]');
        if (closeTrigger) {
            const targetSelector = closeTrigger.getAttribute('data-close-target');
            const targetModal = targetSelector ? document.querySelector(targetSelector) : null;
            closeModal(targetModal);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('[role="dialog"]:not(.hidden)').forEach((modal) => {
            closeModal(modal);
        });
    });
}

function initAddModal() {
    const modal = document.getElementById('modal-tlane-add');
    const openBtn = document.getElementById('btn-open-tlane-add');
    if (!modal || !openBtn) return;

    const tabButtons = modal.querySelectorAll('.tlane-add-tab-btn');
    const singlePanel = document.getElementById('tlane-add-single-panel');
    const bulkPanel = document.getElementById('tlane-add-bulk-panel');

    const activateTab = (tabName) => {
        const isBulk = tabName === 'bulk';
        singlePanel?.classList.toggle('hidden', isBulk);
        bulkPanel?.classList.toggle('hidden', !isBulk);

        tabButtons.forEach((btn) => {
            const active = btn.dataset.addTab === tabName;
            btn.classList.toggle('border-indigo-600', active);
            btn.classList.toggle('text-indigo-700', active);
            btn.classList.toggle('border-transparent', !active);
            btn.classList.toggle('text-slate-500', !active);
        });
    };

    openBtn.addEventListener('click', () => {
        activateTab('single');
        openModal(modal);
    });

    tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            activateTab(btn.dataset.addTab || 'single');
        });
    });

    document.getElementById('btn-bulk-select-all')?.addEventListener('click', () => {
        modal.querySelectorAll('.tlane-bulk-checkbox').forEach((cb) => {
            cb.checked = true;
        });
    });

    document.getElementById('btn-bulk-clear-all')?.addEventListener('click', () => {
        modal.querySelectorAll('.tlane-bulk-checkbox').forEach((cb) => {
            cb.checked = false;
        });
    });
}

function initDetailModal() {
    const modal = document.getElementById('modal-tlane-detail');
    if (!modal) return;

    const badgeEl = document.getElementById('detail-tlane-badge');
    const badgeIconEl = document.getElementById('detail-tlane-badge-icon');
    const badgeNameEl = document.getElementById('detail-tlane-badge-name');
    const tasksLinkEl = document.getElementById('detail-tlane-tasks-link');
    const descEl = document.getElementById('detail-tlane-description');
    const leadEl = document.getElementById('detail-tlane-lead');
    const fixedStaffEl = document.getElementById('detail-tlane-fixed-staff');
    const notesEl = document.getElementById('detail-tlane-notes');
    const tasksCountEl = document.getElementById('detail-tlane-tasks-count');
    const tasksListEl = document.getElementById('detail-tlane-tasks-list');
    const requestsListEl = document.getElementById('detail-tlane-requests-list');

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-view-tlane');
        if (!btn) return;

        const tl = parsePayload(btn.dataset.tlane);
        if (!tl) return;

        if (badgeEl && tl.color_classes?.badge) {
            badgeEl.className = `inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border ${tl.color_classes.badge}`;
        }
        if (badgeIconEl) badgeIconEl.textContent = tl.icon_label || '📋';
        if (badgeNameEl) badgeNameEl.textContent = tl.name || '';
        if (tasksLinkEl && tl.tasks_url) tasksLinkEl.href = tl.tasks_url;
        if (descEl) descEl.textContent = tl.description || '登録されていません。';
        if (leadEl) leadEl.textContent = tl.lead_staff_name ? `👑 ${tl.lead_staff_name}` : '未設定';
        if (notesEl) notesEl.textContent = tl.notes || '特記メモはありません。';

        if (fixedStaffEl) {
            const staffList = Array.isArray(tl.fixed_staff) ? tl.fixed_staff : [];
            if (staffList.length === 0) {
                fixedStaffEl.innerHTML = '<span class="text-slate-400">未設定</span>';
            } else {
                fixedStaffEl.innerHTML = staffList.map((s) => `
                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 text-[11px] font-bold">
                        ${escapeHtml(s.name)}
                    </span>
                `).join('');
            }
        }

        if (tasksCountEl) tasksCountEl.textContent = String(tl.tasks_count ?? 0);
        if (tasksListEl) {
            const tasks = Array.isArray(tl.tasks) ? tl.tasks : [];
            if (tasks.length === 0) {
                tasksListEl.innerHTML = '<div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 text-slate-400 text-center">作業タスクはまだ登録されていません。</div>';
            } else {
                tasksListEl.innerHTML = tasks.map((t) => `
                    <div class="p-2 rounded-xl border border-slate-200 bg-white flex items-center justify-between gap-2">
                        <div class="min-w-0 truncate">
                            <span class="font-mono text-indigo-700 font-bold mr-1.5">${escapeHtml(t.start_time)}〜${escapeHtml(t.end_time)}</span>
                            <span class="font-bold text-slate-800">${escapeHtml(t.title)}</span>
                            ${t.location ? `<span class="text-slate-400 ml-1">(${escapeHtml(t.location)})</span>` : ''}
                        </div>
                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-bold shrink-0">${escapeHtml(t.status_label)}</span>
                    </div>
                `).join('');
            }
        }

        if (requestsListEl) {
            const requests = Array.isArray(tl.lane_requests) ? tl.lane_requests : [];
            if (requests.length === 0) {
                requestsListEl.innerHTML = '<div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 text-slate-400 text-center">班長からの要請履歴はありません。</div>';
            } else {
                requestsListEl.innerHTML = requests.map((req) => `
                    <div class="p-2 rounded-xl border border-slate-200 bg-white space-y-0.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-slate-800">${escapeHtml(req.type_label)}${req.task_title ? ` — ${escapeHtml(req.task_title)}` : ''}</span>
                            <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-bold shrink-0">${escapeHtml(req.status_label)}</span>
                        </div>
                        ${req.message ? `<div class="text-[11px] text-slate-600">${escapeHtml(req.message)}</div>` : ''}
                        <div class="text-[10px] text-slate-400">${escapeHtml(req.requested_by_name || '')} ${escapeHtml(req.created_at_label || '')}</div>
                    </div>
                `).join('');
            }
        }

        openModal(modal);
    });
}

function initEditModal() {
    const modal = document.getElementById('modal-tlane-edit');
    const form = document.getElementById('tlane-edit-form');
    if (!modal || !form) return;

    const badgeEl = document.getElementById('edit-tlane-badge');
    const badgeIconEl = document.getElementById('edit-tlane-badge-icon');
    const badgeNameEl = document.getElementById('edit-tlane-badge-name');
    const descEl = document.getElementById('edit-tlane-description');
    const leadSelect = document.getElementById('edit-lead-staff-id');
    const notesInput = document.getElementById('edit-notes');
    const fixedCheckboxes = Array.from(form.querySelectorAll('.edit-fixed-staff-checkbox'));

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-edit-tlane');
        if (!btn) return;

        const tl = parsePayload(btn.dataset.tlane);
        if (!tl) return;

        form.action = tl.update_url || '';
        if (badgeEl && tl.color_classes?.badge) {
            badgeEl.className = `inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border ${tl.color_classes.badge}`;
        }
        if (badgeIconEl) badgeIconEl.textContent = tl.icon_label || '📋';
        if (badgeNameEl) badgeNameEl.textContent = tl.name || '';
        if (descEl) descEl.textContent = tl.description || '';
        if (leadSelect) leadSelect.value = tl.lead_staff_id ? String(tl.lead_staff_id) : '';
        if (notesInput) notesInput.value = tl.notes || '';

        const fixedSet = new Set((tl.fixed_staff_ids || []).map((id) => String(id)));
        fixedCheckboxes.forEach((cb) => {
            cb.checked = fixedSet.has(String(cb.value));
        });

        openModal(modal);
    });
}

function initDeleteModal() {
    const modal = document.getElementById('delete-migration-modal');
    const form = document.getElementById('tlane-delete-form');
    if (!modal || !form) return;

    const nameEl = document.getElementById('delete-tlane-name');
    const noTasksMsg = document.getElementById('delete-no-tasks-msg');
    const withTasksOptions = document.getElementById('delete-with-tasks-options');
    const tasksCountEl = document.getElementById('delete-tlane-tasks-count');
    const migrateSelect = document.getElementById('migrate-to-timetable-lane-id');
    const cascadeCheckbox = document.getElementById('cascade-delete-tasks');
    const errorBox = document.getElementById('delete-modal-error');

    let currentTasksCount = 0;

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-delete-tlane');
        if (!btn) return;

        const tl = parsePayload(btn.dataset.tlane);
        if (!tl) return;

        currentTasksCount = Number(tl.tasks_count ?? 0);
        form.action = tl.destroy_url || '';
        if (nameEl) nameEl.textContent = tl.name || '';
        if (tasksCountEl) tasksCountEl.textContent = String(currentTasksCount);
        if (errorBox) errorBox.classList.add('hidden');

        if (migrateSelect) {
            migrateSelect.value = '';
            Array.from(migrateSelect.options).forEach((opt) => {
                opt.disabled = opt.value !== '' && String(opt.value) === String(tl.id);
            });
        }
        if (cascadeCheckbox) cascadeCheckbox.checked = false;

        noTasksMsg?.classList.toggle('hidden', currentTasksCount > 0);
        withTasksOptions?.classList.toggle('hidden', currentTasksCount <= 0);

        openModal(modal);
    });

    migrateSelect?.addEventListener('change', () => {
        if (migrateSelect.value && cascadeCheckbox) {
            cascadeCheckbox.checked = false;
        }
        errorBox?.classList.add('hidden');
    });

    cascadeCheckbox?.addEventListener('change', () => {
        if (cascadeCheckbox.checked && migrateSelect) {
            migrateSelect.value = '';
        }
        errorBox?.classList.add('hidden');
    });

    form.addEventListener('submit', (e) => {
        if (currentTasksCount > 0 && !migrateSelect?.value && !cascadeCheckbox?.checked) {
            e.preventDefault();
            if (errorBox) {
                errorBox.textContent = '移行先の担当班を選択するか、「タスクもまとめて完全に削除する」にチェックを入れてください。';
                errorBox.classList.remove('hidden');
            }
        }
    });
}

function initSortableRows(reorderUrl, csrfToken) {
    const tbody = document.getElementById('timetable-lanes-sortable-body');
    const feedback = document.getElementById('tlane-reorder-feedback');
    if (!tbody || !reorderUrl) return;

    let draggedRow = null;

    const showFeedback = (message, isError = false) => {
        if (!feedback) return;
        feedback.textContent = message;
        feedback.className = isError
            ? 'px-3 py-2 rounded-xl text-xs font-bold border bg-red-50 border-red-200 text-red-700'
            : 'px-3 py-2 rounded-xl text-xs font-bold border bg-emerald-50 border-emerald-200 text-emerald-700';
        feedback.classList.remove('hidden');
        setTimeout(() => {
            feedback.classList.add('hidden');
        }, 3000);
    };

    const renumberRows = () => {
        tbody.querySelectorAll('tr.tlane-row').forEach((row, idx) => {
            const numEl = row.querySelector('.order-number');
            if (numEl) numEl.textContent = String(idx + 1);
        });
    };

    tbody.addEventListener('dragstart', (e) => {
        const row = e.target.closest('tr.tlane-row');
        if (!row) return;
        draggedRow = row;
        row.classList.add('opacity-50');
        if (e.dataTransfer) {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', row.dataset.timetableLaneId || '');
        }
    });

    tbody.addEventListener('dragover', (e) => {
        e.preventDefault();
        const targetRow = e.target.closest('tr.tlane-row');
        if (!targetRow || !draggedRow || targetRow === draggedRow) return;

        const rect = targetRow.getBoundingClientRect();
        const after = e.clientY > rect.top + rect.height / 2;
        if (after) {
            targetRow.after(draggedRow);
        } else {
            targetRow.before(draggedRow);
        }
    });

    tbody.addEventListener('dragend', async () => {
        if (!draggedRow) return;
        draggedRow.classList.remove('opacity-50');
        draggedRow = null;

        renumberRows();

        const timetableLaneIds = Array.from(tbody.querySelectorAll('tr.tlane-row'))
            .map((row) => parseInt(row.dataset.timetableLaneId || '0', 10))
            .filter((id) => id > 0);

        if (timetableLaneIds.length === 0) return;

        try {
            const response = await fetch(reorderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ timetable_lane_ids: timetableLaneIds }),
            });

            if (response.ok) {
                showFeedback('担当班の表示順を更新しました。');
            } else {
                showFeedback('表示順の保存に失敗しました。', true);
            }
        } catch (err) {
            console.error('Timetable lane reorder error:', err);
            showFeedback('通信エラーにより表示順を保存できませんでした。', true);
        }
    });
}
