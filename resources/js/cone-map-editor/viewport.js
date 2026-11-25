import { CAD_FONT_FAMILY } from './constants.js';

export function snapToDevicePixel(val) {
    const dpr = window.devicePixelRatio || 1;
    return Math.round(val * dpr) / dpr;
}

/**
 * ビューポートのパン位置およびSVGネイティブ拡大サイズを更新する
 * (CSS transform: scale によるGPUビットマップボケを防止するためSVG実寸法を直接更新)
 */
export function updateTransform(ctx, scaleChanged = true) {
    const { viewport, svgContainer, zoomText } = ctx.dom;
    const { state } = ctx;

    const pxX = snapToDevicePixel(state.x);
    const pxY = snapToDevicePixel(state.y);

    viewport.style.transform = 'none';
    viewport.style.left = `${pxX}px`;
    viewport.style.top = `${pxY}px`;

    if (scaleChanged) {
        const scaledW = snapToDevicePixel(state.svgWidth * state.scale);
        const scaledH = snapToDevicePixel(state.svgHeight * state.scale);

        const svg = svgContainer.querySelector('svg');
        if (svg) {
            svg.setAttribute('width', `${scaledW}`);
            svg.setAttribute('height', `${scaledH}`);
            svg.style.width = `${scaledW}px`;
            svg.style.height = `${scaledH}px`;
        }

        if (zoomText) zoomText.textContent = `${Math.round(state.scale * 100)}%`;
    }
}

export function fitToScreen(ctx) {
    const rect = ctx.dom.canvasContainer.getBoundingClientRect();
    if (!rect.width || !rect.height) return;
    const pad = 20;
    const s = Math.min((rect.width - pad * 2) / ctx.state.svgWidth, (rect.height - pad * 2) / ctx.state.svgHeight, 2.0);
    ctx.state.scale = Math.max(0.25, Math.min(s, 16.0));
    ctx.state.x = (rect.width - ctx.state.svgWidth * ctx.state.scale) / 2;
    ctx.state.y = (rect.height - ctx.state.svgHeight * ctx.state.scale) / 2;
    updateTransform(ctx);
}

export function zoomAround(ctx, factor, clientX, clientY) {
    const rect = ctx.dom.canvasContainer.getBoundingClientRect();
    const cx = clientX - rect.left;
    const cy = clientY - rect.top;
    const oldS = ctx.state.scale;
    const newS = Math.max(0.25, Math.min(oldS * factor, 16.0));
    const svgX = (cx - ctx.state.x) / oldS;
    const svgY = (cy - ctx.state.y) / oldS;
    ctx.state.scale = newS;
    ctx.state.x = cx - svgX * newS;
    ctx.state.y = cy - svgY * newS;
    updateTransform(ctx);
}

export function getSvgCoords(ctx, clientX, clientY) {
    const svg = ctx.dom.svgContainer.querySelector('svg');
    if (!svg) {
        const rect = ctx.dom.canvasContainer.getBoundingClientRect();
        return {
            x: (clientX - rect.left - ctx.state.x) / ctx.state.scale,
            y: (clientY - rect.top - ctx.state.y) / ctx.state.scale,
        };
    }
    const pt = svg.createSVGPoint();
    pt.x = clientX;
    pt.y = clientY;
    const ctm = svg.getScreenCTM();
    if (!ctm) {
        const rect = ctx.dom.canvasContainer.getBoundingClientRect();
        return {
            x: (clientX - rect.left - ctx.state.x) / ctx.state.scale,
            y: (clientY - rect.top - ctx.state.y) / ctx.state.scale,
        };
    }
    const res = pt.matrixTransform(ctm.inverse());
    return { x: res.x, y: res.y };
}

export function focusOnSvgPoint(ctx, svgX, svgY, targetScale = 3.0) {
    const rect = ctx.dom.canvasContainer.getBoundingClientRect();
    if (!rect.width || !rect.height) return;
    ctx.state.scale = Math.max(ctx.state.scale, targetScale);
    ctx.state.x = rect.width / 2 - svgX * ctx.state.scale;
    ctx.state.y = rect.height / 2 - svgY * ctx.state.scale;
    updateTransform(ctx);
}

export async function loadSvgMap(ctx, onLoaded) {
    const { svgContainer, loadingEl } = ctx.dom;
    try {
        const res = await fetch(ctx.config.svgUrl);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const svgText = await res.text();
        svgContainer.innerHTML = svgText;

        const svg = svgContainer.querySelector('svg');
        if (svg) {
            svg.setAttribute('width', `${ctx.state.svgWidth}`);
            svg.setAttribute('height', `${ctx.state.svgHeight}`);
            svg.setAttribute('shape-rendering', 'geometricPrecision');
            svg.setAttribute('text-rendering', 'geometricPrecision');
            svg.style.display = 'block';
            svg.style.maxWidth = 'none';
            svg.style.fontFamily = CAD_FONT_FAMILY;
        }

        if (loadingEl) {
            loadingEl.style.opacity = '0';
            setTimeout(() => {
                loadingEl.style.display = 'none';
            }, 250);
        }
        onLoaded();
        fitToScreen(ctx);
    } catch (err) {
        console.error(err);
        if (loadingEl) loadingEl.textContent = `白地図の読み込みに失敗しました: ${err.message}`;
    }
}
