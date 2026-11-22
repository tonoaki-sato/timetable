import { showToast } from './context.js';
import { renderHygieneCirclesAndCheck } from './hygiene.js';
import { findPlacementNodeElement, populatePlacementSvgGroup } from './svg-renderer.js';

/**
 * カスタムPNGアイコン管理 (生成・編集・削除・パレット動的連携)
 */
export function createCustomIcons(ctx, deps) {
    const { scaleRatio, iconStoreUrl, iconBaseUrl, csrfToken } = ctx.config;

    function normalizeAssetUrl(path) {
        if (!path) return '';
        return path.startsWith('/') ? path : `/${path}`;
    }

    function findPaletteCardByShapeType(shapeType) {
        return Array.from(document.querySelectorAll('.palette-item')).find((el) => el.dataset.shapeType === shapeType) || null;
    }

    function updateScaleHintText(wInput, hInput, hintEl) {
        if (!wInput || !hInput || !hintEl) return;
        const wM = parseFloat(wInput.value) || 0;
        const hM = parseFloat(hInput.value) || 0;
        const wPt = (wM * scaleRatio).toFixed(2);
        const hPt = (hM * scaleRatio).toFixed(2);
        hintEl.textContent = `A3 1/400換算: ${wPt}pt × ${hPt}pt`;
    }

    /**
     * XSS安全: DOM API + textContent でカスタムアイコンカードをパレットへ追加する
     */
    function addIconToPalette(iconDef) {
        const { paletteShapesGrid } = ctx.dom;
        if (!paletteShapesGrid || !iconDef) return;
        ctx.iconDefinitions[iconDef.icon_key] = iconDef;

        const assetUrl = normalizeAssetUrl(iconDef.icon_asset_path);
        const wM = (parseFloat(iconDef.default_width) / scaleRatio).toFixed(1);
        const hM = (parseFloat(iconDef.default_height) / scaleRatio).toFixed(1);

        const card = document.createElement('div');
        card.className =
            'palette-item group relative p-2 bg-slate-50 hover:bg-indigo-50/70 border border-slate-200 hover:border-indigo-300 rounded-xl cursor-grab active:cursor-grabbing transition flex flex-col items-center text-center select-none';
        card.draggable = true;
        card.dataset.shapeType = iconDef.icon_key;
        card.dataset.defaultWidth = iconDef.default_width;
        card.dataset.defaultHeight = iconDef.default_height;
        card.dataset.defaultColor = iconDef.default_color || 'red';
        card.dataset.name = iconDef.name;
        card.dataset.category = iconDef.category;
        card.dataset.isPreset = '0';
        card.dataset.iconId = iconDef.id;
        card.dataset.renderStyle = iconDef.render_style;
        card.dataset.assetPath = assetUrl;
        card.dataset.isRotatable = iconDef.is_rotatable ? '1' : '0';
        card.dataset.isResizable = iconDef.is_resizable ? '1' : '0';
        card.dataset.rotationStep = iconDef.rotation_step || '45';
        card.dataset.hasHygieneCircle = iconDef.has_hygiene_circle ? '1' : '0';

        // アクションボタン群
        const actionsWrap = document.createElement('div');
        actionsWrap.className = 'absolute top-1 right-1 hidden group-hover:flex items-center space-x-0.5 z-10';

        const editBtn = document.createElement('button');
        editBtn.type = 'button';
        editBtn.className =
            'btn-edit-custom-icon px-1 py-0.5 bg-white/90 hover:bg-amber-100 border border-amber-300 text-amber-700 rounded text-[9px] font-bold shadow-2xs transition cursor-pointer';
        editBtn.title = '編集';
        editBtn.dataset.iconId = String(iconDef.id);
        editBtn.textContent = '✏️';

        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className =
            'btn-delete-custom-icon px-1 py-0.5 bg-white/90 hover:bg-red-100 border border-red-300 text-red-700 rounded text-[9px] font-bold shadow-2xs transition cursor-pointer';
        deleteBtn.title = '削除';
        deleteBtn.dataset.iconId = String(iconDef.id);
        deleteBtn.textContent = '🗑️';

        actionsWrap.appendChild(editBtn);
        actionsWrap.appendChild(deleteBtn);

        // プレビュー画像
        const imgWrap = document.createElement('div');
        imgWrap.className = 'w-9 h-7 flex items-center justify-center pointer-events-none mb-1';
        const img = document.createElement('img');
        img.src = assetUrl;
        img.alt = iconDef.name || '';
        img.className = 'max-w-full max-h-full object-contain';
        imgWrap.appendChild(img);

        // 名称
        const nameSpan = document.createElement('span');
        nameSpan.className = 'text-[10px] font-bold text-slate-700 group-hover:text-indigo-700 leading-tight pointer-events-none truncate w-full';
        nameSpan.textContent = iconDef.name || '';

        // 寸法ラベル
        const sizeSpan = document.createElement('span');
        sizeSpan.className = 'text-[9px] text-slate-400 font-mono mt-0.5 pointer-events-none';
        sizeSpan.textContent = `${wM}m×${hM}m`;

        card.appendChild(actionsWrap);
        card.appendChild(imgWrap);
        card.appendChild(nameSpan);
        card.appendChild(sizeSpan);

        paletteShapesGrid.appendChild(card);
        deps.interactions.bindPaletteItem(card);
    }

    function updateIconInPalette(iconDef) {
        if (!iconDef) return;
        ctx.iconDefinitions[iconDef.icon_key] = iconDef;

        const card = findPaletteCardByShapeType(iconDef.icon_key);
        if (card) {
            const assetUrl = normalizeAssetUrl(iconDef.icon_asset_path);
            const wM = (parseFloat(iconDef.default_width) / scaleRatio).toFixed(1);
            const hM = (parseFloat(iconDef.default_height) / scaleRatio).toFixed(1);

            card.dataset.defaultWidth = iconDef.default_width;
            card.dataset.defaultHeight = iconDef.default_height;
            card.dataset.name = iconDef.name;
            card.dataset.category = iconDef.category;
            card.dataset.assetPath = assetUrl;
            card.dataset.isRotatable = iconDef.is_rotatable ? '1' : '0';
            card.dataset.isResizable = iconDef.is_resizable ? '1' : '0';
            card.dataset.hasHygieneCircle = iconDef.has_hygiene_circle ? '1' : '0';

            const nameSpan = card.querySelector('span.text-slate-700');
            if (nameSpan) nameSpan.textContent = iconDef.name;

            const sizeSpan = card.querySelector('span.text-slate-400');
            if (sizeSpan) sizeSpan.textContent = `${wM}m×${hM}m`;

            const imgEl = card.querySelector('img');
            if (imgEl) {
                if (assetUrl) imgEl.src = assetUrl;
                imgEl.alt = iconDef.name || '';
            }
        }

        // 白地図上の既存配置ノードを最新情報でインプレース再描画
        ctx.state.placements.forEach((p) => {
            if (p.shape_type === iconDef.icon_key) {
                const g = findPlacementNodeElement(ctx, p.id);
                if (g) populatePlacementSvgGroup(ctx, g, p);
            }
        });
        renderHygieneCirclesAndCheck(ctx);
        deps.inspector.updateSummaryAndInspector();
    }

    function removeIconFromPalette(shapeType) {
        const card = findPaletteCardByShapeType(shapeType);
        if (card) card.remove();

        delete ctx.iconDefinitions[shapeType];

        // 白地図上の配置ノードを削除
        const toRemove = ctx.state.placements.filter((p) => p.shape_type === shapeType);
        toRemove.forEach((p) => {
            const g = findPlacementNodeElement(ctx, p.id);
            if (g) g.remove();
            ctx.state.selectedIds.delete(p.id);
        });
        ctx.state.placements = ctx.state.placements.filter((p) => p.shape_type !== shapeType);

        renderHygieneCirclesAndCheck(ctx);
        deps.inspector.updateSummaryAndInspector();
        deps.inspector.syncSidebarLayoutItems();
    }

    function openEditIconModal(shapeType) {
        const {
            editIconId,
            editIconName,
            editIconCategory,
            editIconWidth,
            editIconHeight,
            editIconScaleHint,
            editIconRotatable,
            editIconResizable,
            editIconHygiene,
            editIconCurrentImg,
            editIconFile,
            editIconErrorMsg,
            modalEditIcon,
        } = ctx.dom;

        const def = ctx.iconDefinitions[shapeType];
        if (!def || def.is_preset) return;

        if (editIconId) editIconId.value = def.id;
        if (editIconName) editIconName.value = def.name;
        if (editIconCategory) editIconCategory.value = def.category;
        if (editIconWidth) editIconWidth.value = (parseFloat(def.default_width) / scaleRatio).toFixed(1);
        if (editIconHeight) editIconHeight.value = (parseFloat(def.default_height) / scaleRatio).toFixed(1);
        if (editIconRotatable) editIconRotatable.checked = Boolean(def.is_rotatable);
        if (editIconResizable) editIconResizable.checked = Boolean(def.is_resizable);
        if (editIconHygiene) editIconHygiene.checked = Boolean(def.has_hygiene_circle);

        const assetUrl = normalizeAssetUrl(def.icon_asset_path);
        if (editIconCurrentImg) editIconCurrentImg.src = assetUrl;
        if (editIconFile) editIconFile.value = '';
        if (editIconErrorMsg) editIconErrorMsg.classList.add('hidden');

        updateScaleHintText(editIconWidth, editIconHeight, editIconScaleHint);
        modalEditIcon?.classList.remove('hidden');
    }

    async function openDeleteIconModal(shapeType) {
        const {
            deleteIconId,
            deleteIconImg,
            deleteIconNameText,
            deleteIconSizeText,
            deleteIconUsageWarning,
            deleteIconUsageCount,
            deleteIconErrorMsg,
            modalDeleteIcon,
        } = ctx.dom;

        const def = ctx.iconDefinitions[shapeType];
        if (!def || def.is_preset) return;

        if (deleteIconId) deleteIconId.value = def.id;
        if (deleteIconNameText) deleteIconNameText.textContent = def.name;
        const wM = (parseFloat(def.default_width) / scaleRatio).toFixed(1);
        const hM = (parseFloat(def.default_height) / scaleRatio).toFixed(1);
        if (deleteIconSizeText) deleteIconSizeText.textContent = `${wM}m × ${hM}m (${def.category})`;

        const assetUrl = normalizeAssetUrl(def.icon_asset_path);
        if (deleteIconImg) deleteIconImg.src = assetUrl;

        if (deleteIconUsageWarning) deleteIconUsageWarning.classList.add('hidden');
        if (deleteIconErrorMsg) deleteIconErrorMsg.classList.add('hidden');
        modalDeleteIcon?.classList.remove('hidden');

        try {
            const res = await fetch(`${iconBaseUrl}/${def.id}/usage`, {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();
            if (res.ok && data.success && data.usage_count > 0) {
                if (deleteIconUsageCount) deleteIconUsageCount.textContent = data.usage_count;
                if (deleteIconUsageWarning) deleteIconUsageWarning.classList.remove('hidden');
            }
        } catch (err) {
            console.warn('Failed to fetch icon usage count:', err);
        }
    }

    function bindCustomIconEvents() {
        const {
            btnOpenCreateIconModal,
            modalCreateIcon,
            formCreateIcon,
            createIconDropzone,
            createIconFile,
            createIconPrompt,
            createIconPreviewWrap,
            createIconPreviewImg,
            createIconFilename,
            createIconWidth,
            createIconHeight,
            createIconScaleHint,
            createIconErrorMsg,
            btnSubmitCreateIcon,
            modalEditIcon,
            formEditIcon,
            editIconId,
            editIconWidth,
            editIconHeight,
            editIconScaleHint,
            editIconErrorMsg,
            btnSubmitEditIcon,
            modalDeleteIcon,
            deleteIconId,
            deleteIconErrorMsg,
            btnSubmitDeleteIcon,
        } = ctx.dom;

        // 汎用モーダルクローズ
        document.querySelectorAll('.btn-close-modal').forEach((btn) => {
            btn.addEventListener('click', () => {
                const modalId = btn.dataset.modal;
                if (modalId) {
                    const modal = document.getElementById(modalId);
                    if (modal) modal.classList.add('hidden');
                } else {
                    [modalCreateIcon, modalEditIcon, modalDeleteIcon].forEach((m) => m?.classList.add('hidden'));
                }
            });
        });

        if (createIconWidth && createIconHeight && createIconScaleHint) {
            createIconWidth.addEventListener('input', () => updateScaleHintText(createIconWidth, createIconHeight, createIconScaleHint));
            createIconHeight.addEventListener('input', () => updateScaleHintText(createIconWidth, createIconHeight, createIconScaleHint));
        }
        if (editIconWidth && editIconHeight && editIconScaleHint) {
            editIconWidth.addEventListener('input', () => updateScaleHintText(editIconWidth, editIconHeight, editIconScaleHint));
            editIconHeight.addEventListener('input', () => updateScaleHintText(editIconWidth, editIconHeight, editIconScaleHint));
        }

        // 新規生成モーダル開閉 & ファイルドロップ
        if (btnOpenCreateIconModal) {
            btnOpenCreateIconModal.addEventListener('click', () => {
                if (formCreateIcon) formCreateIcon.reset();
                if (createIconPrompt) createIconPrompt.classList.remove('hidden');
                if (createIconPreviewWrap) createIconPreviewWrap.classList.add('hidden');
                if (createIconErrorMsg) createIconErrorMsg.classList.add('hidden');
                if (createIconWidth) createIconWidth.value = '2.0';
                if (createIconHeight) createIconHeight.value = '2.0';
                updateScaleHintText(createIconWidth, createIconHeight, createIconScaleHint);
                if (modalCreateIcon) modalCreateIcon.classList.remove('hidden');
            });
        }

        function processSelectedPngFile(file) {
            if (!file) return;
            if (file.type !== 'image/png' && !file.name.toLowerCase().endsWith('.png')) {
                if (createIconErrorMsg) {
                    createIconErrorMsg.textContent = 'PNG形式の画像ファイルのみアップロード可能です。';
                    createIconErrorMsg.classList.remove('hidden');
                }
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                if (createIconErrorMsg) {
                    createIconErrorMsg.textContent = 'ファイルサイズが上限 (5MB) を超えています。';
                    createIconErrorMsg.classList.remove('hidden');
                }
                return;
            }
            if (createIconErrorMsg) createIconErrorMsg.classList.add('hidden');

            const reader = new FileReader();
            reader.onload = (e) => {
                const dataUrl = e.target.result;
                const img = new Image();
                img.onload = () => {
                    const nw = img.naturalWidth;
                    const nh = img.naturalHeight;
                    if (nw > 0 && nh > 0) {
                        const aspect = nw / nh;
                        const suggestedW = 2.0;
                        let suggestedH = Math.round((suggestedW / aspect) * 10) / 10;
                        if (suggestedH < 0.5) suggestedH = 0.5;
                        if (suggestedH > 20.0) suggestedH = 20.0;
                        if (createIconWidth) createIconWidth.value = suggestedW.toFixed(1);
                        if (createIconHeight) createIconHeight.value = suggestedH.toFixed(1);
                        updateScaleHintText(createIconWidth, createIconHeight, createIconScaleHint);
                    }
                };
                img.src = dataUrl;

                if (createIconPreviewImg) createIconPreviewImg.src = dataUrl;
                if (createIconFilename) createIconFilename.textContent = file.name;
                if (createIconPrompt) createIconPrompt.classList.add('hidden');
                if (createIconPreviewWrap) createIconPreviewWrap.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }

        if (createIconDropzone && createIconFile) {
            createIconDropzone.addEventListener('click', () => createIconFile.click());
            createIconFile.addEventListener('change', () => {
                if (createIconFile.files && createIconFile.files[0]) {
                    processSelectedPngFile(createIconFile.files[0]);
                }
            });
            createIconDropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                createIconDropzone.classList.add('border-indigo-500', 'bg-indigo-50/60');
            });
            createIconDropzone.addEventListener('dragleave', () => {
                createIconDropzone.classList.remove('border-indigo-500', 'bg-indigo-50/60');
            });
            createIconDropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                createIconDropzone.classList.remove('border-indigo-500', 'bg-indigo-50/60');
                if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                    createIconFile.files = e.dataTransfer.files;
                    processSelectedPngFile(e.dataTransfer.files[0]);
                }
            });
        }

        // 新規アイコン作成送信
        if (formCreateIcon) {
            formCreateIcon.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (!createIconFile || !createIconFile.files || !createIconFile.files[0]) {
                    if (createIconErrorMsg) {
                        createIconErrorMsg.textContent = 'PNG画像ファイルを選択してください。';
                        createIconErrorMsg.classList.remove('hidden');
                    }
                    return;
                }

                if (btnSubmitCreateIcon) btnSubmitCreateIcon.disabled = true;
                if (createIconErrorMsg) createIconErrorMsg.classList.add('hidden');

                try {
                    const formData = new FormData(formCreateIcon);
                    const res = await fetch(iconStoreUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            Accept: 'application/json',
                        },
                        body: formData,
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        const msg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'アイコンの生成に失敗しました。');
                        if (createIconErrorMsg) {
                            createIconErrorMsg.textContent = msg;
                            createIconErrorMsg.classList.remove('hidden');
                        }
                        return;
                    }

                    addIconToPalette(data.icon);
                    modalCreateIcon?.classList.add('hidden');
                    formCreateIcon.reset();
                    showToast(ctx, `アイコン「${data.icon.name}」を生成・追加しました`);
                } catch (err) {
                    console.error('Failed to create icon:', err);
                    if (createIconErrorMsg) {
                        createIconErrorMsg.textContent = '通信エラーが発生しました。時間をおいて再試行してください。';
                        createIconErrorMsg.classList.remove('hidden');
                    }
                } finally {
                    if (btnSubmitCreateIcon) btnSubmitCreateIcon.disabled = false;
                }
            });
        }

        // 編集フォーム送信
        if (formEditIcon) {
            formEditIcon.addEventListener('submit', async (e) => {
                e.preventDefault();
                const iconId = editIconId?.value;
                if (!iconId) return;

                if (btnSubmitEditIcon) btnSubmitEditIcon.disabled = true;
                if (editIconErrorMsg) editIconErrorMsg.classList.add('hidden');

                try {
                    const formData = new FormData(formEditIcon);
                    const res = await fetch(`${iconBaseUrl}/${iconId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            Accept: 'application/json',
                        },
                        body: formData,
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        const msg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'アイコンの更新に失敗しました。');
                        if (editIconErrorMsg) {
                            editIconErrorMsg.textContent = msg;
                            editIconErrorMsg.classList.remove('hidden');
                        }
                        return;
                    }

                    updateIconInPalette(data.icon);
                    modalEditIcon?.classList.add('hidden');
                    showToast(ctx, `アイコン「${data.icon.name}」を更新しました`);
                } catch (err) {
                    console.error('Failed to update icon:', err);
                    if (editIconErrorMsg) {
                        editIconErrorMsg.textContent = '通信エラーが発生しました。時間をおいて再試行してください。';
                        editIconErrorMsg.classList.remove('hidden');
                    }
                } finally {
                    if (btnSubmitEditIcon) btnSubmitEditIcon.disabled = false;
                }
            });
        }

        // 削除実行
        if (btnSubmitDeleteIcon) {
            btnSubmitDeleteIcon.addEventListener('click', async () => {
                const iconId = deleteIconId?.value;
                if (!iconId) return;

                btnSubmitDeleteIcon.disabled = true;
                if (deleteIconErrorMsg) deleteIconErrorMsg.classList.add('hidden');

                try {
                    const res = await fetch(`${iconBaseUrl}/${iconId}?force=1`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            Accept: 'application/json',
                        },
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        const msg = data.message || 'アイコンの削除に失敗しました。';
                        if (deleteIconErrorMsg) {
                            deleteIconErrorMsg.textContent = msg;
                            deleteIconErrorMsg.classList.remove('hidden');
                        }
                        return;
                    }

                    let targetKey = null;
                    for (const [key, d] of Object.entries(ctx.iconDefinitions)) {
                        if (d.id == iconId) {
                            targetKey = key;
                            break;
                        }
                    }
                    if (targetKey) {
                        removeIconFromPalette(targetKey);
                    }

                    modalDeleteIcon?.classList.add('hidden');
                    showToast(ctx, 'カスタムアイコンを削除しました');
                } catch (err) {
                    console.error('Failed to delete icon:', err);
                    if (deleteIconErrorMsg) {
                        deleteIconErrorMsg.textContent = '通信エラーが発生しました。時間をおいて再試行してください。';
                        deleteIconErrorMsg.classList.remove('hidden');
                    }
                } finally {
                    btnSubmitDeleteIcon.disabled = false;
                }
            });
        }
    }

    return {
        openEditIconModal,
        openDeleteIconModal,
        bindCustomIconEvents,
    };
}
