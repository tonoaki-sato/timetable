/**
 * セルフ登録画面 LIFF 初期化 & プロフィール自動取得スクリプト
 * CSP準拠（外部ファイル・イベントリスナー・即時関数）
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', async function () {
        const form = document.getElementById('staffJoinForm');
        if (!form) return;

        const liffId = form.dataset.liffId;
        const defaultAvatar = form.dataset.defaultAvatar || '';
        const manualArea = document.getElementById('manualLineInputArea');

        // LIFF ID未設定環境（ローカル開発環境やオフライン等）のフォールバック
        if (!liffId || typeof liff === 'undefined') {
            if (manualArea) {
                manualArea.classList.remove('hidden');
            }
            return;
        }

        try {
            await liff.init({ liffId: liffId });

            if (liff.isLoggedIn()) {
                const profile = await liff.getProfile();

                // フォームの隠しフィールドにセット
                const userIdInput = document.getElementById('line_user_id');
                const displayNameInput = document.getElementById('line_display_name');
                const pictureUrlInput = document.getElementById('line_picture_url');

                if (userIdInput) userIdInput.value = profile.userId || '';
                if (displayNameInput) displayNameInput.value = profile.displayName || '';
                if (pictureUrlInput) pictureUrlInput.value = profile.pictureUrl || '';

                // UIプレビューバッジの反映
                const preview = document.getElementById('lineProfilePreview');
                const avatar = document.getElementById('lineAvatar');
                const nameText = document.getElementById('lineNameText');

                if (preview && avatar && nameText) {
                    if (profile.pictureUrl) {
                        avatar.src = profile.pictureUrl;
                    } else if (defaultAvatar) {
                        avatar.src = defaultAvatar;
                    }
                    nameText.textContent = (profile.displayName || 'LINEユーザー') + ' として連携中';
                    preview.classList.remove('hidden');
                }
            } else {
                // LINEアプリ内ブラウザで開かれていて未ログインの場合はログイン実行
                if (liff.isInClient()) {
                    liff.login();
                } else if (manualArea) {
                    manualArea.classList.remove('hidden');
                }
            }
        } catch (error) {
            console.warn('[LIFF] 初期化をスキップしました (通常フォームとして動作します):', error);
            if (manualArea) {
                manualArea.classList.remove('hidden');
            }
        }
    });
})();
