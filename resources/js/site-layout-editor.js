import { createPlacementApi } from './site-layout-editor/api.js';
import { createEditorContext } from './site-layout-editor/context.js';
import { createCustomIcons } from './site-layout-editor/custom-icons.js';
import { createHistory } from './site-layout-editor/history.js';
import { renderHygieneCirclesAndCheck } from './site-layout-editor/hygiene.js';
import { createInspector } from './site-layout-editor/inspector.js';
import { createInteractions } from './site-layout-editor/interactions.js';
import { createSvgRenderer } from './site-layout-editor/svg-renderer.js';
import { loadSvgMap } from './site-layout-editor/viewport.js';

/**
 * 白地図配置エディタ (Site Layout Editor) - 3ペイン CADベクタードローツール
 * A3 1/400 (842pt × 1191pt) 精密ベクター編集・スマートスナップ・20m衛生チェック・PDF出力連動
 */
document.addEventListener('DOMContentLoaded', () => {
    const appEl = document.getElementById('site-layout-editor-app');
    if (!appEl) return;

    const ctx = createEditorContext(appEl);
    const deps = {};

    deps.inspector = createInspector(ctx, deps);
    deps.history = createHistory(ctx, deps);
    deps.api = createPlacementApi(ctx, deps);
    deps.interactions = createInteractions(ctx, deps);
    deps.customIcons = createCustomIcons(ctx, deps);

    deps.renderer = createSvgRenderer(ctx, {
        onNodeMouseDown: (e, placement) => deps.interactions.handleNodeMouseDown(e, placement),
        onNodeDblClick: (e, placement) => {
            e.stopPropagation();
            if (ctx.state.toolMode === 'pan' || ctx.state.spacePressed) return;
            deps.inspector.openInlineTextEditor(placement);
        },
        onNodeMouseEnter: (e, placement) => deps.inspector.showHoverTooltip(e, placement),
        onNodeMouseMove: (e) => deps.inspector.moveHoverTooltip(e),
        onNodeMouseLeave: () => deps.inspector.hideHoverTooltip(),
        onAfterRenderAll: () => {
            renderHygieneCirclesAndCheck(ctx);
            deps.inspector.updateSummaryAndInspector();
            deps.inspector.syncSidebarLayoutItems();
        },
    });

    deps.inspector.bindInspectorEvents();
    deps.api.bindResetAndClearActions();
    deps.interactions.bindInteractionEvents();
    deps.customIcons.bindCustomIconEvents();

    loadSvgMap(ctx, () => deps.renderer.renderAllPlacements());
});
