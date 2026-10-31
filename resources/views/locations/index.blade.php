<x-layouts.app title="拠点管理">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- 1. ヘッダーエリア -->
    <x-page-header
        title="拠点管理"
        icon="📍"
        description="番所・駐車場・コミュニティスペース等の基本情報と、年月日時分単位の利用可能時間帯・申請状況を一元管理します。">
        <x-slot:badges>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                全 {{ $locationsWithStatus->count() }} 拠点
            </span>
        </x-slot:badges>
        <x-slot:actions>
            <a href="{{ route('location-types.index') }}"
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition">
                <span>🏷️</span>
                <span>タイプマスタ管理</span>
            </a>
            <a href="{{ route('locations.create') }}"
                class="inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                <span>➕</span>
                <span>拠点を新規登録</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 2. スリム拠点一覧テーブル (1〜2行程度にコンパクト化) -->
    @if($locationsWithStatus->isEmpty())
        <div class="bg-white p-12 rounded-xl border border-dashed border-slate-200 text-center shadow-xs">
            <div class="text-3xl mb-2">📍</div>
            <h3 class="text-sm font-bold text-slate-800 mb-1">拠点がまだ登録されていません</h3>
            <p class="text-xs text-slate-500 mb-4">番所や近隣駐車場、コミュニティスペース等の拠点を登録してください。</p>
            <a href="{{ route('locations.create') }}"
                class="inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                <span>➕</span>
                <span>拠点を登録する</span>
            </a>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
            <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs text-slate-500 font-bold">
                <span>登録拠点一覧</span>
                <span class="text-[11px] font-normal text-slate-400">判定基準: {{ $now->format('Y/m/d H:i') }} 現在</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs divide-y divide-slate-100">
                    <thead class="bg-slate-50/75 border-b border-slate-200 text-slate-500 font-bold text-[11px]">
                        <tr>
                            <th class="py-2.5 px-4 min-w-[200px]">拠点名 / 種別</th>
                            <th class="py-2.5 px-4 min-w-[170px]">現在の状態</th>
                            <th class="py-2.5 px-4 min-w-[240px]">本日の利用可能枠</th>
                            <th class="py-2.5 px-4 text-right whitespace-nowrap min-w-[150px]">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($locationsWithStatus as $item)
                            @php
                                $loc = $item['location'];
                                $eval = $item['evaluation'];
                                $todaySlots = $item['todaySchedules'];
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <!-- 列1: 拠点名 & タイプ -->
                                <td class="py-2.5 px-4 align-middle">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('locations.show', $loc) }}" class="font-bold text-slate-900 hover:text-indigo-600 transition text-sm">
                                            {{ $loc->name }}
                                        </a>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                            {{ $loc->type?->label ?? '未分類' }}
                                        </span>
                                        @if($loc->is_all_day)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                終日
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- 列2: 現在の状態 -->
                                <td class="py-2.5 px-4 align-middle whitespace-nowrap">
                                    <div class="flex items-center space-x-1.5">
                                        <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $eval->badgeClass() }}">
                                            <span>{{ $eval->icon() }}</span>
                                            <span>{{ $eval->label() }}</span>
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 truncate max-w-[200px] mt-0.5" title="{{ $eval->detail }}">
                                        {{ $eval->detail }}
                                    </p>
                                </td>

                                <!-- 列3: 本日の利用可能枠 (横並びバッジ) -->
                                <td class="py-2.5 px-4 align-middle">
                                    @if($todaySlots->isNotEmpty())
                                        <div class="flex flex-wrap items-center gap-1">
                                            @foreach($todaySlots as $slot)
                                                <span class="inline-flex items-center space-x-1 text-[10px] px-1.5 py-0.5 rounded border {{ $slot->application_status_badge_class }}"
                                                      title="{{ $slot->title ? $slot->title . ' / ' : '' }}{{ $slot->note }}">
                                                    <span class="font-mono font-bold">{{ $slot->start_at->format('H:i') }}-{{ $slot->end_at->format('H:i') }}</span>
                                                    <span class="font-bold">[{{ $slot->application_status === 'unapplied' ? '未申請' : ($slot->application_status === 'approved' ? '承認済' : $slot->application_status_label) }}]</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @elseif($loc->is_all_day)
                                        <span class="text-[11px] text-blue-600 font-bold">終日常時利用可能</span>
                                    @else
                                        <span class="text-[11px] text-slate-400">本日枠なし</span>
                                    @endif
                                </td>

                                <!-- 列4: 操作 -->
                                <td class="py-2.5 px-4 align-middle text-right space-x-1 whitespace-nowrap">
                                    <a href="{{ route('locations.show', $loc) }}"
                                        class="inline-flex items-center space-x-1 px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-[11px] font-bold transition">
                                        <span>⏱️</span>
                                        <span>時間帯</span>
                                    </a>
                                    <a href="{{ route('locations.edit', $loc) }}"
                                        class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-medium transition inline-block">
                                        編集
                                    </a>
                                    <form method="POST" action="{{ route('locations.destroy', $loc) }}"
                                          data-confirm="拠点「{{ $loc->name }}」を削除しますか？&#10;（関連する時間帯データも削除されます）"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-600 rounded text-[11px] font-medium transition cursor-pointer">
                                            削除
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

@vite(['resources/js/locations.js'])
</x-layouts.app>
