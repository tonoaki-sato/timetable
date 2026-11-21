/**
 * 担当班マスター管理画面スクリプト (CSP完全準拠・バニラJS)
 */
document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('lanes-master-app');
    if (!app) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const storeUrl = app.dataset.storeUrl || '';
    const reorderUrl = app.dataset.reorderUrl || '';
    const timetableLanesUrlTemplate = app.dataset.timetableLanesUrlTemplate || '/timetables/__TIMETABLE_ID__/lanes';

    initModalCloseHandlers();
    initDetailModal(timetableLanesUrlTemplate);
    initFormModal(storeUrl, csrfToken);
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

function parseJsonDataset(value) {
    if (!value) return null;
    try {
        return JSON.parse(value);
    } catch (e) {
        console.error('Failed to parse lane payload:', e);
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

function initDetailModal(timetableLanesUrlTemplate) {
    const modal = document.getElementById('modal-lane-detail');
    if (!modal) return;

    const badgeEl = document.getElementById('detail-lane-badge');
    const badgeIconEl = document.getElementById('detail-lane-badge-icon');
    const badgeNameEl = document.getElementById('detail-lane-badge-name');
    const statusEl = document.getElementById('detail-lane-status');
    const orderEl = document.getElementById('detail-lane-order');
    const colorEl = document.getElementById('detail-lane-color');
    const timetablesCountEl = document.getElementById('detail-lane-timetables-count');
    const tasksCountEl = document.getElementById('detail-lane-tasks-count');
    const descriptionEl = document.getElementById('detail-lane-description');
    const timetablesListEl = document.getElementById('detail-lane-timetables-list');

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-view-lane');
        if (!btn) return;

        const lane = parseJsonDataset(btn.dataset.lane);
        if (!lane) return;

        if (badgeEl && lane.color_classes?.badge) {
            badgeEl.className = `inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border ${lane.color_classes.badge}`;
        }
        if (badgeIconEl) badgeIconEl.textContent = lane.icon_label || '📋';
        if (badgeNameEl) badgeNameEl.textContent = lane.name || '';

        if (statusEl) {
            if (lane.is_active) {
                statusEl.className = 'px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200';
                statusEl.textContent = '有効';
            } else {
                statusEl.className = 'px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200';
                statusEl.textContent = '無効';
            }
        }

        if (orderEl) orderEl.textContent = String(lane.display_order ?? '-');
        if (colorEl) colorEl.textContent = lane.color_label || lane.color || '-';
        if (timetablesCountEl) timetablesCountEl.textContent = String(lane.timetable_lanes_count ?? 0);
        if (tasksCountEl) tasksCountEl.textContent = String(lane.tasks_count ?? 0);
        if (descriptionEl) descriptionEl.textContent = lane.description || '登録されていません。';

        if (timetablesListEl) {
            const timetables = Array.isArray(lane.timetables) ? lane.timetables : [];
            if (timetables.length === 0) {
                timetablesListEl.innerHTML = '<div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-slate-400 text-center">どの運行計画にもまだ編成されていません。</div>';
            } else {
                timetablesListEl.innerHTML = timetables.map((tt) => {
                    const href = timetableLanesUrlTemplate.replace('__TIMETABLE_ID__', String(tt.id));
                    const datePart = tt.target_date_label
                        ? `<span class="text-[11px] text-slate-500 font-mono">${escapeHtml(tt.target_date_label)}</span>`
                        : '';
                    return `
                        <a href="${escapeHtml(href)}"
                            class="flex items-center justify-between p-2.5 rounded-xl border border-slate-200 bg-white hover:bg-indigo-50/50 hover:border-indigo-300 transition">
                            <span class="font-bold text-slate-800">${escapeHtml(tt.name)}</span>
                            <span class="flex items-center space-x-2">
                                ${datePart}
                                <span class="text-indigo-600 font-bold text-[11px]">編成画面 →</span>
                            </span>
                        </a>
                    `;
                }).join('');
            }
        }

        openModal(modal);
    });
}

function initFormModal(storeUrl, csrfToken) {
    const modal = document.getElementById('modal-lane-form');
    const form = document.getElementById('lane-master-form');
    if (!modal || !form) return;

    const modalTitleEl = document.getElementById('modal-lane-form-title');
    const methodInput = document.getElementById('lane-form-method');
    const nameInput = document.getElementById('lane-form-name');
    const iconInput = document.getElementById('lane-form-icon');
    const descInput = document.getElementById('lane-form-description');
    const isActiveInput = document.getElementById('lane-form-is-active');
    const usageWarning = document.getElementById('lane-form-usage-warning');
    const usageCountEl = document.getElementById('lane-form-usage-count');
    const errorBox = document.getElementById('lane-form-errors');
    const submitBtn = document.getElementById('lane-form-submit-btn');

    const previewBadge = document.getElementById('lane-preview-badge');
    const previewIcon = document.getElementById('lane-preview-icon');
    const previewName = document.getElementById('lane-preview-name');
    const colorCards = Array.from(form.querySelectorAll('.lane-color-card'));

    const updatePreview = () => {
        if (previewName && nameInput) {
            previewName.textContent = nameInput.value.trim() || '新しい担当班';
        }
        if (previewIcon && iconInput) {
            previewIcon.textContent = iconInput.value.trim() || '📋';
        }

        let selectedBadgeClass = 'bg-slate-100 text-slate-800 border-slate-300';
        colorCards.forEach((card) => {
            const radio = card.querySelector('.lane-color-radio');
            const isChecked = radio ? radio.checked : false;
            card.classList.toggle('border-indigo-600', isChecked);
            card.classList.toggle('bg-indigo-50/50', isChecked);
            card.classList.toggle('ring-2', isChecked);
            card.classList.toggle('ring-indigo-500/20', isChecked);
            card.classList.toggle('border-slate-200', !isChecked);
            card.classList.toggle('bg-white', !isChecked);
            if (isChecked && card.dataset.badgeClass) {
                selectedBadgeClass = card.dataset.badgeClass;
            }
        });

        if (previewBadge) {
            previewBadge.className = `inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border transition-all duration-150 ${selectedBadgeClass}`;
        }
    };

    const selectColor = (colorKey) => {
        const target = form.querySelector(`.lane-color-radio[value="${colorKey}"]`) || form.querySelector('.lane-color-radio[value="slate"]');
        if (target) {
            target.checked = true;
        }
        updatePreview();
    };

    const clearErrors = () => {
        if (!errorBox) return;
        errorBox.innerHTML = '';
        errorBox.classList.add('hidden');
    };

    const openCreateModal = () => {
        clearErrors();
        form.action = storeUrl;
        if (methodInput) methodInput.value = 'POST';
        if (modalTitleEl) {
            modalTitleEl.innerHTML = '<span>🏷️</span><span>新規担当班マスター登録</span>';
        }
        if (nameInput) nameInput.value = '';
        if (iconInput) iconInput.value = '📋';
        if (descInput) descInput.value = '';
        if (isActiveInput) isActiveInput.checked = true;
        if (usageWarning) usageWarning.classList.add('hidden');
        selectColor('slate');
        openModal(modal);
        nameInput?.focus();
    };

    const openEditModal = (lane) => {
        clearErrors();
        form.action = lane.update_url || '';
        if (methodInput) methodInput.value = 'PUT';
        if (modalTitleEl) {
            modalTitleEl.innerHTML = `<span>✏️</span><span>担当班マスター編集: ${escapeHtml(lane.name)}</span>`;
        }
        if (nameInput) nameInput.value = lane.name || '';
        if (iconInput) iconInput.value = lane.icon ?? '';
        if (descInput) descInput.value = lane.description ?? '';
        if (isActiveInput) isActiveInput.checked = Boolean(lane.is_active);

        if (usageWarning && usageCountEl) {
            const count = Number(lane.timetable_lanes_count ?? 0);
            usageCountEl.textContent = String(count);
            usageWarning.classList.toggle('hidden', count <= 0);
        }

        selectColor(lane.color || 'slate');
        openModal(modal);
        nameInput?.focus();
    };

    document.getElementById('btn-open-create-lane')?.addEventListener('click', openCreateModal);

    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.btn-edit-lane');
        if (editBtn) {
            const lane = parseJsonDataset(editBtn.dataset.lane);
            if (lane) openEditModal(lane);
            return;
        }

        const emojiBtn = e.target.closest('.btn-emoji-preset');
        if (emojiBtn && form.contains(emojiBtn)) {
            if (iconInput) {
                iconInput.value = emojiBtn.dataset.emoji || '📋';
                updatePreview();
            }
        }
    });

    nameInput?.addEventListener('input', updatePreview);
    iconInput?.addEventListener('input', updatePreview);
    form.addEventListener('change', (e) => {
        if (e.target.classList.contains('lane-color-radio')) {
            updatePreview();
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErrors();

        if (submitBtn) submitBtn.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: new FormData(form),
            });

            const data = await response.json().catch(() => ({}));
            if (response.ok && data.success) {
                window.location.reload();
                return;
            }

            const messages = [];
            if (data.errors && typeof data.errors === 'object') {
                Object.values(data.errors).forEach((errs) => {
                    if (Array.isArray(errs)) messages.push(...errs);
                });
            }
            if (messages.length === 0 && data.message) {
                messages.push(data.message);
            }
            if (messages.length === 0) {
                messages.push('保存に失敗しました。入力内容をご確認ください。');
            }

            if (errorBox) {
                errorBox.innerHTML = `<ul class="list-disc list-inside space-y-0.5">${messages.map((m) => `<li>${escapeHtml(m)}</li>`).join('')}</ul>`;
                errorBox.classList.remove('hidden');
            }
        } catch (err) {
            console.error('Lane form submit error:', err);
            if (errorBox) {
                errorBox.textContent = '通信エラーが発生しました。再度お試しください。';
                errorBox.classList.remove('hidden');
            }
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    });
}

function initDeleteModal() {
    const modal = document.getElementById('modal-lane-delete');
    const form = document.getElementById('lane-delete-form');
    const nameEl = document.getElementById('delete-lane-name');
    if (!modal || !form) return;

    document.addEventListener('click', (e) => {
        const deleteBtn = e.target.closest('.btn-delete-lane');
        if (!deleteBtn) return;

        const lane = parseJsonDataset(deleteBtn.dataset.lane);
        if (!lane) return;

        form.action = lane.destroy_url || '';
        if (nameEl) nameEl.textContent = lane.name || '';
        openModal(modal);
    });
}

function initSortableRows(reorderUrl, csrfToken) {
    const tbody = document.getElementById('lanes-sortable-body');
    const feedback = document.getElementById('lane-reorder-feedback');
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
        tbody.querySelectorAll('tr.lane-master-row').forEach((row, idx) => {
            const numEl = row.querySelector('.order-number');
            if (numEl) numEl.textContent = String(idx + 1);
        });
    };

    tbody.addEventListener('dragstart', (e) => {
        const row = e.target.closest('tr.lane-master-row');
        if (!row) return;
        draggedRow = row;
        row.classList.add('opacity-50');
        if (e.dataTransfer) {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', row.dataset.laneId || '');
        }
    });

    tbody.addEventListener('dragover', (e) => {
        e.preventDefault();
        const targetRow = e.target.closest('tr.lane-master-row');
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

        const laneIds = Array.from(tbody.querySelectorAll('tr.lane-master-row'))
            .map((row) => parseInt(row.dataset.laneId || '0', 10))
            .filter((id) => id > 0);

        if (laneIds.length === 0) return;

        try {
            const response = await fetch(reorderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ lane_ids: laneIds }),
            });

            if (response.ok) {
                showFeedback('担当班マスターの表示順を更新しました。');
            } else {
                showFeedback('表示順の保存に失敗しました。', true);
            }
        } catch (err) {
            console.error('Lane reorder error:', err);
            showFeedback('通信エラーにより表示順を保存できませんでした。', true);
        }
    });
}
