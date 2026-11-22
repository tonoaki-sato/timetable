/**
 * 拠点タイプ（種別マスタ）管理スクリプト (CSP準拠・外部JavaScriptモジュール)
 */
document.addEventListener('DOMContentLoaded', () => {
    initTypeModal();
});

/**
 * 拠点タイプ 登録・編集モーダルの初期化
 */
function initTypeModal() {
    const modal = document.getElementById('type-modal');
    const form = document.getElementById('type-modal-form');
    const modalTitle = document.getElementById('type-modal-title');
    const methodContainer = document.getElementById('type-form-method');

    if (!modal || !form) {
        return;
    }

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

    document.querySelectorAll('[data-action="close-type-modal"]').forEach((btn) => {
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
