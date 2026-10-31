/**
 * 資材・機材 搬出入動態管理スクリプト (CSP準拠・外部JavaScriptモジュール)
 */
document.addEventListener('DOMContentLoaded', () => {
    initLocationModal();
    initEquipmentForm();
    initStepModal();
    initApplyTemplateModal();
});

/**
 * 1. 拠点登録・編集モーダル (equipments.index)
 */
function initLocationModal() {
    const modal = document.getElementById('modal-location-create');
    const form = document.getElementById('form-location');
    const modalTitle = document.getElementById('modal-location-title');
    const methodContainer = document.getElementById('form-location-method');
    const allDayCheckbox = document.getElementById('location_is_all_day');

    if (!modal || !form) {
        return;
    }

    const defaultAction = form.action;

    function toggleAvailabilityInputs(isAllDay) {
        const box = document.getElementById('availability-inputs');
        if (!box) return;
        if (isAllDay) {
            box.classList.add('opacity-40', 'pointer-events-none');
        } else {
            box.classList.remove('opacity-40', 'pointer-events-none');
        }
    }

    if (allDayCheckbox) {
        allDayCheckbox.addEventListener('change', (e) => {
            toggleAvailabilityInputs(e.target.checked);
        });
    }

    function closeModal() {
        modal.classList.add('hidden');
        form.reset();
        form.action = defaultAction;
        if (methodContainer) {
            methodContainer.innerHTML = '';
        }
        if (modalTitle) {
            modalTitle.innerHTML = '<span>📍</span><span>拠点の登録</span>';
        }
        toggleAvailabilityInputs(false);
    }

    // 開くボタン（新規・編集）
    document.querySelectorAll('[data-action="open-location-modal"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            if (mode === 'create') {
                if (modalTitle) {
                    modalTitle.innerHTML = '<span>📍</span><span>拠点の登録</span>';
                }
                form.action = defaultAction;
                if (methodContainer) {
                    methodContainer.innerHTML = '';
                }
                form.reset();
                toggleAvailabilityInputs(false);
            } else if (mode === 'edit') {
                if (modalTitle) {
                    modalTitle.innerHTML = '<span>📍</span><span>拠点の編集</span>';
                }
                try {
                    const location = JSON.parse(btn.dataset.location || '{}');
                    form.action = `/logistics-locations/${location.id}`;
                    if (methodContainer) {
                        methodContainer.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                    }

                    const nameInput = document.getElementById('location_name');
                    const typeInput = document.getElementById('location_type');
                    const orderInput = document.getElementById('location_display_order');
                    const descInput = document.getElementById('location_description');
                    const fromInput = document.getElementById('location_available_from');
                    const toInput = document.getElementById('location_available_to');

                    if (nameInput) nameInput.value = location.name || '';
                    if (typeInput) typeInput.value = location.type || 'parking';
                    if (orderInput) orderInput.value = location.display_order ?? 10;
                    if (descInput) descInput.value = location.description || '';

                    const isAllDay = !!location.is_all_day;
                    if (allDayCheckbox) {
                        allDayCheckbox.checked = isAllDay;
                    }
                    toggleAvailabilityInputs(isAllDay);

                    if (fromInput) {
                        fromInput.value = location.available_from ? location.available_from.replace(' ', 'T').substring(0, 16) : '';
                    }
                    if (toInput) {
                        toInput.value = location.available_to ? location.available_to.replace(' ', 'T').substring(0, 16) : '';
                    }
                } catch (e) {
                    console.error('Failed to parse location data', e);
                }
            }
            modal.classList.remove('hidden');
        });
    });

    // 閉じるボタン
    document.querySelectorAll('[data-action="close-location-modal"]').forEach((btn) => {
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
 * 2. 資材新規作成・編集フォーム (equipments.create, equipments.edit)
 */
function initEquipmentForm() {
    const typeRadios = document.querySelectorAll('input[name="type"]');
    const templateRadios = document.querySelectorAll('input[name="template_type"]');
    const commSec = document.getElementById('section-committee');
    const rentSec = document.getElementById('section-rental');

    function toggleEquipmentType(type) {
        if (!commSec && !rentSec) return;
        if (type === 'committee') {
            if (commSec) commSec.classList.remove('hidden');
            if (rentSec) rentSec.classList.add('hidden');
        } else {
            if (commSec) commSec.classList.add('hidden');
            if (rentSec) rentSec.classList.remove('hidden');
        }
    }

    function toggleTemplateOptions(templateType) {
        const box = document.getElementById('template-options-box');
        const witnessBox = document.getElementById('witness-staff-select-box');
        if (!box) return;

        if (templateType === 'none' || templateType === 'pattern_c') {
            box.classList.add('hidden');
        } else {
            box.classList.remove('hidden');
            if (witnessBox) {
                if (templateType === 'pattern_b') {
                    witnessBox.classList.remove('hidden');
                } else {
                    witnessBox.classList.add('hidden');
                }
            }
        }
    }

    // 資材種別ラジオ切り替え
    typeRadios.forEach((radio) => {
        radio.addEventListener('change', (e) => {
            toggleEquipmentType(e.target.value);
        });
    });

    // テンプレートラジオ切り替え
    templateRadios.forEach((radio) => {
        radio.addEventListener('change', (e) => {
            toggleTemplateOptions(e.target.value);
        });
    });

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
    const checkedType = document.querySelector('input[name="type"]:checked');
    if (checkedType) {
        toggleEquipmentType(checkedType.value);
    }
    const checkedTemplate = document.querySelector('input[name="template_type"]:checked');
    if (checkedTemplate) {
        toggleTemplateOptions(checkedTemplate.value);
    }
}

/**
 * 3. 搬出入計画ステップ登録・編集モーダル (equipments.show)
 */
function initStepModal() {
    const modal = document.getElementById('modal-step');
    const form = document.getElementById('form-step');
    const modalTitle = document.getElementById('modal-step-title');
    const methodContainer = document.getElementById('form-step-method');
    const toLocationSelect = document.getElementById('step_to_location_id');

    if (!modal || !form) {
        return;
    }

    const defaultAction = form.action;

    // 場所セレクト変更時にテキスト入力も同期
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
            if (mode === 'create') {
                if (modalTitle) {
                    modalTitle.innerHTML = '<span>⏱️</span><span>ステップの追加</span>';
                }
                form.action = defaultAction;
                if (methodContainer) {
                    methodContainer.innerHTML = '';
                }
                form.reset();
            } else if (mode === 'edit') {
                if (modalTitle) {
                    modalTitle.innerHTML = '<span>⏱️</span><span>ステップの編集</span>';
                }
                try {
                    const plan = JSON.parse(btn.dataset.plan || '{}');
                    form.action = `/equipment-plans/${plan.id}`;
                    if (methodContainer) {
                        methodContainer.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                    }

                    const setValue = (id, val) => {
                        const el = document.getElementById(id);
                        if (el) el.value = val ?? '';
                    };

                    setValue('step_title', plan.title);
                    setValue('step_day_phase', plan.day_phase || 'pre_day');
                    setValue('step_action_type', plan.action_type || 'staging_move');
                    setValue('step_target_date', plan.target_date ? plan.target_date.substring(0, 10) : '');
                    setValue('step_start_time', plan.start_time ? plan.start_time.substring(0, 5) : '');
                    setValue('step_end_time', plan.end_time ? plan.end_time.substring(0, 5) : '');
                    setValue('step_from_location', plan.from_location);
                    setValue('step_to_location', plan.to_location);
                    setValue('step_to_location_id', plan.to_location_id);
                    setValue('step_quantity', plan.quantity);
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
 * 4. テンプレート適用モーダル (equipments.show)
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

