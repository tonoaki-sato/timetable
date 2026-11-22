<x-layouts.app title="担当班マスター">
<div id="lanes-master-app"
    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5"
    data-store-url="{{ route('lanes.store') }}"
    data-reorder-url="{{ route('lanes.reorder') }}"
    data-timetable-lanes-url-template="{{ route('timetables.lanes.index', '__TIMETABLE_ID__') }}">

    <!-- ページヘッダー -->
    <x-page-header
        title="担当班マスター"
        icon="🏷️"
        description="全運行日で共通利用する担当班の定義（班名・テーマカラー・アイコン・標準役割）を一元管理します。">
        <x-slot:breadcrumbs>
            <span class="text-slate-500">🗂️ マスター管理</span>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">🏷️ 担当班マスター</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button variant="header-action" :href="route('timetable-lanes.index')">
                <span>🗓️</span>
                <span>運行日別の担当班編成へ</span>
            </x-ui.button>
            @can('create', App\Models\Lane::class)
                <x-ui.button variant="header-action" id="btn-open-create-lane">
                    <span>➕</span>
                    <span>新規担当班マスター登録</span>
                </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-ui.validation-errors title="入力内容を確認してください" />

    <div id="lane-reorder-feedback" class="hidden px-3 py-2 rounded-xl text-xs font-bold border"></div>

    <!-- 担当班マスター一覧テーブルカード -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-sm text-slate-900 flex items-center space-x-2">
                    <span>🏷️</span>
                    <span>登録済み担当班マスター</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-mono font-normal">
                        全 {{ $lanes->count() }} 班
                    </span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    班名をクリックすると標準役割や運行日の利用状況を確認できます。ドラッグ操作で標準並び順を変更できます。
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap w-20">順</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">班名</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">バッジ</th>
                        <th scope="col" class="px-4 py-2.5">標準役割概要</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap text-right">操作</th>
                    </tr>
                </thead>
                <tbody id="lanes-sortable-body" class="divide-y divide-slate-100 bg-white">
                    @forelse($lanes as $lane)
                        @php
                            $payload = $lane->presenter()->toModalPayload();
                            $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
                            $inUse = $lane->timetable_lanes_count > 0;
                        @endphp
                        <tr data-lane-id="{{ $lane->id }}"
                            @can('update', App\Models\Lane::class) draggable="true" @endcan
                            class="lane-master-row hover:bg-slate-50/70 transition {{ !$lane->is_active ? 'bg-slate-50/60 opacity-75' : '' }}">
                            <!-- 1. 表示順 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center space-x-1.5">
                                    @can('update', App\Models\Lane::class)
                                        <span class="drag-handle cursor-grab active:cursor-grabbing text-slate-400 hover:text-indigo-600 select-none text-sm" title="ドラッグして並び替え">⋮⋮</span>
                                    @endcan
                                    <span class="order-number font-mono font-bold text-slate-600">{{ $loop->iteration }}</span>
                                </div>
                            </td>

                            <!-- 2. 班名（閲覧専用詳細モーダル起動） -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center space-x-1.5">
                                    <button type="button"
                                        class="btn-view-lane font-bold text-slate-800 hover:text-indigo-600 hover:underline transition cursor-pointer text-left"
                                        data-lane="{{ $payloadJson }}"
                                        title="クリックして詳細・利用状況を表示">
                                        {{ $lane->name }}
                                    </button>
                                    @if(!$lane->is_active)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                            無効
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- 3. バッジ -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold border {{ $lane->presenter()->colorClasses()['badge'] }}">
                                    <span class="mr-1 shrink-0">{{ $lane->presenter()->iconLabel() }}</span>
                                    <span>{{ $lane->name }}</span>
                                </span>
                            </td>

                            <!-- 4. 標準役割概要 -->
                            <td class="px-4 py-3 text-slate-600 max-w-md">
                                @if($lane->description)
                                    <span class="line-clamp-2" title="{{ $lane->description }}">{{ $lane->description }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            <!-- 5. 操作 -->
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="inline-flex items-center justify-end space-x-1.5">
                                    @can('update', $lane)
                                        <button type="button"
                                            class="btn-edit-lane inline-flex items-center space-x-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                            data-lane="{{ $payloadJson }}">
                                            <span>✏️</span>
                                            <span>編集</span>
                                        </button>
                                    @endcan

                                    @can('delete', $lane)
                                        @if($inUse)
                                            <span class="inline-flex items-center space-x-1 px-2.5 py-1 bg-slate-100 text-slate-400 border border-slate-200 rounded-lg text-xs font-bold cursor-not-allowed select-none"
                                                title="運行計画（{{ $lane->timetable_lanes_count }}件）で使用中のため削除不可。不要な場合は編集から無効化してください。">
                                                <span>🔒</span>
                                                <span>使用中</span>
                                            </span>
                                        @else
                                            <button type="button"
                                                class="btn-delete-lane inline-flex items-center space-x-1 px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-xs font-bold transition cursor-pointer"
                                                data-lane="{{ $payloadJson }}">
                                                <span>🗑️</span>
                                                <span>削除</span>
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-ui.empty-state
                                    icon="🏷️"
                                    title="担当班マスターがまだ登録されていません"
                                    description="「新規担当班マスター登録」から全運行日で共通利用する担当班を作成してください。" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 1. 閲覧専用 詳細モーダル -->
    <x-ui.modal id="modal-lane-detail" title="担当班マスター詳細" icon="🏷️" max-width="lg">
        <div class="space-y-4 text-xs">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <span id="detail-lane-badge" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border bg-slate-100 text-slate-800 border-slate-300">
                        <span id="detail-lane-badge-icon" class="mr-1.5">📋</span>
                        <span id="detail-lane-badge-name">班名</span>
                    </span>
                    <span id="detail-lane-status" class="px-2 py-0.5 rounded-full text-[11px] font-bold"></span>
                </div>
                <div class="text-[11px] text-slate-500 font-mono">
                    表示順: <span id="detail-lane-order" class="font-bold text-slate-700">-</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                    <div class="text-[11px] font-bold text-slate-500">テーマカラー</div>
                    <div id="detail-lane-color" class="font-bold text-slate-800">-</div>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                    <div class="text-[11px] font-bold text-slate-500">運行計画での利用状況</div>
                    <div class="font-bold text-slate-800">
                        <span id="detail-lane-timetables-count" class="text-indigo-700">0</span> 運行日
                        ／
                        <span id="detail-lane-tasks-count" class="text-slate-700">0</span> タスク
                    </div>
                </div>
            </div>

            <div class="space-y-1">
                <div class="text-[11px] font-bold text-slate-500">標準役割概要・担当範囲</div>
                <div id="detail-lane-description" class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-slate-700 whitespace-pre-wrap leading-relaxed">-</div>
            </div>

            <div class="space-y-1.5">
                <div class="text-[11px] font-bold text-slate-500">参照している運行計画</div>
                <div id="detail-lane-timetables-list" class="space-y-1.5"></div>
            </div>
        </div>

        <x-slot:footer>
            <x-ui.button variant="secondary" data-close-target="#modal-lane-detail">閉じる</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <!-- 2. 新規登録 / 編集モーダル -->
    <x-ui.modal id="modal-lane-form" title="新規担当班マスター登録" icon="🏷️" max-width="xl">
        <div id="lane-form-usage-warning" class="hidden mb-4 p-3 bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-xl space-y-1">
            <div class="font-bold flex items-center space-x-1.5">
                <span>⚠️</span>
                <span>この担当班は <span id="lane-form-usage-count" class="font-mono">0</span> 件の運行計画で使用されています</span>
            </div>
            <p class="text-[11px] text-amber-700 leading-relaxed">
                班名・テーマカラー・アイコン・標準役割を変更すると、この担当班を編成しているすべての運行日の進行表・印刷帳票に即座に反映されます。
            </p>
        </div>

        <div id="lane-form-errors" class="hidden mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl space-y-1"></div>

        <form id="lane-master-form" action="{{ route('lanes.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <input type="hidden" id="lane-form-method" name="_method" value="POST">

            <!-- 班名 & リアルタイムプレビュー -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                <div>
                    <label for="lane-form-name" class="block font-bold text-slate-700 mb-1">
                        担当班名 <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="lane-form-name" name="name" required maxlength="100"
                        placeholder="例: 本部総務班、交通規制・警備班"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
                    <span class="text-[11px] font-bold text-slate-500">表示プレビュー</span>
                    <span id="lane-preview-badge"
                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border transition-all duration-150 {{ $colorPaletteMap['slate']['badge'] }}">
                        <span id="lane-preview-icon" class="mr-1.5">📋</span>
                        <span id="lane-preview-name">新しい担当班</span>
                    </span>
                </div>
            </div>

            <!-- テーマカラー選択 -->
            <div>
                <div class="font-bold text-slate-700 mb-1.5">テーマカラー <span class="text-red-600">*</span></div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    @foreach($colorOptions as $colorKey => $colorLabel)
                        @php $palette = $colorPaletteMap[$colorKey] ?? $colorPaletteMap['slate']; @endphp
                        <label class="lane-color-card relative flex items-center justify-between p-2 rounded-xl border cursor-pointer transition select-none hover:bg-slate-50 border-slate-200 bg-white"
                            data-color-key="{{ $colorKey }}"
                            data-badge-class="{{ $palette['badge'] }}">
                            <div class="flex items-center space-x-2 min-w-0">
                                <input type="radio" name="color" value="{{ $colorKey }}"
                                    class="lane-color-radio text-indigo-600 focus:ring-indigo-500"
                                    {{ $colorKey === 'slate' ? 'checked' : '' }}>
                                <span class="w-3 h-3 rounded-full {{ $palette['dot'] }} shrink-0 border border-black/10"></span>
                                <span class="text-[11px] font-bold text-slate-700 truncate">{{ $colorLabel }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- アイコン（絵文字）選択 -->
            <div>
                <label for="lane-form-icon" class="block font-bold text-slate-700 mb-1">
                    アイコン絵文字
                </label>
                <div class="flex items-center space-x-2 mb-2">
                    <input type="text" id="lane-form-icon" name="icon" maxlength="50" value="📋"
                        class="w-24 px-3 py-1.5 bg-white border border-slate-300 rounded-xl text-sm text-center text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <span class="text-[11px] text-slate-500">下のプリセットからワンクリック、または任意の絵文字を入力できます。</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($emojiPresets as $emoji)
                        <button type="button"
                            class="btn-emoji-preset px-2 py-1 rounded-lg bg-slate-50 hover:bg-indigo-50 border border-slate-200 hover:border-indigo-300 text-sm transition cursor-pointer"
                            data-emoji="{{ $emoji }}">
                            {{ $emoji }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- 標準役割概要 -->
            <div>
                <label for="lane-form-description" class="block font-bold text-slate-700 mb-1">
                    標準役割概要・担当エリア
                </label>
                <textarea id="lane-form-description" name="description" rows="3" maxlength="2000"
                    placeholder="全運行日で共通するこの班の基本役割や管轄エリアを入力してください"
                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>

            <!-- 有効状態 -->
            <div class="pt-1">
                <label class="inline-flex items-center space-x-2 cursor-pointer select-none">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="lane-form-is-active" name="is_active" value="1" checked
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                    <span class="font-bold text-slate-700">有効な担当班として新規運行日の候補に表示する</span>
                </label>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <x-ui.button variant="secondary" data-close-target="#modal-lane-form">キャンセル</x-ui.button>
                <x-ui.button type="submit" variant="primary" id="lane-form-submit-btn">保存する</x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <!-- 3. 削除確認モーダル -->
    <x-ui.modal id="modal-lane-delete" title="担当班マスターの削除確認" icon="🗑️" max-width="md">
        <form id="lane-delete-form" action="" method="POST" class="space-y-4 text-xs">
            @csrf
            @method('DELETE')
            <p class="text-slate-700 leading-relaxed">
                担当班マスター「<strong id="delete-lane-name" class="text-slate-900"></strong>」を削除します。この操作は取り消せません。よろしいですか？
            </p>
            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <x-ui.button variant="secondary" data-close-target="#modal-lane-delete">キャンセル</x-ui.button>
                <button type="submit"
                    class="inline-flex items-center space-x-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold shadow-xs transition cursor-pointer">
                    <span>🗑️</span>
                    <span>削除する</span>
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>

@vite(['resources/js/lanes.js'])
</x-layouts.app>
