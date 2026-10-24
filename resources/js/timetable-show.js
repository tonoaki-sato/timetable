/**
 * 運行タイムテーブル詳細画面スクリプト (CSP完全準拠・バニラJS)
 */
document.addEventListener('DOMContentLoaded', () => {
    initCopyAssignmentsModal();
});

function initCopyAssignmentsModal() {
    const modal = document.getElementById('copyAssignmentsModal');
    if (!modal) return;

    function openModal() {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    // イベント委譲（クリック）
    document.addEventListener('click', (e) => {
        // 開くボタン
        if (e.target.closest('[data-action="open-copy-modal"]')) {
            openModal();
            return;
        }

        // 閉じるボタン（キャンセルボタン、×ボタン等）
        if (e.target.closest('[data-action="close-copy-modal"]')) {
            closeModal();
            return;
        }

        // モーダル背景（外側）クリックで閉じる
        if (e.target === modal) {
            closeModal();
            return;
        }
    });

    // Escキーで閉じる
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
}
