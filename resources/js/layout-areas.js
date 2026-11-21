import { confirmDialog } from './utils/ui.js';

/**
 * 会場エリアマスター管理スクリプト (CSP準拠・外部JavaScriptモジュール)
 */
document.addEventListener('DOMContentLoaded', () => {
    initDragAndDropReorder();
    initLayoutAreaDetailModal();
    initLayoutAreaFormModal();
});

/**
 * 1. ドラッグ＆ドロップによる表示順並び替え
 */
function initDragAndDropReorder() {
    const container = document.getElementById('layout-areas-container');
    if (!container || container.dataset.canReorder !== '1') return;

    let draggedItem = null;

    container.addEventListener('dragstart', (e) => {
        const item = e.target.closest('[data-layout-area-id]');
        if (!item) return;

        draggedItem = item;
        item.classList.add('opacity-40', 'bg-indigo-50/70');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', item.dataset.layoutAreaId || '');
    });

    container.addEventListener('dragend', (e) => {
        const item = e.target.closest('[data-layout-area-id]');
        if (item) {
            item.classList.remove('opacity-40', 'bg-indigo-50/70');
        }
        draggedItem = null;
    });

    container.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const targetItem = e.target.closest('[data-layout-area-id]');
        if (!targetItem || targetItem === draggedItem) return;

        const rect = targetItem.getBoundingClientRect();
        const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
        container.insertBefore(draggedItem, next ? targetItem.nextSibling : targetItem);
    });

    container.addEventListener('drop', async (e) => {
        e.preventDefault();
        if (!draggedItem) return;

        const items = Array.from(container.querySelectorAll('[data-layout-area-id]'));
        const layoutAreaIds = items
            .map((el) => parseInt(el.dataset.layoutAreaId || '', 10))
            .filter(Boolean);
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
                body: JSON.stringify({ layout_area_ids: layoutAreaIds }),
            });

            const data = await res.json();
            if (res.ok && data.success) {
                showToast(data.message || '会場エリアの表示順を更新しました。', 'success');
            } else {
                showToast(data.message || '並び順の更新に失敗しました。', 'error');
            }
        } catch (err) {
            console.error('Layout area reorder error:', err);
            showToast('通信エラーが発生しました。', 'error');
        }
    });
}

/**
 * 2. 閲覧専用詳細モーダル (#modal-layout-area-detail)
 */
function initLayoutAreaDetailModal() {
    const modal = document.getElementById('modal-layout-area-detail');
    if (!modal) return;

    const nameEl = document.getElementById('detail-area-name');
    const activeBadge = document.getElementById('detail-area-active-badge');
    const inactiveBadge = document.getElementById('detail-area-inactive-badge');
    const locationEl = document.getElementById('detail-area-location');
    const usageEl = document.getElementById('detail-area-usage');
    const notesEl = document.getElementById('detail-area-notes');

    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    const openDetail = (payload) => {
        if (!payload || typeof payload !== 'object') return;

        const isActive = Boolean(payload.is_active);
        if (nameEl) nameEl.textContent = payload.name || '—';
        activeBadge?.classList.toggle('hidden', !isActive);
        inactiveBadge?.classList.toggle('hidden', isActive);

        if (locationEl) {
            if (payload.location_name) {
                const typePart = payload.location_type_label ? `（${payload.location_type_label}）` : '';
                locationEl.textContent = `${payload.location_name}${typePart}`;
            } else {
                locationEl.textContent = '関連拠点なし（通り等）';
            }
        }

        if (usageEl) {
            const mapsCount = Number(payload.layout_maps_count ?? 0);
            const itemsCount = Number(payload.items_count ?? 0);
            usageEl.textContent = `${mapsCount} 日程 / ${itemsCount} 配置`;
        }

        if (notesEl) {
            notesEl.textContent = payload.notes || '備考・特記事項は登録されていません。';
        }

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    document.querySelectorAll('.btn-view-layout-area').forEach((btn) => {
        btn.addEventListener('click', () => {
            try {
                const payload = JSON.parse(btn.dataset.layoutArea || '{}');
                openDetail(payload);
            } catch (err) {
                console.error('Failed to parse layout area detail payload:', err);
            }
        });
    });

    modal.querySelectorAll('.btn-close-modal, .modal-backdrop').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
}

/**
 * 3. 新規登録・編集兼用モーダル (#modal-layout-area-form) および削除確認
 */
function initLayoutAreaFormModal() {
    // 削除確認ダイアログ（一覧内フォーム）
    document.querySelectorAll('.btn-delete-layout-area').forEach((btn) => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const areaName = btn.dataset.areaName || 'このエリア';
            const confirmed = await confirmDialog(
                `会場エリア「${areaName}」を削除してもよろしいですか？`,
                { variant: 'danger', title: '会場エリアの削除確認', confirmText: '削除する' }
            );
            if (confirmed) {
                btn.closest('form')?.submit();
            }
        });
    });

    const modal = document.getElementById('modal-layout-area-form');
    const form = document.getElementById('form-layout-area-mutation');
    if (!modal || !form) return;

    const modalTitleEl = document.getElementById('layout-area-form-modal-title');
    const methodInput = document.getElementById('layout-area-form-method');
    const actionMirrorInput = document.getElementById('layout-area-form-action-mirror');
    const methodMirrorInput = document.getElementById('layout-area-form-method-mirror');
    const submitBtn = document.getElementById('layout-area-form-submit-btn');

    const warningBanner = document.getElementById('edit-area-usage-warning');
    const warningItemsCountEl = document.getElementById('edit-area-usage-items-count');
    const warningMapsCountEl = document.getElementById('edit-area-usage-maps-count');

    const nameInput = document.getElementById('layout_area_name');
    const locationSelect = document.getElementById('layout_area_location_id');
    const isActiveCheckbox = document.getElementById('layout_area_is_active');
    const notesTextarea = document.getElementById('layout_area_notes');

    const setTitleText = (text) => {
        if (!modalTitleEl) return;
        const spans = modalTitleEl.querySelectorAll('span');
        if (spans.length >= 2) {
            spans[1].textContent = text;
        } else {
            modalTitleEl.textContent = text;
        }
    };

    const openModal = () => {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    const createBtn = document.getElementById('btn-open-create-layout-area');
    if (createBtn) {
        createBtn.addEventListener('click', () => {
            const storeUrl = createBtn.dataset.storeUrl || form.action;
            form.action = storeUrl;
            if (methodInput) methodInput.value = 'POST';
            if (actionMirrorInput) actionMirrorInput.value = storeUrl;
            if (methodMirrorInput) methodMirrorInput.value = 'POST';

            setTitleText('新規会場エリアの登録');
            if (submitBtn) submitBtn.textContent = 'エリアを登録する';
            warningBanner?.classList.add('hidden');

            if (nameInput) nameInput.value = '';
            if (locationSelect) locationSelect.value = '';
            if (isActiveCheckbox) isActiveCheckbox.checked = true;
            if (notesTextarea) notesTextarea.value = '';

            openModal();
            nameInput?.focus();
        });
    }

    document.querySelectorAll('.btn-edit-layout-area').forEach((btn) => {
        btn.addEventListener('click', () => {
            let payload = {};
            try {
                payload = JSON.parse(btn.dataset.layoutArea || '{}');
            } catch (err) {
                console.error('Failed to parse layout area edit payload:', err);
                return;
            }

            const updateUrl = payload.update_url || '';
            form.action = updateUrl;
            if (methodInput) methodInput.value = 'PUT';
            if (actionMirrorInput) actionMirrorInput.value = updateUrl;
            if (methodMirrorInput) methodMirrorInput.value = 'PUT';

            setTitleText('会場エリアの編集');
            if (submitBtn) submitBtn.textContent = '変更内容を保存する';

            const itemsCount = Number(payload.items_count ?? 0);
            const mapsCount = Number(payload.layout_maps_count ?? 0);
            if (itemsCount > 0) {
                if (warningItemsCountEl) warningItemsCountEl.textContent = String(itemsCount);
                if (warningMapsCountEl) warningMapsCountEl.textContent = String(mapsCount);
                warningBanner?.classList.remove('hidden');
            } else {
                warningBanner?.classList.add('hidden');
            }

            if (nameInput) nameInput.value = payload.name || '';
            if (locationSelect) {
                locationSelect.value = payload.location_id ? String(payload.location_id) : '';
            }
            if (isActiveCheckbox) isActiveCheckbox.checked = Boolean(payload.is_active);
            if (notesTextarea) notesTextarea.value = payload.notes || '';

            openModal();
            nameInput?.focus();
        });
    });

    modal.querySelectorAll('.btn-close-modal, .modal-backdrop').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    if (modal.dataset.reopenOnError === '1') {
        const oldMethod = (modal.dataset.oldMethod || 'POST').toUpperCase();
        const oldAction = modal.dataset.oldAction || form.action;
        form.action = oldAction;
        if (methodInput) methodInput.value = oldMethod;
        if (actionMirrorInput) actionMirrorInput.value = oldAction;
        if (methodMirrorInput) methodMirrorInput.value = oldMethod;

        if (oldMethod === 'PUT') {
            setTitleText('会場エリアの編集');
            if (submitBtn) submitBtn.textContent = '変更内容を保存する';
        } else {
            setTitleText('新規会場エリアの登録');
            if (submitBtn) submitBtn.textContent = 'エリアを登録する';
        }

        openModal();
    }
}

/**
 * トースト通知の表示 (XSS安全)
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
    const bgColor = type === 'success' ? 'bg-emerald-800' : 'bg-red-800';
    toast.className = `${bgColor} text-white px-4 py-2.5 rounded-xl text-xs font-bold shadow-lg transition-all transform translate-y-2 opacity-0 pointer-events-auto flex items-center space-x-2`;

    const iconSpan = document.createElement('span');
    iconSpan.textContent = type === 'success' ? '✅' : '⚠️';
    const textSpan = document.createElement('span');
    textSpan.textContent = String(message || '');
    toast.append(iconSpan, textSpan);

    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    });

    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
