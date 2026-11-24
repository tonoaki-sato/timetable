/**
 * タイムライン・ガントチャート共通 時間/座標ユーティリティ
 */

export function hhmmToMinutes(hhmm) {
    if (!hhmm || typeof hhmm !== 'string') return 0;
    const parts = hhmm.split(':');
    if (parts.length < 2) return 0;
    return (parseInt(parts[0], 10) || 0) * 60 + (parseInt(parts[1], 10) || 0);
}

export function minutesToHhmm(totalMinutes) {
    const clamped = Math.max(0, Math.min(24 * 60, Math.round(totalMinutes)));
    const h = String(Math.floor(clamped / 60)).padStart(2, '0');
    const m = String(clamped % 60).padStart(2, '0');
    return `${h}:${m}`;
}

export function snapMinutes(minutes, step = 15) {
    return Math.round(minutes / step) * step;
}

export function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}
