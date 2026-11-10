/**
 * 搬出入パターン管理モーダル操作スクリプト (CSP完全準拠・Vanilla JS)
 * パターン作成、パターン詳細表示、ステップ追加、ステップ編集モーダルの開閉制御
 */

document.addEventListener('DOMContentLoaded', () => {
    initEquipmentPatternModals();
});

function initEquipmentPatternModals() {
    const createModal = document.getElementById('pattern-create-modal');
    const detailModal = document.getElementById('pattern-detail-modal');
    const stepCreateModal = document.getElementById('pattern-step-create-modal');
    const stepEditModal = document.getElementById('pattern-step-edit-modal');
    const stepTemplate = document.getElementById('tpl-pattern-step-row');

    const allModals = [createModal, detailModal, stepCreateModal, stepEditModal].filter(Boolean);

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

    // summary 内のクリックが details の開閉をトリガーしないよう伝播を停止
    document.querySelectorAll('summary [data-action], summary form, summary button').forEach((el) => {
        el.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    });

    // ==========================================
    // 1. 新規パターン作成モーダル関連
    // ==========================================
    const createForm = document.getElementById('pattern-create-form');
    const createStepsContainer = document.getElementById('create-pattern-steps-container');

    function reindexCreateStepRows(container) {
        if (!container) return;
        const rows = container.querySelectorAll('.pattern-step-row');
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

    function addCreateStepRow(container, stepData = null) {
        if (!container || !stepTemplate) return;
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
        }

        const removeBtn = row.querySelector('[data-action="remove-pattern-step"]');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                row.remove();
                reindexCreateStepRows(container);
            });
        }

        container.appendChild(row);
        reindexCreateStepRows(container);
    }

    document.querySelectorAll('[data-action="open-pattern-create"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (createForm) {
                createForm.reset();
            }
            if (createStepsContainer) {
                createStepsContainer.innerHTML = '';
                addCreateStepRow(createStepsContainer, {
                    day_phase: 'pre_day',
                    action_type: 'storage_out',
                    title: '前日倉庫出し・現場中継',
                    operator_type: 'committee',
                });
            }
            openModal(createModal);
        });
    });

    document.querySelectorAll('[data-action="add-pattern-step"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const containerSelector = btn.dataset.container;
            const container = containerSelector ? document.querySelector(containerSelector) : null;
            if (container) {
                addCreateStepRow(container);
            }
        });
    });

    if (createForm) {
        createForm.addEventListener('submit', (e) => {
            if (createStepsContainer && createStepsContainer.querySelectorAll('.pattern-step-row').length === 0) {
                e.preventDefault();
                alert('構成ステップを少なくとも1つ登録してください。');
            }
        });
    }

    // ==========================================
    // 2. パターン詳細モーダル関連
    // ==========================================
    const detailName = document.getElementById('detail_pattern_name');
    const detailTargetType = document.getElementById('detail_pattern_target_type');
    const detailDescription = document.getElementById('detail_pattern_description');
    const detailStepsCount = document.getElementById('detail_steps_count');
    const detailStepsTbody = document.getElementById('detail_steps_tbody');

    document.querySelectorAll('[data-action="open-pattern-detail"]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();

            let patternData;
            try {
                patternData = JSON.parse(btn.dataset.pattern);
            } catch (err) {
                console.error('Failed to parse pattern data:', err);
                return;
            }

            if (detailName) detailName.textContent = patternData.name || '';
            if (detailTargetType) detailTargetType.textContent = patternData.target_type_label || patternData.target_type || '';
            if (detailDescription) detailDescription.textContent = patternData.description || '（説明文未設定）';

            if (detailStepsTbody) {
                detailStepsTbody.innerHTML = '';
                const steps = patternData.steps || [];
                if (detailStepsCount) {
                    detailStepsCount.textContent = `（全 ${steps.length} ステップ）`;
                }

                if (steps.length === 0) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td colspan="7" class="px-3.5 py-6 text-center text-slate-400">ステップが登録されていません</td>';
                    detailStepsTbody.appendChild(tr);
                } else {
                    steps.forEach((step, idx) => {
                        const tr = document.createElement('tr');
                        tr.className = 'hover:bg-slate-50/70 transition';

                        const witnessHtml = step.witness_required
                            ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 font-bold text-[10px] border border-rose-200">必須</span>'
                            : '<span class="text-slate-300">-</span>';

                        tr.innerHTML = `
                            <td class="px-3.5 py-2.5 text-center font-mono text-slate-400 font-bold whitespace-nowrap">#${idx + 1}</td>
                            <td class="px-3.5 py-2.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border bg-slate-50 text-slate-700 border-slate-200">
                                    ${escapeHtml(step.phase_label || step.day_phase)}
                                </span>
                            </td>
                            <td class="px-3.5 py-2.5 font-medium text-slate-800 whitespace-nowrap">${escapeHtml(step.action_label || step.action_type)}</td>
                            <td class="px-3.5 py-2.5 font-bold text-slate-900">${escapeHtml(step.title)}</td>
                            <td class="px-3.5 py-2.5 text-slate-700 font-mono text-[11px]">
                                <span class="text-slate-500">${escapeHtml(step.from_location_display || '（未指定）')}</span>
                                <span class="text-indigo-600 font-bold px-1">➔</span>
                                <span class="font-bold text-slate-800">${escapeHtml(step.to_location_display || '（未指定）')}</span>
                            </td>
                            <td class="px-3.5 py-2.5 text-slate-600 whitespace-nowrap">${escapeHtml(step.operator_type_label || step.operator_type)}</td>
                            <td class="px-3.5 py-2.5 text-center whitespace-nowrap">${witnessHtml}</td>
                        `;
                        detailStepsTbody.appendChild(tr);
                    });
                }
            }

            openModal(detailModal);
        });
    });

    // ==========================================
    // 3. ステップ追加モーダル関連
    // ==========================================
    const stepCreateForm = document.getElementById('pattern-step-create-form');
    const stepCreatePatternName = document.getElementById('step_create_pattern_name');

    document.querySelectorAll('[data-action="open-step-create"]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();

            const patternName = btn.dataset.patternName || '';
            const storeUrl = btn.dataset.storeUrl || '';

            if (stepCreatePatternName) stepCreatePatternName.textContent = patternName;
            if (stepCreateForm) {
                stepCreateForm.action = storeUrl;
                stepCreateForm.reset();
            }

            openModal(stepCreateModal);
        });
    });

    // ==========================================
    // 4. ステップ編集モーダル関連
    // ==========================================
    const stepEditForm = document.getElementById('pattern-step-edit-form');
    const stepEditPatternName = document.getElementById('step_edit_pattern_name');
    const stepEditDayPhase = document.getElementById('step_edit_day_phase');
    const stepEditActionType = document.getElementById('step_edit_action_type');
    const stepEditOperatorType = document.getElementById('step_edit_operator_type');
    const stepEditTitle = document.getElementById('step_edit_title');
    const stepEditFromLoc = document.getElementById('step_edit_from_location_id');
    const stepEditToLoc = document.getElementById('step_edit_to_location_id');
    const stepEditDisplayOrder = document.getElementById('step_edit_display_order');
    const stepEditWitness = document.getElementById('step_edit_witness_required');

    document.querySelectorAll('[data-action="open-step-edit"]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();

            let stepData;
            try {
                stepData = JSON.parse(btn.dataset.step);
            } catch (err) {
                console.error('Failed to parse step data:', err);
                return;
            }

            if (stepEditPatternName) stepEditPatternName.textContent = stepData.pattern_name || '';
            if (stepEditForm && stepData.update_url) {
                stepEditForm.action = stepData.update_url;
            }

            if (stepEditDayPhase) stepEditDayPhase.value = stepData.day_phase || 'pre_day';
            if (stepEditActionType) stepEditActionType.value = stepData.action_type || 'staging_move';
            if (stepEditOperatorType) stepEditOperatorType.value = stepData.operator_type || 'committee';
            if (stepEditTitle) stepEditTitle.value = stepData.title || '';
            if (stepEditFromLoc) stepEditFromLoc.value = stepData.from_location_id || '';
            if (stepEditToLoc) stepEditToLoc.value = stepData.to_location_id || '';
            if (stepEditDisplayOrder) stepEditDisplayOrder.value = stepData.display_order ?? '';
            if (stepEditWitness) stepEditWitness.checked = Boolean(stepData.witness_required);

            openModal(stepEditModal);
        });
    });

    // ==========================================
    // 5. モーダル閉じる共通操作
    // ==========================================
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

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
