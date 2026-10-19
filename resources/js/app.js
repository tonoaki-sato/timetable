// まつり総合管理システム アプリケーションスクリプト
document.addEventListener('DOMContentLoaded', () => {
    initRealtimeClock();
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
