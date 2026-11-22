import { initTimetableStatusFilters } from './timetables/status-filters.js';
import { initTimetableActionMenus } from './timetables/action-menus.js';
import { initTimetableFormModal } from './timetables/form-modal.js';

/**
 * 運行情報一覧画面（/timetables）および詳細画面（/timetables/{id}）
 * ステータス絞り込みトグルボタン、管理メニュープルダウン、および登録・編集モーダルフォーム操作スクリプト
 * (CSP完全準拠・Vanilla JS)
 */
document.addEventListener('DOMContentLoaded', () => {
    initTimetableStatusFilters();
    initTimetableActionMenus();
    initTimetableFormModal();
});
