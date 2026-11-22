import { getCsrfToken } from '../utils/html.js';

/**
 * 人員配置モーダル・ワークスペース共通 API クライアント
 */

async function parseJsonSafe(res) {
    return res.json().catch(() => ({}));
}

/**
 * タスク詳細および優先順位付き候補スタッフ一覧を取得する
 *
 * @param {number|string} taskId
 * @returns {Promise<object>}
 */
export async function fetchAssignModalData(taskId) {
    const res = await fetch(`/timetable-tasks/${taskId}/assign-modal-data`, {
        headers: { Accept: 'application/json' },
    });
    const data = await parseJsonSafe(res);
    if (!res.ok) {
        throw new Error(data.message || 'タスク情報の取得に失敗しました');
    }
    return data;
}

/**
 * 選択スタッフを一括配置する
 *
 * @param {number|string} taskId
 * @param {{ staffIds: number[], roleDescription?: string, force?: boolean }} options
 * @returns {Promise<{ ok: boolean, status: number, hasConflict: boolean, message: string, data: object }>}
 */
export async function bulkAssignStaff(taskId, { staffIds, roleDescription = '', force = false }) {
    const res = await fetch(`/timetable-tasks/${taskId}/assign-bulk`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({
            staff_ids: staffIds,
            role_description: roleDescription,
            force: force ? 1 : 0,
        }),
    });
    const data = await parseJsonSafe(res);
    return {
        ok: res.ok,
        status: res.status,
        hasConflict: Boolean(data.has_conflict),
        message: data.message || '',
        data,
    };
}

/**
 * 担当班の空きメンバーから不足分を自動配置する
 *
 * @param {number|string} taskId
 * @returns {Promise<{ ok: boolean, status: number, message: string, data: object }>}
 */
export async function assignLanePool(taskId) {
    const res = await fetch(`/timetable-tasks/${taskId}/assign-lane-pool`, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
    });
    const data = await parseJsonSafe(res);
    return {
        ok: res.ok,
        status: res.status,
        message: data.message || '',
        data,
    };
}

/**
 * タスクから指定スタッフの配置を解除する
 *
 * @param {number|string} taskId
 * @param {number|string} staffId
 * @returns {Promise<{ ok: boolean, status: number, message: string, data: object }>}
 */
export async function removeAssignedStaff(taskId, staffId) {
    const res = await fetch(`/timetable-tasks/${taskId}/staff/${staffId}`, {
        method: 'DELETE',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
    });
    const data = await parseJsonSafe(res);
    return {
        ok: res.ok,
        status: res.status,
        message: data.message || '',
        data,
    };
}
