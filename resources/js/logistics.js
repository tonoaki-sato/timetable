import { initEquipmentFilters } from './logistics/equipment-filters.js';
import { initLogisticsPlanModals } from './logistics/plan-modals.js';

/**
 * 搬出入計画一覧（/timetables/{timetable}/logistics）モーダル操作スクリプト (CSP完全準拠・Vanilla JS)
 */
document.addEventListener('DOMContentLoaded', () => {
    initEquipmentFilters();
    initLogisticsPlanModals();
});
