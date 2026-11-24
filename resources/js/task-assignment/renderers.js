import { escapeHtml } from '../utils/html.js';

/**
 * 人員配置モーダル・ワークスペース共通 HTML レンダラー
 * Strict CSP準拠（インラインstyle/on*不使用、全動的値をescapeHtml済み）
 */

/**
 * タスクの人員充足ステータスバッジHTMLを生成する
 *
 * @param {object} task
 * @returns {string}
 */
export function renderTaskStatusBadgeHtml(task) {
    if (task.is_satisfied) {
        return `<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/40 font-mono">✔ ${task.assigned_count}/${escapeHtml(task.required_people_label)} 充足</span>`;
    }
    return `<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-500/25 text-amber-200 border border-amber-400/50 font-mono">⚠️ ${task.assigned_count}/${escapeHtml(task.required_people_label)} (不足${task.shortage_count}名)</span>`;
}

/**
 * 現在配置中のスタッフ一覧HTMLを生成する（班長の解除不可ガード統一）
 *
 * @param {Array<object>} assignments
 * @param {{ emptyMessage?: string, wrapInGrid?: boolean }} options
 * @returns {string}
 */
export function renderCurrentAssignmentsHtml(assignments, { emptyMessage, wrapInGrid = false } = {}) {
    const list = Array.isArray(assignments) ? assignments : [];

    if (list.length === 0) {
        const msg = emptyMessage || 'まだスタッフが配置されていません。下の一覧から選択して配置してください。';
        const spanClass = wrapInGrid ? '' : 'col-span-full ';
        return `<div class="${spanClass}px-3 py-2.5 bg-slate-50 rounded-xl border border-dashed border-slate-200 text-center text-xs text-slate-400">${escapeHtml(msg)}</div>`;
    }

    const cardsHtml = list.map((a) => `
        <div class="px-2.5 py-1.5 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between text-xs hover:border-slate-300 transition">
            <div class="min-w-0 pr-1.5 flex items-center space-x-1.5">
                <span class="font-bold text-slate-900 truncate">${escapeHtml(a.staff_name)}</span>
                <span class="px-1.5 py-0.2 rounded text-[9px] font-medium bg-white text-slate-600 border border-slate-200 shrink-0">
                    ${a.is_lane_lead ? '👑 班長' : escapeHtml(a.role_description)}
                </span>
                ${a.affiliation ? `<span class="text-[10px] text-slate-400 truncate hidden sm:inline">(${escapeHtml(a.affiliation)})</span>` : ''}
            </div>
            ${a.is_lane_lead ? `
                <span class="px-1.5 py-0.5 text-slate-400 bg-slate-200/60 rounded text-[11px] font-medium shrink-0 cursor-not-allowed select-none" title="班長は解除できません">
                    解除不可
                </span>
            ` : `
                <button type="button" data-action="remove-staff" data-staff-id="${a.staff_id}" data-staff-name="${escapeHtml(a.staff_name)}"
                    class="px-1.5 py-0.5 text-red-600 hover:text-red-800 hover:bg-red-50 rounded text-[11px] font-bold transition cursor-pointer shrink-0"
                    title="配置を解除">
                    解除
                </button>
            `}
        </div>
    `).join('');

    if (wrapInGrid) {
        return `<div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-28 overflow-y-auto pr-0.5">${cardsHtml}</div>`;
    }
    return cardsHtml;
}

/**
 * 候補スタッフカード一覧HTMLを生成する
 *
 * @param {Array<object>} candidates
 * @param {number[]} selectedStaffIds
 * @param {{ variant?: 'grid'|'list', emptyMessage?: string }} options
 * @returns {string}
 */
export function renderCandidateCardsHtml(candidates, selectedStaffIds, { variant = 'grid', emptyMessage } = {}) {
    const list = Array.isArray(candidates) ? candidates : [];

    if (list.length === 0) {
        const msg = emptyMessage || '該当するスタッフがいません。';
        const boxClass = variant === 'grid' ? 'col-span-full p-6' : 'py-12';
        return `<div class="${boxClass} text-center text-xs text-slate-400">${escapeHtml(msg)}</div>`;
    }

    if (variant === 'list') {
        return list.map((c) => {
            const isSelected = selectedStaffIds.includes(c.id);
            const isAssigned = Boolean(c.is_already_assigned);

            let badgeHtml = '';
            if (c.is_lane_lead) {
                badgeHtml += '<span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">👑 班長</span> ';
            }
            if (c.is_same_lane && !c.is_conflicted) {
                badgeHtml += '<span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">🌟 推奨</span> ';
            } else if (c.is_surplus_fixed && !c.is_conflicted) {
                badgeHtml += '<span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-300" title="他班で待機中または定員超過のため融通可能">✨ 他班余剰</span> ';
            } else if (!c.is_conflicted) {
                badgeHtml += '<span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">🟢 空き</span> ';
            }
            if (c.is_conflicted) {
                badgeHtml += `<span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-200" title="${escapeHtml(c.conflicted_label)}">⚠️ 重複中</span> `;
            } else if (c.has_concurrent_tasks) {
                badgeHtml += `<span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-teal-50 text-teal-800 border border-teal-200" title="${escapeHtml(c.concurrent_label)}">🔄 定常等並行可</span> `;
            }
            if (c.is_checked_in) {
                badgeHtml += '<span class="px-1.5 py-0.2 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200">📍 受付済</span> ';
            }

            const hoursToday = c.minutes_today ? (c.minutes_today / 60).toFixed(1) : '0';
            const cardBorder = isAssigned
                ? 'bg-slate-50/60 border-slate-200 opacity-60 cursor-not-allowed'
                : (isSelected ? 'bg-indigo-50/60 border-indigo-500 ring-1 ring-indigo-500' : 'bg-white border-slate-200 hover:border-indigo-200 hover:bg-slate-50/70');

            return `
                <label data-candidate-card data-staff-id="${c.id}"
                    class="flex items-center justify-between p-2 rounded-xl border text-xs transition cursor-pointer select-none ${cardBorder}">
                    <div class="flex items-center space-x-2.5 min-w-0 flex-1">
                        <input type="checkbox" data-staff-checkbox value="${c.id}" ${isSelected ? 'checked' : ''} ${isAssigned ? 'disabled' : ''}
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 shrink-0 cursor-pointer">

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center space-x-1.5">
                                <span class="font-bold text-slate-900 truncate">${escapeHtml(c.name)}</span>
                                ${c.kana ? `<span class="text-[10px] text-slate-400 truncate hidden sm:inline">(${escapeHtml(c.kana)})</span>` : ''}
                            </div>
                            <div class="text-[10px] text-slate-500 flex items-center space-x-2 mt-0.5">
                                <span class="truncate">${escapeHtml(c.affiliation || '所属なし')}</span>
                                <span>・</span>
                                <span>本日稼働: <strong class="font-mono text-slate-700">${hoursToday}h</strong></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-1.5 shrink-0 pl-2">
                        ${badgeHtml}
                        ${c.is_conflicted ? `<span class="text-[10px] text-red-500 hidden md:inline truncate max-w-[100px]" title="${escapeHtml(c.conflicted_label)}">${escapeHtml(c.conflicted_task_title)}</span>` : ''}
                    </div>
                </label>
            `;
        }).join('');
    }

    // Compact 2-column grid cards (Matrix quick-assign modal)
    return list.map((c) => {
        const isSelected = selectedStaffIds.includes(c.id);
        const isAssigned = Boolean(c.is_already_assigned);
        const isConflicted = Boolean(c.is_conflicted);

        let cardClass = 'px-2.5 py-2 rounded-lg border text-xs select-none transition flex flex-col justify-between gap-1 ';
        if (isAssigned) {
            cardClass += 'opacity-55 bg-slate-100 border-slate-200 cursor-not-allowed';
        } else if (isSelected) {
            cardClass += 'bg-indigo-50/90 border-indigo-600 ring-1 ring-indigo-500 cursor-pointer';
        } else if (isConflicted) {
            cardClass += 'bg-amber-50/60 border-amber-200/80 hover:border-amber-300 cursor-pointer';
        } else if (c.is_surplus_fixed) {
            cardClass += 'bg-purple-50/60 border-purple-200 hover:border-purple-300 cursor-pointer';
        } else {
            cardClass += 'bg-white border-slate-200 hover:border-indigo-300 hover:shadow-2xs cursor-pointer';
        }

        let topRightBadges = '';
        if (c.is_lane_lead) {
            topRightBadges += '<span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-300">👑 班長</span>';
        }
        if (c.is_checked_in) {
            topRightBadges += '<span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-sky-100 text-sky-800 border border-sky-300" title="現場到着受付済み">着</span>';
        }
        if (isAssigned) {
            topRightBadges += '<span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-slate-200 text-slate-600">配置済</span>';
        }

        let statusBadge = '';
        if (isConflicted) {
            statusBadge = `<span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-300 truncate max-w-[115px]" title="${escapeHtml(c.conflicted_label)}">⚠️ ${escapeHtml(c.conflicted_task_title || '重複中')}</span>`;
        } else if (c.has_concurrent_tasks && c.is_same_lane) {
            statusBadge = `<span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-teal-100 text-teal-800 border border-teal-300 truncate max-w-[115px]" title="${escapeHtml(c.concurrent_label)}">🌟 同班・並行可</span>`;
        } else if (c.has_concurrent_tasks) {
            statusBadge = `<span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-teal-50 text-teal-800 border border-teal-300 truncate max-w-[115px]" title="${escapeHtml(c.concurrent_label)}">🔄 並行可</span>`;
        } else if (c.is_same_lane) {
            statusBadge = '<span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">🌟 同班推奨</span>';
        } else if (c.is_surplus_fixed) {
            statusBadge = '<span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-purple-100 text-purple-800 border border-purple-300" title="他班で待機中または定員超過のため融通可能">✨ 他班余剰</span>';
        } else {
            statusBadge = '<span class="px-1.5 py-0.2 rounded text-[9px] font-medium bg-slate-100 text-slate-600 border border-slate-200">🟢 空き</span>';
        }

        const hours = Math.round(((c.minutes_today || 0) / 60) * 10) / 10;

        return `
            <div class="${cardClass}" data-action="toggle-candidate" data-staff-id="${c.id}" data-assigned="${isAssigned ? '1' : '0'}">
                <div class="flex items-center justify-between gap-1.5 min-w-0">
                    <div class="flex items-center space-x-2 min-w-0">
                        <input type="checkbox" value="${c.id}" ${isSelected ? 'checked' : ''} ${isAssigned ? 'disabled' : ''}
                            class="candidate-checkbox h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 cursor-pointer pointer-events-none shrink-0">
                        <span class="font-bold text-slate-900 truncate" title="${escapeHtml(c.name)}">${escapeHtml(c.name)}</span>
                        ${c.kana ? `<span class="text-[10px] text-slate-400 font-normal truncate hidden xl:inline">(${escapeHtml(c.kana)})</span>` : ''}
                    </div>
                    <div class="flex items-center space-x-1 shrink-0">${topRightBadges}</div>
                </div>

                <div class="flex items-center justify-between gap-1.5 pl-5 text-[10px]">
                    <span class="text-slate-500 truncate" title="${escapeHtml(c.affiliation || '所属なし')}">
                        ${escapeHtml(c.affiliation || '所属なし')}
                    </span>
                    <div class="flex items-center space-x-1.5 shrink-0">
                        ${statusBadge}
                        <span class="font-mono text-slate-400">${hours}h</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * 統一アラートボックスを描画する（重複409強制承認UI対応）
 *
 * @param {HTMLElement|null} alertArea
 * @param {{ message: string, type?: 'error'|'warning'|'success', allowForce?: boolean, showCancel?: boolean, forceAction?: string, dismissAction?: string, conflictTitle?: string|null }} options
 */
export function renderAlertBox(alertArea, {
    message,
    type = 'error',
    allowForce = false,
    showCancel = false,
    forceAction = 'submit-bulk-assign-force',
    dismissAction = 'dismiss-alert',
    conflictTitle = null,
}) {
    if (!alertArea) {
        return;
    }

    const isSuccess = type === 'success';
    const isWarning = type === 'warning' || allowForce;

    if (allowForce && conflictTitle) {
        alertArea.className = '';
        alertArea.innerHTML = `
            <div class="p-3 bg-amber-50 border border-amber-300 rounded-xl text-amber-900 text-xs space-y-2">
                <p class="font-bold flex items-center space-x-1">
                    <span>⚠️</span>
                    <span>${escapeHtml(conflictTitle)}</span>
                </p>
                <p class="text-[11px]">${escapeHtml(message)}</p>
                <div class="flex items-center space-x-2 pt-1">
                    <button type="button" data-action="${forceAction}"
                        class="px-3 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-bold text-xs shadow-xs transition cursor-pointer">
                        重複を承認してアサインする
                    </button>
                    ${showCancel ? `
                    <button type="button" data-action="${dismissAction}"
                        class="px-3 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-xs font-medium transition cursor-pointer">
                        キャンセル
                    </button>
                    ` : ''}
                </div>
            </div>
        `;
        alertArea.classList.remove('hidden');
        return;
    }

    const bgClass = isSuccess
        ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
        : (isWarning ? 'bg-amber-50 border-amber-300 text-amber-900' : 'bg-red-50 border-red-200 text-red-800');
    const icon = isSuccess ? '✔' : (isWarning ? '⚠️' : '🚨');

    alertArea.className = `p-2.5 rounded-xl border text-xs flex flex-col gap-2 ${bgClass}`;

    const forceBtnHtml = allowForce
        ? `<div class="flex justify-end space-x-2">
            ${showCancel ? `<button type="button" data-action="${dismissAction}" class="px-3 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-[11px] font-medium transition cursor-pointer">キャンセル</button>` : ''}
            <button type="button" data-action="${forceAction}" class="px-3 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-[11px] font-bold transition cursor-pointer">
                ⚠️ 重複を承認してそのまま配置する
            </button>
           </div>`
        : '';

    alertArea.innerHTML = `
        <div class="flex items-start justify-between gap-2">
            <div class="flex items-center space-x-1.5 min-w-0">
                <span class="shrink-0">${icon}</span>
                <span>${escapeHtml(message)}</span>
            </div>
            <button type="button" data-action="${dismissAction}" class="font-bold text-sm leading-none cursor-pointer shrink-0">&times;</button>
        </div>
        ${forceBtnHtml}
    `;
    alertArea.classList.remove('hidden');
}

/**
 * アラート表示を消去する
 *
 * @param {HTMLElement|null} alertArea
 */
export function clearAlertBox(alertArea) {
    if (!alertArea) {
        return;
    }
    alertArea.innerHTML = '';
    alertArea.classList.add('hidden');
}

/**
 * 選択人数カウンタと送信・解除ボタンの状態を安全に更新する
 * （submitBtn内の子要素 <strong id="..."> を破壊しない）
 *
 * @param {{
 *   count: number,
 *   countEl: HTMLElement|null,
 *   submitCountEl: HTMLElement|null,
 *   clearBtn: HTMLElement|null,
 *   submitBtn: HTMLButtonElement|null,
 *   activeClass: string,
 *   disabledClass: string,
 *   isSubmitting?: boolean,
 * }} options
 */
export function updateSelectionButtons({
    count,
    countEl,
    submitCountEl,
    clearBtn,
    submitBtn,
    activeClass,
    disabledClass,
    isSubmitting = false,
}) {
    if (countEl) {
        countEl.textContent = count;
    }
    if (submitCountEl) {
        submitCountEl.textContent = count;
    }
    if (clearBtn) {
        clearBtn.classList.toggle('hidden', count === 0);
    }
    if (submitBtn) {
        const disabled = isSubmitting || count === 0;
        submitBtn.disabled = disabled;
        submitBtn.setAttribute('aria-busy', isSubmitting ? 'true' : 'false');
        if (isSubmitting) {
            submitBtn.className = `${activeClass} opacity-70 cursor-wait`;
        } else if (count > 0) {
            submitBtn.className = activeClass;
        } else {
            submitBtn.className = disabledClass;
        }
    }
}
