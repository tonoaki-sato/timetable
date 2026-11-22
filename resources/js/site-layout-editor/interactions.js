import { showToast } from './context.js';
import { renderHygieneCirclesAndCheck } from './hygiene.js';
import { clearGuideLines, computeSnap, isAlongStreet } from './snap.js';
import { fitToScreen, getSvgCoords, updateTransform, zoomAround } from './viewport.js';

/**
 * キャンバス操作・マウス/rAF移動・範囲選択・リサイズ・ショートカット・整列・D&D配置
 */
export function createInteractions(ctx, deps) {
    const { scaleRatio } = ctx.config;

    function setToolMode(mode) {
        const { canvasContainer, btnModeSelect, btnModePan } = ctx.dom;
        ctx.state.toolMode = mode;
        const isPan = mode === 'pan' || ctx.state.spacePressed;
        canvasContainer.style.cursor = isPan ? 'grab' : 'default';
        if (btnModeSelect && btnModePan) {
            btnModeSelect.className =
                mode === 'select'
                    ? 'px-2 py-1 rounded-md font-bold bg-indigo-600 text-white transition cursor-pointer'
                    : 'px-2 py-1 rounded-md font-bold text-slate-700 hover:bg-slate-200 transition cursor-pointer';
            btnModePan.className =
                mode === 'pan'
                    ? 'px-2 py-1 rounded-md font-bold bg-indigo-600 text-white transition cursor-pointer'
                    : 'px-2 py-1 rounded-md font-bold text-slate-700 hover:bg-slate-200 transition cursor-pointer';
        }
    }

    function toggleToolMode() {
        const { state } = ctx;
        if (state.isMarquee) {
            state.isMarquee = false;
            if (ctx.dom.marqueeBox) ctx.dom.marqueeBox.classList.add('hidden');
        }
        if (state.isMoving) {
            state.isMoving = false;
            clearGuideLines(ctx);
        }
        if (state.isResizing) {
            state.isResizing = false;
            state.resizeOrig = null;
        }
        if (state.isPanning) {
            state.isPanning = false;
        }
        const nextMode = state.toolMode === 'select' ? 'pan' : 'select';
        setToolMode(nextMode);
    }

    function handleNodeMouseDown(e, placement) {
        const { state } = ctx;
        if (e.button !== 0 || state.toolMode === 'pan' || state.spacePressed) return;
        e.stopPropagation();

        const pid = placement.id;
        if (e.shiftKey) {
            deps.inspector.toggleSelection(pid);
        } else if (!state.selectedIds.has(pid)) {
            deps.inspector.setSingleSelection(pid);
        }

        state.isMoving = true;
        const sc = getSvgCoords(ctx, e.clientX, e.clientY);
        state.moveStartSvgX = sc.x;
        state.moveStartSvgY = sc.y;
        state.moveOrigPositions.clear();
        state.selectedIds.forEach((sid) => {
            const sp = state.placements.find((p) => p.id === sid);
            if (sp) {
                state.moveOrigPositions.set(sid, {
                    x: parseFloat(sp.pos_x),
                    y: parseFloat(sp.pos_y),
                    rot: sp.rotation,
                    locked: sp.is_rotation_locked,
                });
            }
        });
    }

    function incrementLabel(text) {
        const m = String(text || '').match(/^(.*?)(\d+)$/);
        if (!m) return text;
        const prefix = m[1];
        const numStr = m[2];
        const nextNum = String(parseInt(numStr, 10) + 1).padStart(numStr.length, '0');
        return `${prefix}${nextNum}`;
    }

    async function duplicateSelected() {
        if (ctx.state.selectedIds.size !== 1) return;
        const src = ctx.state.placements.find((p) => p.id === [...ctx.state.selectedIds][0]);
        if (!src) return;

        const w = parseFloat(src.width);
        const h = parseFloat(src.height);
        const isVerticalColumn = isAlongStreet(parseFloat(src.pos_x), parseFloat(src.pos_y), w);
        const newX = isVerticalColumn ? parseFloat(src.pos_x) : parseFloat(src.pos_x) + w;
        const newY = isVerticalColumn ? parseFloat(src.pos_y) + h : parseFloat(src.pos_y);

        await deps.api.apiCreatePlacement(
            {
                shape_type: src.shape_type,
                display_text: incrementLabel(src.display_text),
                color_theme: src.color_theme,
                pos_x: Math.round(newX * 100) / 100,
                pos_y: Math.round(newY * 100) / 100,
                width: w,
                height: h,
                rotation: src.rotation,
                is_rotation_locked: src.is_rotation_locked,
                font_size: src.font_size,
            },
            true,
        );
    }

    async function alignSelected(mode) {
        const nodes = ctx.state.placements.filter((p) => ctx.state.selectedIds.has(p.id));
        if (nodes.length < 2) return;

        const before = nodes.map((p) => ({ id: p.id, pos_x: p.pos_x, pos_y: p.pos_y }));
        const items = [];

        if (mode === 'left') {
            const minX = Math.min(...nodes.map((n) => parseFloat(n.pos_x)));
            nodes.forEach((n) => items.push({ id: n.id, pos_x: minX, pos_y: n.pos_y }));
        } else if (mode === 'top') {
            const minY = Math.min(...nodes.map((n) => parseFloat(n.pos_y)));
            nodes.forEach((n) => items.push({ id: n.id, pos_x: n.pos_x, pos_y: minY }));
        } else if (mode === 'pack_vert') {
            const sorted = [...nodes].sort((a, b) => parseFloat(a.pos_y) - parseFloat(b.pos_y));
            const baseX = parseFloat(sorted[0].pos_x);
            let curY = parseFloat(sorted[0].pos_y);
            sorted.forEach((n) => {
                items.push({ id: n.id, pos_x: baseX, pos_y: Math.round(curY * 100) / 100 });
                curY += parseFloat(n.height);
            });
        } else if (mode === 'pack_horiz') {
            const sorted = [...nodes].sort((a, b) => parseFloat(a.pos_x) - parseFloat(b.pos_x));
            let curX = parseFloat(sorted[0].pos_x);
            const baseY = parseFloat(sorted[0].pos_y);
            sorted.forEach((n) => {
                items.push({ id: n.id, pos_x: Math.round(curX * 100) / 100, pos_y: baseY });
                curX += parseFloat(n.width);
            });
        }

        await deps.api.apiBulkUpdate(items, true, before);
        showToast(ctx, '整列を適用しました');
    }

    function bindPaletteItem(item) {
        item.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData(
                'application/json',
                JSON.stringify({
                    kind: 'palette',
                    shape_type: item.dataset.shapeType,
                    default_width: parseFloat(item.dataset.defaultWidth) || 13.86,
                    default_height: parseFloat(item.dataset.defaultHeight) || 13.86,
                    color_theme: item.dataset.defaultColor || 'red',
                }),
            );
            e.dataTransfer.effectAllowed = 'copy';
        });

        const btnEdit = item.querySelector('.btn-edit-custom-icon');
        if (btnEdit) {
            btnEdit.addEventListener('click', (e) => {
                e.stopPropagation();
                deps.customIcons.openEditIconModal(item.dataset.shapeType);
            });
        }

        const btnDelete = item.querySelector('.btn-delete-custom-icon');
        if (btnDelete) {
            btnDelete.addEventListener('click', (e) => {
                e.stopPropagation();
                deps.customIcons.openDeleteIconModal(item.dataset.shapeType);
            });
        }
    }

    function bindInteractionEvents() {
        const {
            canvasContainer,
            marqueeBox,
            inspWidthM,
            inspHeightM,
            btnModeSelect,
            btnModePan,
            btnUndo,
            btnRedo,
            btnDuplicate,
            btnDeleteSelected,
            btnRotateSelected,
            btnAlignLeft,
            btnAlignTop,
            btnPackVert,
            btnPackHoriz,
            btnToggleHygiene,
            btnToggleSnap,
            btnZoomIn,
            btnZoomOut,
            btnZoom100,
            btnZoomFit,
            tabBtnShapes,
            tabBtnItems,
            tabPanelShapes,
            tabPanelItems,
            sidebarSearch,
            sidebarUnplacedOnly,
        } = ctx.dom;
        const { state, iconDefinitions } = ctx;

        // キャンバス上の右クリックで「選択」↔「手のひら」をトグル切替
        canvasContainer.addEventListener('contextmenu', (e) => {
            const tag = e.target?.tagName?.toLowerCase();
            if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
            e.preventDefault();
            toggleToolMode();
        });

        // マウス押下
        canvasContainer.addEventListener('mousedown', (e) => {
            if (e.button !== 0) return;
            if (state.toolMode === 'pan' || state.spacePressed) {
                state.isPanning = true;
                state.panStartX = e.clientX - state.x;
                state.panStartY = e.clientY - state.y;
                canvasContainer.style.cursor = 'grabbing';
                return;
            }
            // 背景クリック -> 範囲選択(ラバーバンド)開始
            state.isMarquee = true;
            state.marqueeStartClientX = e.clientX;
            state.marqueeStartClientY = e.clientY;
            if (!e.shiftKey) deps.inspector.clearSelection();
        });

        let mouseMoveRafId = null;
        let pendingMouseEvent = null;

        function processMouseMove(ev) {
            if (state.isPanning) {
                state.x = ev.clientX - state.panStartX;
                state.y = ev.clientY - state.panStartY;
                updateTransform(ctx, false);
                return;
            }

            if (state.isMarquee && marqueeBox) {
                const rect = canvasContainer.getBoundingClientRect();
                const x1 = Math.min(state.marqueeStartClientX, ev.clientX) - rect.left;
                const y1 = Math.min(state.marqueeStartClientY, ev.clientY) - rect.top;
                const w = Math.abs(ev.clientX - state.marqueeStartClientX);
                const h = Math.abs(ev.clientY - state.marqueeStartClientY);
                if (w > 4 || h > 4) {
                    marqueeBox.style.left = `${x1}px`;
                    marqueeBox.style.top = `${y1}px`;
                    marqueeBox.style.width = `${w}px`;
                    marqueeBox.style.height = `${h}px`;
                    marqueeBox.classList.remove('hidden');
                }
                return;
            }

            // リサイズ中 (回転考慮の逆行列変換)
            if (state.isResizing && state.resizeOrig) {
                const sc = getSvgCoords(ctx, ev.clientX, ev.clientY);
                const dx = sc.x - state.resizeStartSvgX;
                const dy = sc.y - state.resizeStartSvgY;
                const target = state.placements.find((p) => p.id === state.resizeId);
                if (!target) return;

                const rad = -(state.resizeOrig.rot * Math.PI) / 180;
                const ldx = dx * Math.cos(rad) - dy * Math.sin(rad);
                const ldy = dx * Math.sin(rad) + dy * Math.cos(rad);
                const minS = 7.0866;

                let { x, y, w, h } = state.resizeOrig;
                if (state.resizeHandle.includes('r')) w = Math.max(minS, w + ldx);
                if (state.resizeHandle.includes('b')) h = Math.max(minS, h + ldy);
                if (state.resizeHandle.includes('l')) {
                    const nw = Math.max(minS, w - ldx);
                    x += w - nw;
                    w = nw;
                }
                if (state.resizeHandle.includes('t')) {
                    const nh = Math.max(minS, h - ldy);
                    y += h - nh;
                    h = nh;
                }

                target.pos_x = Math.round(x * 100) / 100;
                target.pos_y = Math.round(y * 100) / 100;
                target.width = Math.round(w * 100) / 100;
                target.height = Math.round(h * 100) / 100;
                deps.renderer.renderPlacementNode(target);
                if (inspWidthM) inspWidthM.value = (target.width / scaleRatio).toFixed(2);
                if (inspHeightM) inspHeightM.value = (target.height / scaleRatio).toFixed(2);
                return;
            }

            // ノード移動中
            if (state.isMoving) {
                const sc = getSvgCoords(ctx, ev.clientX, ev.clientY);
                const dx = sc.x - state.moveStartSvgX;
                const dy = sc.y - state.moveStartSvgY;

                if (state.selectedIds.size === 1) {
                    const sid = [...state.selectedIds][0];
                    const target = state.placements.find((p) => p.id === sid);
                    const orig = state.moveOrigPositions.get(sid);
                    if (target && orig) {
                        const snapped = ev.altKey
                            ? { x: orig.x + dx, y: orig.y + dy }
                            : computeSnap(ctx, sid, orig.x + dx, orig.y + dy, parseFloat(target.width), parseFloat(target.height));
                        target.pos_x = Math.round(snapped.x * 100) / 100;
                        target.pos_y = Math.round(snapped.y * 100) / 100;
                        deps.renderer.renderPlacementNode(target);
                        if (target.shape_type === 'water_supply') renderHygieneCirclesAndCheck(ctx);
                    }
                } else {
                    state.selectedIds.forEach((sid) => {
                        const target = state.placements.find((p) => p.id === sid);
                        const orig = state.moveOrigPositions.get(sid);
                        if (target && orig) {
                            target.pos_x = Math.round((orig.x + dx) * 100) / 100;
                            target.pos_y = Math.round((orig.y + dy) * 100) / 100;
                            deps.renderer.renderPlacementNode(target);
                        }
                    });
                }
            }
        }

        function flushPendingMouseMove() {
            if (mouseMoveRafId !== null) {
                cancelAnimationFrame(mouseMoveRafId);
                mouseMoveRafId = null;
            }
            if (pendingMouseEvent !== null) {
                const ev = pendingMouseEvent;
                pendingMouseEvent = null;
                processMouseMove(ev);
            }
        }

        window.addEventListener('mousemove', (e) => {
            if (!state.isPanning && !state.isMarquee && !state.isResizing && !state.isMoving) {
                return;
            }
            pendingMouseEvent = { clientX: e.clientX, clientY: e.clientY, altKey: e.altKey };
            if (mouseMoveRafId === null) {
                mouseMoveRafId = requestAnimationFrame(() => {
                    mouseMoveRafId = null;
                    if (pendingMouseEvent !== null) {
                        const ev = pendingMouseEvent;
                        pendingMouseEvent = null;
                        processMouseMove(ev);
                    }
                });
            }
        });

        window.addEventListener('mouseup', async (e) => {
            flushPendingMouseMove();

            if (state.isPanning) {
                state.isPanning = false;
                canvasContainer.style.cursor = state.toolMode === 'pan' || state.spacePressed ? 'grab' : 'default';
                return;
            }

            if (state.isMarquee) {
                state.isMarquee = false;
                if (marqueeBox) marqueeBox.classList.add('hidden');
                const w = Math.abs(e.clientX - state.marqueeStartClientX);
                const h = Math.abs(e.clientY - state.marqueeStartClientY);
                if (w > 5 || h > 5) {
                    const p1 = getSvgCoords(ctx, state.marqueeStartClientX, state.marqueeStartClientY);
                    const p2 = getSvgCoords(ctx, e.clientX, e.clientY);
                    const rx1 = Math.min(p1.x, p2.x);
                    const rx2 = Math.max(p1.x, p2.x);
                    const ry1 = Math.min(p1.y, p2.y);
                    const ry2 = Math.max(p1.y, p2.y);
                    const prev = new Set(state.selectedIds);
                    state.placements.forEach((p) => {
                        const px = parseFloat(p.pos_x);
                        const py = parseFloat(p.pos_y);
                        const pw = parseFloat(p.width);
                        const ph = parseFloat(p.height);
                        if (px + pw >= rx1 && px <= rx2 && py + ph >= ry1 && py <= ry2) {
                            state.selectedIds.add(p.id);
                        }
                    });
                    deps.inspector.refreshChangedNodes(prev, state.selectedIds);
                    deps.inspector.updateSummaryAndInspector();
                }
                return;
            }

            if (state.isResizing) {
                const target = state.placements.find((p) => p.id === state.resizeId);
                const orig = state.resizeOrig;
                state.isResizing = false;
                state.resizeOrig = null;
                if (target && orig && (target.width !== orig.w || target.height !== orig.h || target.pos_x !== orig.x || target.pos_y !== orig.y)) {
                    const beforeOverride = [
                        {
                            id: target.id,
                            pos_x: orig.x,
                            pos_y: orig.y,
                            width: orig.w,
                            height: orig.h,
                            rotation: orig.rot,
                            is_rotation_locked: target.is_rotation_locked,
                            display_text: target.display_text,
                            color_theme: target.color_theme,
                        },
                    ];
                    await deps.api.applySingleUpdate(
                        target,
                        { pos_x: target.pos_x, pos_y: target.pos_y, width: target.width, height: target.height },
                        true,
                        beforeOverride,
                    );
                }
                return;
            }

            if (state.isMoving) {
                state.isMoving = false;
                clearGuideLines(ctx);
                const updates = [];
                const before = [];
                state.selectedIds.forEach((sid) => {
                    const target = state.placements.find((p) => p.id === sid);
                    const orig = state.moveOrigPositions.get(sid);
                    if (target && orig && (target.pos_x !== orig.x || target.pos_y !== orig.y)) {
                        before.push({ id: sid, pos_x: orig.x, pos_y: orig.y, rotation: orig.rot, is_rotation_locked: orig.locked });
                        const inStreet = isAlongStreet(target.pos_x, target.pos_y, parseFloat(target.width));
                        const nextLocked = inStreet;
                        const nextRot = inStreet ? 0 : target.rotation;
                        target.is_rotation_locked = nextLocked;
                        target.rotation = nextRot;
                        updates.push({ id: sid, pos_x: target.pos_x, pos_y: target.pos_y, rotation: nextRot, is_rotation_locked: nextLocked });
                    }
                });
                if (updates.length === 1) {
                    const target = state.placements.find((p) => p.id === updates[0].id);
                    if (target) await deps.api.applySingleUpdate(target, updates[0], true, before);
                } else if (updates.length > 1) {
                    await deps.api.apiBulkUpdate(updates, true, before);
                }
            }
        });

        canvasContainer.addEventListener(
            'wheel',
            (e) => {
                e.preventDefault();
                zoomAround(ctx, e.deltaY < 0 ? 1.15 : 0.85, e.clientX, e.clientY);
            },
            { passive: false },
        );

        // キーボードショートカット
        window.addEventListener('keydown', (e) => {
            const tag = document.activeElement?.tagName?.toLowerCase();
            const isInput = tag === 'input' || tag === 'textarea' || tag === 'select';
            if (isInput) return;

            if (e.code === 'Space' && !state.spacePressed) {
                e.preventDefault();
                state.spacePressed = true;
                canvasContainer.style.cursor = 'grab';
            } else if (e.key === 'v' || e.key === 'V') {
                setToolMode('select');
            } else if (e.key === 'h' || e.key === 'H') {
                setToolMode('pan');
            } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z' && !e.shiftKey) {
                e.preventDefault();
                deps.history.performUndo();
            } else if ((e.ctrlKey || e.metaKey) && (e.key.toLowerCase() === 'y' || (e.shiftKey && e.key.toLowerCase() === 'z'))) {
                e.preventDefault();
                deps.history.performRedo();
            } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'd') {
                e.preventDefault();
                duplicateSelected();
            } else if (e.key === 'Delete' || e.key === 'Backspace') {
                if (state.selectedIds.size > 0) {
                    e.preventDefault();
                    deps.api.deleteSelectedPlacements();
                }
            } else if (e.key === 'r' || e.key === 'R') {
                if (state.selectedIds.size === 1) {
                    const p = state.placements.find((item) => item.id === [...state.selectedIds][0]);
                    const def = p ? iconDefinitions[p.shape_type] : null;
                    if (p && !p.is_rotation_locked && (!def || def.is_rotatable)) {
                        e.preventDefault();
                        deps.api.applySingleUpdate(p, { rotation: (parseInt(p.rotation, 10) + 45) % 360 });
                    }
                }
            } else if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key) && state.selectedIds.size > 0) {
                e.preventDefault();
                const step = e.shiftKey ? 3.54 : 0.5;
                const dx = e.key === 'ArrowLeft' ? -step : e.key === 'ArrowRight' ? step : 0;
                const dy = e.key === 'ArrowUp' ? -step : e.key === 'ArrowDown' ? step : 0;
                state.selectedIds.forEach((sid) => {
                    const p = state.placements.find((item) => item.id === sid);
                    if (p) {
                        p.pos_x = Math.round((parseFloat(p.pos_x) + dx) * 100) / 100;
                        p.pos_y = Math.round((parseFloat(p.pos_y) + dy) * 100) / 100;
                        deps.renderer.renderPlacementNode(p);
                    }
                });
                renderHygieneCirclesAndCheck(ctx);
                clearTimeout(state.nudgeTimer);
                state.nudgeTimer = setTimeout(() => {
                    const items = [...state.selectedIds]
                        .map((sid) => {
                            const p = state.placements.find((item) => item.id === sid);
                            return p ? { id: p.id, pos_x: p.pos_x, pos_y: p.pos_y } : null;
                        })
                        .filter(Boolean);
                    deps.api.apiBulkUpdate(items, false);
                }, 350);
            } else if (e.key === 'Escape') {
                deps.inspector.clearSelection();
            }
        });

        window.addEventListener('keyup', (e) => {
            if (e.code === 'Space') {
                state.spacePressed = false;
                canvasContainer.style.cursor = state.toolMode === 'pan' ? 'grab' : 'default';
            }
        });

        // ツールバー & 左ペインバインド
        if (btnModeSelect) btnModeSelect.addEventListener('click', () => setToolMode('select'));
        if (btnModePan) btnModePan.addEventListener('click', () => setToolMode('pan'));
        if (btnUndo) btnUndo.addEventListener('click', deps.history.performUndo);
        if (btnRedo) btnRedo.addEventListener('click', deps.history.performRedo);
        if (btnDuplicate) btnDuplicate.addEventListener('click', duplicateSelected);
        if (btnDeleteSelected) btnDeleteSelected.addEventListener('click', deps.api.deleteSelectedPlacements);
        if (btnRotateSelected) {
            btnRotateSelected.addEventListener('click', () => {
                if (state.selectedIds.size !== 1) return;
                const p = state.placements.find((item) => item.id === [...state.selectedIds][0]);
                if (p && !p.is_rotation_locked) deps.api.applySingleUpdate(p, { rotation: (parseInt(p.rotation, 10) + 45) % 360 });
            });
        }
        if (btnAlignLeft) btnAlignLeft.addEventListener('click', () => alignSelected('left'));
        if (btnAlignTop) btnAlignTop.addEventListener('click', () => alignSelected('top'));
        if (btnPackVert) btnPackVert.addEventListener('click', () => alignSelected('pack_vert'));
        if (btnPackHoriz) btnPackHoriz.addEventListener('click', () => alignSelected('pack_horiz'));

        if (btnToggleHygiene) {
            btnToggleHygiene.addEventListener('click', () => {
                state.showHygiene = !state.showHygiene;
                btnToggleHygiene.textContent = state.showHygiene ? '💧 20m衛生圏 ON' : '💧 20m衛生圏 OFF';
                btnToggleHygiene.className = state.showHygiene
                    ? 'px-2.5 py-1 bg-sky-600 text-white border border-sky-600 rounded-lg font-bold shadow-2xs transition cursor-pointer'
                    : 'px-2.5 py-1 bg-white text-slate-600 border border-slate-300 rounded-lg font-bold shadow-2xs transition cursor-pointer';
                renderHygieneCirclesAndCheck(ctx);
            });
        }

        if (btnToggleSnap) {
            btnToggleSnap.addEventListener('click', () => {
                state.snapEnabled = !state.snapEnabled;
                btnToggleSnap.textContent = state.snapEnabled ? '🧲 吸着 ON' : '🧲 吸着 OFF';
                btnToggleSnap.className = state.snapEnabled
                    ? 'px-2.5 py-1 bg-emerald-600 text-white border border-emerald-600 rounded-lg font-bold shadow-2xs transition cursor-pointer'
                    : 'px-2.5 py-1 bg-white text-slate-600 border border-slate-300 rounded-lg font-bold shadow-2xs transition cursor-pointer';
            });
        }

        if (btnZoomIn) {
            btnZoomIn.addEventListener('click', () => {
                const r = canvasContainer.getBoundingClientRect();
                zoomAround(ctx, 1.25, r.left + r.width / 2, r.top + r.height / 2);
            });
        }
        if (btnZoomOut) {
            btnZoomOut.addEventListener('click', () => {
                const r = canvasContainer.getBoundingClientRect();
                zoomAround(ctx, 0.8, r.left + r.width / 2, r.top + r.height / 2);
            });
        }
        if (btnZoom100) {
            btnZoom100.addEventListener('click', () => {
                const r = canvasContainer.getBoundingClientRect();
                state.scale = 1.0;
                state.x = (r.width - state.svgWidth) / 2;
                state.y = (r.height - state.svgHeight) / 2;
                updateTransform(ctx);
            });
        }
        if (btnZoomFit) btnZoomFit.addEventListener('click', () => fitToScreen(ctx));

        // 左ペインタブ切替
        if (tabBtnShapes && tabBtnItems) {
            tabBtnShapes.addEventListener('click', () => {
                tabPanelShapes.classList.remove('hidden');
                tabPanelItems.classList.add('hidden');
                tabPanelItems.classList.remove('flex');
                tabBtnShapes.className = 'py-1 rounded-lg text-xs font-bold bg-white text-indigo-700 shadow-2xs transition cursor-pointer';
                tabBtnItems.className = 'py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition cursor-pointer';
            });
            tabBtnItems.addEventListener('click', () => {
                tabPanelShapes.classList.add('hidden');
                tabPanelItems.classList.remove('hidden');
                tabPanelItems.classList.add('flex');
                tabBtnItems.className = 'py-1 rounded-lg text-xs font-bold bg-white text-indigo-700 shadow-2xs transition cursor-pointer';
                tabBtnShapes.className = 'py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition cursor-pointer';
            });
        }

        if (sidebarSearch) sidebarSearch.addEventListener('input', deps.inspector.syncSidebarLayoutItems);
        if (sidebarUnplacedOnly) sidebarUnplacedOnly.addEventListener('change', deps.inspector.syncSidebarLayoutItems);

        // パレット＆台帳からのドラッグ＆ドロップ配置
        document.querySelectorAll('.palette-item').forEach((item) => bindPaletteItem(item));

        document.querySelectorAll('.sidebar-layout-item').forEach((item) => {
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData(
                    'application/json',
                    JSON.stringify({
                        kind: 'layout_item',
                        layout_item_id: parseInt(item.dataset.layoutItemId, 10),
                        code: item.dataset.code,
                        item_type: item.dataset.itemType,
                    }),
                );
                e.dataTransfer.effectAllowed = 'copy';
            });
            // 配置済アイテムをクリックするとその位置にズーム＆フォーカス
            item.addEventListener('click', () => {
                const lid = parseInt(item.dataset.layoutItemId, 10);
                const found = state.placements.find((p) => p.layout_item_id === lid);
                if (!found) return;
                deps.inspector.setSingleSelection(found.id);
                const rect = canvasContainer.getBoundingClientRect();
                state.scale = 3.0;
                state.x = rect.width / 2 - (parseFloat(found.pos_x) + parseFloat(found.width) / 2) * state.scale;
                state.y = rect.height / 2 - (parseFloat(found.pos_y) + parseFloat(found.height) / 2) * state.scale;
                updateTransform(ctx);
            });
        });

        canvasContainer.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
        });

        canvasContainer.addEventListener('drop', async (e) => {
            e.preventDefault();
            const raw = e.dataTransfer.getData('application/json');
            if (!raw) return;

            let data;
            try {
                data = JSON.parse(raw);
            } catch {
                return;
            }

            const sc = getSvgCoords(ctx, e.clientX, e.clientY);
            let shapeType = data.shape_type || 'booth_standard';
            let w = data.default_width || 13.86;
            let h = data.default_height || 13.86;
            let color = data.color_theme || 'red';
            let displayText = '';
            let layoutItemId = null;

            if (data.kind === 'layout_item') {
                layoutItemId = data.layout_item_id;
                displayText = data.code || '';
                if (data.item_type === 'facility') {
                    shapeType = 'water_supply';
                    w = 6.24;
                    h = 6.24;
                    color = 'blue';
                }
            }

            let posX = sc.x - w / 2;
            let posY = sc.y - h / 2;
            if (state.snapEnabled) {
                const snapped = computeSnap(ctx, null, posX, posY, w, h);
                posX = snapped.x;
                posY = snapped.y;
                clearGuideLines(ctx);
            }

            const inStreet = isAlongStreet(posX, posY, w);
            await deps.api.apiCreatePlacement(
                {
                    shape_type: shapeType,
                    layout_item_id: layoutItemId,
                    display_text: displayText,
                    color_theme: color,
                    pos_x: Math.round(posX * 100) / 100,
                    pos_y: Math.round(posY * 100) / 100,
                    width: w,
                    height: h,
                    rotation: 0,
                    is_rotation_locked: inStreet,
                },
                true,
            );
        });
    }

    return {
        setToolMode,
        toggleToolMode,
        handleNodeMouseDown,
        duplicateSelected,
        alignSelected,
        bindPaletteItem,
        bindInteractionEvents,
    };
}
