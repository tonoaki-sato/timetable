/**
 * カラーコーン・バリケード配置エディタ定数定義 (A3 1/400 pt 基準)
 */

export const CONE_SPEC = {
    heightM: 0.7,
    baseM: 0.388,
};

export const BAR_SPEC = {
    lengthM: 2.0,
    diameterM: 0.034,
};

export const BARRICADE_SPECS = {
    type_a: {
        label: 'A型バリケード',
        lengthM: 1.2,
        depthM: 0.46,
        heightM: 0.8,
        specText: '120×46×H80cm',
    },
    resin_pipe: {
        label: '樹脂製単管バリケード',
        lengthM: 1.2,
        depthM: 0.64,
        heightM: 0.82,
        specText: '120×64×H82cm',
    },
};

export const CONE_COLORS = {
    red: { label: '赤コーン', fill: '#dc2626', stroke: '#0f172a' },
    yellow: { label: '黄コーン', fill: '#eab308', stroke: '#0f172a' },
    blue: { label: '青コーン', fill: '#2563eb', stroke: '#0f172a' },
    green: { label: '緑コーン', fill: '#16a34a', stroke: '#0f172a' },
    orange: { label: '橙コーン', fill: '#f97316', stroke: '#0f172a' },
};

export const BAR_STYLES = {
    yellow_black: { label: '黄黒トラ柄', primary: '#eab308', secondary: '#0f172a' },
    red_white: { label: '赤白', primary: '#dc2626', secondary: '#ffffff' },
    blue_white: { label: '青白', primary: '#2563eb', secondary: '#ffffff' },
    green_white: { label: '緑白', primary: '#16a34a', secondary: '#ffffff' },
};

export const CALLOUT_COLORS = {
    amber: { label: '注意 (黄)', border: '#d97706', bg: '#fffbeb', text: '#78350f' },
    red: { label: '重要 (赤)', border: '#dc2626', bg: '#fef2f2', text: '#7f1d1d' },
    blue: { label: '案内 (青)', border: '#2563eb', bg: '#eff6ff', text: '#1e3a8a' },
    slate: { label: 'メモ (灰)', border: '#475569', bg: '#f8fafc', text: '#0f172a' },
};

export const STREET_RAILS_X = [309.72, 353.94, 681.36, 725.58];

export const CAD_FONT_FAMILY =
    "'BIZ UDPGothic', 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', 'Yu Gothic UI', 'Meiryo', 'Noto Sans JP', sans-serif";
