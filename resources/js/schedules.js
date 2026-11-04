/**
 * スケジュール管理（文面コピー）スクリプト
 */
document.addEventListener('DOMContentLoaded', () => {
    initScheduleCopyButtons();
});

/**
 * 文面コピーボタン（一覧画面 & 詳細画面）
 */
function initScheduleCopyButtons() {
    // 一覧画面でのコピーボタン (data-action="copy-schedule-line")
    document.addEventListener('click', (e) => {
        const button = e.target.closest('[data-action="copy-schedule-line"]');
        if (!button) return;

        const text = button.getAttribute('data-line-text');
        if (!text) return;

        navigator.clipboard.writeText(text).then(() => {
            const originalHtml = button.innerHTML;
            button.innerHTML = '<span>✔</span><span class="text-emerald-800">コピー完了!</span>';
            button.classList.add('bg-emerald-200');
            setTimeout(() => {
                button.innerHTML = originalHtml;
                button.classList.remove('bg-emerald-200');
            }, 2000);
        }).catch(() => {
            alert('クリップボードへのコピーに失敗しました。手動でコピーしてください。');
        });
    });

    // 詳細画面での文面コピー (data-action="copy-current-line")
    document.addEventListener('click', (e) => {
        const button = e.target.closest('[data-action="copy-current-line"]');
        if (!button) return;

        const textBox = document.getElementById('lineTextBox');
        const text = textBox ? textBox.textContent : '';
        if (!text) return;

        navigator.clipboard.writeText(text).then(() => {
            const label = document.getElementById('copyBtnLabel');
            const originalText = label ? label.textContent : '';
            if (label) label.textContent = 'コピーしました！';
            button.classList.remove('bg-emerald-600', 'hover:bg-emerald-500');
            button.classList.add('bg-white', 'text-slate-900');

            setTimeout(() => {
                if (label) label.textContent = originalText;
                button.classList.remove('bg-white', 'text-slate-900');
                button.classList.add('bg-emerald-600', 'hover:bg-emerald-500');
            }, 2000);
        }).catch(() => {
            alert('クリップボードへのコピーに失敗しました。');
        });
    });
}
