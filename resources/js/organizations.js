/**
 * 組織管理 (Organization Management) JavaScript モジュール
 * - タブ切り替え（組織一覧 ↔ 組織図ビュー）
 * - 組織一覧ドラッグ＆ドロップ並び替え
 * - 詳細モーダル（閲覧専用・JSON取得）
 * - 登録・編集モーダル（2カラム、スタッフ追加/削除、スタッフDnD並び替え）
 * - 削除確認
 * - 厳格なCSP（インラインスクリプト/スタイル禁止）に完全準拠
 */

document.addEventListener('DOMContentLoaded', () => {
    initOrganizationTabs();
    initOrganizationTableReorder();
    initDetailModal();
    initFormModal();
    initDeleteAction();
    initPrintPageActions();
});

/**
 * 0. 印刷ページボタン制御
 */
function initPrintPageActions() {
    const btnPrint = document.getElementById('btn-print-page');
    if (btnPrint) {
        btnPrint.addEventListener('click', () => {
            window.print();
        });
    }

    const btnClose = document.getElementById('btn-close-page');
    if (btnClose) {
        btnClose.addEventListener('click', () => {
            window.close();
        });
    }
}

/**
 * 1. タブ切り替え制御
 */
function initOrganizationTabs() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetId = button.dataset.tabTarget;
            if (!targetId) return;

            // ボタン状態の切り替え
            tabButtons.forEach(btn => {
                btn.classList.remove('border-indigo-600', 'text-indigo-600');
                btn.classList.add('border-transparent', 'text-slate-500');
            });
            button.classList.add('border-indigo-600', 'text-indigo-600');
            button.classList.remove('border-transparent', 'text-slate-500');

            // コンテンツ表示の切り替え
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });
            const targetContent = document.getElementById(targetId);
            if (targetContent) {
                targetContent.classList.remove('hidden');
            }
        });
    });
}

/**
 * 2. 組織一覧テーブルのドラッグ＆ドロップ並び替え
 */
function initOrganizationTableReorder() {
    const tableBody = document.getElementById('organizations-table-body');
    if (!tableBody) return;

    let draggedRow = null;

    tableBody.addEventListener('dragstart', (e) => {
        const row = e.target.closest('[data-unit-id]');
        if (!row) return;

        draggedRow = row;
        row.classList.add('opacity-40', 'bg-indigo-50/70');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', row.dataset.unitId || '');
    });

    tableBody.addEventListener('dragend', () => {
        if (draggedRow) {
            draggedRow.classList.remove('opacity-40', 'bg-indigo-50/70');
        }
        draggedRow = null;
    });

    tableBody.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const targetRow = e.target.closest('[data-unit-id]');
        if (!targetRow || targetRow === draggedRow) return;

        const rect = targetRow.getBoundingClientRect();
        const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
        tableBody.insertBefore(draggedRow, next ? targetRow.nextSibling : targetRow);
    });

    tableBody.addEventListener('drop', async (e) => {
        e.preventDefault();
        if (!draggedRow) return;

        const rows = Array.from(tableBody.querySelectorAll('[data-unit-id]'));
        const ids = rows.map(r => parseInt(r.dataset.unitId, 10)).filter(id => !isNaN(id));

        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';

        try {
            const response = await fetch('/organizations/reorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ ids }),
            });

            if (response.ok) {
                showToast('並び順を更新しました');
            } else {
                console.error('並び順の更新に失敗しました');
            }
        } catch (error) {
            console.error('並び順更新通信エラー:', error);
        }
    });

    // 各行にドラッグ属性付与
    tableBody.querySelectorAll('tr[data-unit-id]').forEach(row => {
        row.setAttribute('draggable', 'true');
    });
}

/**
 * 3. 詳細モーダル（閲覧専用）
 */
function initDetailModal() {
    const modal = document.getElementById('organization-detail-modal');
    if (!modal) return;

    // 開くトリガー
    document.addEventListener('click', async (e) => {
        const trigger = e.target.closest('[data-open-detail-modal]');
        if (!trigger) return;

        const unitId = trigger.dataset.openDetailModal;
        if (!unitId) return;

        try {
            const response = await fetch(`/organizations/${unitId}`, {
                headers: { 'Accept': 'application/json' },
            });
            if (!response.ok) return;

            const data = await response.json();
            const unit = data.unit;

            document.getElementById('detail-modal-name').textContent = unit.name || '-';
            document.getElementById('detail-modal-parent').textContent = data.parent_name || 'なし（最上位）';
            document.getElementById('detail-modal-short-description').textContent = unit.short_description || '-';
            document.getElementById('detail-modal-description').textContent = unit.description || '（詳細説明はありません）';

            const staffContainer = document.getElementById('detail-modal-staff-container');
            staffContainer.innerHTML = '';
            if (data.staff && data.staff.length > 0) {
                data.staff.forEach(staff => {
                    const badge = document.createElement('span');
                    badge.className = 'px-2.5 py-1 bg-white text-slate-800 border border-slate-200 rounded-lg text-xs font-medium shadow-xs';
                    badge.textContent = staff.name;
                    staffContainer.appendChild(badge);
                });
            } else {
                staffContainer.innerHTML = '<span class="text-slate-400 italic text-xs">担当スタッフは設定されていません</span>';
            }

            modal.classList.remove('hidden');
        } catch (err) {
            console.error('詳細データの取得に失敗しました:', err);
        }
    });

    // 閉じるトリガー
    modal.querySelectorAll('[data-close-detail-modal]').forEach(el => {
        el.addEventListener('click', () => {
            modal.classList.add('hidden');
        });
    });

    // 背景クリックで閉じる
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
}

/**
 * 4. 登録・編集モーダル（スタッフDnD並び替え対応）
 */
function initFormModal() {
    const modal = document.getElementById('organization-form-modal');
    const form = document.getElementById('organization-form');
    if (!modal || !form) return;

    const btnOpenCreate = document.getElementById('btn-open-create-modal');
    const staffListContainer = document.getElementById('form-staff-list');
    const btnAddStaffRow = document.getElementById('btn-add-staff-row');
    const errorAlert = document.getElementById('form-error-alert');

    // 新規登録モーダルを開く
    if (btnOpenCreate) {
        btnOpenCreate.addEventListener('click', () => {
            form.reset();
            document.getElementById('form-unit-id').value = '';
            document.getElementById('form-method-override').value = 'POST';
            document.getElementById('form-modal-title').textContent = '組織・係の登録';
            document.getElementById('btn-submit-text').textContent = '登録する';
            form.action = '/organizations';
            errorAlert.classList.add('hidden');
            staffListContainer.innerHTML = '';

            const parentSelect = document.getElementById('form-parent-id');
            if (parentSelect) {
                Array.from(parentSelect.options).forEach(opt => {
                    opt.disabled = false;
                });
            }

            modal.classList.remove('hidden');
        });
    }

    // 編集モーダルを開く
    document.addEventListener('click', async (e) => {
        const trigger = e.target.closest('[data-open-edit-modal]');
        if (!trigger) return;

        const unitId = trigger.dataset.openEditModal;
        if (!unitId) return;

        try {
            const response = await fetch(`/organizations/${unitId}/edit`, {
                headers: { 'Accept': 'application/json' },
            });
            if (!response.ok) return;

            const data = await response.json();
            const unit = data.unit;

            form.reset();
            document.getElementById('form-unit-id').value = unit.id;
            document.getElementById('form-method-override').value = 'PUT';
            document.getElementById('form-modal-title').textContent = '組織・係の編集';
            document.getElementById('btn-submit-text').textContent = '更新する';
            form.action = `/organizations/${unit.id}`;
            errorAlert.classList.add('hidden');

            document.getElementById('form-parent-id').value = unit.parent_id || '';
            const parentSelect = document.getElementById('form-parent-id');
            if (parentSelect) {
                Array.from(parentSelect.options).forEach(opt => {
                    opt.disabled = (opt.value === String(unit.id));
                });
            }

            document.getElementById('form-name').value = unit.name || '';
            document.getElementById('form-short-description').value = unit.short_description || '';
            document.getElementById('form-description').value = unit.description || '';

            // 担当スタッフ行の生成
            staffListContainer.innerHTML = '';
            if (data.staff && data.staff.length > 0) {
                data.staff.forEach(staff => {
                    addStaffRow(staff.id);
                });
            }

            modal.classList.remove('hidden');
        } catch (err) {
            console.error('編集データの取得に失敗しました:', err);
        }
    });

    // スタッフ行の追加
    if (btnAddStaffRow) {
        btnAddStaffRow.addEventListener('click', () => {
            addStaffRow();
        });
    }

    // スタッフ行追加関数
    function addStaffRow(selectedStaffId = '') {
        const template = document.getElementById('staff-options-template');
        if (!template) return;

        const row = document.createElement('div');
        row.className = 'staff-row-item flex items-center space-x-2 p-1.5 bg-white rounded-xl border border-slate-200 shadow-2xs group cursor-default';
        row.setAttribute('draggable', 'true');

        row.innerHTML = `
            <span class="staff-drag-handle text-slate-300 hover:text-slate-600 cursor-grab active:cursor-grabbing text-base px-1" title="ドラッグして並び替え">
                ⠿
            </span>
            <select name="staff_ids[]" class="flex-1 py-1.5 px-2.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-hidden focus:ring-1 focus:ring-indigo-500">
                ${template.innerHTML}
            </select>
            <button type="button" class="btn-remove-staff p-1 text-slate-400 hover:text-red-500 rounded-md transition cursor-pointer" title="削除">
                ✕
            </button>
        `;

        if (selectedStaffId) {
            const select = row.querySelector('select');
            if (select) {
                select.value = selectedStaffId;
            }
        }

        // 削除ボタンイベント
        row.querySelector('.btn-remove-staff').addEventListener('click', () => {
            row.remove();
        });

        staffListContainer.appendChild(row);
    }

    // モーダル内スタッフリストのDnD並び替え
    let draggedStaffRow = null;

    staffListContainer.addEventListener('dragstart', (e) => {
        const row = e.target.closest('.staff-row-item');
        if (!row) return;

        draggedStaffRow = row;
        row.classList.add('opacity-40', 'bg-indigo-50');
        e.dataTransfer.effectAllowed = 'move';
    });

    staffListContainer.addEventListener('dragend', () => {
        if (draggedStaffRow) {
            draggedStaffRow.classList.remove('opacity-40', 'bg-indigo-50');
        }
        draggedStaffRow = null;
    });

    staffListContainer.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const targetRow = e.target.closest('.staff-row-item');
        if (!targetRow || targetRow === draggedStaffRow) return;

        const rect = targetRow.getBoundingClientRect();
        const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
        staffListContainer.insertBefore(draggedStaffRow, next ? targetRow.nextSibling : targetRow);
    });

    // 閉じるトリガー
    modal.querySelectorAll('[data-close-form-modal]').forEach(el => {
        el.addEventListener('click', () => {
            modal.classList.add('hidden');
        });
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });

    // ESCキーでモーダルを閉じる共通処理
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            modal.classList.add('hidden');
            const detailModal = document.getElementById('organization-detail-modal');
            if (detailModal) detailModal.classList.add('hidden');
        }
    });
}

/**
 * 5. 削除確認・実行
 */
function initDeleteAction() {
    const deleteForm = document.getElementById('delete-organization-form');
    if (!deleteForm) return;

    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-delete-unit]');
        if (!trigger) return;

        const unitId = trigger.dataset.deleteUnit;
        const unitName = trigger.dataset.unitName || '該当組織';
        if (!unitId) return;

        const confirmed = window.confirm(`組織「${unitName}」を削除してもよろしいですか？\n※配下に属する子組織もすべて連動して削除されます。`);
        if (!confirmed) return;

        deleteForm.action = `/organizations/${unitId}`;
        deleteForm.submit();
    });
}

/**
 * トースト通知の表示
 */
function showToast(message) {
    const toast = document.getElementById('reorder-toast');
    const toastMessage = document.getElementById('reorder-toast-message');
    if (!toast) return;

    if (toastMessage) toastMessage.textContent = message;
    toast.classList.remove('hidden');

    setTimeout(() => {
        toast.classList.add('hidden');
    }, 2500);
}
