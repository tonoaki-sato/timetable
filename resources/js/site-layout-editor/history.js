/**
 * Undo / Redo ヒストリー管理
 */
export function createHistory(ctx, deps) {
    function pushHistory(action) {
        ctx.state.undoStack.push(action);
        if (ctx.state.undoStack.length > 30) ctx.state.undoStack.shift();
        ctx.state.redoStack = [];
        deps.inspector.updateSummaryAndInspector();
    }

    async function applyHistoryAction(action, isUndo) {
        if (action.type === 'update') {
            const items = isUndo ? action.before : action.after;
            await deps.api.apiBulkUpdate(items);
        } else if (action.type === 'delete') {
            if (isUndo) {
                const created = await deps.api.apiCreatePlacement(action.snapshot);
                if (created) action.restoredId = created.id;
            } else {
                const targetId = action.restoredId || action.snapshot.id;
                await deps.api.apiDeletePlacement(targetId, false);
            }
        } else if (action.type === 'create') {
            if (isUndo) {
                await deps.api.apiDeletePlacement(action.created.id, false);
            } else {
                const reCreated = await deps.api.apiCreatePlacement(action.created);
                if (reCreated) action.created = reCreated;
            }
        }
    }

    async function performUndo() {
        const action = ctx.state.undoStack.pop();
        if (!action) return;
        ctx.state.redoStack.push(action);
        await applyHistoryAction(action, true);
        deps.inspector.updateSummaryAndInspector();
    }

    async function performRedo() {
        const action = ctx.state.redoStack.pop();
        if (!action) return;
        ctx.state.undoStack.push(action);
        await applyHistoryAction(action, false);
        deps.inspector.updateSummaryAndInspector();
    }

    return {
        pushHistory,
        performUndo,
        performRedo,
        applyHistoryAction,
    };
}
