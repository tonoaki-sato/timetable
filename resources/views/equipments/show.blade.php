<x-layouts.app :title="$equipment->name . ' - 資材マスター詳細'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

        <!-- ヘッダーエリア -->
        <x-page-header
            :title="$equipment->name"
            :icon="$equipment->isCommittee() ? '🏛️' : ($equipment->isRental() ? '🏢' : '🎒')"
            :description="$equipment->isRental()
            ? '提携イベント会社レンタル機材（台帳基本情報・仕様管理）'
            : ($equipment->isPersonal()
                ? '個人持ち込み私物（台帳基本情報・定常保管: ' . ($equipment->storage_location ?: '自宅') . '）'
                : '実行委員会保有備品（台帳基本情報・定常保管: ' . ($equipment->storage_location ?: '番所地下倉庫') . '）')">
            <x-slot:breadcrumbs>
                <a href="{{ route('equipments.index') }}" class="hover:text-indigo-600 transition shrink-0">資材管理</a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-800 font-bold truncate">{{ $equipment->name }}</span>
            </x-slot:breadcrumbs>

            <x-slot:badges>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold font-mono {{ $equipment->isCommittee() ? 'bg-emerald-100 text-emerald-800' : ($equipment->isRental() ? 'bg-sky-100 text-sky-800' : 'bg-amber-100 text-amber-800') }}">
                    {{ $equipment->formatted_quantity }}
                </span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $equipment->isCommittee() ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($equipment->isRental() ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                    {{ $equipment->type_label }}
                </span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold
                @if($equipment->checkout_rule === 'permission_required') bg-rose-50 text-rose-700 border border-rose-200
                @elseif($equipment->checkout_rule === 'leader_only') bg-purple-50 text-purple-700 border border-purple-200
                @else bg-slate-50 text-slate-600 border border-slate-200 @endif">
                    {{ $equipment->checkout_rule_label }}
                </span>
                @if($equipment->lane)
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                    {{ $equipment->lane->icon }} {{ $equipment->lane->name }}
                </span>
                @endif
            </x-slot:badges>

            <x-slot:actions>
                <a href="{{ route('logistics.equipments.show', $equipment) }}"
                    class="inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                    <span>🚚</span>
                    <span>搬出入計画を確認</span>
                </a>
                <a href="{{ route('equipments.edit', $equipment) }}"
                    class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition">
                    <span>✏️</span>
                    <span>基本情報編集</span>
                </a>
                @can('delete', $equipment)
                <form method="POST" action="{{ route('equipments.destroy', $equipment) }}" class="inline"
                    data-confirm="資材「{{ $equipment->name }}」を削除しますか？&#10;紐づくすべての搬出入計画ステップも削除されます。">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-rose-50 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold shadow-2xs transition cursor-pointer">
                        <span>🗑️</span>
                        <span>資材削除</span>
                    </button>
                </form>
                @endcan
                <a href="{{ route('equipments.index') }}"
                    class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold shadow-2xs transition">
                    <span>←</span>
                    <span>資材一覧</span>
                </a>
            </x-slot:actions>
        </x-page-header>

        <!-- 1. 基本台帳スペックカード -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900 flex items-center space-x-2">
                    <span>📦</span>
                    <span>資材基本台帳情報</span>
                </h2>
                <span class="text-xs text-slate-500 font-mono">ID: #{{ $equipment->id }}</span>
            </div>

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    <!-- 資材名 -->
                    <div class="space-y-1">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">資材名</span>
                        <span class="text-base font-bold text-slate-900 block">{{ $equipment->name }}</span>
                    </div>

                    <!-- 所有区分 -->
                    <div class="space-y-1">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">所有・調達区分</span>
                        <div>
                            @if($equipment->isCommittee())
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800">
                                🏛️ 実行委員会 自前保有
                            </span>
                            @elseif($equipment->isRental())
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-sky-100 text-sky-800">
                                🏢 提携イベント会社レンタル
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-amber-100 text-amber-800">
                                🎒 個人持ち込み私物
                            </span>
                            @endif
                        </div>
                    </div>

                    <!-- 保有数量 -->
                    <div class="space-y-1">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">総数量</span>
                        <div class="flex items-center space-x-2">
                            <span class="text-xl font-bold font-mono text-slate-900">{{ $equipment->formatted_quantity }}</span>
                            @if($equipment->is_approximate_quantity)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">概数</span>
                            @endif
                        </div>
                    </div>

                    <!-- 定常保管場所 -->
                    <div class="space-y-1">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">定常保管場所</span>
                        <div class="text-sm font-medium text-slate-800 flex items-center space-x-1.5">
                            <span class="text-slate-400">📍</span>
                            <span>
                                @if($equipment->isRental())
                                イベント会社手配（事前搬入）
                                @else
                                {{ $equipment->storage_location ?: ($equipment->isPersonal() ? '自宅（個人保管）' : '番所地下倉庫') }}
                                @endif
                            </span>
                        </div>
                        @if($equipment->storage_detail)
                        <div class="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded px-2 py-1 mt-1">
                            <span class="font-bold text-slate-600">定位置詳細:</span> {{ $equipment->storage_detail }}
                        </div>
                        @endif
                    </div>

                    <!-- 主担当班 -->
                    <div class="space-y-1">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">主担当班（所管）</span>
                        <div>
                            @if($equipment->lane)
                            <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-800 border border-slate-200">
                                <span>{{ $equipment->lane->icon }}</span>
                                <span class="font-bold">{{ $equipment->lane->name }}</span>
                            </span>
                            @else
                            <span class="text-xs text-slate-400">指定なし（全体共通）</span>
                            @endif
                        </div>
                    </div>

                    <!-- 持出ルール -->
                    <div class="space-y-1">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">持出・使用管理ルール</span>
                        <div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold
                            @if($equipment->checkout_rule === 'permission_required') bg-rose-100 text-rose-800 border border-rose-200
                            @elseif($equipment->checkout_rule === 'leader_only') bg-purple-100 text-purple-800 border border-purple-200
                            @else bg-slate-100 text-slate-700 border border-slate-200 @endif">
                                {{ $equipment->checkout_rule_label }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 仕様・備考 -->
                @if($equipment->description)
                <div class="border-t border-slate-100 pt-4 space-y-1">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">仕様メモ・特記事項</span>
                    <p class="text-xs text-slate-700 leading-relaxed bg-slate-50 p-3 rounded-xl border border-slate-200 whitespace-pre-wrap">{{ $equipment->description }}</p>
                </div>
                @endif

                <!-- メタデータ（登録日時・更新日時） -->
                <div class="border-t border-slate-100 pt-4 flex flex-wrap items-center justify-between text-[11px] text-slate-400 gap-2">
                    <div>
                        @if($equipment->creator)
                        <span>登録者: <strong class="text-slate-600 font-medium">{{ $equipment->creator->name }}</strong></span>
                        <span class="mx-1.5">•</span>
                        @endif
                        <span>登録日: {{ $equipment->created_at?->format('Y/m/d H:i') ?? '-' }}</span>
                    </div>
                    <div>
                        <span>最終更新: {{ $equipment->updated_at?->format('Y/m/d H:i') ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. 搬出入計画 連携ハイライトカード -->
        <div class="bg-gradient-to-br from-indigo-50/70 via-white to-slate-50 rounded-2xl border border-indigo-200 shadow-xs p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-indigo-950 flex items-center space-x-2">
                        <span>🚚</span>
                        <span>搬出入計画・現場配備状況</span>
                    </h3>
                    <p class="text-xs text-indigo-800/80 mt-0.5">
                        宿場まつり運行（前日〜当日〜撤収）における倉庫出し・中継・現場配備・夜間保管スケジュール
                    </p>
                </div>
                <a href="{{ route('logistics.equipments.show', $equipment) }}"
                    class="inline-flex items-center space-x-1.5 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition shrink-0 self-start sm:self-auto cursor-pointer">
                    <span>🚚</span>
                    <span>搬出入計画を編集・確認する</span>
                    <span>➔</span>
                </a>
            </div>

            @php
            $plansCount = $equipment->plans->count();
            $completedCount = $equipment->plans->where('status', 'completed')->count();
            @endphp

            @if($plansCount > 0)
            <div class="bg-white rounded-xl p-4 border border-indigo-100 space-y-3">
                <div class="flex items-center justify-between flex-wrap gap-2 text-xs">
                    <div class="flex items-center space-x-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800">
                            {{ $plansCount }} ステップ設定済
                        </span>
                        <span class="text-slate-500 font-mono text-[11px]">
                            完了進捗: {{ $completedCount }} / {{ $plansCount }}
                        </span>
                    </div>
                    <span class="text-[11px] text-slate-400">
                        ステップの追加・時間変更・ステータス更新は搬出入計画画面から行えます
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 pt-1">
                    @foreach($equipment->plans as $plan)
                    <div class="p-2.5 rounded-lg border {{ $plan->isCompleted() ? 'border-emerald-200 bg-emerald-50/20' : 'border-slate-200 bg-slate-50/50' }} text-xs space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold
                                    @if($plan->day_phase === 'pre_day') bg-purple-100 text-purple-800
                                    @elseif($plan->day_phase === 'day1_morning') bg-sky-100 text-sky-800
                                    @elseif($plan->day_phase === 'day1_evening') bg-indigo-100 text-indigo-800
                                    @elseif($plan->day_phase === 'day2_morning') bg-teal-100 text-teal-800
                                    @elseif($plan->day_phase === 'day2_evening') bg-amber-100 text-amber-800
                                    @else bg-slate-100 text-slate-700 @endif">
                                {{ $plan->phase_label }}
                            </span>
                            <span class="text-[10px] font-bold {{ $plan->isCompleted() ? 'text-emerald-700' : 'text-slate-500' }}">
                                {{ $plan->status_label }}
                            </span>
                        </div>
                        <div class="font-bold text-slate-800 truncate">{{ $plan->title }}</div>
                        <div class="text-[11px] text-slate-500 truncate flex items-center space-x-1">
                            <span>{{ $plan->resolved_from_location }}</span>
                            <span class="text-indigo-400">➔</span>
                            <span>{{ $plan->resolved_to_location }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <div class="bg-white/80 rounded-xl p-5 border border-dashed border-indigo-200 text-center space-y-2">
                <span class="text-2xl block">📋</span>
                <p class="text-xs font-bold text-slate-700">搬出入計画ステップがまだ設定されていません</p>
                <p class="text-[11px] text-slate-500 max-w-md mx-auto">
                    宿場まつり運行に向け、前日の倉庫出し、会場設置、夜間保管、撤収格納のステップを設定してください。パターンテンプレートからの一括適用も可能です。
                </p>
                <div class="pt-1">
                    <a href="{{ route('logistics.equipments.show', $equipment) }}"
                        class="inline-block px-3.5 py-1.5 rounded-lg text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition">
                        ⚡ パターン適用・ステップ設定を開く
                    </a>
                </div>
            </div>
            @endif
        </div>

    </div>
</x-layouts.app>