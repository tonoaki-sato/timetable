/**
 * 白地図配置エディタ定数定義 (A3 1/400 pt 基準)
 */

export const COLOR_MAP = {
    red: { stroke: '#dc2626', text: '#dc2626' },
    green: { stroke: '#16a34a', text: '#16a34a' },
    black: { stroke: '#0f172a', text: '#0f172a' },
    cyan: { stroke: '#06b6d4', text: '#0891b2' },
    orange: { stroke: '#f97316', text: '#ea580c' },
    blue: { stroke: '#2563eb', text: '#2563eb' },
};

// 通り沿い歩道4レールX座標 (A3 1/400 pt)
export const STREET_RAILS_X = [309.72, 353.94, 681.36, 725.58];

export const HYGIENE_RADIUS_PT = 138.0; // 20m

export const CAD_FONT_FAMILY =
    "'BIZ UDPGothic', 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', 'Yu Gothic UI', 'Meiryo', 'Noto Sans JP', sans-serif";
