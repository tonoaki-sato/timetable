/**
 * 印刷画面用スクリプト (CSP完全準拠)
 */
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="print"]')) {
            window.print();
        }
    });
});
