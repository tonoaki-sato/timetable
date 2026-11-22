import { confirmDialog } from '../utils/ui.js';

/**
 * CSP対応: 宣言的属性（data-*）に基づくグローバルイベントハンドラ
 */
export function initGlobalEventHandlers() {
    // 1. フォーム送信前の確認ダイアログ (data-confirm) & 二重送信防止 (data-disable-on-submit)
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!form) return;
        const message = form.getAttribute('data-confirm');
        if (message && form.dataset.confirmBypassed !== '1') {
            e.preventDefault();
            e.stopImmediatePropagation();
            confirmDialog(message, { variant: 'danger' }).then((ok) => {
                if (ok) {
                    form.dataset.confirmBypassed = '1';
                    form.requestSubmit();
                }
            });
            return;
        }
        if (form.dataset.confirmBypassed === '1') {
            delete form.dataset.confirmBypassed;
        }

        if (form.hasAttribute('data-disable-on-submit')) {
            const loadingText = form.getAttribute('data-disable-on-submit') || '送信中...';
            const submitBtns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitBtns.forEach((btn) => {
                btn.disabled = true;
                btn.classList.add('opacity-70', 'cursor-not-allowed');
                if (btn.tagName === 'BUTTON') {
                    btn.textContent = loadingText;
                } else {
                    btn.value = loadingText;
                }
            });
        }
    });

    // 2. ボタン・リンク単位の確認ダイアログ (button[data-confirm], a[data-confirm])
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-confirm], a[data-confirm]');
        if (!btn) return;
        if (btn.dataset.confirmBypassed === '1') {
            delete btn.dataset.confirmBypassed;
            return;
        }
        const message = btn.getAttribute('data-confirm');
        if (!message) return;

        e.preventDefault();
        e.stopImmediatePropagation();
        confirmDialog(message, { variant: 'danger' }).then((ok) => {
            if (!ok) return;
            if (btn.tagName === 'A' && btn.href) {
                window.location.href = btn.href;
                return;
            }
            if (btn.form) {
                btn.form.dataset.confirmBypassed = '1';
                btn.dataset.confirmBypassed = '1';
                btn.form.requestSubmit(btn);
                return;
            }
            btn.dataset.confirmBypassed = '1';
            btn.click();
        });
    });

    // 3. select変更時の自動フォーム送信 (data-auto-submit) & ページ遷移 (data-action="navigate-on-change")
    document.addEventListener('change', (e) => {
        const target = e.target;
        if (target && target.hasAttribute('data-auto-submit')) {
            target.form?.submit();
        }
        if (target && target.matches('select[data-action="navigate-on-change"]') && target.value) {
            window.location.href = target.value;
        }
    });

    // 4. 印刷アクション (data-action="print")
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="print"]')) {
            window.print();
        }
    });

    // 5. モバイルメニュー開閉 (data-toggle="mobile-menu")
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-toggle="mobile-menu"]')) {
            const modal = document.getElementById('mobileMenuModal');
            if (modal) {
                modal.classList.toggle('hidden');
            }
        }
    });

    // 6〜11. 汎用クリックアクション
    document.addEventListener('click', (e) => {
        // 6. 汎用ターゲット要素の表示切替 (data-toggle-target="#id" / data-close-target="#id")
        const toggleBtn = e.target.closest('[data-toggle-target]');
        if (toggleBtn) {
            const selector = toggleBtn.getAttribute('data-toggle-target');
            if (selector) document.querySelector(selector)?.classList.toggle('hidden');
            return;
        }

        const closeBtn = e.target.closest('[data-close-target]');
        if (closeBtn) {
            const selector = closeBtn.getAttribute('data-close-target');
            if (selector) document.querySelector(selector)?.classList.add('hidden');
            return;
        }

        // 7. 入力値クリア (data-clear-target="inputId")
        const clearBtn = e.target.closest('[data-clear-target]');
        if (clearBtn) {
            const targetId = clearBtn.getAttribute('data-clear-target');
            const targetInput = document.getElementById(targetId);
            if (targetInput) {
                targetInput.value = '';
                targetInput.focus();
            }
            return;
        }

        // 8. アコーディオン一括開閉 (data-action="toggle-accordions", data-target="#containerId")
        const accordionBtn = e.target.closest('[data-action="toggle-accordions"]');
        if (accordionBtn) {
            const containerSelector = accordionBtn.getAttribute('data-target') || '#task-accordions';
            const container = document.querySelector(containerSelector);
            if (container) {
                const detailsList = container.querySelectorAll('details');
                const shouldOpen = accordionBtn.getAttribute('data-state') !== 'open';
                detailsList.forEach((d) => {
                    d.open = shouldOpen;
                });
                accordionBtn.setAttribute('data-state', shouldOpen ? 'open' : 'closed');
                const label = accordionBtn.querySelector('[data-label]');
                if (label) {
                    label.textContent = shouldOpen ? 'すべて折りたたむ' : 'すべて開く';
                }
                const icon = accordionBtn.querySelector('[data-icon]');
                if (icon) {
                    icon.textContent = shouldOpen ? '🔼' : '🔽';
                }
            }
            return;
        }

        // 9. アラート通知閉じる (data-dismiss-alert)
        const dismissAlertBtn = e.target.closest('[data-dismiss-alert]');
        if (dismissAlertBtn) {
            const alertBox = dismissAlertBtn.closest('[data-alert-box]');
            if (alertBox) {
                alertBox.remove();
            }
            return;
        }

        // 10. パスワード表示/非表示トグル (data-toggle-password="inputId")
        const togglePwBtn = e.target.closest('[data-toggle-password]');
        if (togglePwBtn) {
            const targetId = togglePwBtn.getAttribute('data-toggle-password');
            const input = targetId ? document.getElementById(targetId) : null;
            if (input) {
                const isMasked = input.getAttribute('type') === 'password';
                input.setAttribute('type', isMasked ? 'text' : 'password');
                togglePwBtn.setAttribute('aria-pressed', isMasked ? 'true' : 'false');
                togglePwBtn.setAttribute('aria-label', isMasked ? 'パスワードを隠す' : 'パスワードを表示');
                togglePwBtn.textContent = isMasked ? '隠す' : '表示';
            }
            return;
        }

        // 11. ページ再読み込みアクション (data-action="reload")
        if (e.target.closest('[data-action="reload"]')) {
            window.location.reload();
        }
    });
}
