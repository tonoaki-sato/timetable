/**
 * スケジュール管理（タブ切替・文面コピー）スクリプト
 */
document.addEventListener('DOMContentLoaded', () => {
    initScheduleShowTabs();
    initScheduleCopyButtons();
});

let currentMode = 'share';

/**
 * 1. スケジュール詳細画面: タブ切り替え（開催案内 / リマインド）
 */
function initScheduleShowTabs() {
    const shareTemplate = document.getElementById('shareTextTemplate');
    const remindTemplate = document.getElementById('remindTextTemplate');
    if (!shareTemplate || !remindTemplate) return;

    const shareText = shareTemplate.textContent;
    const remindText = remindTemplate.textContent;

    document.addEventListener('click', (e) => {
        const tabBtn = e.target.closest('[data-schedule-tab]');
        if (!tabBtn) return;

        const mode = tabBtn.getAttribute('data-schedule-tab');
        currentMode = mode;

        const textBox = document.getElementById('lineTextBox');
        const tabShare = document.getElementById('tabBtnShare');
        const tabRemind = document.getElementById('tabBtnRemind');

        if (mode === 'share') {
            if (textBox) textBox.textContent = shareText;
            if (tabShare) tabShare.className = 'px-3.5 py-1.5 rounded-lg text-xs font-bold transition bg-emerald-600 text-white shadow-xs cursor-pointer';
            if (tabRemind) tabRemind.className = 'px-3.5 py-1.5 rounded-lg text-xs font-bold transition text-slate-400 hover:text-white cursor-pointer';
        } else {
            if (textBox) textBox.textContent = remindText;
            if (tabRemind) tabRemind.className = 'px-3.5 py-1.5 rounded-lg text-xs font-bold transition bg-emerald-600 text-white shadow-xs cursor-pointer';
            if (tabShare) tabShare.className = 'px-3.5 py-1.5 rounded-lg text-xs font-bold transition text-slate-400 hover:text-white cursor-pointer';
        }
    });
}

/**
 * 2. 文面コピーボタン（一覧画面 & 詳細画面）
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

    // 詳細画面でのアクティブ文面コピー (data-action="copy-current-line")
    document.addEventListener('click', (e) => {
        const button = e.target.closest('[data-action="copy-current-line"]');
        if (!button) return;

        const shareTemplate = document.getElementById('shareTextTemplate');
        const remindTemplate = document.getElementById('remindTextTemplate');
        if (!shareTemplate || !remindTemplate) return;

        const text = currentMode === 'share' ? shareTemplate.textContent : remindTemplate.textContent;

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

