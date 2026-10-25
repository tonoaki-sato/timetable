/**
 * レーン管理機能 JavaScript (CSP完全準拠)
 */

document.addEventListener('DOMContentLoaded', () => {
    initDragAndDropReorder();
    initDeleteMigrationModal();
});

/**
 * 1. ドラッグ＆ドロップによる表示順並び替え
 */
function initDragAndDropReorder() {
    const container = document.getElementById('lanes-container');
    if (!container) return;

    let draggedItem = null;

    container.addEventListener('dragstart', (e) => {
        const item = e.target.closest('[data-lane-id]');
        if (!item) return;

        draggedItem = item;
        item.classList.add('opacity-40', 'bg-indigo-50/70');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', item.dataset.laneId || '');
    });

    container.addEventListener('dragend', (e) => {
        const item = e.target.closest('[data-lane-id]');
        if (item) {
            item.classList.remove('opacity-40', 'bg-indigo-50/70');
        }
        draggedItem = null;
    });

    container.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const targetItem = e.target.closest('[data-lane-id]');
        if (!targetItem || targetItem === draggedItem) return;

        const rect = targetItem.getBoundingClientRect();
        const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
        container.insertBefore(draggedItem, next ? targetItem.nextSibling : targetItem);
    });

    container.addEventListener('drop', async (e) => {
        e.preventDefault();
        if (!draggedItem) return;

        const items = Array.from(container.querySelectorAll('[data-lane-id]'));
        const laneIds = items.map(el => parseInt(el.dataset.laneId, 10)).filter(Boolean);
        const reorderUrl = container.dataset.reorderUrl;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!reorderUrl || !csrfToken) return;

        // 連番バッジの仮更新
        items.forEach((el, idx) => {
            const badge = el.querySelector('.order-badge');
            if (badge) badge.textContent = `#${(idx + 1) * 10}`;
        });

        try {
            const res = await fetch(reorderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ lane_ids: laneIds }),
            });

            const data = await res.json();
            if (data.success) {
                showToast(data.message || '並び順を更新しました。', 'success');
            } else {
                showToast(data.message || '並び順の更新に失敗しました。', 'error');
            }
        } catch (err) {
            console.error('Reorder error:', err);
            showToast('通信エラーが発生しました。', 'error');
        }
    });
}

/**
 * 2. タスク移行付き削除モーダル制御
 */
function initDeleteMigrationModal() {
    const modal = document.getElementById('delete-migration-modal');
    if (!modal) return;

    const modalForm = document.getElementById('delete-migration-form');
    const modalLaneName = document.getElementById('modal-delete-lane-name');
    const modalTaskCount = document.getElementById('modal-delete-task-count');
    const modalTargetSelect = document.getElementById('modal-migrate-target-select');
    const closeBtns = modal.querySelectorAll('[data-close-modal]');

    // 各レーンの削除ボタン
    document.querySelectorAll('[data-action="open-delete-modal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const laneId = btn.dataset.laneId;
            const laneName = btn.dataset.laneName;
            const taskCount = parseInt(btn.dataset.taskCount || '0', 10);
            const deleteUrl = btn.dataset.deleteUrl;

            if (modalForm) modalForm.action = deleteUrl;
            if (modalLaneName) modalLaneName.textContent = laneName;
            if (modalTaskCount) modalTaskCount.textContent = taskCount;

            // 移行先セレクトボックスから自レーンを除外
            if (modalTargetSelect) {
                Array.from(modalTargetSelect.options).forEach(opt => {
                    opt.disabled = (opt.value === laneId);
                    if (opt.value === laneId && opt.selected) {
                        opt.selected = false;
                    }
                });
            }

            modal.classList.remove('hidden');
        });
    });

    closeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
}

/**
 * 簡易トースト通知
 */
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed bottom-5 right-5 z-50 flex flex-col space-y-2 pointer-events-none';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-emerald-800' : 'bg-rose-800';
    toast.className = `${bgColor} text-white px-4 py-2.5 rounded-xl text-xs font-bold shadow-lg transition-all transform translate-y-2 opacity-0 pointer-events-auto flex items-center space-x-2`;
    toast.innerHTML = `<span>${type === 'success' ? '✅' : '⚠️'}</span><span>${message}</span>`;

    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    });

    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
