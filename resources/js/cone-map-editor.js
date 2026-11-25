import { createConeMapApi } from './cone-map-editor/api.js';
import { createConeMapContext } from './cone-map-editor/context.js';
import { createConeMapHistory } from './cone-map-editor/history.js';
import { createConeMapInspector } from './cone-map-editor/inspector.js';
import { createConeMapInteractions } from './cone-map-editor/interactions.js';
import { createConeMapRenderer } from './cone-map-editor/svg-renderer.js';
import { loadSvgMap } from './cone-map-editor/viewport.js';

/**
 * カラーコーン・バリケード配置エディタ (Cone Map Editor)
 */
document.addEventListener('DOMContentLoaded', () => {
    const appEl = document.getElementById('cone-map-editor-app');
    if (!appEl) return;

    const ctx = createConeMapContext(appEl);
    const deps = {};

    deps.inspector = createConeMapInspector(ctx, deps);
    deps.history = createConeMapHistory(ctx, deps);
    deps.api = createConeMapApi(ctx, deps);
    deps.interactions = createConeMapInteractions(ctx, deps);

    deps.renderer = createConeMapRenderer(ctx, {
        onNodeMouseDown: (e, kind, entity) => deps.interactions.handleNodeMouseDown(e, kind, entity),
        onNodeDblClick: (e, kind, entity) => deps.interactions.handleNodeDblClick(e, kind, entity),
        onCalloutAnchorMouseDown: (e, callout) => deps.interactions.handleCalloutAnchorMouseDown(e, callout),
        onNodeMouseEnter: (e, kind, entity) => deps.inspector.showHoverTooltip(e, kind, entity),
        onNodeMouseMove: (e) => deps.inspector.moveHoverTooltip(e),
        onNodeMouseLeave: () => deps.inspector.hideHoverTooltip(),
        onAfterRenderAll: () => {
            deps.inspector.updateSummaryAndInspector();
            deps.inspector.syncCalloutsList();
        },
    });

    deps.inspector.bindInspectorEvents();
    deps.api.bindClearAndBaseMapActions();
    deps.interactions.bindInteractionEvents();

    loadSvgMap(ctx, () => deps.renderer.renderAll());
});
