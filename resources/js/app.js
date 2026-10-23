// まつり総合管理システム アプリケーションスクリプト
document.addEventListener('DOMContentLoaded', () => {
    initRealtimeClock();
    initGlobalEventHandlers();
    initNavigation();
});

/**
 * CSP対応: 宣言的属性（data-*）に基づくグローバルイベントハンドラ
 */
function initGlobalEventHandlers() {
    // 1. フォーム送信前の確認ダイアログ (data-confirm)
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!form) return;
        const message = form.getAttribute('data-confirm');
        if (message && !window.confirm(message)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });

    // 2. ボタン単位の確認ダイアログ (button[data-confirm])
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-confirm], a[data-confirm]');
        if (!btn) return;
        const message = btn.getAttribute('data-confirm');
        if (message && !window.confirm(message)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });

    // 3. select変更時の自動フォーム送信 (data-auto-submit)
    document.addEventListener('change', (e) => {
        const target = e.target;
        if (target && target.hasAttribute('data-auto-submit')) {
            target.form?.submit();
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

    // 6. 汎用ターゲット要素の表示切替 (data-toggle-target="#id" / data-close-target="#id")
    document.addEventListener('click', (e) => {
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
    });
}

/**
 * ヘッダーナビゲーション（ドロップダウン & Escキー制御）
 */
function initNavigation() {
    const dropdowns = document.querySelectorAll('.nav-dropdown');

    dropdowns.forEach(dropdown => {
        const trigger = dropdown.querySelector('.dropdown-trigger');
        const menu = dropdown.querySelector('.dropdown-menu');

        if (!trigger || !menu) return;

        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            const isCurrentlyOpen = !menu.classList.contains('hidden');

            // 他のすべてのドロップダウンを閉じる
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));

            if (!isCurrentlyOpen) {
                menu.classList.remove('hidden');
            }
        });
    });

    // 外側クリックでドロップダウンを閉じる
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        }
    });

    // Escキーで閉じる
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            const mobileMenu = document.getElementById('mobileMenuModal');
            if (mobileMenu) mobileMenu.classList.add('hidden');
        }
    });
}

/**
 * ダッシュボードの現在日時リアルタイム時計
 */
function initRealtimeClock() {
    const clockElement = document.getElementById('realtime-clock');
    if (!clockElement || window.__realtimeClockInitialized) return;
    window.__realtimeClockInitialized = true;

    const serverTimeMs = parseInt(clockElement.dataset.serverTime, 10);
    const clientTimeMs = Date.now();
    const timeOffset = isNaN(serverTimeMs) ? 0 : (serverTimeMs - clientTimeMs);
    const dayNames = ['日', '月', '火', '水', '木', '金', '土'];

    function updateClock() {
        const now = new Date(Date.now() + timeOffset);
        const month = now.getMonth() + 1;
        const date = now.getDate();
        const day = dayNames[now.getDay()];
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');

        clockElement.textContent = `${month}月${date}日 (${day}) ${hours}:${minutes}:${seconds}`;
    }

    updateClock();
    setInterval(updateClock, 1000);
}

