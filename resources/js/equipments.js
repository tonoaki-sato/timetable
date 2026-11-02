/**
 * 資材管理 & 搬出入動態管理スクリプト (CSP準拠・外部JavaScriptモジュール)
 */
document.addEventListener('DOMContentLoaded', () => {
    initEquipmentForm();
    initStepModal();
    initApplyTemplateModal();
    initSavePatternModal();
    initPatternModal();
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
        const detailInput = document.getElementById('storage_detail');
        if (type === 'rental') {
            commSec.classList.add('hidden');
            if (storageInput) storageInput.disabled = true;
            if (detailInput) detailInput.disabled = true;
        } else {
            commSec.classList.remove('hidden');
            if (storageInput) storageInput.disabled = false;
            if (detailInput) detailInput.disabled = false;
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
                setValue('step_display_order', 10);
            } else if (mode === 'edit') {
                if (modalTitle) {
                    modalTitle.innerHTML = '<span>⏱️</span><span>ステップの編集</span>';
                }
                try {
                    const plan = JSON.parse(btn.dataset.plan || '{}');
                    form.action = `/logistics/plans/${plan.id}`;
                    if (methodContainer) {
                        methodContainer.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                    }

                    setValue('step_title', plan.title);
                    setValue('step_day_phase', plan.day_phase || 'pre_day');
                    setValue('step_action_type', plan.action_type || 'staging_move');
                    setValue('step_status', plan.status || 'planned');
                    setValue('step_display_order', plan.display_order ?? 10);
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
 * 5. カスタム搬出入パターン新規作成・編集モーダル (logistics.index ?tab=patterns)
 */
function initPatternModal() {
    const modal = document.getElementById('modal-pattern');
    const form = document.getElementById('form-pattern');
    const modalTitle = document.getElementById('modal-pattern-title');
    const methodContainer = document.getElementById('form-pattern-method');
    const stepsContainer = document.getElementById('pattern-steps-container');
    const stepTemplate = document.getElementById('tpl-pattern-step-row');

    if (!modal || !form || !stepsContainer || !stepTemplate) {
        return;
    }

    const defaultAction = form.action;

    function reindexStepRows() {
        const rows = stepsContainer.querySelectorAll('.pattern-step-row');
        rows.forEach((row, index) => {
            const numLabel = row.querySelector('.step-row-number');
            if (numLabel) {
                numLabel.textContent = `ステップ #${index + 1}`;
            }
            row.querySelectorAll('[data-field]').forEach((field) => {
                const fieldName = field.dataset.field;
                field.name = `steps[${index}][${fieldName}]`;
            });
            const removeBtn = row.querySelector('[data-action="remove-pattern-step"]');
            if (removeBtn) {
                removeBtn.classList.toggle('hidden', rows.length <= 1);
            }
        });
    }

    function addStepRow(stepData = null) {
        const clone = stepTemplate.content.cloneNode(true);
        const row = clone.querySelector('.pattern-step-row');

        if (stepData) {
            const setField = (name, val) => {
                const el = row.querySelector(`[data-field="${name}"]`);
                if (!el) return;
                if (el.type === 'checkbox') {
                    el.checked = Boolean(val);
                } else {
                    el.value = val ?? '';
                }
            };
            setField('day_phase', stepData.day_phase || 'pre_day');
            setField('action_type', stepData.action_type || 'staging_move');
            setField('title', stepData.title || '');
            setField('operator_type', stepData.operator_type || 'committee');
            setField('from_location_id', stepData.from_location_id || '');
            setField('to_location_id', stepData.to_location_id || '');
            setField('witness_required', stepData.witness_required);
            setField('notes', stepData.notes || '');
        }

        const removeBtn = row.querySelector('[data-action="remove-pattern-step"]');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                row.remove();
                reindexStepRows();
            });
        }

        stepsContainer.appendChild(row);
        reindexStepRows();
    }

    document.querySelectorAll('[data-action="add-pattern-step"]').forEach((btn) => {
        btn.addEventListener('click', () => addStepRow());
    });

    document.querySelectorAll('[data-action="open-pattern-modal"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            stepsContainer.innerHTML = '';

            if (mode === 'create') {
                if (modalTitle) {
                    modalTitle.innerHTML = '<span>🧩</span><span>新規パターンの作成</span>';
                }
                form.action = defaultAction;
                if (methodContainer) {
                    methodContainer.innerHTML = '';
                }
                form.reset();
                addStepRow();
            } else if (mode === 'edit') {
                if (modalTitle) {
                    modalTitle.innerHTML = '<span>🧩</span><span>パターンの編集</span>';
                }
                try {
                    const pattern = JSON.parse(btn.dataset.pattern || '{}');
                    form.action = `/logistics/patterns/${pattern.id}`;
                    if (methodContainer) {
                        methodContainer.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                    }

                    const nameEl = document.getElementById('pattern_name');
                    const typeEl = document.getElementById('pattern_target_type');
                    const descEl = document.getElementById('pattern_description');

                    if (nameEl) nameEl.value = pattern.name || '';
                    if (typeEl) typeEl.value = pattern.target_type || 'committee';
                    if (descEl) descEl.value = pattern.description || '';

                    if (Array.isArray(pattern.steps) && pattern.steps.length > 0) {
                        pattern.steps.forEach((step) => addStepRow(step));
                    } else {
                        addStepRow();
                    }
                } catch (e) {
                    console.error('Failed to parse pattern data', e);
                    addStepRow();
                }
            }

            modal.classList.remove('hidden');
        });
    });

    document.querySelectorAll('[data-action="close-pattern-modal"]').forEach((btn) => {
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
