<!-- 1. 閲覧専用 詳細モーダル -->
<x-ui.modal id="modal-tlane-detail" title="担当班編成の詳細" icon="🏷️" max-width="xl">
    <div class="space-y-4 text-xs">
        <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100">
            <span id="detail-tlane-badge" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border bg-slate-100 text-slate-800 border-slate-300">
                <span id="detail-tlane-badge-icon" class="mr-1.5">📋</span>
                <span id="detail-tlane-badge-name">班名</span>
            </span>
            <a id="detail-tlane-tasks-link" href="{{ route('timetables.tasks.index', $timetable) }}"
                class="text-indigo-600 hover:underline font-bold">
                この班のタスク管理を見る →
            </a>
        </div>

        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
            <div class="text-[11px] font-bold text-slate-500">標準役割（マスター定義）</div>
            <div id="detail-tlane-description" class="text-slate-700 whitespace-pre-wrap leading-relaxed">-</div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                <div class="text-[11px] font-bold text-slate-500">👑 当日の班長（リーダー）</div>
                <div id="detail-tlane-lead" class="font-bold text-slate-800">未設定</div>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                <div class="text-[11px] font-bold text-slate-500">📌 固定スタッフ</div>
                <div id="detail-tlane-fixed-staff" class="flex flex-wrap gap-1"></div>
            </div>
        </div>

        <div class="space-y-1">
            <div class="text-[11px] font-bold text-slate-500">📝 当日運用メモ</div>
            <div id="detail-tlane-notes" class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-slate-700 whitespace-pre-wrap">-</div>
        </div>

        <div class="space-y-1.5">
            <div class="text-[11px] font-bold text-slate-500">
                📋 登録タスク（<span id="detail-tlane-tasks-count" class="font-mono">0</span> 件）
            </div>
            <div id="detail-tlane-tasks-list" class="max-h-40 overflow-y-auto space-y-1"></div>
        </div>

        <div class="space-y-1.5">
            <div class="text-[11px] font-bold text-slate-500">📣 班長からの応援・機材要請履歴</div>
            <div id="detail-tlane-requests-list" class="max-h-36 overflow-y-auto space-y-1"></div>
        </div>
    </div>

    <x-slot:footer>
        <x-ui.button variant="secondary" data-close-target="#modal-tlane-detail">閉じる</x-ui.button>
    </x-slot:footer>
</x-ui.modal>
