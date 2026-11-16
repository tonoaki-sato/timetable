/**
 * 出展・出店者マスター管理 JavaScript (CSP準拠・外部モジュール)
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. 要素の取得
    const detailModal = document.getElementById('modal-vendor-detail');
    const formModal = document.getElementById('modal-vendor-form');
    const formMutation = document.getElementById('form-vendor-mutation');
    const formMethod = document.getElementById('form-method');
    const formModalTitle = document.getElementById('form-modal-title');

    const inputName = document.getElementById('vendor_name');
    const inputContactPerson = document.getElementById('vendor_contact_person');
    const inputContact = document.getElementById('vendor_contact');
    const inputNotes = document.getElementById('vendor_notes');

    const detailTitle = document.getElementById('detail-modal-title');
    const detailName = document.getElementById('detail-vendor-name');
    const detailContactPerson = document.getElementById('detail-contact-person');
    const detailContact = document.getElementById('detail-contact');
    const detailNotes = document.getElementById('detail-notes');

    // 2. モーダル開閉関数
    const openModal = (modal) => {
        if (modal) {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
    };

    const closeModal = (modal) => {
        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    };

    // 閉じるボタンおよびBackdropクリックの共通イベント
    document.querySelectorAll('.btn-close-modal, .modal-backdrop').forEach((el) => {
        el.addEventListener('click', () => {
            closeModal(detailModal);
            closeModal(formModal);
        });
    });

    // ESCキーでモーダルを閉じる
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal(detailModal);
            closeModal(formModal);
        }
    });

    // 3. 新規登録モーダルを開く
    const btnCreate = document.getElementById('btn-open-create-vendor');
    if (btnCreate) {
        btnCreate.addEventListener('click', () => {
            if (formMutation) {
                formMutation.action = btnCreate.dataset.storeUrl || '';
                formMethod.value = 'POST';
                formModalTitle.textContent = '出展・出店者の新規登録';

                if (inputName) inputName.value = '';
                if (inputContactPerson) inputContactPerson.value = '';
                if (inputContact) inputContact.value = '';
                if (inputNotes) inputNotes.value = '';

                openModal(formModal);
                inputName?.focus();
            }
        });
    }

    // 4. 編集モーダルを開く
    document.querySelectorAll('.btn-edit-vendor').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (formMutation) {
                formMutation.action = btn.dataset.updateUrl || '';
                formMethod.value = 'PUT';
                formModalTitle.textContent = `出展・出店者の編集: ${btn.dataset.name || ''}`;

                if (inputName) inputName.value = btn.dataset.name || '';
                if (inputContactPerson) inputContactPerson.value = btn.dataset.contactPerson || '';
                if (inputContact) inputContact.value = btn.dataset.contact || '';
                if (inputNotes) inputNotes.value = btn.dataset.notes || '';

                openModal(formModal);
                inputName?.focus();
            }
        });
    });

    // 5. 閲覧専用詳細モーダルを開く
    document.querySelectorAll('.btn-view-vendor').forEach((btn) => {
        btn.addEventListener('click', () => {
            const name = btn.dataset.vendorName || '';
            const contactPerson = btn.dataset.contactPerson || '—';
            const contact = btn.dataset.contact || '—';
            const notes = btn.dataset.notes || '—';

            if (detailTitle) detailTitle.textContent = `出展・出店者詳細: ${name}`;
            if (detailName) detailName.textContent = name;
            if (detailContactPerson) detailContactPerson.textContent = contactPerson;
            if (detailContact) detailContact.textContent = contact;
            if (detailNotes) detailNotes.textContent = notes;

            openModal(detailModal);
        });
    });

    // 6. 削除確認ダイアログ
    document.querySelectorAll('.btn-delete-vendor').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            const vendorName = btn.dataset.vendorName || 'この出展者';
            if (confirm(`出展・出店者「${vendorName}」を削除してもよろしいですか？\n※ 割当済みのコマがある場合、割当が解除されます。`)) {
                btn.closest('form')?.submit();
            }
        });
    });
});
