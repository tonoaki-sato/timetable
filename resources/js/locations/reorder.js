import { showToast } from '../utils/ui.js';

/**
 * 1. ドラッグ＆ドロップによる拠点表示順並び替え
 */
export function initDragAndDropReorder() {
    const container = document.getElementById('locations-container');
    if (!container) return;

    let draggedItem = null;

    container.addEventListener('dragstart', (e) => {
        const item = e.target.closest('[data-location-id]');
        if (!item) return;

        draggedItem = item;
        item.classList.add('opacity-40', 'bg-indigo-50/70');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', item.dataset.locationId || '');
    });

    container.addEventListener('dragend', (e) => {
        const item = e.target.closest('[data-location-id]');
        if (item) {
            item.classList.remove('opacity-40', 'bg-indigo-50/70');
        }
        draggedItem = null;
    });

    container.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const targetItem = e.target.closest('[data-location-id]');
        if (!targetItem || targetItem === draggedItem) return;

        const rect = targetItem.getBoundingClientRect();
        const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
        container.insertBefore(draggedItem, next ? targetItem.nextSibling : targetItem);
    });

    container.addEventListener('drop', async (e) => {
        e.preventDefault();
        if (!draggedItem) return;

        const items = Array.from(container.querySelectorAll('[data-location-id]'));
        const locationIds = items.map((el) => parseInt(el.dataset.locationId, 10)).filter(Boolean);
        const reorderUrl = container.dataset.reorderUrl;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!reorderUrl || !csrfToken) return;

        try {
            const res = await fetch(reorderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ location_ids: locationIds }),
            });

            const data = await res.json();
            if (data.success) {
                showToast(data.message || '拠点の表示順を更新しました。', 'success');
            } else {
                showToast(data.message || '並び順の更新に失敗しました。', 'error');
            }
        } catch (err) {
            console.error('Reorder error:', err);
            showToast('通信エラーが発生しました。', 'error');
        }
    });
}
