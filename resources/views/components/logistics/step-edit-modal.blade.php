@props(['locations' => collect(), 'staffs' => collect(), 'defaultTargetDate' => ''])

<!-- 個別搬出入ステップ編集モーダル -->
<div id="plan-edit-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" data-action="close-modal"></div>

    <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden transform transition-all max-h-[calc(100vh-2.5rem)] flex flex-col">

            <!-- モーダルヘッダー (固定) -->
            <div class="shrink-0 px-5 py-3.5 bg-white border-b border-slate-100 flex items-center justify-between gap-2">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center text-sm shadow-2xs shrink-0 font-bold">
                        ✏️
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-slate-900 truncate flex items-center space-x-2">
                            <span>搬出入ステップ編集:</span>
                            <span id="edit_plan_equipment_name" class="text-indigo-600 font-bold truncate"></span>
                        </h3>
                    </div>
                </div>

                <button type="button" data-action="close-modal"
                    class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition text-sm leading-none cursor-pointer shrink-0"
                    title="閉じる">
                    ✕
                </button>
            </div>

            <!-- 入力フォーム (縦スクロール可能ボディ) -->
            <form id="plan-edit-form" action="" method="POST" data-default-target-date="{{ $defaultTargetDate }}" class="flex-1 flex flex-col min-h-0">
                @csrf
                @method('PUT')

                <div class="overflow-y-auto flex-1 p-5 space-y-4 text-xs">
                    <x-logistics.step-form-fields prefix="edit_plan" :locations="$locations" :staffs="$staffs" :default-target-date="$defaultTargetDate" />
                </div>

                <!-- モーダルフッター (固定) -->
                <div class="shrink-0 px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-2">
                    <button type="button" data-action="close-modal"
                        class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer">
                        キャンセル
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                        変更を保存する
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

