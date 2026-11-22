/**
 * CSP対応: data-percent 属性に基づく動的プログレスバーの初期化 (CSSOM)
 */
export function initProgressBars(root = document) {
    const bars = root.querySelectorAll('.progress-bar-fill[data-percent]');
    bars.forEach((bar) => {
        const rawPercent = parseFloat(bar.getAttribute('data-percent')) || 0;
        const clampedPercent = Math.min(100, Math.max(0, rawPercent));
        bar.style.width = `${clampedPercent}%`;
    });
}

window.initProgressBars = initProgressBars;
