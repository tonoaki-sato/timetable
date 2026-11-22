<x-layouts.app :title="$timetable->name . ' - 運行基本情報'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 space-y-3">

    <!-- 1. ナビゲーション & ヘッダー (コンパクト1行化) -->
    <x-page-header
        :title="$timetable->name"
        icon="⏱️"
        :description="$timetable->description ?: '「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）の運行基本情報・規制時間帯および担当班サマリーです。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>⏱️</span>
                <span>運行情報一覧</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">📅 {{ $timetable->name }}</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <div class="flex items-center space-x-2 shrink-0">
                @can('update', $timetable)
                <button type="button"
                    id="btn-open-edit-timetable"
                    class="btn-edit-timetable inline-flex items-center space-x-1.5 px-3 py-1.5 bg-white hover:bg-indigo-600 hover:text-white hover:border-indigo-600 border border-slate-300 text-slate-700 rounded-lg text-xs font-bold transition cursor-pointer shadow-2xs"
                    data-timetable-id="{{ $timetable->id }}"
                    data-update-url="{{ route('timetables.update', $timetable) }}"
                    data-name="{{ $timetable->name }}"
                    data-target-date="{{ $timetable->target_date?->format('Y-m-d') }}"
                    data-status="{{ $timetable->status }}"
                    data-start-time="{{ substr($timetable->start_time, 0, 5) }}"
                    data-end-time="{{ substr($timetable->end_time, 0, 5) }}"
                    data-description="{{ $timetable->description }}"
                    data-road-periods='@json($timetable->road_periods ?? [])'
                    data-festival-periods='@json($timetable->festival_periods ?? [])'>
                    <span>✏️</span>
                    <span>基本情報を編集</span>
                </button>
                @endcan

                <x-ui.button variant="header-action" :href="route('timetables.index')">
                    <span>←</span>
                    <span>運行情報一覧に戻る</span>
                </x-ui.button>
            </div>
        </x-slot:actions>
    </x-page-header>

    <!-- 2. 運行日コンテキストバー (日程切替・サブナビ) -->
    <x-timetable-context-bar :timetable="$timetable" active="show" />

    <!-- 3. 当運行計画 4大統合オペレーションKPIストリップ (薄型) -->
    <x-timetable.show-kpi-strip :overview="$overview" />

    @include('timetables.partials.show-print-reports')

    <!-- 5. 高密度 3カラム コックピット (カード内独立スクロールでゼロスクロール最適化) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-start">

        @include('timetables.partials.show-lanes-tasks-column')

        @include('timetables.partials.show-overview-column')

        @include('timetables.partials.show-logistics-requests-column')

    </div>

    <!-- 運行情報 編集モーダル -->
    @include('timetables._form_modal', [
        'statuses' => \App\Enums\TimetableStatus::options(),
        'isEditOnly' => true,
        'redirectTo' => 'show',
    ])

</div>

@vite(['resources/js/timetables.js'])
</x-layouts.app>
