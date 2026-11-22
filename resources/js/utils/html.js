/**
 * HTMLエスケープおよびCSRFトークン取得ユーティリティ
 * Strict CSP準拠（DOM操作・文字列サニタイズ用）
 */

/**
 * HTML特殊文字をエスケープする
 *
 * @param {string|number|null|undefined} str
 * @returns {string}
 */
export function escapeHtml(str) {
    if (str === null || str === undefined || str === '') {
        return '';
    }
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * メタタグからCSRFトークンを取得する
 *
 * @returns {string}
 */
export function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}
