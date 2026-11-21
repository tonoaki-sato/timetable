/**
 * 白地図マスター管理スクリプト (CSP準拠・外部JavaScriptモジュール)
 */
document.addEventListener('DOMContentLoaded', () => {
    initSiteBaseMapDetailModal();
    initSiteBaseMapFormModal();
});

/**
 * 1. 閲覧専用詳細モーダル (#modal-site-base-map-detail)
 */
function initSiteBaseMapDetailModal() {
    const modal = document.getElementById('modal-site-base-map-detail');
    if (!modal) return;

    const svgPreviewImg = document.getElementById('detail-base-map-svg-preview');
    const svgEmptyEl = document.getElementById('detail-base-map-svg-empty');
    const nameEl = document.getElementById('detail-base-map-name');
    const defaultBadge = document.getElementById('detail-base-map-default-badge');
    const inactiveBadge = document.getElementById('detail-base-map-inactive-badge');
    const cadVersionEl = document.getElementById('detail-base-map-cad-version');
    const paperScaleEl = document.getElementById('detail-base-map-paper-scale');
    const dimensionsEl = document.getElementById('detail-base-map-dimensions');
    const viewboxEl = document.getElementById('detail-base-map-viewbox');
    const svgPathEl = document.getElementById('detail-base-map-svg-path');
    const pdfLinkEl = document.getElementById('detail-base-map-pdf-link');
    const pdfNoneEl = document.getElementById('detail-base-map-pdf-none');
    const usageEl = document.getElementById('detail-base-map-usage');
    const notesEl = document.getElementById('detail-base-map-notes');

    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    if (svgPreviewImg) {
        svgPreviewImg.addEventListener('error', () => {
            svgPreviewImg.classList.add('hidden');
            svgEmptyEl?.classList.remove('hidden');
        });
        svgPreviewImg.addEventListener('load', () => {
            svgPreviewImg.classList.remove('hidden');
            svgEmptyEl?.classList.add('hidden');
        });
    }

    const openDetail = (payload) => {
        if (!payload || typeof payload !== 'object') return;

        if (svgPreviewImg) {
            if (payload.svg_url) {
                svgPreviewImg.src = payload.svg_url;
                svgPreviewImg.classList.remove('hidden');
                svgEmptyEl?.classList.add('hidden');
            } else {
                svgPreviewImg.removeAttribute('src');
                svgPreviewImg.classList.add('hidden');
                svgEmptyEl?.classList.remove('hidden');
            }
        }

        if (nameEl) nameEl.textContent = payload.name || '—';
        defaultBadge?.classList.toggle('hidden', !payload.is_default);
        inactiveBadge?.classList.toggle('hidden', Boolean(payload.is_active));

        if (cadVersionEl) cadVersionEl.textContent = payload.cad_version || '—';
        if (paperScaleEl) {
            const scaleText = Number(payload.scale_ratio || 0).toFixed(4);
            paperScaleEl.textContent = `${payload.paper_size || '—'} / ${scaleText} px/m`;
        }
        if (dimensionsEl) {
            const w = Number(payload.width_meters || 0).toFixed(2);
            const h = Number(payload.height_meters || 0).toFixed(2);
            dimensionsEl.textContent = `${w}m × ${h}m`;
        }
        if (viewboxEl) viewboxEl.textContent = payload.viewbox || '—';
        if (svgPathEl) svgPathEl.textContent = payload.svg_path || '—';

        if (pdfLinkEl && pdfNoneEl) {
            if (payload.pdf_url) {
                pdfLinkEl.href = payload.pdf_url;
                pdfLinkEl.classList.remove('hidden');
                pdfNoneEl.classList.add('hidden');
            } else {
                pdfLinkEl.removeAttribute('href');
                pdfLinkEl.classList.add('hidden');
                pdfNoneEl.classList.remove('hidden');
            }
        }

        if (usageEl) {
            const ttCount = Number(payload.timetables_count ?? 0);
            const pCount = Number(payload.placements_count ?? 0);
            usageEl.textContent = `${ttCount} 日程 / ${pCount} 配置`;
        }

        if (notesEl) {
            notesEl.textContent = payload.notes || '特記事項・CADメモは登録されていません。';
        }

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    document.querySelectorAll('.btn-view-site-base-map').forEach((btn) => {
        btn.addEventListener('click', () => {
            try {
                const payload = JSON.parse(btn.dataset.baseMap || '{}');
                openDetail(payload);
            } catch (err) {
                console.error('Failed to parse site base map detail payload:', err);
            }
        });
    });

    modal.querySelectorAll('.btn-close-modal, .modal-backdrop').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
}

/**
 * 2. 新規登録・編集兼用モーダル (#modal-site-base-map-form)
 */
function initSiteBaseMapFormModal() {
    const modal = document.getElementById('modal-site-base-map-form');
    const form = document.getElementById('form-site-base-map-mutation');
    if (!modal || !form) return;

    const modalTitleEl = document.getElementById('site-base-map-form-modal-title');
    const methodInput = document.getElementById('base-map-form-method');
    const actionMirrorInput = document.getElementById('base-map-form-action-mirror');
    const methodMirrorInput = document.getElementById('base-map-form-method-mirror');
    const submitBtn = document.getElementById('site-base-map-form-submit-btn');

    const warningBanner = document.getElementById('edit-placement-warning');
    const warningCountEl = document.getElementById('edit-placement-warning-count');

    const nameInput = document.getElementById('base_map_name');
    const cadVersionInput = document.getElementById('base_map_cad_version');
    const paperSizeInput = document.getElementById('base_map_paper_size');
    const scaleRatioInput = document.getElementById('base_map_scale_ratio');
    const widthMetersInput = document.getElementById('base_map_width_meters');
    const heightMetersInput = document.getElementById('base_map_height_meters');
    const svgFileInput = document.getElementById('base_map_svg_file');
    const svgPathInput = document.getElementById('base_map_svg_path');
    const viewboxInput = document.getElementById('base_map_viewbox');
    const pdfFileInput = document.getElementById('base_map_pdf_file');
    const pdfPathInput = document.getElementById('base_map_pdf_path');
    const isDefaultCheckbox = document.getElementById('base_map_is_default');
    const isActiveCheckbox = document.getElementById('base_map_is_active');
    const notesTextarea = document.getElementById('base_map_notes');

    const svgPreviewWrap = document.getElementById('base-map-svg-preview-wrap');
    const svgPreviewImg = document.getElementById('base-map-svg-preview-img');
    const svgPreviewName = document.getElementById('base-map-svg-preview-name');

    const setTitleText = (text) => {
        if (!modalTitleEl) return;
        const spans = modalTitleEl.querySelectorAll('span');
        if (spans.length >= 2) {
            spans[1].textContent = text;
        } else {
            modalTitleEl.textContent = text;
        }
    };

    const openModal = () => {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    const resetSvgPreview = () => {
        svgPreviewWrap?.classList.add('hidden');
        if (svgPreviewImg) svgPreviewImg.removeAttribute('src');
        if (svgPreviewName) svgPreviewName.textContent = '';
    };

    const extractViewBoxFromSvgText = (svgText) => {
        try {
            const parser = new DOMParser();
            const doc = parser.parseFromString(svgText, 'image/svg+xml');
            const root = doc.documentElement;
            if (!root || root.tagName.toLowerCase() !== 'svg') return null;

            const vb = (root.getAttribute('viewBox') || root.getAttribute('viewbox') || '').trim();
            if (/^-?\d+(?:\.\d+)?[\s,]+-?\d+(?:\.\d+)?[\s,]+\d+(?:\.\d+)?[\s,]+\d+(?:\.\d+)?$/.test(vb)) {
                return vb.replace(/[\s,]+/g, ' ');
            }

            const parseLen = (val) => {
                if (!val || val.includes('%')) return null;
                const m = val.trim().match(/^(\d+(?:\.\d+)?)/);
                return m ? m[1] : null;
            };
            const w = parseLen(root.getAttribute('width'));
            const h = parseLen(root.getAttribute('height'));
            if (w && h) {
                return `0 0 ${w} ${h}`;
            }
        } catch {
            return null;
        }

        return null;
    };

    if (svgFileInput) {
        svgFileInput.addEventListener('change', () => {
            const file = svgFileInput.files?.[0];
            if (!file) {
                resetSvgPreview();
                return;
            }

            const textReader = new FileReader();
            textReader.onload = (e) => {
                const svgText = String(e.target?.result || '');
                const extractedVb = extractViewBoxFromSvgText(svgText);
                if (extractedVb && viewboxInput) {
                    viewboxInput.value = extractedVb;
                }
            };
            textReader.readAsText(file);

            const dataReader = new FileReader();
            dataReader.onload = (e) => {
                if (svgPreviewImg) svgPreviewImg.src = String(e.target?.result || '');
                if (svgPreviewName) svgPreviewName.textContent = file.name;
                svgPreviewWrap?.classList.remove('hidden');
            };
            dataReader.readAsDataURL(file);
        });
    }

    if (isDefaultCheckbox && isActiveCheckbox) {
        isDefaultCheckbox.addEventListener('change', () => {
            if (isDefaultCheckbox.checked) {
                isActiveCheckbox.checked = true;
            }
        });
    }

    const createBtn = document.getElementById('btn-open-create-site-base-map');
    if (createBtn) {
        createBtn.addEventListener('click', () => {
            const storeUrl = createBtn.dataset.storeUrl || form.action;
            form.action = storeUrl;
            if (methodInput) methodInput.value = 'POST';
            if (actionMirrorInput) actionMirrorInput.value = storeUrl;
            if (methodMirrorInput) methodMirrorInput.value = 'POST';

            setTitleText('新規白地図の登録');
            if (submitBtn) submitBtn.textContent = '白地図を登録する';
            warningBanner?.classList.add('hidden');

            if (nameInput) nameInput.value = '';
            if (cadVersionInput) cadVersionInput.value = '260813';
            if (paperSizeInput) paperSizeInput.value = 'A3';
            if (scaleRatioInput) scaleRatioInput.value = '7.0866';
            if (widthMetersInput) widthMetersInput.value = '118.80';
            if (heightMetersInput) heightMetersInput.value = '168.00';
            if (svgFileInput) svgFileInput.value = '';
            if (svgPathInput) svgPathInput.value = '';
            if (viewboxInput) viewboxInput.value = '';
            if (pdfFileInput) pdfFileInput.value = '';
            if (pdfPathInput) pdfPathInput.value = '';
            if (isDefaultCheckbox) isDefaultCheckbox.checked = false;
            if (isActiveCheckbox) isActiveCheckbox.checked = true;
            if (notesTextarea) notesTextarea.value = '';
            resetSvgPreview();

            openModal();
        });
    }

    document.querySelectorAll('.btn-edit-site-base-map').forEach((btn) => {
        btn.addEventListener('click', () => {
            let payload = {};
            try {
                payload = JSON.parse(btn.dataset.baseMap || '{}');
            } catch (err) {
                console.error('Failed to parse site base map edit payload:', err);
                return;
            }

            const updateUrl = payload.update_url || '';
            form.action = updateUrl;
            if (methodInput) methodInput.value = 'PUT';
            if (actionMirrorInput) actionMirrorInput.value = updateUrl;
            if (methodMirrorInput) methodMirrorInput.value = 'PUT';

            setTitleText('白地図の編集');
            if (submitBtn) submitBtn.textContent = '変更内容を保存する';

            const placementsCount = Number(payload.placements_count ?? 0);
            if (placementsCount > 0) {
                if (warningCountEl) warningCountEl.textContent = String(placementsCount);
                warningBanner?.classList.remove('hidden');
            } else {
                warningBanner?.classList.add('hidden');
            }

            if (nameInput) nameInput.value = payload.name || '';
            if (cadVersionInput) cadVersionInput.value = payload.cad_version || '260813';
            if (paperSizeInput) paperSizeInput.value = payload.paper_size || 'A3';
            if (scaleRatioInput) scaleRatioInput.value = String(payload.scale_ratio ?? '7.0866');
            if (widthMetersInput) widthMetersInput.value = Number(payload.width_meters ?? 118.8).toFixed(2);
            if (heightMetersInput) heightMetersInput.value = Number(payload.height_meters ?? 168.0).toFixed(2);
            if (svgFileInput) svgFileInput.value = '';
            if (svgPathInput) svgPathInput.value = payload.svg_path || '';
            if (viewboxInput) viewboxInput.value = payload.viewbox || '0 0 842 1191';
            if (pdfFileInput) pdfFileInput.value = '';
            if (pdfPathInput) pdfPathInput.value = payload.pdf_path || '';
            if (isDefaultCheckbox) isDefaultCheckbox.checked = Boolean(payload.is_default);
            if (isActiveCheckbox) isActiveCheckbox.checked = Boolean(payload.is_active);
            if (notesTextarea) notesTextarea.value = payload.notes || '';
            resetSvgPreview();

            openModal();
        });
    });

    modal.querySelectorAll('.btn-close-modal, .modal-backdrop').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    if (modal.dataset.reopenOnError === '1') {
        const oldMethod = (modal.dataset.oldMethod || 'POST').toUpperCase();
        const oldAction = modal.dataset.oldAction || form.action;
        form.action = oldAction;
        if (methodInput) methodInput.value = oldMethod;
        if (actionMirrorInput) actionMirrorInput.value = oldAction;
        if (methodMirrorInput) methodMirrorInput.value = oldMethod;

        if (oldMethod === 'PUT') {
            setTitleText('白地図の編集');
            if (submitBtn) submitBtn.textContent = '変更内容を保存する';
        } else {
            setTitleText('新規白地図の登録');
            if (submitBtn) submitBtn.textContent = '白地図を登録する';
        }

        openModal();
    }
}
