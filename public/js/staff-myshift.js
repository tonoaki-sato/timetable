/**
 * 現場マイタイムテーブル用スクリプト
 * CSP準拠（外部ファイル・イベントリスナー・即時関数）
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // 1. 到着チェックイン処理
        const checkInBtn = document.getElementById('checkInBtn');
        if (checkInBtn) {
            checkInBtn.addEventListener('click', async function () {
                const endpoint = checkInBtn.dataset.endpoint;
                const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

                if (!endpoint) return;

                checkInBtn.disabled = true;
                const originalText = checkInBtn.innerHTML;
                checkInBtn.innerHTML = '<span>⏳</span><span>受付中...</span>';

                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        const timeStr = data.time || new Date().toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' });
                        
                        // 受付済みバッジに置換
                        const container = checkInBtn.parentElement;
                        if (container) {
                            container.innerHTML = `
                                <div class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-300">
                                    <span>✅</span>
                                    <span>受付済み (${timeStr})</span>
                                </div>
                            `;
                        }
                    } else {
                        alert('チェックインの記録に失敗しました。電波の良い場所で再度お試しください。');
                        checkInBtn.disabled = false;
                        checkInBtn.innerHTML = originalText;
                    }
                } catch (err) {
                    console.error('Checkin error:', err);
                    alert('通信エラーが発生しました。電波の良い場所で再度お試しください。');
                    checkInBtn.disabled = false;
                    checkInBtn.innerHTML = originalText;
                }
            });
        }

        // 2. URLコピーボタン
        const copyUrlBtn = document.getElementById('copyUrlBtn');
        if (copyUrlBtn) {
            copyUrlBtn.addEventListener('click', async function () {
                const url = window.location.href;
                try {
                    await navigator.clipboard.writeText(url);
                    const originalText = copyUrlBtn.innerHTML;
                    copyUrlBtn.innerHTML = '<span>✅</span><span>URLをコピーしました！</span>';
                    setTimeout(() => {
                        copyUrlBtn.innerHTML = originalText;
                    }, 2500);
                } catch (e) {
                    prompt('以下のURLをコピーして保存してください:', url);
                }
            });
        }

        // 3. 最新情報に更新ボタン
        const refreshBtn = document.getElementById('refreshBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                window.location.reload();
            });
        }
    });
})();
