/**
 * 拠点管理機能スクリプト (CSP準拠・外部JavaScriptモジュール)
 */
document.addEventListener('DOMContentLoaded', () => {
    initDragAndDropReorder();
    initScheduleFormRows();
    initTypeModal();
    initAutoSubmitSelects();
    initConfirmForms();
});

/**
 * 1. ドラッグ＆ドロップによる表示順並び替え
 */
function initDragAndDropReorder() {
    const container = document.getElementById('locations-container');
    if (!container) return;

    let draggedItem = null;

    container.addEventListener('dragstart', (e) => {
        const item = e.target.closest('[data-location-id]');
        if (!item) return;

        draggedItem = item;
        item.classList.add('opacity-40', 'bg-indigo-50/70');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', item.dataset.locationId || '');
    });

    container.addEventListener('dragend', (e) => {
        const item = e.target.closest('[data-location-id]');
        if (item) {
            item.classList.remove('opacity-40', 'bg-indigo-50/70');
        }
        draggedItem = null;
    });

    container.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const targetItem = e.target.closest('[data-location-id]');
        if (!targetItem || targetItem === draggedItem) return;

        const rect = targetItem.getBoundingClientRect();
        const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
        container.insertBefore(draggedItem, next ? targetItem.nextSibling : targetItem);
    });

    container.addEventListener('drop', async (e) => {
        e.preventDefault();
        if (!draggedItem) return;

        const items = Array.from(container.querySelectorAll('[data-location-id]'));
        const locationIds = items.map(el => parseInt(el.dataset.locationId, 10)).filter(Boolean);
        const reorderUrl = container.dataset.reorderUrl;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!reorderUrl || !csrfToken) return;

        try {
            const res = await fetch(reorderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ location_ids: locationIds }),
            });

            const data = await res.json();
            if (data.success) {
                showToast(data.message || '拠点の表示順を更新しました。', 'success');
            } else {
                showToast(data.message || '並び順の更新に失敗しました。', 'error');
            }
        } catch (err) {
            console.error('Reorder error:', err);
            showToast('通信エラーが発生しました。', 'error');
        }
    });
}

/**
 * 2. 登録・編集フォームでの利用可能時間帯行の動的追加・削除制御
 */
function initScheduleFormRows() {
    const isAllDayCheckbox = document.getElementById('is_all_day');
    const schedulesContainer = document.getElementById('schedules-form-container');
    const tableBody = document.getElementById('schedules-form-body');
    const addBtn = document.getElementById('add-schedule-row-btn');

    if (!schedulesContainer || !tableBody) {
        return;
    }

    // 終日利用可能トグルに応じて表示切り替え
    const toggleSchedulesVisibility = () => {
        if (isAllDayCheckbox && isAllDayCheckbox.checked) {
            schedulesContainer.classList.add('hidden');
        } else {
            schedulesContainer.classList.remove('hidden');
        }
    };

    if (isAllDayCheckbox) {
        isAllDayCheckbox.addEventListener('change', toggleSchedulesVisibility);
        toggleSchedulesVisibility();
    }

    // 行削除ハンドラーの設定
    const bindRemoveButtons = () => {
        tableBody.querySelectorAll('[data-action="remove-schedule-row"]').forEach(btn => {
            btn.onclick = () => {
                const row = btn.closest('[data-schedule-row]');
                if (row) {
                    row.remove();
                    checkEmptyState();
                }
            };
        });
    };

    const checkEmptyState = () => {
        const rows = tableBody.querySelectorAll('[data-schedule-row]');
        const emptyMsg = document.getElementById('schedules-empty-message');
        if (emptyMsg) {
            if (rows.length === 0) {
                emptyMsg.classList.remove('hidden');
            } else {
                emptyMsg.classList.add('hidden');
            }
        }
    };

    bindRemoveButtons();
    checkEmptyState();

    // 新規行追加
    if (addBtn) {
        addBtn.addEventListener('click', () => {
            const index = Date.now();
            const targetDateStr = addBtn.dataset.targetDate || '';
            const defaultStart = targetDateStr ? `${targetDateStr}T09:00` : '';
            const defaultEnd = targetDateStr ? `${targetDateStr}T17:00` : '';

            const tr = document.createElement('tr');
            tr.setAttribute('data-schedule-row', 'true');
            tr.className = 'border-b border-slate-100 hover:bg-slate-50/60 transition';
            tr.innerHTML = `
                <td class="py-2.5 px-3">
                    <div class="flex items-center space-x-1 font-mono">
                        <input type="datetime-local" name="schedules[${index}][start_at]" value="${defaultStart}" required
                            class="px-2 py-1 bg-white border border-slate-300 rounded text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500 font-mono">
                        <span class="text-slate-400">〜</span>
                        <input type="datetime-local" name="schedules[${index}][end_at]" value="${defaultEnd}" required
                            class="px-2 py-1 bg-white border border-slate-300 rounded text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500 font-mono">
                    </div>
                </td>
                <td class="py-2.5 px-3">
                    <select name="schedules[${index}][availability_status]" required
                        class="px-2 py-1 bg-white border border-slate-300 rounded text-xs font-bold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="available" selected>利用可（確保済）</option>
                        <option value="unavailable">未確保（調整中）</option>
                    </select>
                </td>
                <td class="py-2.5 px-3">
                    <input type="text" name="schedules[${index}][title]" placeholder="例: 午前設営枠, 夜間枠"
                        class="w-full px-2 py-1 bg-white border border-slate-300 rounded text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </td>
                <td class="py-2.5 px-3">
                    <input type="text" name="schedules[${index}][note]" placeholder="例: 道路使用許可申請中"
                        class="w-full px-2 py-1 bg-white border border-slate-300 rounded text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </td>
                <td class="py-2.5 px-3 text-right">
                    <button type="button" data-action="remove-schedule-row"
                        class="px-2 py-1 bg-white hover:bg-rose-50 text-rose-600 border border-slate-200 hover:border-rose-200 rounded text-xs font-bold transition cursor-pointer">
                        ✕ 削除
                    </button>
                </td>
            `;

            tableBody.appendChild(tr);
            bindRemoveButtons();
            checkEmptyState();
        });
    }
}

/**
 * 3. 拠点タイプ（種別マスタ）登録・編集モーダル
 */
function initTypeModal() {
    const modal = document.getElementById('type-modal');
    const form = document.getElementById('type-modal-form');
    const modalTitle = document.getElementById('type-modal-title');
    const methodContainer = document.getElementById('type-form-method');

    if (!modal || !form) {
        return;
    }

    // 開くボタン
    document.querySelectorAll('[data-action="open-type-modal"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            const storeUrl = btn.dataset.storeUrl;
            const updateUrl = btn.dataset.updateUrl;

            if (mode === 'create') {
                if (modalTitle) {
                    modalTitle.innerHTML = '<span>🏷️</span><span>新しい拠点タイプの追加</span>';
                }
                form.action = storeUrl || form.action;
                if (methodContainer) {
                    methodContainer.innerHTML = '';
                }
                form.reset();
            } else if (mode === 'edit') {
                if (modalTitle) {
                    modalTitle.innerHTML = '<span>🏷️</span><span>拠点タイプの編集</span>';
                }
                form.action = updateUrl;
                if (methodContainer) {
                    methodContainer.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                }

                try {
                    const type = JSON.parse(btn.dataset.type || '{}');
                    const labelInput = document.getElementById('type_label');
                    const nameInput = document.getElementById('type_name');
                    const descInput = document.getElementById('type_description');
                    const orderInput = document.getElementById('type_display_order');
                    const activeInput = document.getElementById('type_is_active');

                    if (labelInput) labelInput.value = type.label || '';
                    if (nameInput) nameInput.value = type.name || '';
                    if (descInput) descInput.value = type.description || '';
                    if (orderInput) orderInput.value = type.display_order ?? 10;
                    if (activeInput) activeInput.checked = Boolean(type.is_active);
                } catch (e) {
                    console.error('Failed to parse type data', e);
                }
            }

            modal.classList.remove('hidden');
        });
    });

    // 閉じるボタン
    document.querySelectorAll('[data-action="close-type-modal"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });
    });

    // 背景クリックで閉じる
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
}

/**
 * 4. 申請ステータスの自動送信セレクトボックス
 */
function initAutoSubmitSelects() {
    document.querySelectorAll('select[data-action="auto-submit-status"]').forEach((select) => {
        select.addEventListener('change', () => {
            const form = select.closest('form');
            if (form) {
                form.submit();
            }
        });
    });
}

/**
 * 5. 削除確認等の確認ダイアログ
 */
function initConfirmForms() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            const message = form.dataset.confirm || '本当に実行しますか？';
            if (!window.confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * トースト通知の表示
 */
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed bottom-5 right-5 z-50 flex flex-col space-y-2 pointer-events-none';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-emerald-800' : 'bg-rose-800';
    toast.className = `${bgColor} text-white px-4 py-2.5 rounded-xl text-xs font-bold shadow-lg transition-all transform translate-y-2 opacity-0 pointer-events-auto flex items-center space-x-2`;
    toast.innerHTML = `<span>${type === 'success' ? '✅' : '⚠️'}</span><span>${message}</span>`;

    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    });

    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
