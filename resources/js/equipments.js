/**
 * 資材管理スクリプト (CSP準拠・外部JavaScriptモジュール)
 */
document.addEventListener('DOMContentLoaded', () => {
    initEquipmentDragAndDropReorder();
    initEquipmentForm();
});

/**
 * 1. 資材新規作成・編集フォーム (equipments.create, equipments.edit)
 */
function initEquipmentForm() {
    const typeSelect = document.querySelector('select[name="type"]');
    const commSec = document.getElementById('section-committee');

    function toggleEquipmentType(type) {
        if (!commSec) return;
        const storageInput = document.getElementById('storage_location');
        if (type === 'rental') {
            commSec.classList.add('hidden');
            if (storageInput) storageInput.disabled = true;
        } else {
            commSec.classList.remove('hidden');
            if (storageInput) storageInput.disabled = false;
        }
    }

    // 資材種別セレクト切り替え
    if (typeSelect) {
        typeSelect.addEventListener('change', (e) => {
            toggleEquipmentType(e.target.value);
        });
    }

    // 定常保管場所のクイック設定ボタン
    document.querySelectorAll('[data-set-storage-location]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const storageInput = document.getElementById('storage_location');
            if (storageInput) {
                storageInput.value = btn.dataset.setStorageLocation || '';
            }
        });
    });

    // 初期状態反映
    if (typeSelect) {
        toggleEquipmentType(typeSelect.value);
    }
}

/**
 * 2. 備品一覧 ドラッグ＆ドロップによる表示順並び替え
 */
function initEquipmentDragAndDropReorder() {
    const container = document.getElementById('equipments-container');
    if (!container) return;

    let draggedItem = null;

    container.addEventListener('dragstart', (e) => {
        const item = e.target.closest('[data-equipment-id]');
        if (!item) return;

        draggedItem = item;
        item.classList.add('opacity-40', 'bg-indigo-50/70');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', item.dataset.equipmentId || '');
    });

    container.addEventListener('dragend', (e) => {
        const item = e.target.closest('[data-equipment-id]');
        if (item) {
            item.classList.remove('opacity-40', 'bg-indigo-50/70');
        }
        draggedItem = null;
    });

    container.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const targetItem = e.target.closest('[data-equipment-id]');
        if (!targetItem || targetItem === draggedItem) return;

        const rect = targetItem.getBoundingClientRect();
        const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
        container.insertBefore(draggedItem, next ? targetItem.nextSibling : targetItem);
    });

    container.addEventListener('drop', async (e) => {
        e.preventDefault();
        if (!draggedItem) return;

        const items = Array.from(container.querySelectorAll('[data-equipment-id]'));
        const equipmentIds = items.map(el => parseInt(el.dataset.equipmentId, 10)).filter(Boolean);
        const reorderUrl = container.dataset.reorderUrl;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!reorderUrl || !csrfToken) return;

        try {
            const res = await fetch(reorderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ equipment_ids: equipmentIds }),
            });

            const data = await res.json();
            if (data.success) {
                showToast(data.message || '備品の表示順を更新しました。', 'success');
            } else {
                showToast(data.message || '表示順の更新に失敗しました。', 'error');
            }
        } catch (err) {
            console.error('Reorder error:', err);
            showToast('通信エラーが発生しました。', 'error');
        }
    });
}

function showToast(message, type = 'success') {
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'fixed bottom-5 right-5 z-50 flex flex-col space-y-2 pointer-events-none';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-emerald-800' : 'bg-rose-800';
    toast.className = `${bgColor} text-white px-4 py-2.5 rounded-xl text-xs font-bold shadow-lg transition-all transform translate-y-2 opacity-0 pointer-events-auto flex items-center space-x-2`;
    toast.innerHTML = `<span>${type === 'success' ? '✅' : '⚠️'}</span><span>${message}</span>`;

    toastContainer.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    });

    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
