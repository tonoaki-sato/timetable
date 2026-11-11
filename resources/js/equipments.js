/**
 * 資材管理 & 搬出入動態管理スクリプト (CSP準拠・外部JavaScriptモジュール)
 */
document.addEventListener('DOMContentLoaded', () => {
    initEquipmentDragAndDropReorder();
    initEquipmentForm();
    initStepModal();
    initApplyTemplateModal();
    initSavePatternModal();
});

/**
 * 1. 資材新規作成・編集フォーム (equipments.create, equipments.edit)
 */
function initEquipmentForm() {
    const typeSelect = document.querySelector('select[name="type"]');
    const commSec = document.getElementById('section-committee');

    function toggleEquipmentType(type) {
        if (!commSec) return;
        const storageInput = document.getElementById('storage_location');
        if (type === 'rental') {
            commSec.classList.add('hidden');
            if (storageInput) storageInput.disabled = true;
        } else {
            commSec.classList.remove('hidden');
            if (storageInput) storageInput.disabled = false;
        }
    }

    // 資材種別セレクト切り替え
    if (typeSelect) {
        typeSelect.addEventListener('change', (e) => {
            toggleEquipmentType(e.target.value);
        });
    }

    // 定常保管場所のクイック設定ボタン
    document.querySelectorAll('[data-set-storage-location]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const storageInput = document.getElementById('storage_location');
            if (storageInput) {
                storageInput.value = btn.dataset.setStorageLocation || '';
            }
        });
    });

    // 初期状態反映
    if (typeSelect) {
        toggleEquipmentType(typeSelect.value);
    }
}

/**
 * 2. 搬出入計画ステップ登録・編集モーダル (equipments.show)
 */
function initStepModal() {
    const modal = document.getElementById('modal-step');
    const form = document.getElementById('form-step');
    const modalTitle = document.getElementById('modal-step-title');
    const methodContainer = document.getElementById('form-step-method');
    const fromLocationSelect = document.getElementById('step_from_location_id');
    const toLocationSelect = document.getElementById('step_to_location_id');

    if (!modal || !form) {
        return;
    }

    const defaultAction = form.action;

    // 移動元セレクト変更時にテキスト入力も同期
    if (fromLocationSelect) {
        fromLocationSelect.addEventListener('change', () => {
            const selected = fromLocationSelect.options[fromLocationSelect.selectedIndex];
            const fromLocationInput = document.getElementById('step_from_location');
            if (selected && selected.dataset.name && fromLocationInput) {
                fromLocationInput.value = selected.dataset.name;
            }
        });
    }

    // 移動先セレクト変更時にテキスト入力も同期
    if (toLocationSelect) {
        toLocationSelect.addEventListener('change', () => {
            const selected = toLocationSelect.options[toLocationSelect.selectedIndex];
            const toLocationInput = document.getElementById('step_to_location');
            if (selected && selected.dataset.name && toLocationInput) {
                toLocationInput.value = selected.dataset.name;
            }
        });
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    // 開くボタン（新規・編集）
    document.querySelectorAll('[data-action="open-step-modal"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            const setValue = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = val ?? '';
            };

            if (mode === 'create') {
                if (modalTitle) {
                    modalTitle.innerHTML = '<span>⏱️</span><span>ステップの追加</span>';
                }
                form.action = defaultAction;
                if (methodContainer) {
                    methodContainer.innerHTML = '';
                }
                form.reset();
                setValue('step_status', 'planned');
            } else if (mode === 'edit') {
                if (modalTitle) {
                    modalTitle.innerHTML = '<span>⏱️</span><span>ステップの編集</span>';
                }
                try {
                    const plan = JSON.parse(btn.dataset.plan || '{}');
                    form.action = btn.dataset.updateUrl || `/logistics/plans/${plan.id}`;
                    if (methodContainer) {
                        methodContainer.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                    }

                    setValue('step_title', plan.title);
                    setValue('step_day_phase', plan.day_phase || 'pre_day');
                    setValue('step_action_type', plan.action_type || 'staging_move');
                    setValue('step_status', plan.status || 'planned');
                    setValue('step_target_date', plan.target_date ? plan.target_date.substring(0, 10) : '');
                    setValue('step_start_time', plan.start_time ? plan.start_time.substring(0, 5) : '');
                    setValue('step_end_time', plan.end_time ? plan.end_time.substring(0, 5) : '');
                    setValue('step_from_location_id', plan.from_location_id);
                    setValue('step_from_location', plan.from_location);
                    setValue('step_to_location_id', plan.to_location_id);
                    setValue('step_to_location', plan.to_location);
                    setValue('step_quantity', plan.quantity);
                    setValue('step_quantity_note', plan.quantity_note);
                    setValue('step_operator_type', plan.operator_type || 'committee');
                    setValue('step_witness_staff_id', plan.witness_staff_id);
                    setValue('step_notes', plan.notes);

                    const witnessReq = document.getElementById('step_witness_required');
                    if (witnessReq) {
                        witnessReq.checked = !!plan.witness_required;
                    }
                } catch (e) {
                    console.error('Failed to parse step plan data', e);
                }
            }
            modal.classList.remove('hidden');
        });
    });

    // 閉じるボタン
    document.querySelectorAll('[data-action="close-step-modal"]').forEach((btn) => {
        btn.addEventListener('click', closeModal);
    });

    // 背景クリックで閉じる
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });
}

/**
 * 3. パターン適用モーダル (equipments.show)
 */
function initApplyTemplateModal() {
    const modal = document.getElementById('modal-apply-template');
    if (!modal) return;

    document.querySelectorAll('[data-action="open-apply-template-modal"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            modal.classList.remove('hidden');
        });
    });

    document.querySelectorAll('[data-action="close-apply-template-modal"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
}

/**
 * 4. 現在のステップ構成をパターンとして保存するモーダル (equipments.show)
 */
function initSavePatternModal() {
    const modal = document.getElementById('modal-save-pattern');
    if (!modal) return;

    document.querySelectorAll('[data-action="open-save-pattern-modal"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            modal.classList.remove('hidden');
        });
    });

    document.querySelectorAll('[data-action="close-save-pattern-modal"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
}



/**
 * 6. 備品一覧 ドラッグ＆ドロップによる表示順並び替え
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
        const equipmentIds = items.map(el => parseInt(el.dataset.equipmentId, 10)).filter(Boolean);
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
    const bgColor = type === 'success' ? 'bg-emerald-800' : 'bg-rose-800';
    toast.className = `${bgColor} text-white px-4 py-2.5 rounded-xl text-xs font-bold shadow-lg transition-all transform translate-y-2 opacity-0 pointer-events-auto flex items-center space-x-2`;
    toast.innerHTML = `<span>${type === 'success' ? '✅' : '⚠️'}</span><span>${message}</span>`;

    toastContainer.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    });

    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
