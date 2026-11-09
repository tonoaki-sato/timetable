<x-layouts.app title="搬出入計画 - タイムテーブルの選択">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ナビゲーション & ヘッダー -->
    <x-page-header
        title="搬出入計画"
        icon="🚚"
        description="資材の搬出入・設営・保管計画を設定・確認するタイムテーブル（日程）を選択してください。">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>⏱️</span>
                <span>運行情報一覧</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">🚚 搬出入計画 - タイムテーブル選択</span>
        </x-slot:breadcrumbs>
    </x-page-header>

    @php
        $targetLogisticsUrl = fn($tt) => route('timetables.logistics.index', array_merge(['timetable' => $tt], $queryParams ?? []));
    @endphp

    <!-- アラートメッセージ -->
    @if(session('info'))
    <div class="p-4 bg-sky-50 border border-sky-200 text-sky-800 text-xs font-bold rounded-xl flex items-center space-x-2">
        <span>ℹ️</span>
        <span>{{ session('info') }}</span>
    </div>
    @endif

    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl flex items-center space-x-2">
        <span>✅</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <!-- 運行情報一覧リスト（テーブル形式） -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- リストヘッダー -->
        <div class="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/70 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="text-base">📋</span>
                <h2 class="font-bold text-sm text-slate-800">運行日別 搬出入計画一覧</h2>
            </div>
            <a href="{{ route('equipments.index') }}"
                class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold transition inline-flex items-center space-x-1 shadow-2xs">
                <span>📦</span>
                <span>資材管理（台帳）</span>
            </a>
        </div>

        <!-- テーブル本体 -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-600 select-none">
                    <tr>
                        <th class="px-4 py-3.5 font-bold whitespace-nowrap">運行日程・名称</th>
                        <th class="px-4 py-3.5 font-bold whitespace-nowrap">運行時間枠</th>
                        <th class="px-4 py-3.5 font-bold text-center whitespace-nowrap">計画ステップ数</th>
                        <th class="px-4 py-3.5 font-bold text-right whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($timetables as $timetable)
                    @php
                        $statusBadge = match($timetable->status) {
                            'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold',
                            'completed' => 'bg-slate-100 text-slate-600 border-slate-200',
                            default => 'bg-blue-50 text-blue-700 border-blue-200',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50/70 transition group">
                        <!-- 運行日程・名称 -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <div class="flex items-center space-x-3">
                                <div class="flex items-center space-x-1.5 font-mono font-bold text-indigo-600 text-xs">
                                    <span>📅 {{ $timetable->target_date?->format('Y年m月d日') }}</span>
                                    @if($timetable->target_date)
                                    <span class="text-[11px] font-bold text-slate-500">
                                        ({{ ['日','月','火','水','木','金','土'][$timetable->target_date->dayOfWeek] }})
                                    </span>
                                    @endif
                                </div>
                                <div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] border {{ $statusBadge }}">
                                        @if($timetable->status === 'active')
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse mr-1"></span>
                                        @endif
                                        {{ $timetable->status_label }}
                                    </span>
                                </div>
                                <div class="font-bold text-sm text-slate-900 group-hover:text-indigo-600 transition">
                                    <a href="{{ $targetLogisticsUrl($timetable) }}">
                                        {{ $timetable->name }}
                                    </a>
                                </div>
                            </div>
                        </td>

                        <!-- 運行時間枠 -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <div class="inline-flex items-center space-x-1 text-slate-700 font-mono font-bold bg-slate-50 px-2 py-1 rounded-md border border-slate-100">
                                <span>⏰</span>
                                <span>{{ substr($timetable->start_time, 0, 5) }} 〜 {{ substr($timetable->end_time, 0, 5) }}</span>
                            </div>
                        </td>

                        <!-- 計画ステップ数 -->
                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-mono font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                🚚 {{ $timetable->logistics_plans_count ?? $timetable->logisticsPlans->count() }} 件
                            </span>
                        </td>

                        <!-- 操作 -->
                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                            <a href="{{ $targetLogisticsUrl($timetable) }}"
                                class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition inline-flex items-center space-x-1 shadow-2xs">
                                <span>🚚</span>
                                <span>計画を確認・編集</span>
                                <span>→</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-400">
                            運行日程が登録されていません。<br>
                            <a href="{{ route('timetables.create') }}" class="mt-2 inline-block text-indigo-600 hover:underline font-bold">
                                ＋ 最初の運行日を登録する
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
</x-layouts.app>

