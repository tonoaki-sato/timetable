/**
 * コマ割り・現場配置管理 JavaScript (CSP完全準拠・Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', () => {
    initTypeFilter();
    initBoothDetailModal();
    initItemFormModal();
    initDeleteConfirmations();
});

/**
 * 表示種別クイックチップ (すべて / 出店ブース / 現場設備)
 */
function initTypeFilter() {
    const filterGroup = document.getElementById('item-type-filter-group');
    const hiddenItemType = document.getElementById('hiddenItemType');
    const filterForm = document.getElementById('filterForm');

    if (!filterGroup || !hiddenItemType || !filterForm) return;

    filterGroup.querySelectorAll('.type-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const typeFilter = btn.dataset.typeFilter || '';
            hiddenItemType.value = typeFilter === 'all' ? '' : typeFilter;
            filterForm.submit();
        });
    });
}

/**
 * 閲覧専用詳細モーダルの制御
 */
function initBoothDetailModal() {
    const detailModal = document.getElementById('modal-item-detail');
    if (!detailModal) return;

    const statusIcon = document.getElementById('detail-status-icon');
    const itemName = document.getElementById('detail-name');
    const itemCode = document.getElementById('detail-code');
    const areaName = document.getElementById('detail-area-name');
    const statusLabel = document.getElementById('detail-status-label');
    const typeLabel = document.getElementById('detail-type-label');
    const subTypeLabel = document.getElementById('detail-sub-type-label');
    const vendorSection = document.getElementById('detail-vendor-section');
    const vendorName = document.getElementById('detail-vendor-name');
    const vendorContactPerson = document.getElementById('detail-vendor-contact-person');
    const vendorContact = document.getElementById('detail-vendor-contact');
    const location = document.getElementById('detail-location');
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

    document.querySelectorAll('.btn-view-item').forEach(btn => {
        btn.addEventListener('click', () => {
            const data = btn.dataset;

            if (statusIcon) statusIcon.textContent = data.statusIcon || '🟢';
            if (itemName) itemName.textContent = data.name || '配置詳細';
            if (itemCode) itemCode.textContent = `識別コード: ${data.code || '—'}`;
            if (areaName) areaName.textContent = data.area || '—';
            if (statusLabel) statusLabel.textContent = `${data.statusIcon || '🟢'} ${data.statusLabel || '確定'}`;
            if (typeLabel) typeLabel.textContent = `${data.typeIcon || ''} ${data.typeLabel || '—'}`;
            if (subTypeLabel) subTypeLabel.textContent = data.subTypeLabel || '—';

            // 出展者マスター情報 (ブースのみ)
            if (data.type === 'booth' && data.vendorName) {
                if (vendorSection) vendorSection.classList.remove('hidden');
                if (vendorName) vendorName.textContent = data.vendorName;
                if (vendorContactPerson) {
                    vendorContactPerson.textContent = data.vendorContactPerson ? `担当: ${data.vendorContactPerson}` : '';
                }
                if (vendorContact) {
                    vendorContact.textContent = data.vendorContact ? `連絡先: ${data.vendorContact}` : '';
                }
            } else {
                if (vendorSection) vendorSection.classList.add('hidden');
            }

            if (location) location.textContent = data.location || '—';
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
 * 登録・編集兼用フォームモーダルの制御
 */
function initItemFormModal() {
    const modal = document.getElementById('itemEditModal');
    const form = document.getElementById('itemEditForm');
    if (!modal || !form) return;

    const modalIcon = document.getElementById('modalFormIcon');
    const modalTitle = document.getElementById('modalFormTitle');
    const modalCodeBadge = document.getElementById('modalItemCodeBadge');
    const modalSubmitBtn = document.getElementById('modalSubmitBtn');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const modalCancelBtn = document.getElementById('modalCancelBtn');

    // フォーム入力項目
    const formMethod = document.getElementById('itemFormMethod');
    const inputType = document.getElementById('inputItemType');
    const inputStatus = document.getElementById('inputItemStatus');
    const inputCode = document.getElementById('inputItemCode');
    const selectArea = document.getElementById('selectItemArea');
    const inputName = document.getElementById('inputItemName');
    const rowVendor = document.getElementById('rowVendorSelect');
    const selectVendor = document.getElementById('selectVendor');
    const selectBoothCategory = document.getElementById('selectBoothCategory');
    const selectFacilityType = document.getElementById('selectFacilityType');
    const rowBoothType = document.getElementById('rowBoothTypeSelect');
    const selectBoothType = document.getElementById('selectBoothType');
    const inputLocation = document.getElementById('inputLocation');
    const textareaNotes = document.getElementById('textareaNotes');

    // 種別トグルボタン
    const typeBtnBooth = document.getElementById('typeBtnBooth');
    const typeBtnFacility = document.getElementById('typeBtnFacility');

    // ステータストグルボタン
    const statusBtnConfirmed = document.getElementById('statusBtnConfirmed');
    const statusBtnTentative = document.getElementById('statusBtnTentative');

    const updateTypeUI = (type) => {
        inputType.value = type;
        if (type === 'booth') {
            typeBtnBooth.className = 'item-type-toggle-btn flex-1 py-1.5 px-2 rounded-md text-xs font-bold transition flex items-center justify-center space-x-1 cursor-pointer bg-white text-indigo-700 shadow-2xs border border-indigo-200';
            typeBtnFacility.className = 'item-type-toggle-btn flex-1 py-1.5 px-2 rounded-md text-xs font-bold transition flex items-center justify-center space-x-1 cursor-pointer text-slate-500 hover:text-slate-700 border border-transparent';
            if (rowVendor) rowVendor.classList.remove('hidden');
            if (selectBoothCategory) selectBoothCategory.classList.remove('hidden');
            if (selectFacilityType) selectFacilityType.classList.add('hidden');
            if (rowBoothType) rowBoothType.classList.remove('hidden');
        } else {
            typeBtnBooth.className = 'item-type-toggle-btn flex-1 py-1.5 px-2 rounded-md text-xs font-bold transition flex items-center justify-center space-x-1 cursor-pointer text-slate-500 hover:text-slate-700 border border-transparent';
            typeBtnFacility.className = 'item-type-toggle-btn flex-1 py-1.5 px-2 rounded-md text-xs font-bold transition flex items-center justify-center space-x-1 cursor-pointer bg-white text-cyan-800 shadow-2xs border border-cyan-300';
            if (rowVendor) rowVendor.classList.add('hidden');
            if (selectBoothCategory) selectBoothCategory.classList.add('hidden');
            if (selectFacilityType) selectFacilityType.classList.remove('hidden');
            if (rowBoothType) rowBoothType.classList.add('hidden');
        }
    };

    const updateStatusUI = (status) => {
        inputStatus.value = status;
        if (status === 'confirmed') {
            statusBtnConfirmed.className = 'status-toggle-btn flex-1 py-1.5 px-2 rounded-md text-xs font-bold transition flex items-center justify-center space-x-1 cursor-pointer bg-white text-emerald-700 shadow-2xs border border-emerald-200';
            statusBtnTentative.className = 'status-toggle-btn flex-1 py-1.5 px-2 rounded-md text-xs font-bold transition flex items-center justify-center space-x-1 cursor-pointer text-slate-500 hover:text-slate-700 border border-transparent';
        } else {
            statusBtnConfirmed.className = 'status-toggle-btn flex-1 py-1.5 px-2 rounded-md text-xs font-bold transition flex items-center justify-center space-x-1 cursor-pointer text-slate-500 hover:text-slate-700 border border-transparent';
            statusBtnTentative.className = 'status-toggle-btn flex-1 py-1.5 px-2 rounded-md text-xs font-bold transition flex items-center justify-center space-x-1 cursor-pointer bg-white text-amber-700 shadow-2xs border border-amber-200';
        }
    };

    typeBtnBooth?.addEventListener('click', () => updateTypeUI('booth'));
    typeBtnFacility?.addEventListener('click', () => updateTypeUI('facility'));

    statusBtnConfirmed?.addEventListener('click', () => updateStatusUI('confirmed'));
    statusBtnTentative?.addEventListener('click', () => updateStatusUI('tentative'));

    const closeModal = () => {
        modal.classList.add('hidden');
    };

    modalCloseBtn?.addEventListener('click', closeModal);
    modalCancelBtn?.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    // 新規登録モーダル起動
    const btnCreate = document.getElementById('btnOpenCreateItemModal');
    btnCreate?.addEventListener('click', () => {
        form.action = form.dataset.storeUrl;
        formMethod.value = 'POST';

        if (modalIcon) modalIcon.textContent = '➕';
        if (modalTitle) modalTitle.textContent = '新規配置の登録';
        if (modalCodeBadge) modalCodeBadge.textContent = '';
        if (modalSubmitBtn) modalSubmitBtn.textContent = '➕ 新規登録する';

        // フィールドリセット
        inputCode.value = '';
        selectArea.value = '';
        inputName.value = '';
        if (selectVendor) selectVendor.value = '';
        if (selectBoothCategory) selectBoothCategory.selectedIndex = 0;
        if (selectFacilityType) selectFacilityType.selectedIndex = 0;
        if (selectBoothType) selectBoothType.selectedIndex = 0;
        inputLocation.value = '';
        textareaNotes.value = '';

        updateTypeUI('booth');
        updateStatusUI('confirmed');

        modal.classList.remove('hidden');
        setTimeout(() => inputCode.focus(), 50);
    });

    // 編集モーダル起動
    document.querySelectorAll('[data-action="open-edit-modal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const data = btn.dataset;
            form.action = `${form.dataset.baseUpdateUrl}/${data.id}`;
            formMethod.value = 'PUT';

            if (modalIcon) modalIcon.textContent = '✏️';
            if (modalTitle) modalTitle.textContent = '配置情報の編集';
            if (modalCodeBadge) modalCodeBadge.textContent = `(${data.code})`;
            if (modalSubmitBtn) modalSubmitBtn.textContent = '💾 変更を保存する';

            inputCode.value = data.code || '';
            selectArea.value = data.areaId || '';
            inputName.value = data.name || '';
            inputLocation.value = data.location || '';
            textareaNotes.value = data.notes || '';

            const itemType = data.type || 'booth';
            updateTypeUI(itemType);
            updateStatusUI(data.status || 'confirmed');

            if (itemType === 'booth') {
                if (selectVendor) selectVendor.value = data.vendorId || '';
                if (selectBoothCategory) selectBoothCategory.value = data.category || '';
                if (selectBoothType) selectBoothType.value = data.subType || '';
            } else {
                if (selectFacilityType) selectFacilityType.value = data.subType || '';
            }

            modal.classList.remove('hidden');
            setTimeout(() => inputCode.focus(), 50);
        });
    });

    // フォーム送信前調整: 設備の場合は sub_type_facility の選択値から category と sub_type を設定
    form.addEventListener('submit', () => {
        if (inputType.value === 'facility') {
            const selectedOpt = selectFacilityType.options[selectFacilityType.selectedIndex];
            const cat = selectedOpt ? selectedOpt.dataset.cat : 'water';
            const subType = selectFacilityType.value;

            // category フィールドをセット
            let hiddenCategory = form.querySelector('input[name="category"][data-facility-auto]');
            if (!hiddenCategory) {
                hiddenCategory = document.createElement('input');
                hiddenCategory.type = 'hidden';
                hiddenCategory.name = 'category';
                hiddenCategory.dataset.facilityAuto = 'true';
                form.appendChild(hiddenCategory);
            }
            hiddenCategory.value = cat;

            // sub_type フィールドをセット
            let hiddenSubType = form.querySelector('input[name="sub_type"][data-facility-auto]');
            if (!hiddenSubType) {
                hiddenSubType = document.createElement('input');
                hiddenSubType.type = 'hidden';
                hiddenSubType.name = 'sub_type';
                hiddenSubType.dataset.facilityAuto = 'true';
                form.appendChild(hiddenSubType);
            }
            hiddenSubType.value = subType;

            // ブース用セレクトのnameを一時的に無効化
            if (selectBoothCategory) selectBoothCategory.name = '';
            if (selectBoothType) selectBoothType.name = '';
            if (selectVendor) selectVendor.value = '';
        } else {
            // ブースの場合
            if (selectBoothCategory) selectBoothCategory.name = 'category';
            if (selectBoothType) selectBoothType.name = 'sub_type';

            const autoCat = form.querySelector('input[name="category"][data-facility-auto]');
            if (autoCat) autoCat.remove();
            const autoSub = form.querySelector('input[name="sub_type"][data-facility-auto]');
            if (autoSub) autoSub.remove();
        }
    });
}

/**
 * 削除確認ダイアログ
 */
function initDeleteConfirmations() {
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', (e) => {
            const msg = form.dataset.confirm || '本当に削除しますか？';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });
}
