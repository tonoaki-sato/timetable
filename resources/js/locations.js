import { initDragAndDropReorder } from './locations/reorder.js';
import { initLocationModals } from './locations/modals.js';

/**
 * 拠点管理機能スクリプト (CSP準拠・外部JavaScriptモジュール)
 */
document.addEventListener('DOMContentLoaded', () => {
    initDragAndDropReorder();
    initLocationModals();
});
