/**
 * レーン管理機能 JavaScript (CSP完全準拠)
 */

document.addEventListener('DOMContentLoaded', () => {
    initDragAndDropReorder();
    initDeleteMigrationModal();
    initLeadStaffAutoSelection();
    initLaneFormInteractions();
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

/**
 * 3. 班長選択時に固定スタッフチェックボックスを自動選択
 */
function initLeadStaffAutoSelection() {
    document.addEventListener('change', (e) => {
        const select = e.target;
        if (!select || select.name !== 'lead_staff_id') return;

        // data-auto-submitを持つクイック更新セレクトはフォーム全体が即時送信されるため除外
        if (select.hasAttribute('data-auto-submit')) return;

        const form = select.closest('form');
        if (!form) return;

        const selectedStaffId = select.value;
        if (!selectedStaffId) return;

        // 同一フォーム内の固定スタッフチェックボックスから該当スタッフを自動選択
        const checkbox = form.querySelector(`input[name="fixed_staff_ids[]"][value="${selectedStaffId}"]`);
        if (checkbox) {
            checkbox.checked = true;
        }
    });
}

/**
 * 4. 担当班登録・編集フォームの対話操作（絵文字パレット・カラースウォッチ・リアルタイムプレビュー）
 */
function initLaneFormInteractions() {
    const form = document.getElementById('lane-form');
    if (!form) return;

    const nameInput = document.getElementById('name');
    const iconInput = document.getElementById('icon');
    const previewBadge = document.getElementById('lane-preview-badge');
    const previewName = document.getElementById('preview-name');
    const previewIcon = document.getElementById('preview-icon');
    const colorCards = form.querySelectorAll('.lane-color-card');

    // 1. 班名入力とプレビュー同期
    if (nameInput && previewName) {
        nameInput.addEventListener('input', () => {
            const val = nameInput.value.trim();
            previewName.textContent = val || '担当班名';
        });
    }

    // 2. 絵文字アイコン入力とプレビュー同期
    if (iconInput && previewIcon) {
        iconInput.addEventListener('input', () => {
            const val = iconInput.value.trim();
            previewIcon.textContent = val || '📋';
        });
    }

    // 3. 絵文字クイックパレットクリック
    const palette = document.getElementById('emoji-palette');
    if (palette && iconInput && previewIcon) {
        palette.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-emoji]');
            if (!btn) return;
            const emoji = btn.dataset.emoji;
            if (!emoji) return;

            iconInput.value = emoji;
            previewIcon.textContent = emoji;

            // パレットボタンのクリックフィードバック
            btn.classList.add('ring-2', 'ring-indigo-500', 'bg-indigo-100');
            setTimeout(() => {
                btn.classList.remove('ring-2', 'ring-indigo-500', 'bg-indigo-100');
            }, 200);
        });
    }

    // 4. カラースウォッチ選択とプレビュー同期
    if (colorCards.length > 0 && previewBadge) {
        const updateColorSelection = (selectedCard) => {
            colorCards.forEach(card => {
                const isTarget = card === selectedCard;
                card.classList.toggle('border-indigo-600', isTarget);
                card.classList.toggle('bg-indigo-50/50', isTarget);
                card.classList.toggle('ring-2', isTarget);
                card.classList.toggle('ring-indigo-500/20', isTarget);
                card.classList.toggle('shadow-2xs', isTarget);

                card.classList.toggle('border-slate-200', !isTarget);
                card.classList.toggle('bg-white', !isTarget);
            });

            // プレビューバッジのクラス更新
            const badgeClass = selectedCard.dataset.badgeClass;
            if (badgeClass) {
                // 既存のカラー関連クラス（bg-*, text-*, border-*）をリセットして新クラスを適用
                const baseClasses = ['inline-flex', 'items-center', 'px-3', 'py-1', 'rounded-lg', 'text-xs', 'font-bold', 'border', 'transition-all', 'duration-150'];
                previewBadge.className = `${baseClasses.join(' ')} ${badgeClass}`;
            }
        };

        colorCards.forEach(card => {
            card.addEventListener('click', () => {
                const radio = card.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                }
                updateColorSelection(card);
            });
        });
    }
}
