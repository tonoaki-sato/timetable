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
 * 表示種別クイックチップ (すべて / 出店ブース / 現場設備) および エリア・区分絞り込み自動サブミット
 */
function initTypeFilter() {
    const filterGroup = document.getElementById('item-type-filter-group');
    const hiddenItemType = document.getElementById('hiddenItemType');
    const filterForm = document.getElementById('filterForm');

    if (!filterForm) return;

    if (filterGroup && hiddenItemType) {
        filterGroup.querySelectorAll('.type-filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const typeFilter = btn.dataset.typeFilter || '';
                hiddenItemType.value = typeFilter === 'all' ? '' : typeFilter;
                filterForm.submit();
            });
        });
    }

    // エリア・区分のセレクトボックス変更時に即時絞り込み
    filterForm.querySelectorAll('select[name="area_id"], select[name="category"]').forEach(select => {
        select.addEventListener('change', () => {
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
    const boothTypeLabel = document.getElementById('detail-booth-type-label');
    const vendorSection = document.getElementById('detail-vendor-section');
    const vendorName = document.getElementById('detail-vendor-name');
    const vendorContactPerson = document.getElementById('detail-vendor-contact-person');
    const vendorContact = document.getElementById('detail-vendor-contact');
    const multipleContainer = document.getElementById('detail-vendor-multiple-booths-container');
    const boothsCount = document.getElementById('detail-vendor-booths-count');
    const boothsList = document.getElementById('detail-vendor-booths-list');
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
            const currentId = Number(data.id);

            if (statusIcon) statusIcon.textContent = data.statusIcon || '🟢';
            if (itemName) itemName.textContent = data.name || '配置詳細';
            if (itemCode) itemCode.textContent = `識別コード: ${data.code || '—'}`;
            if (areaName) areaName.textContent = data.area || '—';
            if (statusLabel) statusLabel.textContent = `${data.statusIcon || '🟢'} ${data.statusLabel || '確定'}`;
            if (typeLabel) typeLabel.textContent = `${data.typeIcon || ''} ${data.typeLabel || '—'}`;
            if (boothTypeLabel) boothTypeLabel.textContent = data.boothTypeLabel || '—';

            // 出展・出店者情報 (ブース時のみ)
            if (data.type === 'booth' && data.vendorName) {
                if (vendorSection) vendorSection.classList.remove('hidden');
                if (vendorName) vendorName.textContent = data.vendorName;
                if (vendorContactPerson) {
                    vendorContactPerson.textContent = data.vendorContactPerson ? `担当: ${data.vendorContactPerson}` : '';
                }
                if (vendorContact) {
                    vendorContact.textContent = data.vendorContact ? `連絡先: ${data.vendorContact}` : '';
                }

                // 複数ブース展開情報のレンダリング（色分け表示）
                let booths = [];
                try {
                    booths = JSON.parse(data.vendorBooths || '[]');
                } catch (e) {
                    booths = [];
                }

                if (multipleContainer && boothsCount && boothsList) {
                    if (booths.length > 1) {
                        multipleContainer.classList.remove('hidden');
                        boothsCount.textContent = booths.length;
                        boothsList.innerHTML = '';

                        booths.forEach(b => {
                            const isCurrent = Number(b.id) === currentId;
                            const card = document.createElement('div');
                            card.className = isCurrent
                                ? 'p-2 rounded-xl bg-indigo-50/90 border border-indigo-300 ring-1 ring-indigo-200 text-xs shadow-2xs'
                                : 'p-2 rounded-xl bg-white border border-slate-200 text-xs hover:bg-slate-50 transition shadow-2xs';

                            card.innerHTML = `
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center space-x-1.5 min-w-0">
                                        <span class="font-mono font-bold ${isCurrent ? 'text-indigo-900' : 'text-slate-800'} text-[11px] px-1.5 py-0.2 bg-white rounded border border-slate-200 shrink-0">
                                            ${b.code}
                                        </span>
                                        <span class="font-bold truncate ${isCurrent ? 'text-indigo-950' : 'text-slate-800'}">
                                            ${b.name || '(名称未設定)'}
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-1 shrink-0">
                                        <span class="px-1.5 py-0.2 rounded text-[9px] border font-bold ${b.status_badge || 'bg-slate-50 text-slate-700 border-slate-300'}">
                                            ${b.status_icon || '🟢'} ${b.status_label || '確定'}
                                        </span>
                                        ${isCurrent 
                                            ? '<span class="px-1.5 py-0.2 rounded text-[9px] bg-indigo-600 text-white font-bold shrink-0">📍 閲覧中</span>' 
                                            : '<span class="px-1.5 py-0.2 rounded text-[9px] bg-slate-100 text-slate-600 shrink-0">他配置</span>'}
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 text-[10px] text-slate-500">
                                    <span>📍 ${b.area || '通り'}</span>
                                    <span>•</span>
                                    <span class="px-1 rounded ${b.category_badge || 'bg-slate-100'}">${b.category_label || ''}</span>
                                    <span>•</span>
                                    <span>${b.type_label || '—'}</span>
                                </div>
                                ${b.notes ? `<div class="text-[10px] text-slate-600 mt-1 pl-1 border-l-2 ${isCurrent ? 'border-indigo-400' : 'border-slate-200'} truncate">${b.notes}</div>` : ''}
                            `;
                            boothsList.appendChild(card);
                        });
                    } else {
                        multipleContainer.classList.add('hidden');
                    }
                }
            } else {
                if (vendorSection) vendorSection.classList.add('hidden');
                if (multipleContainer) multipleContainer.classList.add('hidden');
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
 * 登録・編集兼用フォームモーダルの制御 (2列対称レイアウト)
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
        } else {
            typeBtnBooth.className = 'item-type-toggle-btn flex-1 py-1.5 px-2 rounded-md text-xs font-bold transition flex items-center justify-center space-x-1 cursor-pointer text-slate-500 hover:text-slate-700 border border-transparent';
            typeBtnFacility.className = 'item-type-toggle-btn flex-1 py-1.5 px-2 rounded-md text-xs font-bold transition flex items-center justify-center space-x-1 cursor-pointer bg-white text-cyan-800 shadow-2xs border border-cyan-300';
            if (rowVendor) rowVendor.classList.add('hidden');
            if (selectBoothCategory) selectBoothCategory.classList.add('hidden');
            if (selectFacilityType) selectFacilityType.classList.remove('hidden');
        }
        // ブース形態(selectBoothType)は出店・設備共通で常時有効
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

            if (selectBoothType) selectBoothType.value = data.boothType || 'tent';

            if (itemType === 'booth') {
                if (selectVendor) selectVendor.value = data.vendorId || '';
                if (selectBoothCategory) selectBoothCategory.value = data.category || '';
            } else {
                if (selectFacilityType) selectFacilityType.value = data.category || '';
            }

            modal.classList.remove('hidden');
            setTimeout(() => inputCode.focus(), 50);
        });
    });

    // フォーム送信前調整
    form.addEventListener('submit', () => {
        if (inputType.value === 'facility') {
            // 設備時: category に selectFacilityType の値をセット
            let hiddenCategory = form.querySelector('input[name="category"][data-facility-auto]');
            if (!hiddenCategory) {
                hiddenCategory = document.createElement('input');
                hiddenCategory.type = 'hidden';
                hiddenCategory.name = 'category';
                hiddenCategory.dataset.facilityAuto = 'true';
                form.appendChild(hiddenCategory);
            }
            hiddenCategory.value = selectFacilityType.value;

            // ブース用セレクトを無効化
            if (selectBoothCategory) selectBoothCategory.name = '';
            if (selectVendor) selectVendor.value = '';
        } else {
            // ブース時
            if (selectBoothCategory) selectBoothCategory.name = 'category';

            const autoCat = form.querySelector('input[name="category"][data-facility-auto]');
            if (autoCat) autoCat.remove();
        }
        // selectBoothType (booth_type) は常時送信
        if (selectBoothType) selectBoothType.name = 'booth_type';
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
