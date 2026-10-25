<x-layouts.app title="タイムテーブル管理">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ヘッダー -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">⏱️ タイムテーブル管理</h2>
            <p class="text-xs text-slate-500 mt-1">
                まつり開催日ごとの基本枠（運行日・開始終了時刻）および各日の作業タスク・人員動態を管理します。
            </p>
        </div>

        @can('create', App\Models\Timetable::class)
        <a href="{{ route('timetables.create') }}"
            class="inline-flex items-center space-x-1.5 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
            <span>➕</span>
            <span>新規タイムテーブルを登録</span>
        </a>
        @endcan
    </div>

    <!-- ステータス絞り込みタブ -->
    <div class="flex items-center space-x-2 border-b border-slate-200 pb-2">
        <a href="{{ route('timetables.index') }}"
            class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ !request('status') ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            すべての運行日
        </a>
        @foreach($statuses as $key => $label)
        <a href="{{ route('timetables.index', ['status' => $key]) }}"
            class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ request('status') === $key ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    <!-- 運行日カード・一覧グリッド -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($timetables as $timetable)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col hover:border-indigo-300 transition group">
            <!-- カードヘッダー -->
            <div class="p-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white flex items-center justify-between">
                <div>
                    <span class="text-xs font-mono font-bold text-indigo-600 block">
                        📅 {{ $timetable->target_date?->format('Y年m月d日') }}
                    </span>
                    <h3 class="font-bold text-base text-slate-900 mt-0.5 group-hover:text-indigo-600 transition">
                        <a href="{{ route('timetables.show', $timetable) }}">
                            {{ $timetable->name }}
                        </a>
                    </h3>
                </div>

                @php
                    $statusBadge = match($timetable->status) {
                        'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold animate-pulse',
                        'completed' => 'bg-slate-100 text-slate-600 border-slate-200',
                        default => 'bg-blue-50 text-blue-700 border-blue-200',
                    };
                @endphp
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[11px] border {{ $statusBadge }}">
                    {{ $timetable->status_label }}
                </span>
            </div>

            <!-- カードボディ -->
            <div class="p-5 flex-1 space-y-4">
                <div class="flex items-center justify-between text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <div>
                        <span class="text-[10px] text-slate-400 block font-bold">運行時間枠</span>
                        <span class="font-mono font-bold text-slate-800">
                            {{ substr($timetable->start_time, 0, 5) }} 〜 {{ substr($timetable->end_time, 0, 5) }}
                        </span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 block font-bold">登録タスク</span>
                        <span class="font-mono font-bold text-indigo-600 text-sm">
                            {{ $timetable->tasks_count ?? 0 }} <span class="text-xs font-normal text-slate-500">件</span>
                        </span>
                    </div>
                </div>

                @if($timetable->description)
                <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                    {{ $timetable->description }}
                </p>
                @endif

                <!-- クイックアクセスリンク（タイムテーブル & 担当班管理） -->
                <div class="grid grid-cols-2 gap-2 pt-3 border-t border-slate-100">
                    <a href="{{ route('timetable.index', ['timetable_id' => $timetable->id]) }}"
                        class="inline-flex items-center justify-center space-x-1 px-2.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition">
                        <span>⏱️</span>
                        <span>タイムテーブル</span>
                    </a>
                    <a href="{{ route('timetables.lanes.index', $timetable) }}"
                        class="inline-flex items-center justify-center space-x-1 px-2.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                        <span>🏷️</span>
                        <span>担当班管理</span>
                    </a>
                </div>
            </div>

            <!-- カードフッター / アクション -->
            <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between text-xs">
                <a href="{{ route('timetables.show', $timetable) }}"
                    class="font-bold text-indigo-600 hover:text-indigo-800 flex items-center space-x-1">
                    <span>タスク・人員詳細を開く</span>
                    <span>→</span>
                </a>

                <div class="space-x-1">
                    @can('update', $timetable)
                    <a href="{{ route('timetables.edit', $timetable) }}"
                        class="px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-200 rounded text-[11px] font-medium text-slate-700 transition">
                        編集
                    </a>
                    @endcan

                    @can('delete', $timetable)
                    <form action="{{ route('timetables.destroy', $timetable) }}" method="POST" class="inline"
                        data-confirm="タイムテーブル「{{ $timetable->name }}」を削除しますか？&#10;（関連するすべての作業タスクも削除されます）">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="px-2.5 py-1 bg-white hover:bg-red-50 border border-slate-200 hover:border-red-200 text-red-600 rounded text-[11px] font-medium transition cursor-pointer">
                            削除
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white p-12 rounded-2xl border border-slate-200 text-center text-slate-400">
            <span class="text-3xl block mb-2">⏱️</span>
            登録されている運行タイムテーブルはありません。
        </div>
        @endforelse
    </div>

</div>
</x-layouts.app>
