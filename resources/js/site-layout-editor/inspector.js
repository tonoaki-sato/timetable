/**
 * 選択管理・右インスペクタ・サマリー・左サイドバーアイテム同期・ホバーツールチップ・インラインテキスト編集
 */
export function createInspector(ctx, deps) {
    const { scaleRatio } = ctx.config;

    function refreshChangedNodes(prevSet, nextSet) {
        const allChanged = new Set([...prevSet, ...nextSet]);
        allChanged.forEach((id) => {
            const p = ctx.state.placements.find((item) => item.id === id);
            if (p) deps.renderer.renderPlacementNode(p);
        });
    }

    function setSingleSelection(id) {
        ctx.state.inspDisplayTextBefore = null;
        const prev = new Set(ctx.state.selectedIds);
        ctx.state.selectedIds.clear();
        if (id !== null) ctx.state.selectedIds.add(id);
        refreshChangedNodes(prev, ctx.state.selectedIds);
        updateSummaryAndInspector();
    }

    function toggleSelection(id) {
        ctx.state.inspDisplayTextBefore = null;
        const prev = new Set(ctx.state.selectedIds);
        if (ctx.state.selectedIds.has(id)) ctx.state.selectedIds.delete(id);
        else ctx.state.selectedIds.add(id);
        refreshChangedNodes(prev, ctx.state.selectedIds);
        updateSummaryAndInspector();
    }

    function clearSelection() {
        ctx.state.inspDisplayTextBefore = null;
        if (ctx.state.selectedIds.size === 0) return;
        const prev = new Set(ctx.state.selectedIds);
        ctx.state.selectedIds.clear();
        refreshChangedNodes(prev, ctx.state.selectedIds);
        updateSummaryAndInspector();
    }

    function updateSummaryAndInspector() {
        const {
            sumTotal,
            sumRed,
            sumGreen,
            sumWater,
            btnDuplicate,
            btnDeleteSelected,
            btnAlignLeft,
            btnAlignTop,
            btnPackVert,
            btnPackHoriz,
            btnUndo,
            btnRedo,
            inspBadge,
            inspEmpty,
            inspSingle,
            inspMulti,
            btnRotateSelected,
            inspMultiCount,
            inspShapeName,
            inspDisplayText,
            inspFontSize,
            inspNotes,
            inspWidthM,
            inspHeightM,
            inspColorNote,
            inspColorBtns,
            inspRotation,
            inspRotationLocked,
            inspWaterOptions,
            inspShowHygieneCircle,
            inspArcMode,
            inspLayoutItemId,
        } = ctx.dom;
        const { state, iconDefinitions } = ctx;

        const total = state.placements.length;
        const red = state.placements.filter((p) => p.color_theme === 'red').length;
        const green = state.placements.filter((p) => p.color_theme === 'green').length;
        const water = state.placements.filter((p) => p.shape_type === 'water_supply').length;
        if (sumTotal) sumTotal.textContent = total;
        if (sumRed) sumRed.textContent = red;
        if (sumGreen) sumGreen.textContent = green;
        if (sumWater) sumWater.textContent = water;

        const selCount = state.selectedIds.size;
        if (btnDuplicate) btnDuplicate.disabled = selCount !== 1;
        if (btnDeleteSelected) btnDeleteSelected.disabled = selCount === 0;
        if (btnAlignLeft) btnAlignLeft.disabled = selCount < 2;
        if (btnAlignTop) btnAlignTop.disabled = selCount < 2;
        if (btnPackVert) btnPackVert.disabled = selCount < 2;
        if (btnPackHoriz) btnPackHoriz.disabled = selCount < 2;

        if (btnUndo) btnUndo.disabled = state.undoStack.length === 0;
        if (btnRedo) btnRedo.disabled = state.redoStack.length === 0;

        if (selCount === 0) {
            if (inspBadge) inspBadge.textContent = '未選択';
            if (inspEmpty) inspEmpty.classList.remove('hidden');
            if (inspSingle) inspSingle.classList.add('hidden');
            if (inspMulti) inspMulti.classList.add('hidden');
            if (btnRotateSelected) btnRotateSelected.disabled = true;
            return;
        }

        if (selCount > 1) {
            if (inspBadge) inspBadge.textContent = `${selCount}件選択`;
            if (inspEmpty) inspEmpty.classList.add('hidden');
            if (inspSingle) inspSingle.classList.add('hidden');
            if (inspMulti) inspMulti.classList.remove('hidden');
            if (inspMultiCount) inspMultiCount.textContent = `${selCount} 件のオブジェクトを選択中`;
            if (btnRotateSelected) btnRotateSelected.disabled = true;
            return;
        }

        // 単一選択時
        const sid = [...state.selectedIds][0];
        const p = state.placements.find((item) => item.id === sid);
        if (!p) return;

        const def = iconDefinitions[p.shape_type];
        if (inspBadge) inspBadge.textContent = p.display_text || '1件選択';
        if (inspEmpty) inspEmpty.classList.add('hidden');
        if (inspSingle) inspSingle.classList.remove('hidden');
        if (inspMulti) inspMulti.classList.add('hidden');

        if (inspShapeName) inspShapeName.textContent = def ? def.name : p.shape_type;
        if (inspDisplayText) inspDisplayText.value = p.display_text || '';
        if (inspFontSize) inspFontSize.value = p.font_size || '';
        if (inspNotes) inspNotes.value = p.notes || '';
        if (inspWidthM) {
            inspWidthM.value = (parseFloat(p.width) / scaleRatio).toFixed(2);
            inspWidthM.disabled = !(def && def.is_resizable);
        }
        if (inspHeightM) {
            inspHeightM.value = (parseFloat(p.height) / scaleRatio).toFixed(2);
            inspHeightM.disabled = !(def && def.is_resizable);
        }

        // カラーボタン制御
        const supported =
            def && Array.isArray(def.supported_colors) ? def.supported_colors : ['red', 'green', 'black', 'cyan', 'orange', 'blue'];
        if (inspColorNote) inspColorNote.textContent = supported.length <= 1 ? '固定色' : '';
        inspColorBtns.forEach((btn) => {
            const c = btn.dataset.color;
            const allowed = supported.includes(c);
            btn.disabled = !allowed;
            btn.classList.toggle('opacity-20', !allowed);
            btn.classList.toggle('cursor-not-allowed', !allowed);
            const active = c === p.color_theme;
            btn.classList.toggle('ring-2', active);
            btn.classList.toggle('ring-indigo-600', active);
            btn.classList.toggle('ring-offset-1', active);
        });

        // 回転制御
        const rotatable = (def ? def.is_rotatable : true) && !p.is_rotation_locked;
        if (btnRotateSelected) btnRotateSelected.disabled = !rotatable;
        if (inspRotation) {
            inspRotation.disabled = !rotatable;
            inspRotation.value = String((Math.round((parseInt(p.rotation, 10) || 0) / 45) * 45) % 360);
        }
        if (inspRotationLocked) inspRotationLocked.checked = Boolean(p.is_rotation_locked);

        // 給水オプション
        if (inspWaterOptions) {
            if (p.shape_type === 'water_supply') {
                inspWaterOptions.classList.remove('hidden');
                if (inspShowHygieneCircle) inspShowHygieneCircle.checked = p.show_hygiene_circle !== false;
                if (inspArcMode) inspArcMode.value = `${p.arc_start_angle ?? 0},${p.arc_end_angle ?? 360}`;
            } else {
                inspWaterOptions.classList.add('hidden');
            }
        }

        // 台帳連動セレクト
        if (inspLayoutItemId) {
            inspLayoutItemId.value = p.layout_item_id || '';
            const usedIds = new Set(
                state.placements.filter((other) => other.id !== p.id && other.layout_item_id).map((other) => String(other.layout_item_id)),
            );
            Array.from(inspLayoutItemId.options).forEach((opt) => {
                if (!opt.value) return;
                const baseLabel = opt.dataset.baseLabel || opt.textContent.replace(' 【配置済】', '');
                opt.dataset.baseLabel = baseLabel;
                opt.textContent = usedIds.has(opt.value) ? `${baseLabel} 【配置済】` : baseLabel;
            });
        }
    }

    function syncSidebarLayoutItems() {
        const { sidebarSearch, sidebarUnplacedOnly } = ctx.dom;
        const usedIds = new Set(ctx.state.placements.filter((p) => p.layout_item_id).map((p) => String(p.layout_item_id)));
        const kw = (sidebarSearch?.value || '').trim().toLowerCase();
        const unplacedOnly = Boolean(sidebarUnplacedOnly?.checked);

        document.querySelectorAll('.sidebar-layout-item').forEach((el) => {
            const id = el.dataset.layoutItemId;
            const placed = usedIds.has(id);
            const badge = el.querySelector('.item-status-badge');
            if (badge) {
                badge.textContent = placed ? '配置済' : '未配置';
                badge.className = placed
                    ? 'item-status-badge text-[9px] px-1.5 py-0.2 rounded font-bold bg-emerald-100 text-emerald-700'
                    : 'item-status-badge text-[9px] px-1.5 py-0.2 rounded font-bold bg-slate-200 text-slate-600';
            }
            const textMatch = !kw || (el.dataset.code || '').toLowerCase().includes(kw) || (el.dataset.name || '').toLowerCase().includes(kw);
            const filterMatch = !unplacedOnly || !placed;
            el.classList.toggle('hidden', !(textMatch && filterMatch));
        });
    }

    // --- ホバーツールチップ ---
    function moveHoverTooltip(e) {
        const { hoverTooltip, canvasContainer } = ctx.dom;
        if (!hoverTooltip || hoverTooltip.classList.contains('hidden')) return;
        const rect = canvasContainer.getBoundingClientRect();
        hoverTooltip.style.left = `${e.clientX - rect.left + 12}px`;
        hoverTooltip.style.top = `${e.clientY - rect.top + 12}px`;
    }

    function showHoverTooltip(e, p) {
        const { hoverTooltip } = ctx.dom;
        if (!hoverTooltip || ctx.state.isMoving || ctx.state.isResizing) return;
        const def = ctx.iconDefinitions[p.shape_type];
        const wM = (parseFloat(p.width) / scaleRatio).toFixed(1);
        const hM = (parseFloat(p.height) / scaleRatio).toFixed(1);
        const vendor = p.layout_item?.vendor?.name ? ` | ${p.layout_item.vendor.name}` : '';
        hoverTooltip.textContent = `${p.display_text || '(無題)'}${vendor} [${def ? def.name : p.shape_type} ${wM}m×${hM}m]`;
        hoverTooltip.classList.remove('hidden');
        moveHoverTooltip(e);
    }

    function hideHoverTooltip() {
        if (ctx.dom.hoverTooltip) ctx.dom.hoverTooltip.classList.add('hidden');
    }

    // --- ダブルクリック・インラインテキスト編集 ---
    function openInlineTextEditor(placement) {
        const { inlineEditor, svgContainer, canvasContainer } = ctx.dom;
        if (!inlineEditor) return;
        const svg = svgContainer.querySelector('svg');
        if (!svg) return;

        const pt = svg.createSVGPoint();
        pt.x = parseFloat(placement.pos_x) + parseFloat(placement.width) / 2;
        pt.y = parseFloat(placement.pos_y) + parseFloat(placement.height) / 2;
        const screenPt = pt.matrixTransform(svg.getScreenCTM());
        const rect = canvasContainer.getBoundingClientRect();

        inlineEditor.value = placement.display_text || '';
        inlineEditor.style.width = '110px';
        inlineEditor.style.left = `${screenPt.x - rect.left - 55}px`;
        inlineEditor.style.top = `${screenPt.y - rect.top - 22}px`;
        inlineEditor.classList.remove('hidden');
        inlineEditor.focus();
        inlineEditor.select();

        const commit = async () => {
            inlineEditor.classList.add('hidden');
            inlineEditor.onblur = null;
            inlineEditor.onkeydown = null;
            const nextVal = inlineEditor.value.trim();
            if (nextVal !== (placement.display_text || '')) {
                await deps.api.applySingleUpdate(placement, { display_text: nextVal }, true);
            }
        };

        inlineEditor.onblur = commit;
        inlineEditor.onkeydown = (ev) => {
            if (ev.key === 'Enter' && !ev.shiftKey) {
                ev.preventDefault();
                commit();
            } else if (ev.key === 'Escape') {
                inlineEditor.classList.add('hidden');
                inlineEditor.onblur = null;
            }
        };
    }

    // --- 右インスペクタ双方向イベント ---
    function bindInspectorEvents() {
        const {
            inspDisplayText,
            inspColorBtns,
            bulkColorBtns,
            inspRotation,
            inspRotationLocked,
            inspFontSize,
            inspWidthM,
            inspHeightM,
            inspShowHygieneCircle,
            inspArcMode,
            inspLayoutItemId,
            inspNotes,
        } = ctx.dom;

        const getSingleSelected = () =>
            ctx.state.selectedIds.size === 1 ? ctx.state.placements.find((p) => p.id === [...ctx.state.selectedIds][0]) : null;

        if (inspDisplayText) {
            inspDisplayText.addEventListener('input', () => {
                const p = getSingleSelected();
                if (!p) return;
                if (ctx.state.inspDisplayTextBefore === null) {
                    ctx.state.inspDisplayTextBefore = p.display_text;
                }
                const origText = ctx.state.inspDisplayTextBefore;
                const nextText = inspDisplayText.value;
                p.display_text = nextText;
                deps.renderer.renderPlacementNode(p);
                clearTimeout(ctx.state.inspTimer);
                ctx.state.inspTimer = setTimeout(() => {
                    const beforeOverride = [
                        {
                            id: p.id,
                            pos_x: p.pos_x,
                            pos_y: p.pos_y,
                            width: p.width,
                            height: p.height,
                            rotation: p.rotation,
                            is_rotation_locked: p.is_rotation_locked,
                            display_text: origText,
                            color_theme: p.color_theme,
                        },
                    ];
                    ctx.state.inspDisplayTextBefore = null;
                    deps.api.applySingleUpdate(p, { display_text: nextText }, true, beforeOverride);
                }, 350);
            });
        }

        inspColorBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                const p = getSingleSelected();
                if (p && !btn.disabled) deps.api.applySingleUpdate(p, { color_theme: btn.dataset.color }, true);
            });
        });

        bulkColorBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                const color = btn.dataset.bulkColor;
                const before = [...ctx.state.selectedIds]
                    .map((id) => {
                        const p = ctx.state.placements.find((item) => item.id === id);
                        return p ? { id: p.id, color_theme: p.color_theme } : null;
                    })
                    .filter(Boolean);
                const items = before.map((b) => ({ id: b.id, color_theme: color }));
                deps.api.apiBulkUpdate(items, true, before);
            });
        });

        if (inspRotation) {
            inspRotation.addEventListener('change', () => {
                const p = getSingleSelected();
                if (p) deps.api.applySingleUpdate(p, { rotation: parseInt(inspRotation.value, 10) }, true);
            });
        }
        if (inspRotationLocked) {
            inspRotationLocked.addEventListener('change', () => {
                const p = getSingleSelected();
                if (p) deps.api.applySingleUpdate(p, { is_rotation_locked: inspRotationLocked.checked }, true);
            });
        }
        if (inspFontSize) {
            inspFontSize.addEventListener('change', () => {
                const p = getSingleSelected();
                if (p) deps.api.applySingleUpdate(p, { font_size: inspFontSize.value ? parseFloat(inspFontSize.value) : null }, true);
            });
        }
        if (inspWidthM) {
            inspWidthM.addEventListener('change', () => {
                const p = getSingleSelected();
                if (p) deps.api.applySingleUpdate(p, { width: Math.max(7.09, Math.round(parseFloat(inspWidthM.value) * scaleRatio * 100) / 100) }, true);
            });
        }
        if (inspHeightM) {
            inspHeightM.addEventListener('change', () => {
                const p = getSingleSelected();
                if (p) deps.api.applySingleUpdate(p, { height: Math.max(7.09, Math.round(parseFloat(inspHeightM.value) * scaleRatio * 100) / 100) }, true);
            });
        }
        if (inspShowHygieneCircle) {
            inspShowHygieneCircle.addEventListener('change', () => {
                const p = getSingleSelected();
                if (p) deps.api.applySingleUpdate(p, { show_hygiene_circle: inspShowHygieneCircle.checked }, true);
            });
        }
        if (inspArcMode) {
            inspArcMode.addEventListener('change', () => {
                const p = getSingleSelected();
                if (!p) return;
                const [s, e] = inspArcMode.value.split(',').map(Number);
                deps.api.applySingleUpdate(p, { arc_start_angle: s, arc_end_angle: e }, true);
            });
        }
        if (inspLayoutItemId) {
            inspLayoutItemId.addEventListener('change', () => {
                const p = getSingleSelected();
                if (!p) return;
                const val = inspLayoutItemId.value ? parseInt(inspLayoutItemId.value, 10) : null;
                const opt = inspLayoutItemId.options[inspLayoutItemId.selectedIndex];
                const patch = { layout_item_id: val };
                if (val && opt?.dataset.code && (!p.display_text || p.display_text === 'A00' || p.display_text === 'テント')) {
                    patch.display_text = opt.dataset.code;
                }
                deps.api.applySingleUpdate(p, patch, true);
            });
        }
        if (inspNotes) {
            inspNotes.addEventListener('change', () => {
                const p = getSingleSelected();
                if (p) deps.api.applySingleUpdate(p, { notes: inspNotes.value }, true);
            });
        }
    }

    return {
        setSingleSelection,
        toggleSelection,
        clearSelection,
        refreshChangedNodes,
        updateSummaryAndInspector,
        syncSidebarLayoutItems,
        showHoverTooltip,
        moveHoverTooltip,
        hideHoverTooltip,
        openInlineTextEditor,
        bindInspectorEvents,
    };
}
