/**
 * 会議管理（プレビュー・タブ切替・LINE文面コピー）スクリプト
 */
document.addEventListener('DOMContentLoaded', () => {
    initMeetingPreview();
    initMeetingShowTabs();
    initMeetingCopyButtons();
});

/**
 * 1. 会議作成/編集画面: LINE案内文のリアルタイムプレビュー
 */
function initMeetingPreview() {
    const previewBox = document.getElementById('linePreviewBox');
    if (!previewBox) return;

    function updateLinePreview() {
        const title = document.getElementById('title')?.value || '（会議名）';
        const dateVal = document.getElementById('meeting_date')?.value;
        const startTime = document.getElementById('start_time')?.value || '18:00';
        const endTime = document.getElementById('end_time')?.value;
        const location = document.getElementById('location')?.value || '（開催場所）';

        const hostSelect = document.getElementById('staff_id');
        const selectedOption = hostSelect ? hostSelect.options[hostSelect.selectedIndex] : null;
        const hostName = selectedOption && selectedOption.dataset.name ? selectedOption.dataset.name : '未定（後から設定）';

        const desc = document.getElementById('description')?.value;
        const note = document.getElementById('notice_note')?.value;

        let dateStr = '（開催日）';
        if (dateVal) {
            const d = new Date(dateVal + 'T00:00:00');
            const weekdayNames = ['日', '月', '火', '水', '木', '金', '土'];
            const w = weekdayNames[d.getDay()];
            dateStr = `${d.getFullYear()}年${d.getMonth() + 1}月${d.getDate()}日(${w})`;
        }

        const timeRange = endTime ? `${startTime} 〜 ${endTime}` : `${startTime} 〜`;

        const lines = [
            '【会議開催のお知らせ】',
            `「${title}」の開催日程が決まりました。`,
            '',
            `📅 日時：${dateStr} ${timeRange}`,
            `📍 場所：${location}`,
            `👤 主催：${hostName}`,
        ];

        if (desc && desc.trim()) {
            lines.push('');
            lines.push('📝 議題・内容：');
            lines.push(desc.trim());
        }

        if (note && note.trim()) {
            lines.push('');
            lines.push('📌 連絡事項：');
            lines.push(note.trim());
        }

        lines.push('--------------------------------');
        lines.push('ご参加よろしくお願いいたします！');

        previewBox.textContent = lines.join('\n');
    }

    const fieldIds = ['title', 'meeting_date', 'start_time', 'end_time', 'location', 'staff_id', 'description', 'notice_note'];
    fieldIds.forEach((id) => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', updateLinePreview);
            el.addEventListener('change', updateLinePreview);
        }
    });

    updateLinePreview();
}

/**
 * 2. 会議詳細画面: タブ切り替え（開催案内 / リマインド）
 */
let currentMode = 'share';

function initMeetingShowTabs() {
    const shareTemplate = document.getElementById('shareTextTemplate');
    const remindTemplate = document.getElementById('remindTextTemplate');
    if (!shareTemplate || !remindTemplate) return;

    const shareText = shareTemplate.textContent;
    const remindText = remindTemplate.textContent;

    document.addEventListener('click', (e) => {
        const tabBtn = e.target.closest('[data-meeting-tab]');
        if (!tabBtn) return;

        const mode = tabBtn.getAttribute('data-meeting-tab');
        currentMode = mode;

        const textBox = document.getElementById('lineTextBox');
        const tabShare = document.getElementById('tabBtnShare');
        const tabRemind = document.getElementById('tabBtnRemind');
        const lineAppBtn = document.getElementById('lineAppBtn');

        if (mode === 'share') {
            if (textBox) textBox.textContent = shareText;
            if (tabShare) tabShare.className = 'px-3.5 py-1.5 rounded-lg text-xs font-bold transition bg-emerald-600 text-white shadow-xs cursor-pointer';
            if (tabRemind) tabRemind.className = 'px-3.5 py-1.5 rounded-lg text-xs font-bold transition text-slate-400 hover:text-white cursor-pointer';
            if (lineAppBtn) lineAppBtn.href = 'https://line.me/R/msg/text/?' + encodeURIComponent(shareText);
        } else {
            if (textBox) textBox.textContent = remindText;
            if (tabRemind) tabRemind.className = 'px-3.5 py-1.5 rounded-lg text-xs font-bold transition bg-emerald-600 text-white shadow-xs cursor-pointer';
            if (tabShare) tabShare.className = 'px-3.5 py-1.5 rounded-lg text-xs font-bold transition text-slate-400 hover:text-white cursor-pointer';
            if (lineAppBtn) lineAppBtn.href = 'https://line.me/R/msg/text/?' + encodeURIComponent(remindText);
        }
    });
}

/**
 * 3. 会議文面コピーボタン（一覧画面 & 詳細画面）
 */
function initMeetingCopyButtons() {
    // 一覧画面でのコピーボタン (data-action="copy-meeting-line")
    document.addEventListener('click', (e) => {
        const button = e.target.closest('[data-action="copy-meeting-line"]');
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
