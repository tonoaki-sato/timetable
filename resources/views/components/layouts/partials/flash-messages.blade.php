<!-- グローバルフラッシュメッセージ -->
@if (session('success') || session('status') || session('error'))
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-3 space-y-2">
    @if (session('success'))
    <div data-alert-box role="status" class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-xl font-bold flex items-center justify-between gap-2 shadow-2xs">
        <div class="flex items-center space-x-2">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" data-dismiss-alert aria-label="メッセージを閉じる" class="text-emerald-600 hover:text-emerald-900 font-bold px-1 cursor-pointer">✕</button>
    </div>
    @endif

    @if (session('status'))
    <div data-alert-box role="status" class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-xl font-bold flex items-center justify-between gap-2 shadow-2xs">
        <div class="flex items-center space-x-2">
            <span>✅</span>
            <span>{{ session('status') }}</span>
        </div>
        <button type="button" data-dismiss-alert aria-label="メッセージを閉じる" class="text-emerald-600 hover:text-emerald-900 font-bold px-1 cursor-pointer">✕</button>
    </div>
    @endif

    @if (session('error'))
    <div data-alert-box role="alert" class="p-3 bg-red-50 border border-red-200 text-red-800 text-xs rounded-xl font-bold flex items-center justify-between gap-2 shadow-2xs">
        <div class="flex items-center space-x-2">
            <span>⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" data-dismiss-alert aria-label="メッセージを閉じる" class="text-red-600 hover:text-red-900 font-bold px-1 cursor-pointer">✕</button>
    </div>
    @endif
</div>
@endif
