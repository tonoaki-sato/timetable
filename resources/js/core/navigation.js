/**
 * ヘッダーナビゲーション（ドロップダウン & Escキー制御）
 */
export function initNavigation() {
    const dropdowns = document.querySelectorAll('.nav-dropdown');

    dropdowns.forEach((dropdown) => {
        const trigger = dropdown.querySelector('.dropdown-trigger');
        const menu = dropdown.querySelector('.dropdown-menu');

        if (!trigger || !menu) return;

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            const isCurrentlyOpen = !menu.classList.contains('hidden');

            // 他のすべてのドロップダウンを閉じる
            document.querySelectorAll('.dropdown-menu').forEach((m) => m.classList.add('hidden'));

            if (!isCurrentlyOpen) {
                menu.classList.remove('hidden');
            }
        });
    });

    // 外側クリックでドロップダウンを閉じる
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.nav-dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach((m) => m.classList.add('hidden'));
        }
    });

    // Escキーで閉じる（ドロップダウン / モバイルメニュー / 汎用ダイアログモーダル）
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.dropdown-menu').forEach((m) => m.classList.add('hidden'));
            const mobileMenu = document.getElementById('mobileMenuModal');
            if (mobileMenu) mobileMenu.classList.add('hidden');
            document.querySelectorAll('[role="dialog"].fixed.inset-0:not(.hidden)').forEach((dialog) => {
                if (dialog.id === 'mobileMenuModal' || dialog.id === 'appConfirmModal') return;
                dialog.classList.add('hidden');
            });
        }
    });
}
