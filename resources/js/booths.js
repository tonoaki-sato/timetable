/**
 * コマ割り・出店ブース管理 JavaScript (CSP準拠・外部モジュール)
 */
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('boothEditModal');
    const form = document.getElementById('boothEditForm');
    const closeBtn = document.getElementById('modalCloseBtn');
    const cancelBtn = document.getElementById('modalCancelBtn');

    const modalBoothCode = document.getElementById('modalBoothCode');
    const modalAreaName = document.getElementById('modalAreaName');
    const inputBoothName = document.getElementById('inputBoothName');
    const selectBoothCategory = document.getElementById('selectBoothCategory');
    const selectBoothType = document.getElementById('selectBoothType');
    const selectBoothStatus = document.getElementById('selectBoothStatus');
    const selectVendor = document.getElementById('selectVendor');
    const textareaItems = document.getElementById('textareaItems');
    const inputLandmark = document.getElementById('inputLandmark');
    const textareaNotes = document.getElementById('textareaNotes');

    const closeModal = () => {
        if (modal) {
            modal.classList.add('hidden');
        }
    };

    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    document.querySelectorAll('[data-action="open-booth-modal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const timetableId = form?.dataset.timetableId;
            if (modalBoothCode) modalBoothCode.textContent = btn.dataset.code || '';
            if (modalAreaName) modalAreaName.textContent = btn.dataset.area || '通り';
            if (inputBoothName) inputBoothName.value = btn.dataset.name || '';
            if (selectBoothCategory) selectBoothCategory.value = btn.dataset.category || 'goza_cooked_food';
            if (selectBoothType) selectBoothType.value = btn.dataset.type || 'tent';
            if (selectBoothStatus) selectBoothStatus.value = btn.dataset.status || 'confirmed';
            if (selectVendor) selectVendor.value = btn.dataset.vendorId || '';
            if (textareaItems) textareaItems.value = btn.dataset.items || '';
            if (inputLandmark) inputLandmark.value = btn.dataset.landmark || '';
            if (textareaNotes) textareaNotes.value = btn.dataset.notes || '';

            if (form) {
                form.action = `/timetables/${timetableId}/layouts/booths/${id}`;
            }
            if (modal) {
                modal.classList.remove('hidden');
            }
        });
    });
});
