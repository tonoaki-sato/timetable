/**
 * 拠点管理機能スクリプト (CSP準拠・外部JavaScriptモジュール)
 */
document.addEventListener('DOMContentLoaded', () => {
    initScheduleModal();
    initTypeModal();
    initAutoSubmitSelects();
    initConfirmForms();
});

/**
 * 1. スケジュール（時間帯スロット）登録・編集モーダル
 */
function initScheduleModal() {
    const modal = document.getElementById('schedule-modal');
    const form = document.getElementById('schedule-modal-form');
    const modalTitle = document.getElementById('schedule-modal-title');
    const methodContainer = document.getElementById('schedule-form-method');

    if (!modal || !form) {
        return;
    }

    // 開くボタン（新規追加 / 編集）
    document.querySelectorAll('[data-action="open-schedule-modal"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            const storeUrl = btn.dataset.storeUrl;
            const updateUrl = btn.dataset.updateUrl;

            if (mode === 'create') {
                if (modalTitle) {
                    modalTitle.innerHTML = '<span>⏱️</span><span>利用時間帯の追加</span>';
                }
                form.action = storeUrl || form.action;
                if (methodContainer) {
                    methodContainer.innerHTML = '';
                }
                form.reset();

                // デフォルト日時を現在日時の正時にセット
                const now = new Date();
                now.setMinutes(0, 0, 0);
                const startStr = formatLocalDateTime(now);
                now.setHours(now.getHours() + 3);
                const endStr = formatLocalDateTime(now);

                const startInput = document.getElementById('modal_start_at');
                const endInput = document.getElementById('modal_end_at');
                const statusInput = document.getElementById('modal_application_status');

                if (startInput) startInput.value = startStr;
                if (endInput) endInput.value = endStr;
                if (statusInput) statusInput.value = 'unapplied';
            } else if (mode === 'edit') {
                if (modalTitle) {
                    modalTitle.innerHTML = '<span>⏱️</span><span>利用時間帯の編集</span>';
                }
                form.action = updateUrl;
                if (methodContainer) {
                    methodContainer.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                }

                try {
                    const schedule = JSON.parse(btn.dataset.schedule || '{}');
                    const startInput = document.getElementById('modal_start_at');
                    const endInput = document.getElementById('modal_end_at');
                    const statusInput = document.getElementById('modal_application_status');
                    const titleInput = document.getElementById('modal_title');
                    const noteInput = document.getElementById('modal_note');

                    if (startInput && schedule.start_at) {
                        startInput.value = schedule.start_at.slice(0, 16);
                    }
                    if (endInput && schedule.end_at) {
                        endInput.value = schedule.end_at.slice(0, 16);
                    }
                    if (statusInput) {
                        statusInput.value = schedule.application_status || 'unapplied';
                    }
                    if (titleInput) {
                        titleInput.value = schedule.title || '';
                    }
                    if (noteInput) {
                        noteInput.value = schedule.note || '';
                    }
                } catch (e) {
                    console.error('Failed to parse schedule data', e);
                }
            }

            modal.classList.remove('hidden');
        });
    });

    // 閉じるボタン
    document.querySelectorAll('[data-action="close-schedule-modal"]').forEach((btn) => {
        btn.addEventListener('click', () => {
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
 * 2. 拠点タイプ（マスタ）追加・編集モーダル
 */
function initTypeModal() {
    const modal = document.getElementById('type-modal');
    const form = document.getElementById('type-modal-form');
    const modalTitle = document.getElementById('type-modal-title');
    const methodContainer = document.getElementById('type-form-method');

    if (!modal || !form) {
        return;
    }

    // 開くボタン（新規追加 / 編集）
    document.querySelectorAll('[data-action="open-type-modal"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            const storeUrl = btn.dataset.storeUrl;
            const updateUrl = btn.dataset.updateUrl;

            if (mode === 'create') {
                if (modalTitle) {
                    modalTitle.innerHTML = '<span>🏷️</span><span>拠点タイプの追加</span>';
                }
                form.action = storeUrl || form.action;
                if (methodContainer) {
                    methodContainer.innerHTML = '';
                }
                form.reset();

                const activeInput = document.getElementById('type_is_active');
                const orderInput = document.getElementById('type_display_order');
                if (activeInput) activeInput.checked = true;
                if (orderInput) orderInput.value = 10;
            } else if (mode === 'edit') {
                if (modalTitle) {
                    modalTitle.innerHTML = '<span>🏷️</span><span>拠点タイプの編集</span>';
                }
                form.action = updateUrl;
                if (methodContainer) {
                    methodContainer.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                }

                try {
                    const type = JSON.parse(btn.dataset.type || '{}');
                    const labelInput = document.getElementById('type_label');
                    const nameInput = document.getElementById('type_name');
                    const descInput = document.getElementById('type_description');
                    const orderInput = document.getElementById('type_display_order');
                    const activeInput = document.getElementById('type_is_active');

                    if (labelInput) labelInput.value = type.label || '';
                    if (nameInput) nameInput.value = type.name || '';
                    if (descInput) descInput.value = type.description || '';
                    if (orderInput) orderInput.value = type.display_order ?? 10;
                    if (activeInput) activeInput.checked = Boolean(type.is_active);
                } catch (e) {
                    console.error('Failed to parse type data', e);
                }
            }

            modal.classList.remove('hidden');
        });
    });

    // 閉じるボタン
    document.querySelectorAll('[data-action="close-type-modal"]').forEach((btn) => {
        btn.addEventListener('click', () => {
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
 * 3. 申請ステータスの自動送信セレクトボックス
 */
function initAutoSubmitSelects() {
    document.querySelectorAll('select[data-action="auto-submit-status"]').forEach((select) => {
        select.addEventListener('change', () => {
            const form = select.closest('form');
            if (form) {
                form.submit();
            }
        });
    });
}

/**
 * 4. 削除確認等の確認ダイアログ
 */
function initConfirmForms() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            const message = form.dataset.confirm || '本当に実行しますか？';
            if (!window.confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * 日時オブジェクトを YYYY-MM-DDTHH:mm 形式にフォーマット (ローカル時刻)
 */
function formatLocalDateTime(date) {
    const pad = (n) => String(n).padStart(2, '0');
    const y = date.getFullYear();
    const m = pad(date.getMonth() + 1);
    const d = pad(date.getDate());
    const h = pad(date.getHours());
    const min = pad(date.getMinutes());
    return `${y}-${m}-${d}T${h}:${min}`;
}

