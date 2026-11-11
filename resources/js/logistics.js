/**
 * 搬出入計画一覧（/timetables/{timetable}/logistics）モーダル操作スクリプト (CSP完全準拠・Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', () => {
    initLogisticsPlanModals();
});

function initLogisticsPlanModals() {
    const detailModal = document.getElementById('plan-detail-modal');
    const editModal = document.getElementById('plan-edit-modal');
    const editForm = document.getElementById('plan-edit-form');
    const allModals = [detailModal, editModal].filter(Boolean);

    if (!allModals.length) {
        return;
    }

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

    function openEditModal(plan) {
        if (!editModal || !editForm || !plan) return;

        if (plan.update_url) {
            editForm.action = plan.update_url;
        }

        setText('edit_plan_equipment_name', plan.equipment_name || '');
        setValue('edit_plan_day_phase', plan.day_phase || 'pre_day');
        setValue('edit_plan_action_type', plan.action_type || 'staging_move');
        setValue('edit_plan_status', plan.status || 'planned');
        setValue('edit_plan_title', plan.title || '');
        setValue('edit_plan_display_order', plan.display_order ?? 10);
        setValue('edit_plan_target_date', plan.target_date ? plan.target_date.substring(0, 10) : '');
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

    const fromSelect = document.getElementById('edit_plan_from_location_id');
    const fromInput = document.getElementById('edit_plan_from_location');
    if (fromSelect && fromInput) {
        fromSelect.addEventListener('change', () => {
            const opt = fromSelect.options[fromSelect.selectedIndex];
            if (opt && opt.dataset.name) {
                fromInput.value = opt.dataset.name;
            }
        });
    }

    const toSelect = document.getElementById('edit_plan_to_location_id');
    const toInput = document.getElementById('edit_plan_to_location');
    if (toSelect && toInput) {
        toSelect.addEventListener('change', () => {
            const opt = toSelect.options[toSelect.selectedIndex];
            if (opt && opt.dataset.name) {
                toInput.value = opt.dataset.name;
            }
        });
    }

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

