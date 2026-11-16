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
    const detailBoothsCount = document.getElementById('detail-booths-count');
    const detailBoothsList = document.getElementById('detail-booths-list');

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

            let booths = [];
            try {
                booths = JSON.parse(btn.dataset.booths || '[]');
            } catch (err) {
                booths = [];
            }

            if (detailTitle) detailTitle.textContent = `出展・出店者詳細: ${name}`;
            if (detailName) detailName.textContent = name;
            if (detailContactPerson) detailContactPerson.textContent = contactPerson;
            if (detailContact) detailContact.textContent = contact;
            if (detailNotes) detailNotes.textContent = notes;

            if (detailBoothsCount) {
                detailBoothsCount.textContent = `(${booths.length}箇所展開)`;
            }

            if (detailBoothsList) {
                detailBoothsList.innerHTML = '';
                if (booths.length === 0) {
                    detailBoothsList.innerHTML = `
                        <div class="p-3 text-slate-400 bg-slate-50 border border-slate-200 rounded-lg text-center">
                            現在アサインされている配置ブースはありません。
                        </div>
                    `;
                } else {
                    booths.forEach((b) => {
                        const div = document.createElement('div');
                        div.className = 'p-2.5 bg-white border border-slate-200 rounded-lg shadow-2xs space-y-1';
                        div.innerHTML = `
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-1.5">
                                    <span class="font-mono font-bold text-slate-800 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">${b.code}</span>
                                    <span class="font-bold text-slate-800">${b.name}</span>
                                    <span class="text-[10px] text-slate-500">(${b.area})</span>
                                </div>
                                <span class="px-1.5 py-0.5 rounded text-[10px] border ${b.category_badge}">${b.category_label}</span>
                            </div>
                            <div class="flex items-center space-x-2 text-[11px] text-slate-600">
                                <span>形態: <strong class="text-slate-800">${b.type_label}</strong></span>
                                <span>•</span>
                                <span>状態: <strong class="text-slate-800">${b.status_label}</strong></span>
                                ${b.items ? `<span>•</span><span>品目: <span class="text-slate-700">${b.items}</span></span>` : ''}
                            </div>
                        `;
                        detailBoothsList.appendChild(div);
                    });
                }
            }

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
