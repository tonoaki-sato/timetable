/**
 * 搬出入パターン管理モーダル操作スクリプト (CSP完全準拠・Vanilla JS)
 * 新規作成・編集モーダルの開閉、ステップ行の動的追加・削除・インデックス再採番
 */

document.addEventListener('DOMContentLoaded', () => {
    initEquipmentPatternModals();
});

function initEquipmentPatternModals() {
    const createModal = document.getElementById('pattern-create-modal');
    const editModal = document.getElementById('pattern-edit-modal');
    const stepTemplate = document.getElementById('tpl-pattern-step-row');

    if (!createModal && !editModal) {
        return;
    }

    const createForm = document.getElementById('pattern-create-form');
    const createStepsContainer = document.getElementById('create-pattern-steps-container');

    const editForm = document.getElementById('pattern-edit-form');
    const editStepsContainer = document.getElementById('edit-pattern-steps-container');
    const editNameInput = document.getElementById('edit_pattern_name');
    const editTargetTypeSelect = document.getElementById('edit_pattern_target_type');
    const editDescriptionInput = document.getElementById('edit_pattern_description');

    /**
     * ステップ行のインデックスを再採番
     * @param {HTMLElement} container
     */
    function reindexStepRows(container) {
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

    /**
     * ステップ行をコンテナに追加
     * @param {HTMLElement} container
     * @param {Object|null} stepData
     */
    function addStepRow(container, stepData = null) {
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
            setField('notes', stepData.notes || '');
        }

        const removeBtn = row.querySelector('[data-action="remove-pattern-step"]');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                row.remove();
                reindexStepRows(container);
            });
        }

        container.appendChild(row);
        reindexStepRows(container);
    }

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
        if (createModal) closeModal(createModal);
        if (editModal) closeModal(editModal);
    }

    // 1. 新規作成モーダル起動
    document.querySelectorAll('[data-action="open-pattern-create"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (createForm) {
                createForm.reset();
            }
            if (createStepsContainer) {
                createStepsContainer.innerHTML = '';
                addStepRow(createStepsContainer, {
                    day_phase: 'pre_day',
                    action_type: 'storage_out',
                    title: '前日倉庫出し・現場中継',
                    operator_type: 'committee',
                });
            }
            openModal(createModal);
        });
    });

    // 2. 編集モーダル起動
    document.querySelectorAll('[data-action="open-pattern-edit"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const rowWrapper = btn.closest('[data-pattern-row]');
            if (!rowWrapper || !rowWrapper.dataset.pattern) return;

            let patternData;
            try {
                patternData = JSON.parse(rowWrapper.dataset.pattern);
            } catch (e) {
                console.error('Failed to parse pattern data:', e);
                return;
            }

            if (editForm && patternData.update_url) {
                editForm.action = patternData.update_url;
            }
            if (editNameInput) {
                editNameInput.value = patternData.name || '';
            }
            if (editTargetTypeSelect) {
                editTargetTypeSelect.value = patternData.target_type || 'committee';
            }
            if (editDescriptionInput) {
                editDescriptionInput.value = patternData.description || '';
            }

            if (editStepsContainer) {
                editStepsContainer.innerHTML = '';
                if (patternData.steps && patternData.steps.length > 0) {
                    patternData.steps.forEach((step) => {
                        addStepRow(editStepsContainer, step);
                    });
                } else {
                    addStepRow(editStepsContainer);
                }
            }

            openModal(editModal);
        });
    });

    // 3. ステップ追加ボタン
    document.querySelectorAll('[data-action="add-pattern-step"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const containerSelector = btn.dataset.container;
            const container = containerSelector ? document.querySelector(containerSelector) : null;
            if (container) {
                addStepRow(container);
            }
        });
    });

    // 4. モーダル閉じる操作 (閉じるボタン・背景クリック)
    document.querySelectorAll('[data-action="close-modal"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            closeAllModals();
        });
    });

    // ESCキー押下で閉じる
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });

    // 5. フォーム送信時バリデーション（最低1ステップ存在）
    [createForm, editForm].forEach((form) => {
        if (!form) return;
        form.addEventListener('submit', (e) => {
            const container = form.querySelector('#create-pattern-steps-container, #edit-pattern-steps-container');
            if (container && container.querySelectorAll('.pattern-step-row').length === 0) {
                e.preventDefault();
                alert('構成ステップを少なくとも1つ登録してください。');
            }
        });
    });
}

