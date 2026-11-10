<x-layouts.app :title="$equipment->name . ' - 機材・備品詳細'">
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$equipment->name"
        :icon="$equipment->type_enum->icon()"
        :description="'機材・備品台帳の基本スペックおよび保管情報です。'">
        <x-slot:breadcrumbs>
            @if($isTimetableScoped)
                <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                    <span>⏱️</span>
                    <span>運行情報一覧</span>
                </a>
                <span class="text-slate-300">/</span>
                <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                    <span>📅</span>
                    <span>{{ $timetable->name }}</span>
                </a>
                <span class="text-slate-300">/</span>
                <a href="{{ $indexUrl }}" class="hover:text-indigo-600 transition truncate">
                    📦 機材・備品一覧
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-bold truncate">{{ $equipment->name }}</span>
            @else
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                    <span>🏮</span>
                    <span>本部</span>
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-500">🗂️ マスター管理</span>
                <span class="text-slate-300">/</span>
                <a href="{{ route('equipments.index') }}" class="hover:text-indigo-600 transition truncate">
                    📦 機材・備品一覧
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-bold truncate">{{ $equipment->name }}</span>
            @endif
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ $indexUrl }}"
                class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>←</span>
                <span>機材・備品一覧に戻る</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    @if($isTimetableScoped)
        <x-timetable-context-bar :timetable="$timetable" active="logistics" />
    @endif

    <!-- アラートメッセージ -->
    @if(session('status'))
        <div class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl flex items-center space-x-2">
            <span>✅</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- 資材基本台帳情報カード -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
        <div class="pb-3 border-b border-slate-100">
            <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                <span>📦</span>
                <span>資材基本台帳情報</span>
            </h2>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block text-[11px]">資機材名</span>
                <span class="font-bold text-slate-900 text-sm block mt-0.5">{{ $equipment->name }}</span>
            </div>

            <div>
                <span class="text-slate-400 block text-[11px]">所有・調達区分</span>
                <div class="mt-0.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $equipment->type_enum->badgeClasses() }}">
                        {{ $equipment->type_enum->icon() }} {{ $equipment->type_label }}
                    </span>
                </div>
            </div>

            <div>
                <span class="text-slate-400 block text-[11px]">基準総数量</span>
                <span class="font-bold font-mono text-slate-900 text-sm block mt-0.5">
                    {{ $equipment->formatted_quantity }}
                </span>
            </div>

            <div>
                <span class="text-slate-400 block text-[11px]">持出・利用ルール</span>
                <div class="mt-0.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $equipment->checkout_rule_enum->badgeClasses() }}">
                        {{ $equipment->checkout_rule_label }}
                    </span>
                </div>
            </div>

            <div>
                <span class="text-slate-400 block text-[11px]">定常保管場所</span>
                <span class="font-bold text-slate-800 flex items-center space-x-1 mt-0.5">
                    <span>📍</span>
                    <span>{{ $equipment->storage_location ?: ($equipment->isPersonal() ? '自宅（個人所有）' : ($equipment->isRental() ? 'イベント会社手配' : '番所地下倉庫')) }}</span>
                </span>
            </div>
        </div>

        <!-- 仕様メモ・注意事項 -->
        <div class="pt-3 border-t border-slate-100">
            <span class="text-slate-400 block text-[11px] mb-1 font-bold">仕様・注意事項・申し送りメモ</span>
            <div class="p-3 bg-slate-50 rounded-xl text-xs text-slate-700 leading-relaxed">
                {{ $equipment->description ?: '仕様メモ・注意事項は登録されていません。' }}
            </div>
        </div>
    </div>

</div>
</x-layouts.app>
