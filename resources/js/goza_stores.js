/**
 * ござ市出店者管理（/timetables/{timetable}/goza-stores）
 * 出店者 登録・編集モーダル操作スクリプト (CSP完全準拠・Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', () => {
    initGozaStoreModal();
    initConfirmDialogs();
});

/**
 * 出店者登録・編集モーダル機能
 */
function initGozaStoreModal() {
    const modal = document.getElementById('storeModal');
    const form = document.getElementById('storeForm');
    if (!modal || !form) {
        return;
    }

    const formMethod = document.getElementById('storeFormMethod');
    const modalIcon = document.getElementById('modalStoreIcon');
    const modalTitle = document.getElementById('modalStoreTitle');
    const modalStoreId = document.getElementById('modalStoreId');
    const nameInput = document.getElementById('inputStoreName');
    const repInput = document.getElementById('inputRepName');
    const contactInput = document.getElementById('inputContact');
    const productsTextarea = document.getElementById('inputProducts');
    const statusSelect = document.getElementById('selectStoreStatus');
    const notesTextarea = document.getElementById('inputStoreNotes');
    const submitBtn = document.getElementById('modalStoreSubmitBtn');
    const openCreateBtn = document.getElementById('btnOpenCreateStoreModal');
    const closeBtn = document.getElementById('storeModalCloseBtn');
    const cancelBtn = document.getElementById('storeModalCancelBtn');

    const baseAction = form.dataset.baseAction || '';
    const storeUrl = form.dataset.storeUrl || '';

    const openModal = () => {
        modal.classList.remove('hidden');
        nameInput?.focus();
    };

    const closeModal = () => {
        modal.classList.add('hidden');
    };

    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);

    // モーダルの外側クリックで閉じる
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Escape キーで閉じる
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    // 新規登録モーダルを開く
    openCreateBtn?.addEventListener('click', () => {
        if (modalIcon) modalIcon.textContent = '🏮';
        if (modalTitle) modalTitle.textContent = 'ござ市出店者の新規登録';
        if (modalStoreId) modalStoreId.textContent = '';
        if (formMethod) formMethod.value = 'POST';
        form.action = storeUrl || openCreateBtn.dataset.storeUrl || form.action;

        if (nameInput) nameInput.value = '';
        if (repInput) repInput.value = '';
        if (contactInput) contactInput.value = '';
        if (productsTextarea) productsTextarea.value = '';
        if (statusSelect) statusSelect.value = 'confirmed';
        if (notesTextarea) notesTextarea.value = '';
        if (submitBtn) submitBtn.textContent = '💾 登録・保存';

        openModal();
    });

    // 編集モーダルを開く（イベントデリゲーション）
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action="open-edit-store-modal"]');
        if (!btn) return;

        const id = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name') || '';
        const rep = btn.getAttribute('data-rep') || '';
        const contact = btn.getAttribute('data-contact') || '';
        const items = btn.getAttribute('data-items') || '';
        const status = btn.getAttribute('data-status') || 'confirmed';
        const notes = btn.getAttribute('data-notes') || '';

        if (modalIcon) modalIcon.textContent = '✏️';
        if (modalTitle) modalTitle.textContent = `出店者の編集: ${name}`;
        if (modalStoreId) modalStoreId.textContent = `(#${id})`;
        if (formMethod) formMethod.value = 'PUT';
        form.action = `${baseAction}/${id}`;

        if (nameInput) nameInput.value = name;
        if (repInput) repInput.value = rep;
        if (contactInput) contactInput.value = contact;
        if (productsTextarea) productsTextarea.value = items;
        if (statusSelect) statusSelect.value = status;
        if (notesTextarea) notesTextarea.value = notes;
        if (submitBtn) submitBtn.textContent = '💾 変更を保存する';

        openModal();
    });
}

/**
 * 削除フォーム等の確認ダイアログハンドラー (CSP準拠)
 */
function initConfirmDialogs() {
    document.addEventListener('submit', (e) => {
        const form = e.target.closest('form[data-confirm]');
        if (!form) return;

        const message = form.getAttribute('data-confirm') || '実行してもよろしいですか？';
        if (!window.confirm(message)) {
            e.preventDefault();
        }
    });
}
