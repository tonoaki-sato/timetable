/**
 * 本部運行総合ダッシュボード専用スクリプト
 * (CSP完全準拠・Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', () => {
    initRealtimeClock();
    initDashboardRealtime();
});

/**
 * ダッシュボードの現在日時リアルタイム時計
 */
function initRealtimeClock() {
    const clockElement = document.getElementById('realtime-clock');
    if (!clockElement || window.__realtimeClockInitialized) return;
    window.__realtimeClockInitialized = true;

    const serverTimeMs = parseInt(clockElement.dataset.serverTime, 10);
    const clientTimeMs = Date.now();
    window.__dashboardTimeOffset = isNaN(serverTimeMs) ? 0 : serverTimeMs - clientTimeMs;
    const dayNames = ['日', '月', '火', '水', '木', '金', '土'];

    function updateClock() {
        const offset = typeof window.__dashboardTimeOffset === 'number' ? window.__dashboardTimeOffset : 0;
        const now = new Date(Date.now() + offset);
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

/**
 * ダッシュボードのサーバー時刻・接続同期（軽量ハートビート）
 */
function initDashboardRealtime() {
    const rootEl = document.getElementById('dashboard-realtime-root');
    if (!rootEl || window.__dashboardRealtimeInitialized) return;
    window.__dashboardRealtimeInitialized = true;

    const realtimeUrl = rootEl.dataset.realtimeUrl;
    if (!realtimeUrl) return;

    const statusTextEl = document.getElementById('dashboard-realtime-status');
    const refreshBtn = document.getElementById('dashboard-manual-refresh');
    const refreshIcon = document.getElementById('dashboard-refresh-icon');

    let isFetching = false;

    async function fetchHeartbeat() {
        if (isFetching) return;
        isFetching = true;

        if (refreshIcon) refreshIcon.classList.add('animate-spin');
        if (statusTextEl) statusTextEl.textContent = '同期中...';

        try {
            const response = await fetch(realtimeUrl, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();
            if (typeof data.server_time_ms === 'number') {
                window.__dashboardTimeOffset = data.server_time_ms - Date.now();
            }
            if (statusTextEl) {
                statusTextEl.textContent = data.updated_at_label ? `${data.updated_at_label} 同期済` : '正常稼働中';
            }
        } catch (err) {
            if (statusTextEl) statusTextEl.textContent = '再接続待機中';
        } finally {
            isFetching = false;
            if (refreshIcon) refreshIcon.classList.remove('animate-spin');
        }
    }

    refreshBtn?.addEventListener('click', () => {
        fetchHeartbeat();
    });
}
