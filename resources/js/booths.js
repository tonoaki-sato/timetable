/**
 * コマ割り・現場配置管理 JavaScript (CSP完全準拠・Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', () => {
    initBoothDetailModal();
    initBoothFormModal();
    initDeleteConfirmations();
});

/**
 * 閲覧専用詳細モーダルの制御
 */
function initBoothDetailModal() {
    const detailModal = document.getElementById('modal-booth-detail');
    if (!detailModal) return;

    const statusIcon = document.getElementById('detail-status-icon');
    const boothName = document.getElementById('detail-booth-name');
    const boothCode = document.getElementById('detail-booth-code');
    const areaName = document.getElementById('detail-area-name');
    const statusLabel = document.getElementById('detail-status-label');
    const categoryLabel = document.getElementById('detail-category-label');
    const typeLabel = document.getElementById('detail-type-label');
    const vendorSection = document.getElementById('detail-vendor-section');
    const vendorEmpty = document.getElementById('detail-vendor-empty');
    const vendorName = document.getElementById('detail-vendor-name');
    const vendorContactPerson = document.getElementById('detail-vendor-contact-person');
    const vendorContact = document.getElementById('detail-vendor-contact');
    const items = document.getElementById('detail-items');
    const landmark = document.getElementById('detail-landmark');
    const notes = document.getElementById('detail-notes');

    const closeDetailModal = () => {
        detailModal.classList.add('hidden');
    };

    detailModal.querySelectorAll('.btn-close-detail-modal').forEach(btn => {
        btn.addEventListener('click', closeDetailModal);
    });

    detailModal.addEventListener('click', (e) => {
        if (e.target === detailModal) {
            closeDetailModal();
        }
    });

    document.querySelectorAll('.btn-view-booth').forEach(btn => {
        btn.addEventListener('click', () => {
            const data = btn.dataset;

            if (statusIcon) statusIcon.textContent = data.statusIcon || '🟢';
            if (boothName) boothName.textContent = data.name || 'コマ詳細';
            if (boothCode) boothCode.textContent = `コマ番号: ${data.code || '—'}`;
            if (areaName) areaName.textContent = data.area || '—';
            if (statusLabel) statusLabel.textContent = `${data.statusIcon || '🟢'} ${data.statusLabel || '確定'}`;
            if (categoryLabel) categoryLabel.textContent = `${data.categoryIcon || ''} ${data.categoryLabel || '—'}`;
            if (typeLabel) typeLabel.textContent = data.typeLabel || '—';

            // 出展者マスター情報
            if (data.vendorName) {
                if (vendorSection) vendorSection.classList.remove('hidden');
                if (vendorEmpty) vendorEmpty.classList.add('hidden');
                if (vendorName) vendorName.textContent = data.vendorName;
                if (vendorContactPerson) {
                    vendorContactPerson.textContent = data.vendorContactPerson ? `担当: ${data.vendorContactPerson}` : '';
                }
                if (vendorContact) {
                    vendorContact.textContent = data.vendorContact ? `連絡先: ${data.vendorContact}` : '';
                }
            } else {
                if (vendorSection) vendorSection.classList.add('hidden');
                if (vendorEmpty) vendorEmpty.classList.remove('hidden');
            }

            if (items) items.textContent = data.items || '—';
            if (landmark) landmark.textContent = data.landmark || '—';
            if (notes) notes.textContent = data.notes || '—';

            detailModal.classList.remove('hidden');
        });
    });

    // ESCキーで閉じる
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !detailModal.classList.contains('hidden')) {
            closeDetailModal();
        }
    });
}

/**
 * 新規追加・編集兼用フォームモーダルの制御
 */
function initBoothFormModal() {
    const modal = document.getElementById('boothEditModal');
    const form = document.getElementById('boothEditForm');
    if (!modal || !form) return;

    const modalIcon = document.getElementById('modalFormIcon');
    const modalTitle = document.getElementById('modalFormTitle');
    const codeBadge = document.getElementById('modalBoothCodeBadge');
    const formMethod = document.getElementById('boothFormMethod');
    const submitBtn = document.getElementById('modalSubmitBtn');

    const inputBoothCode = document.getElementById('inputBoothCode');
    const selectBoothArea = document.getElementById('selectBoothArea');
    const selectVendor = document.getElementById('selectVendor');
    const inputBoothName = document.getElementById('inputBoothName');
    const selectBoothCategory = document.getElementById('selectBoothCategory');
    const selectBoothType = document.getElementById('selectBoothType');
    const inputBoothStatus = document.getElementById('inputBoothStatus');
    const statusBtnConfirmed = document.getElementById('statusBtnConfirmed');
    const statusBtnTentative = document.getElementById('statusBtnTentative');
    const textareaItems = document.getElementById('textareaItems');
    const inputLandmark = document.getElementById('inputLandmark');
    const textareaNotes = document.getElementById('textareaNotes');

    const setBoothStatus = (statusVal) => {
        const val = statusVal === 'tentative' ? 'tentative' : 'confirmed';
        if (inputBoothStatus) inputBoothStatus.value = val;

        if (val === 'confirmed') {
            statusBtnConfirmed?.classList.remove('text-slate-500', 'hover:text-slate-700', 'border-transparent', 'bg-transparent');
            statusBtnConfirmed?.classList.add('bg-white', 'text-emerald-700', 'shadow-2xs', 'border-emerald-200');

            statusBtnTentative?.classList.remove('bg-white', 'text-amber-700', 'shadow-2xs', 'border-amber-200');
            statusBtnTentative?.classList.add('text-slate-500', 'hover:text-slate-700', 'border-transparent', 'bg-transparent');
        } else {
            statusBtnTentative?.classList.remove('text-slate-500', 'hover:text-slate-700', 'border-transparent', 'bg-transparent');
            statusBtnTentative?.classList.add('bg-white', 'text-amber-700', 'shadow-2xs', 'border-amber-200');

            statusBtnConfirmed?.classList.remove('bg-white', 'text-emerald-700', 'shadow-2xs', 'border-emerald-200');
            statusBtnConfirmed?.classList.add('text-slate-500', 'hover:text-slate-700', 'border-transparent', 'bg-transparent');
        }
    };

    statusBtnConfirmed?.addEventListener('click', () => setBoothStatus('confirmed'));
    statusBtnTentative?.addEventListener('click', () => setBoothStatus('tentative'));

    const closeBtn = document.getElementById('modalCloseBtn');
    const cancelBtn = document.getElementById('modalCancelBtn');
    const openCreateBtn = document.getElementById('btnOpenCreateBoothModal');

    const closeModal = () => {
        modal.classList.add('hidden');
    };

    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // 新規登録モーダルを開く
    openCreateBtn?.addEventListener('click', () => {
        if (modalIcon) modalIcon.textContent = '➕';
        if (modalTitle) modalTitle.textContent = '新規配置の追加';
        if (codeBadge) codeBadge.textContent = '';
        if (formMethod) formMethod.value = 'POST';

        const storeUrl = form.dataset.storeUrl || openCreateBtn.dataset.storeUrl || '';
        form.action = storeUrl;

        if (inputBoothCode) {
            inputBoothCode.value = '';
            inputBoothCode.readOnly = false;
        }
        if (selectBoothArea) selectBoothArea.value = '';
        if (selectVendor) selectVendor.value = '';
        if (inputBoothName) inputBoothName.value = '';
        if (selectBoothCategory) selectBoothCategory.value = 'goza_cooked_food';
        if (selectBoothType) selectBoothType.value = 'tent';
        setBoothStatus('confirmed');
        if (textareaItems) textareaItems.value = '';
        if (inputLandmark) inputLandmark.value = '';
        if (textareaNotes) textareaNotes.value = '';

        if (submitBtn) submitBtn.textContent = '➕ 配置を追加する';

        modal.classList.remove('hidden');
        inputBoothCode?.focus();
    });

    // 編集モーダルを開く
    document.querySelectorAll('[data-action="open-booth-modal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const data = btn.dataset;
            const id = data.id;
            const code = data.code || '';
            const baseUpdateUrl = form.dataset.baseUpdateUrl || '';

            if (modalIcon) modalIcon.textContent = '✏️';
            if (modalTitle) modalTitle.textContent = 'コマ情報の編集:';
            if (codeBadge) codeBadge.textContent = code;
            if (formMethod) formMethod.value = 'PUT';

            form.action = `${baseUpdateUrl}/${id}`;

            if (inputBoothCode) {
                inputBoothCode.value = code;
                inputBoothCode.readOnly = false;
            }
            if (selectBoothArea) selectBoothArea.value = data.areaId || '';
            if (selectVendor) selectVendor.value = data.vendorId || '';
            if (inputBoothName) inputBoothName.value = data.name || '';
            if (selectBoothCategory) selectBoothCategory.value = data.category || 'goza_cooked_food';
            if (selectBoothType) selectBoothType.value = data.type || 'tent';
            setBoothStatus(data.status || 'confirmed');
            if (textareaItems) textareaItems.value = data.items || '';
            if (inputLandmark) inputLandmark.value = data.landmark || '';
            if (textareaNotes) textareaNotes.value = data.notes || '';

            if (submitBtn) submitBtn.textContent = '💾 変更を保存する';

            modal.classList.remove('hidden');
            inputBoothName?.focus();
        });
    });

    // ESCキーで閉じる
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
}

/**
 * 削除確認ダイアログの制御
 */
function initDeleteConfirmations() {
    document.addEventListener('submit', (e) => {
        const form = e.target.closest('form[data-confirm]');
        if (form) {
            const message = form.getAttribute('data-confirm') || '削除してもよろしいですか？';
            if (!window.confirm(message)) {
                e.preventDefault();
            }
        }
    });
}
