/**
 * 搬出入計画一覧（/timetables/{timetable}/logistics）モーダル操作スクリプト (CSP完全準拠・Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', () => {
    initLogisticsPlanModals();
});

function initLogisticsPlanModals() {
    const detailModal = document.getElementById('plan-detail-modal');
    const createModal = document.getElementById('plan-create-modal');
    const createForm = document.getElementById('plan-create-form');
    const editModal = document.getElementById('plan-edit-modal');
    const editForm = document.getElementById('plan-edit-form');
    const newPlanModal = document.getElementById('logistics-plan-create-modal');
    const newPlanEquipmentSelect = document.getElementById('new_plan_equipment_select');
    const newPlanEquipmentError = document.getElementById('new_plan_equipment_error');
    const newPlanStepPanel = document.getElementById('new-plan-step-panel');
    const newPlanPatternPanel = document.getElementById('new-plan-pattern-panel');
    const newPlanStepForm = document.getElementById('new-plan-step-form');
    const newPlanPatternForm = document.getElementById('new-plan-pattern-form');
    const newPlanModeTabs = document.querySelectorAll('[data-plan-mode-tab]');
    const allModals = [detailModal, createModal, editModal, newPlanModal].filter(Boolean);

    if (!allModals.length) {
        return;
    }

    document.querySelectorAll('#logistics-accordions summary a, #logistics-accordions summary button').forEach((el) => {
        el.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    });

    let currentDetailPlan = null;

    function openModal(modal) {
        if (!modal) return;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function closeAllModals() {
        allModals.forEach((m) => closeModal(m));
    }

    function getPlanFromTrigger(trigger) {
        const row = trigger.closest('[data-plan-row]');
        if (!row) return null;

        try {
            return JSON.parse(row.dataset.plan || '{}');
        } catch (err) {
            console.error('Failed to parse logistics plan data:', err);
            return null;
        }
    }

    function setText(id, text) {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = text;
        }
    }

    function setValue(id, value) {
        const el = document.getElementById(id);
        if (el) {
            el.value = value ?? '';
        }
    }

    function statusBadgeInfo(status, label) {
        if (status === 'completed') {
            return {
                classes: 'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border bg-emerald-100 text-emerald-800 border-emerald-200',
                text: `✅ ${label || '完了'}`,
            };
        }
        if (status === 'in_progress') {
            return {
                classes: 'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border bg-amber-100 text-amber-800 border-amber-200',
                text: `🏃 ${label || '進行中'}`,
            };
        }
        if (status === 'cancelled') {
            return {
                classes: 'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border bg-rose-100 text-rose-700 border-rose-200',
                text: `⛔ ${label || '中止'}`,
            };
        }
        return {
            classes: 'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border bg-slate-100 text-slate-600 border-slate-200',
            text: `⚪ ${label || '計画中'}`,
        };
    }

    function openDetailModal(plan) {
        if (!detailModal || !plan) return;
        currentDetailPlan = plan;

        setText('detail_plan_equipment_name', plan.equipment_name || '');
        setText('detail_plan_title', plan.title || '');
        setText('detail_plan_action_label', plan.action_label || '-');
        setText('detail_plan_from_location', plan.resolved_from_location || '（指定なし）');
        setText('detail_plan_to_location', plan.resolved_to_location || '（指定なし）');
        setText('detail_plan_operator_label', plan.operator_label || '-');
        setText('detail_plan_quantity', plan.formatted_quantity || '数量指定なし');
        setText('detail_plan_witness_staff', plan.witness_staff_name || '指定なし');
        setText('detail_plan_notes', plan.notes || '（メモなし）');

        const datePart = plan.target_date ? plan.target_date.replaceAll('-', '/') : '';
        const timePart = plan.formatted_time_range || '';
        const datetimeText = [datePart, timePart].filter(Boolean).join(' ') || '日時指定なし';
        setText('detail_plan_datetime', datetimeText);

        const phaseBadge = document.getElementById('detail_plan_phase_badge');
        if (phaseBadge) {
            phaseBadge.className = `inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border ${plan.phase_badge_class || 'bg-slate-50 text-slate-700 border-slate-200'}`;
            phaseBadge.textContent = plan.phase_label || '';
        }

        const witnessBadge = document.getElementById('detail_plan_witness_badge');
        if (witnessBadge) {
            witnessBadge.classList.toggle('hidden', !plan.witness_required);
        }

        const statusBadge = document.getElementById('detail_plan_status_badge');
        if (statusBadge) {
            const info = statusBadgeInfo(plan.status, plan.status_label);
            statusBadge.className = info.classes;
            statusBadge.textContent = info.text;
        }

        openModal(detailModal);
    }

    function openCreateModal(trigger) {
        if (!createModal || !createForm || !trigger) return;

        const {
            equipmentId = '',
            equipmentName = '',
            equipmentQuantity = '',
            equipmentIsRental = '0',
            storeUrl = '',
        } = trigger.dataset;

        if (storeUrl) {
            createForm.action = storeUrl;
        }

        createForm.reset();
        const defaultTargetDate = createForm.dataset.defaultTargetDate || '';
        setText('create_plan_equipment_name', equipmentName);
        setValue('create_plan_equipment_id', equipmentId);
        setValue('create_plan_day_phase', 'pre_day');
        setValue('create_plan_action_type', 'staging_move');
        setValue('create_plan_status', 'planned');
        setValue('create_plan_target_date', defaultTargetDate);
        setValue('create_plan_operator_type', equipmentIsRental === '1' ? 'event_company' : 'committee');
        setValue('create_plan_quantity', equipmentQuantity || '');

        const quantityInput = document.getElementById('create_plan_quantity');
        if (quantityInput) {
            quantityInput.placeholder = equipmentQuantity ? `例: ${equipmentQuantity}` : '数量';
        }

        openModal(createModal);
    }

    function openEditModal(plan) {
        if (!editModal || !editForm || !plan) return;

        if (plan.update_url) {
            editForm.action = plan.update_url;
        }

        const defaultTargetDate = editForm.dataset.defaultTargetDate || '';
        setText('edit_plan_equipment_name', plan.equipment_name || '');
        setValue('edit_plan_day_phase', plan.day_phase || 'pre_day');
        setValue('edit_plan_action_type', plan.action_type || 'staging_move');
        setValue('edit_plan_status', plan.status || 'planned');
        setValue('edit_plan_title', plan.title || '');
        setValue('edit_plan_target_date', plan.target_date ? plan.target_date.substring(0, 10) : defaultTargetDate);
        setValue('edit_plan_start_time', plan.start_time ? plan.start_time.substring(0, 5) : '');
        setValue('edit_plan_end_time', plan.end_time ? plan.end_time.substring(0, 5) : '');
        setValue('edit_plan_from_location_id', plan.from_location_id || '');
        setValue('edit_plan_from_location', plan.from_location || '');
        setValue('edit_plan_to_location_id', plan.to_location_id || '');
        setValue('edit_plan_to_location', plan.to_location || '');
        setValue('edit_plan_quantity', plan.quantity ?? '');
        setValue('edit_plan_quantity_note', plan.quantity_note || '');
        setValue('edit_plan_operator_type', plan.operator_type || 'committee');
        setValue('edit_plan_witness_staff_id', plan.witness_staff_id || '');
        setValue('edit_plan_notes', plan.notes || '');

        const witnessCheckbox = document.getElementById('edit_plan_witness_required');
        if (witnessCheckbox) {
            witnessCheckbox.checked = Boolean(plan.witness_required);
        }

        openModal(editModal);
    }

    function switchNewPlanMode(mode) {
        const isPattern = mode === 'pattern';

        if (newPlanStepPanel) {
            newPlanStepPanel.classList.toggle('hidden', isPattern);
        }
        if (newPlanPatternPanel) {
            newPlanPatternPanel.classList.toggle('hidden', !isPattern);
        }

        newPlanModeTabs.forEach((tab) => {
            const isActive = tab.dataset.planModeTab === mode;
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            tab.classList.toggle('bg-indigo-600', isActive);
            tab.classList.toggle('text-white', isActive);
            tab.classList.toggle('shadow-2xs', isActive);
            tab.classList.toggle('bg-white', !isActive);
            tab.classList.toggle('text-slate-600', !isActive);
            tab.classList.toggle('hover:text-slate-900', !isActive);
        });
    }

    function syncNewPlanEquipment() {
        if (!newPlanEquipmentSelect) return;

        const selectedOpt = newPlanEquipmentSelect.options[newPlanEquipmentSelect.selectedIndex];
        const equipmentId = newPlanEquipmentSelect.value || '';

        if (newPlanEquipmentError && equipmentId) {
            newPlanEquipmentError.classList.add('hidden');
        }

        setValue('new_plan_equipment_id', equipmentId);

        const equipmentType = selectedOpt?.dataset.type || '';
        const equipmentQuantity = selectedOpt?.dataset.quantity || '';
        const isRental = selectedOpt?.dataset.isRental === '1';
        const plansCount = Number(selectedOpt?.dataset.plansCount || 0);
        const applyTemplateUrl = selectedOpt?.dataset.applyTemplateUrl || '';

        setValue('new_plan_operator_type', isRental ? 'event_company' : 'committee');
        setValue('new_plan_quantity', equipmentQuantity);

        const quantityInput = document.getElementById('new_plan_quantity');
        if (quantityInput) {
            quantityInput.placeholder = equipmentQuantity ? `例: ${equipmentQuantity}` : '数量';
        }

        if (newPlanPatternForm) {
            newPlanPatternForm.action = applyTemplateUrl;
        }

        const overrideWarning = document.getElementById('new_plan_override_warning');
        const overrideCheckbox = document.getElementById('new_plan_override_existing');
        if (overrideWarning) {
            overrideWarning.classList.toggle('hidden', !equipmentId || plansCount <= 0);
            setText('new_plan_existing_count', String(plansCount));
        }
        if (overrideCheckbox) {
            overrideCheckbox.checked = true;
        }

        const patternSelect = document.getElementById('new_plan_pattern_id');
        if (patternSelect && equipmentType) {
            const matchedOption = Array.from(patternSelect.options).find(
                (opt) => opt.dataset.targetType === equipmentType
            );
            if (matchedOption) {
                patternSelect.value = matchedOption.value;
            }
        }
    }

    function openNewPlanModal() {
        if (!newPlanModal) return;

        if (newPlanStepForm) {
            newPlanStepForm.reset();
            const defaultTargetDate = newPlanStepForm.dataset.defaultTargetDate || '';
            setValue('new_plan_day_phase', 'pre_day');
            setValue('new_plan_action_type', 'staging_move');
            setValue('new_plan_status', 'planned');
            setValue('new_plan_target_date', defaultTargetDate);
        }

        if (newPlanPatternForm) {
            newPlanPatternForm.reset();
        }

        if (newPlanEquipmentSelect) {
            newPlanEquipmentSelect.value = '';
        }

        if (newPlanEquipmentError) {
            newPlanEquipmentError.classList.add('hidden');
        }

        syncNewPlanEquipment();
        switchNewPlanMode('step');
        openModal(newPlanModal);
    }

    function bindNewPlanSubmitValidation(form, requireActionUrl = false) {
        if (!form || !newPlanEquipmentSelect) return;

        form.addEventListener('submit', (e) => {
            const hasEquipment = Boolean(newPlanEquipmentSelect.value);
            const hasAction = !requireActionUrl || Boolean(form.getAttribute('action'));

            if (!hasEquipment || !hasAction) {
                e.preventDefault();
                if (newPlanEquipmentError) {
                    newPlanEquipmentError.classList.remove('hidden');
                }
                newPlanEquipmentSelect.focus();
            }
        });
    }

    function bindLocationSync(prefix) {
        ['from', 'to'].forEach((dir) => {
            const selectEl = document.getElementById(`${prefix}_${dir}_location_id`);
            const inputEl = document.getElementById(`${prefix}_${dir}_location`);
            if (!selectEl || !inputEl) return;

            selectEl.addEventListener('change', () => {
                const opt = selectEl.options[selectEl.selectedIndex];
                if (opt && opt.dataset.name) {
                    inputEl.value = opt.dataset.name;
                }
            });
        });
    }

    document.querySelectorAll('[data-action="open-plan-create"]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            openCreateModal(btn);
        });
    });

    document.querySelectorAll('[data-action="open-plan-detail"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const plan = getPlanFromTrigger(btn);
            openDetailModal(plan);
        });
    });

    document.querySelectorAll('[data-action="open-plan-edit"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const plan = getPlanFromTrigger(btn);
            openEditModal(plan);
        });
    });

    document.querySelectorAll('[data-action="edit-from-detail"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!currentDetailPlan) return;
            closeModal(detailModal);
            openEditModal(currentDetailPlan);
        });
    });

    document.querySelectorAll('[data-action="open-logistics-plan-create"]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openNewPlanModal();
        });
    });

    newPlanModeTabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            switchNewPlanMode(tab.dataset.planModeTab || 'step');
        });
    });

    if (newPlanEquipmentSelect) {
        newPlanEquipmentSelect.addEventListener('change', syncNewPlanEquipment);
    }

    bindNewPlanSubmitValidation(newPlanStepForm, false);
    bindNewPlanSubmitValidation(newPlanPatternForm, true);

    bindLocationSync('create_plan');
    bindLocationSync('edit_plan');
    bindLocationSync('new_plan');

    document.querySelectorAll('[data-action="close-modal"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            closeAllModals();
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
}

