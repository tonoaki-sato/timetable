import { initMatrixGridControls } from './timetable-matrix/grid-controls.js';
import { initQuickAssignModal } from './timetable-matrix/quick-assign-modal.js';

/**
 * タイムテーブル（マトリクス進行表）エントリポイント
 */
document.addEventListener('DOMContentLoaded', () => {
    initMatrixGridControls();
    initQuickAssignModal();
});
