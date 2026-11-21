/**
 * 機材・備品管理スクリプト (CSP準拠・外部JavaScriptモジュール)
 */
document.addEventListener('DOMContentLoaded', () => {
    initEquipmentDetailModal();
    initEquipmentFormModal();
    initEquipmentDragAndDropReorder();
});

/**
 * 1. 閲覧専用詳細モーダル (#modal-equipment-detail)
 */
function initEquipmentDetailModal() {
    const modal = document.getElementById('modal-equipment-detail');
    if (!modal) return;

    const nameEl = document.getElementById('detail-equipment-name');
    const typeEl = document.getElementById('detail-equipment-type');
    const quantityEl = document.getElementById('detail-equipment-quantity');
    const ruleEl = document.getElementById('detail-equipment-checkout-rule');
    const storageEl = document.getElementById('detail-equipment-storage');
    const descriptionEl = document.getElementById('detail-equipment-description');

    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    const openDetail = (payload) => {
        if (!payload || typeof payload !== 'object') return;

        if (nameEl) nameEl.textContent = payload.name || '—';

        if (typeEl) {
            const icon = payload.type_icon ? `${payload.type_icon} ` : '';
            typeEl.textContent = `${icon}${payload.type_label || '—'}`;
            typeEl.className = `inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border mt-0.5 ${payload.type_badge_classes || 'bg-slate-100 text-slate-700 border-slate-200'}`;
        }

        if (quantityEl) quantityEl.textContent = payload.formatted_quantity || '—';

        if (ruleEl) {
            const ruleIcon = payload.checkout_rule_icon ? `${payload.checkout_rule_icon} ` : '';
            ruleEl.textContent = `${ruleIcon}${payload.checkout_rule_label || '—'}`;
            ruleEl.className = `inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border mt-0.5 ${payload.checkout_rule_badge_classes || 'bg-slate-100 text-slate-700 border-slate-200'}`;
        }

        if (storageEl) {
            let storageText = payload.resolved_storage_location_label || '未指定';
            if (payload.type !== 'rental' && payload.storage_location_id && payload.storage_location_is_active === false) {
                storageText += '（無効）';
            }
            storageEl.textContent = storageText;
        }

        if (descriptionEl) {
            descriptionEl.textContent = payload.description || '仕様メモ・注意事項は登録されていません。';
        }

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    document.querySelectorAll('.btn-view-equipment').forEach((btn) => {
        btn.addEventListener('click', () => {
            try {
                const payload = JSON.parse(btn.dataset.equipment || '{}');
                openDetail(payload);
            } catch (err) {
                console.error('Failed to parse equipment detail payload:', err);
            }
        });
    });

    modal.querySelectorAll('.btn-close-modal, .modal-backdrop').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
}

/**
 * 2. 新規登録・編集兼用モーダル (#modal-equipment-form)
 */
function initEquipmentFormModal() {
    const modal = document.getElementById('modal-equipment-form');
    const form = document.getElementById('form-equipment-mutation');
    if (!modal || !form) return;

    const modalTitleEl = document.getElementById('equipment-form-modal-title');
    const methodInput = document.getElementById('equipment-form-method');
    const actionMirrorInput = document.getElementById('equipment-form-action-mirror');
    const methodMirrorInput = document.getElementById('equipment-form-method-mirror');
    const submitBtn = document.getElementById('equipment-form-submit-btn');

    const nameInput = document.getElementById('equipment_name');
    const typeSelect = document.getElementById('equipment_type');
    const quantityInput = document.getElementById('equipment_quantity');
    const unitInput = document.getElementById('equipment_unit');
    const approxCheckbox = document.getElementById('equipment_is_approximate_quantity');
    const checkoutRuleSelect = document.getElementById('equipment_checkout_rule');
    const storageSelect = document.getElementById('storage_location_id');
    const storageField = document.getElementById('storage-location-field');
    const rentalNotice = document.getElementById('rental-notice');
    const descriptionTextarea = document.getElementById('equipment_description');

    const toggleEquipmentType = (type) => {
        const isRental = type === 'rental';
        if (storageField) {
            storageField.classList.toggle('hidden', isRental);
        }
        if (rentalNotice) {
            rentalNotice.classList.toggle('hidden', !isRental);
        }
        if (storageSelect) {
            storageSelect.disabled = isRental;
        }
    };

    const removeTempStorageOptions = () => {
        if (!storageSelect) return;
        storageSelect.querySelectorAll('option[data-temp-option="1"]').forEach((opt) => opt.remove());
    };

    const setTitleText = (text) => {
        if (!modalTitleEl) return;
        const spans = modalTitleEl.querySelectorAll('span');
        if (spans.length >= 2) {
            spans[1].textContent = text;
        } else {
            modalTitleEl.textContent = text;
        }
    };

    const openModal = () => {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    if (typeSelect) {
        typeSelect.addEventListener('change', (e) => {
            toggleEquipmentType(e.target.value);
        });
    }

    const createBtn = document.getElementById('btn-open-create-equipment');
    if (createBtn) {
        createBtn.addEventListener('click', () => {
            removeTempStorageOptions();

            const storeUrl = createBtn.dataset.storeUrl || form.action;
            form.action = storeUrl;
            if (methodInput) methodInput.value = 'POST';
            if (actionMirrorInput) actionMirrorInput.value = storeUrl;
            if (methodMirrorInput) methodMirrorInput.value = 'POST';

            setTitleText('新規備品の登録');
            if (submitBtn) submitBtn.textContent = '備品を登録する';

            if (nameInput) nameInput.value = '';
            if (typeSelect) typeSelect.value = 'committee';
            if (quantityInput) quantityInput.value = '1';
            if (unitInput) unitInput.value = '個';
            if (approxCheckbox) approxCheckbox.checked = false;
            if (checkoutRuleSelect) checkoutRuleSelect.value = 'free';
            if (storageSelect) storageSelect.value = '';
            if (descriptionTextarea) descriptionTextarea.value = '';

            toggleEquipmentType('committee');
            openModal();
        });
    }

    document.querySelectorAll('.btn-edit-equipment').forEach((btn) => {
        btn.addEventListener('click', () => {
            let payload = {};
            try {
                payload = JSON.parse(btn.dataset.equipment || '{}');
            } catch (err) {
                console.error('Failed to parse equipment edit payload:', err);
                return;
            }

            removeTempStorageOptions();

            const updateUrl = payload.update_url || '';
            form.action = updateUrl;
            if (methodInput) methodInput.value = 'PUT';
            if (actionMirrorInput) actionMirrorInput.value = updateUrl;
            if (methodMirrorInput) methodMirrorInput.value = 'PUT';

            setTitleText('備品の編集');
            if (submitBtn) submitBtn.textContent = '変更内容を保存する';

            const equipmentType = payload.type || 'committee';
            if (nameInput) nameInput.value = payload.name || '';
            if (typeSelect) typeSelect.value = equipmentType;
            if (quantityInput) quantityInput.value = String(payload.quantity ?? 1);
            if (unitInput) unitInput.value = payload.unit || '個';
            if (approxCheckbox) approxCheckbox.checked = Boolean(payload.is_approximate_quantity);
            if (checkoutRuleSelect) checkoutRuleSelect.value = payload.checkout_rule || 'free';
            if (descriptionTextarea) descriptionTextarea.value = payload.description || '';

            if (storageSelect) {
                const locId = payload.storage_location_id !== null && payload.storage_location_id !== undefined
                    ? String(payload.storage_location_id)
                    : '';

                if (locId !== '') {
                    const hasOption = Array.from(storageSelect.options).some((opt) => opt.value === locId);
                    if (!hasOption) {
                        const tempOption = document.createElement('option');
                        tempOption.value = locId;
                        tempOption.textContent = `${payload.storage_location_name || '選択中の拠点'}（無効）`;
                        tempOption.dataset.tempOption = '1';
                        storageSelect.appendChild(tempOption);
                    }
                }

                storageSelect.value = locId;
            }

            toggleEquipmentType(equipmentType);
            openModal();
        });
    });

    modal.querySelectorAll('.btn-close-modal, .modal-backdrop').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    // バリデーションエラー時は元のフォーム種別・値のままモーダルを再表示
    if (typeSelect) {
        toggleEquipmentType(typeSelect.value);
    }

    if (modal.dataset.reopenOnError === '1') {
        const oldMethod = (modal.dataset.oldMethod || 'POST').toUpperCase();
        const oldAction = modal.dataset.oldAction || form.action;
        form.action = oldAction;
        if (methodInput) methodInput.value = oldMethod;
        if (actionMirrorInput) actionMirrorInput.value = oldAction;
        if (methodMirrorInput) methodMirrorInput.value = oldMethod;

        if (oldMethod === 'PUT') {
            setTitleText('備品の編集');
            if (submitBtn) submitBtn.textContent = '変更内容を保存する';
        } else {
            setTitleText('新規備品の登録');
            if (submitBtn) submitBtn.textContent = '備品を登録する';
        }

        openModal();
    }
}

/**
 * 3. 備品一覧 ドラッグ＆ドロップによる表示順並び替え
 */
function initEquipmentDragAndDropReorder() {
    const container = document.getElementById('equipments-container');
    if (!container) return;

    let draggedItem = null;

    container.addEventListener('dragstart', (e) => {
        const item = e.target.closest('[data-equipment-id]');
        if (!item) return;

        draggedItem = item;
        item.classList.add('opacity-40', 'bg-indigo-50/70');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', item.dataset.equipmentId || '');
    });

    container.addEventListener('dragend', (e) => {
        const item = e.target.closest('[data-equipment-id]');
        if (item) {
            item.classList.remove('opacity-40', 'bg-indigo-50/70');
        }
        draggedItem = null;
    });

    container.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const targetItem = e.target.closest('[data-equipment-id]');
        if (!targetItem || targetItem === draggedItem) return;

        const rect = targetItem.getBoundingClientRect();
        const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
        container.insertBefore(draggedItem, next ? targetItem.nextSibling : targetItem);
    });

    container.addEventListener('drop', async (e) => {
        e.preventDefault();
        if (!draggedItem) return;

        const items = Array.from(container.querySelectorAll('[data-equipment-id]'));
        const equipmentIds = items.map((el) => parseInt(el.dataset.equipmentId, 10)).filter(Boolean);
        const reorderUrl = container.dataset.reorderUrl;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!reorderUrl || !csrfToken) return;

        try {
            const res = await fetch(reorderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ equipment_ids: equipmentIds }),
            });

            const data = await res.json();
            if (data.success) {
                showToast(data.message || '備品の表示順を更新しました。', 'success');
            } else {
                showToast(data.message || '表示順の更新に失敗しました。', 'error');
            }
        } catch (err) {
            console.error('Reorder error:', err);
            showToast('通信エラーが発生しました。', 'error');
        }
    });
}

function showToast(message, type = 'success') {
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'fixed bottom-5 right-5 z-50 flex flex-col space-y-2 pointer-events-none';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-emerald-800' : 'bg-red-800';
    toast.className = `${bgColor} text-white px-4 py-2.5 rounded-xl text-xs font-bold shadow-lg transition-all transform translate-y-2 opacity-0 pointer-events-auto flex items-center space-x-2`;

    const iconSpan = document.createElement('span');
    iconSpan.textContent = type === 'success' ? '✅' : '⚠️';

    const messageSpan = document.createElement('span');
    messageSpan.textContent = String(message ?? '');

    toast.appendChild(iconSpan);
    toast.appendChild(messageSpan);
    toastContainer.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    });

    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
