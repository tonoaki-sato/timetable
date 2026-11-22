import { initGlobalEventHandlers } from './core/global-handlers.js';
import { initNavigation } from './core/navigation.js';
import { initProgressBars } from './core/progress-bars.js';
import { initQuickSearch } from './core/quick-search.js';

// まつり総合管理システム 全画面共通エントリポイント
document.addEventListener('DOMContentLoaded', () => {
    initGlobalEventHandlers();
    initNavigation();
    initQuickSearch();
    initProgressBars();
});
