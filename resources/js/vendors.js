/**
 * 商業出店者管理（/timetables/{timetable}/vendors）
 * 出店者 詳細モーダル・登録/編集モーダル操作スクリプト (CSP完全準拠・Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', () => {
    initVendorDetailModal();
    initVendorEditModal();
    initConfirmDialogs();
});

/**
 * 出店者詳細モーダル機能
 */
function initVendorDetailModal() {
    const modal = document.getElementById('vendorDetailModal');
    if (!modal) return;

    const closeBtn = document.getElementById('detailModalCloseBtn');
    const bottomCloseBtn = document.getElementById('detailModalBottomCloseBtn');
    const editFromDetailBtn = document.getElementById('btnEditFromDetail');

    const nameEl = document.getElementById('detailVendorName');
    const repEl = document.getElementById('detailRepName');
    const contactEl = document.getElementById('detailContact');
    const notesEl = document.getElementById('detailNotes');
    const countEl = document.getElementById('detailBoothsCount');
    const boothsContainer = document.getElementById('detailBoothsContainer');

    const closeModal = () => modal.classList.add('hidden');
    const openModal = () => modal.classList.remove('hidden');

    closeBtn?.addEventListener('click', closeModal);
    bottomCloseBtn?.addEventListener('click', closeModal);

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action="open-vendor-detail"]');
        if (!btn) return;

        const id = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name') || '';
        const rep = btn.getAttribute('data-rep') || '（未登録）';
        const contact = btn.getAttribute('data-contact') || '';
        const notes = btn.getAttribute('data-notes') || '（特記事項なし）';
        let booths = [];
        try {
            booths = JSON.parse(btn.getAttribute('data-booths') || '[]');
        } catch {
            booths = [];
        }

        if (nameEl) nameEl.textContent = name;
        if (repEl) repEl.textContent = rep;
        if (contactEl) {
            contactEl.textContent = contact || '（未登録）';
            if (contact) {
                contactEl.href = `tel:${contact.replace(/[^0-9+]/g, '')}`;
                contactEl.classList.remove('pointer-events-none', 'text-slate-600');
                contactEl.classList.add('text-indigo-600', 'hover:underline');
            } else {
                contactEl.removeAttribute('href');
                contactEl.classList.add('pointer-events-none', 'text-slate-600');
                contactEl.classList.remove('text-indigo-600', 'hover:underline');
            }
        }
        if (notesEl) notesEl.textContent = notes;
        if (countEl) countEl.textContent = `${booths.length} コマ確保済`;

        if (boothsContainer) {
            boothsContainer.innerHTML = '';
            if (booths.length === 0) {
                boothsContainer.innerHTML = `
                    <div class="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-xs flex items-center space-x-2">
                        <span>⚠️</span>
                        <span>現在アサインされているコマはありません（未割当）。</span>
                    </div>
                `;
            } else {
                booths.forEach((b) => {
                    const card = document.createElement('div');
                    card.className = 'p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5 text-xs';
                    card.innerHTML = `
                        <div class="flex flex-wrap items-center justify-between gap-1">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 font-mono font-bold rounded text-xs">
                                    ${escapeHtml(b.booth_code)}
                                </span>
                                <span class="font-bold text-slate-800">${escapeHtml(b.area_name || '特設エリア')}</span>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold border ${escapeHtml(b.category_badge || 'bg-slate-100 text-slate-700 border-slate-200')}">
                                ${escapeHtml(b.category_label || '出店')}
                            </span>
                        </div>
                        ${b.adjacent_landmark ? `<div class="text-[11px] text-slate-500">📍 目安: ${escapeHtml(b.adjacent_landmark)}</div>` : ''}
                        <div class="text-[11px] text-slate-700 bg-white p-2 rounded-lg border border-slate-200">
                            <span class="font-bold text-slate-500">販売品目・企画:</span>
                            <span>${escapeHtml(b.product_items || '（品目未指定）')}</span>
                        </div>
                    `;
                    boothsContainer.appendChild(card);
                });
            }
        }

        if (editFromDetailBtn) {
            editFromDetailBtn.setAttribute('data-target-edit-id', id);
        }

        openModal();
    });

    editFromDetailBtn?.addEventListener('click', () => {
        const targetId = editFromDetailBtn.getAttribute('data-target-edit-id');
        closeModal();
        if (targetId) {
            const editTrigger = document.querySelector(`[data-action="open-edit-vendor-modal"][data-id="${targetId}"]`);
            editTrigger?.click();
        }
    });
}

/**
 * 出店者登録・編集モーダル機能
 */
function initVendorEditModal() {
    const modal = document.getElementById('vendorEditModal');
    const form = document.getElementById('vendorEditForm');
    if (!modal || !form) return;

    const formMethod = document.getElementById('vendorFormMethod');
    const modalIcon = document.getElementById('modalVendorIcon');
    const modalTitle = document.getElementById('modalVendorTitle');
    const modalVendorId = document.getElementById('modalVendorId');
    const nameInput = document.getElementById('inputVendorName');
    const repInput = document.getElementById('inputVendorRep');
    const contactInput = document.getElementById('inputVendorContact');
    const notesTextarea = document.getElementById('inputVendorNotes');
    const submitBtn = document.getElementById('modalVendorSubmitBtn');
    const openCreateBtn = document.getElementById('btnOpenCreateVendorModal');
    const closeBtn = document.getElementById('vendorModalCloseBtn');
    const cancelBtn = document.getElementById('vendorModalCancelBtn');

    const createSection = document.getElementById('createBoothSection');
    const editSection = document.getElementById('editBoothsSection');
    const boothsListContainer = document.getElementById('existingBoothsContainer');

    const baseAction = form.dataset.baseAction || '';
    const storeUrl = form.dataset.storeUrl || '';

    const openModal = () => {
        modal.classList.remove('hidden');
        nameInput?.focus();
    };

    const closeModal = () => modal.classList.add('hidden');

    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    // 新規登録モーダルを開く
    openCreateBtn?.addEventListener('click', () => {
        if (modalIcon) modalIcon.textContent = '🏪';
        if (modalTitle) modalTitle.textContent = '新規出店者の登録';
        if (modalVendorId) modalVendorId.textContent = '';
        if (formMethod) formMethod.value = 'POST';
        form.action = storeUrl || openCreateBtn.dataset.storeUrl || form.action;

        if (nameInput) nameInput.value = '';
        if (repInput) repInput.value = '';
        if (contactInput) contactInput.value = '';
        if (notesTextarea) notesTextarea.value = '';
        if (submitBtn) submitBtn.textContent = '💾 登録して保存';

        if (createSection) createSection.classList.remove('hidden');
        if (editSection) editSection.classList.add('hidden');
        if (boothsListContainer) boothsListContainer.innerHTML = '';

        const initialBoothSelect = document.getElementById('selectInitialBooth');
        if (initialBoothSelect) initialBoothSelect.value = '';
        const initialCategorySelect = document.getElementById('selectInitialCategory');
        if (initialCategorySelect) initialCategorySelect.value = 'food_non_cooking';
        const initialItemsInput = document.getElementById('inputInitialItems');
        if (initialItemsInput) initialItemsInput.value = '';

        openModal();
    });

    // 編集モーダルを開く
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action="open-edit-vendor-modal"]');
        if (!btn) return;

        const id = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name') || '';
        const rep = btn.getAttribute('data-rep') || '';
        const contact = btn.getAttribute('data-contact') || '';
        const notes = btn.getAttribute('data-notes') || '';
        let booths = [];
        try {
            booths = JSON.parse(btn.getAttribute('data-booths') || '[]');
        } catch {
            booths = [];
        }

        if (modalIcon) modalIcon.textContent = '✏️';
        if (modalTitle) modalTitle.textContent = `出店者の編集: ${name}`;
        if (modalVendorId) modalVendorId.textContent = `(#${id})`;
        if (formMethod) formMethod.value = 'PUT';
        form.action = `${baseAction}/${id}`;

        if (nameInput) nameInput.value = name;
        if (repInput) repInput.value = rep;
        if (contactInput) contactInput.value = contact;
        if (notesTextarea) notesTextarea.value = notes;
        if (submitBtn) submitBtn.textContent = '💾 変更を保存';

        if (createSection) createSection.classList.add('hidden');
        if (editSection) editSection.classList.remove('hidden');

        // 既存ブースのレンダリング
        if (boothsListContainer) {
            boothsListContainer.innerHTML = '';
            if (booths.length === 0) {
                boothsListContainer.innerHTML = `
                    <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-500 text-[11px]">
                        現在割り当てられているコマはありません。下のセレクトボックスから空きコマを追加割当できます。
                    </div>
                `;
            } else {
                booths.forEach((b, idx) => {
                    const row = document.createElement('div');
                    row.className = 'p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2 text-xs';
                    row.innerHTML = `
                        <input type="hidden" name="booths[${idx}][id]" value="${escapeHtml(b.id)}">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-1.5">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 font-mono font-bold rounded">
                                    ${escapeHtml(b.booth_code)}
                                </span>
                                <span class="font-bold text-slate-700">${escapeHtml(b.area_name || '')}</span>
                            </div>
                            <label class="inline-flex items-center space-x-1 text-red-600 cursor-pointer font-bold text-[11px] hover:text-red-700">
                                <input type="checkbox" name="booths[${idx}][unassign]" value="1" class="rounded border-red-300 text-red-600 focus:ring-red-500">
                                <span>このコマの割当を解除</span>
                            </label>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-0.5">出店区分</label>
                                <select name="booths[${idx}][category]" class="w-full bg-white border border-slate-300 rounded-lg p-1.5 text-xs text-slate-800">
                                    <option value="food_cooking" ${b.category === 'food_cooking' ? 'selected' : ''}>調理屋外食品（赤枠）</option>
                                    <option value="food_non_cooking" ${b.category === 'food_non_cooking' ? 'selected' : ''}>非調理・加工食品（緑枠）</option>
                                    <option value="general_merchandise" ${b.category === 'general_merchandise' ? 'selected' : ''}>一般物販</option>
                                    <option value="corporate_kitchen_car" ${b.category === 'corporate_kitchen_car' ? 'selected' : ''}>企業ブース（キッチンカー）</option>
                                    <option value="corporate_tent" ${b.category === 'corporate_tent' ? 'selected' : ''}>企業ブース（テント）</option>
                                    <option value="special_event" ${b.category === 'special_event' ? 'selected' : ''}>特設企画</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-0.5">このコマでの取扱品目</label>
                                <input type="text" name="booths[${idx}][product_items]" value="${escapeHtml(b.product_items || '')}" placeholder="例: 焼きそば、フランクフルト"
                                    class="w-full bg-white border border-slate-300 rounded-lg p-1.5 text-xs text-slate-800">
                            </div>
                        </div>
                    `;
                    boothsListContainer.appendChild(row);
                });
            }
        }

        const addBoothSelect = document.getElementById('selectAddBooth');
        if (addBoothSelect) addBoothSelect.value = '';
        const addCategorySelect = document.getElementById('selectAddCategory');
        if (addCategorySelect) addCategorySelect.value = 'food_non_cooking';
        const addItemsInput = document.getElementById('inputAddItems');
        if (addItemsInput) addItemsInput.value = '';

        openModal();
    });
}

/**
 * 削除確認ダイアログ
 */
function initConfirmDialogs() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            const message = form.getAttribute('data-confirm');
            if (message && !window.confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
