/**
 * アプリ共通 UI ユーティリティ（統一トースト通知・カスタム確認モーダル）
 * Strict CSP準拠（インラインstyle/on*不使用）
 */

function escapeTextNode(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.textContent;
}

/**
 * 画面右下へ統一トースト通知を表示
 *
 * @param {string} message
 * @param {'success'|'error'|'warning'|'info'} type
 * @param {number} durationMs
 */
export function showToast(message, type = 'success', durationMs = 3500) {
    let container = document.getElementById('app-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'app-toast-container';
        container.className = 'fixed bottom-5 right-5 z-[70] flex flex-col space-y-2 pointer-events-none max-w-sm w-full px-4 sm:px-0';
        document.body.appendChild(container);
    }

    const styles = {
        success: 'bg-emerald-800 border-emerald-700 text-white',
        error: 'bg-red-800 border-red-700 text-white',
        warning: 'bg-amber-700 border-amber-600 text-white',
        info: 'bg-slate-900 border-slate-700 text-white',
    };
    const icons = {
        success: '✅',
        error: '⚠️',
        warning: '⚠️',
        info: 'ℹ️',
    };

    const toast = document.createElement('div');
    toast.setAttribute('role', 'status');
    toast.className = `${styles[type] || styles.info} border px-4 py-2.5 rounded-xl text-xs font-bold shadow-xl transition-all duration-200 transform translate-y-2 opacity-0 pointer-events-auto flex items-center justify-between gap-2`;

    const body = document.createElement('div');
    body.className = 'flex items-center space-x-2 min-w-0';

    const iconSpan = document.createElement('span');
    iconSpan.className = 'shrink-0';
    iconSpan.textContent = icons[type] || icons.info;

    const msgSpan = document.createElement('span');
    msgSpan.className = 'break-words';
    msgSpan.textContent = escapeTextNode(String(message));

    body.appendChild(iconSpan);
    body.appendChild(msgSpan);

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.setAttribute('aria-label', '通知を閉じる');
    closeBtn.className = 'text-white/75 hover:text-white text-xs font-bold px-1 shrink-0 cursor-pointer';
    closeBtn.textContent = '✕';

    const removeToast = () => {
        toast.classList.add('translate-y-2', 'opacity-0');
        setTimeout(() => toast.remove(), 220);
    };

    closeBtn.addEventListener('click', removeToast);

    toast.appendChild(body);
    toast.appendChild(closeBtn);
    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    });

    if (durationMs > 0) {
        setTimeout(removeToast, durationMs);
    }
}

let activeConfirmResolver = null;

function ensureConfirmModal() {
    let modal = document.getElementById('appConfirmModal');
    if (modal) {
        return modal;
    }

    modal = document.createElement('div');
    modal.id = 'appConfirmModal';
    modal.className = 'hidden fixed inset-0 z-[60] overflow-y-auto';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'app-confirm-title');
    modal.innerHTML = `
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" data-confirm-cancel></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-xl border border-slate-200 max-w-md w-full overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 flex items-center space-x-2">
                    <span id="app-confirm-icon" class="text-lg">⚠️</span>
                    <h3 id="app-confirm-title" class="text-sm font-bold text-slate-900">操作の確認</h3>
                </div>
                <div class="p-5">
                    <p id="app-confirm-message" class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line"></p>
                </div>
                <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" id="app-confirm-cancel-btn" data-confirm-cancel
                        class="px-3.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-bold transition cursor-pointer">
                        キャンセル
                    </button>
                    <button type="button" id="app-confirm-ok-btn" data-confirm-ok
                        class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-bold transition cursor-pointer shadow-xs">
                        実行する
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    return modal;
}

/**
 * カスタム確認モーダルを表示し、Promise<boolean> を返す
 *
 * @param {string} message
 * @param {{ title?: string, confirmText?: string, cancelText?: string, variant?: 'danger'|'primary', icon?: string }} options
 * @returns {Promise<boolean>}
 */
export function confirmDialog(message, options = {}) {
    if (activeConfirmResolver) {
        activeConfirmResolver(false);
        activeConfirmResolver = null;
    }

    const modal = ensureConfirmModal();
    const titleEl = modal.querySelector('#app-confirm-title');
    const iconEl = modal.querySelector('#app-confirm-icon');
    const msgEl = modal.querySelector('#app-confirm-message');
    const cancelBtn = modal.querySelector('#app-confirm-cancel-btn');
    const okBtn = modal.querySelector('#app-confirm-ok-btn');

    const variant = options.variant || 'danger';
    if (titleEl) titleEl.textContent = options.title || '操作の確認';
    if (iconEl) iconEl.textContent = options.icon || (variant === 'danger' ? '⚠️' : '❓');
    if (msgEl) msgEl.textContent = message || 'この操作を実行しますか？';
    if (cancelBtn) cancelBtn.textContent = options.cancelText || 'キャンセル';
    if (okBtn) {
        okBtn.textContent = options.confirmText || '実行する';
        okBtn.className = variant === 'primary'
            ? 'px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition cursor-pointer shadow-xs'
            : 'px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-bold transition cursor-pointer shadow-xs';
    }

    modal.classList.remove('hidden');

    return new Promise((resolve) => {
        const cleanup = (result) => {
            modal.classList.add('hidden');
            modal.removeEventListener('click', onModalClick);
            document.removeEventListener('keydown', onKeyDown, true);
            activeConfirmResolver = null;
            resolve(result);
        };

        const onModalClick = (e) => {
            if (e.target.closest('[data-confirm-ok]')) {
                cleanup(true);
            } else if (e.target.closest('[data-confirm-cancel]')) {
                cleanup(false);
            }
        };

        const onKeyDown = (e) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                cleanup(false);
            }
        };

        activeConfirmResolver = cleanup;
        modal.addEventListener('click', onModalClick);
        document.addEventListener('keydown', onKeyDown, true);
        okBtn?.focus();
    });
}
