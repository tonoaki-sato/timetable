/**
 * 現場マイタイムテーブル 共通状態・CSRF・URLコピー・トースト通知
 */
export function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') || '' : '';
}

export function initCopyUrlButton() {
    const copyUrlBtn = document.getElementById('copyUrlBtn');
    if (!copyUrlBtn) {
        return;
    }

    copyUrlBtn.addEventListener('click', async () => {
        const url = window.location.href;
        try {
            await navigator.clipboard.writeText(url);
            const originalHtml = copyUrlBtn.innerHTML;
            copyUrlBtn.innerHTML = '<span>✅</span><span>URLをコピーしました！</span>';
            setTimeout(() => {
                copyUrlBtn.innerHTML = originalHtml;
            }, 2500);
        } catch {
            window.prompt('以下のURLをコピーして保存してください:', url);
        }
    });
}

export function createMyShiftState() {
    const initialLeaderTab = document.querySelector('.leaderLaneTabBtn[aria-selected="true"]');
    let toastTimer = null;

    return {
        activeMainTab: 'shift',
        activeLaneFilter: 'all',
        incompleteOnly: false,
        activeLeaderLaneId: initialLeaderTab ? initialLeaderTab.dataset.leaderLaneTab || null : null,
        activeLeaderReqTabs: new Map(),
        isSyncing: false,
        showToast(message, isError = false) {
            const toast = document.getElementById('myshiftToast');
            const toastText = document.getElementById('myshiftToastText');
            if (!toast || !toastText) {
                return;
            }

            toastText.textContent = isError ? `⚠️ ${message}` : `✅ ${message}`;
            toast.classList.remove(
                'hidden',
                'bg-emerald-950/90',
                'border-emerald-500/50',
                'text-emerald-200',
                'bg-red-950/90',
                'border-red-500/50',
                'text-red-200'
            );

            if (isError) {
                toast.classList.add('bg-red-950/90', 'border-red-500/50', 'text-red-200');
            } else {
                toast.classList.add('bg-emerald-950/90', 'border-emerald-500/50', 'text-emerald-200');
            }

            if (toastTimer) {
                clearTimeout(toastTimer);
            }
            toastTimer = setTimeout(() => {
                toast.classList.add('hidden');
            }, 3500);
        },
    };
}
