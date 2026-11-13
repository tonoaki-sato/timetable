@props([
    'unit',
    'level' => 1,
    'isPrint' => false,
])

@php
    // 子を持つ非末端組織（ブランチ）と、子を持たない末端組織（葉ノード）に分類
    $branchUnits = $unit->children ? $unit->children->filter(fn ($child) => $child->children && $child->children->isNotEmpty()) : collect();
    $leafUnits = $unit->children ? $unit->children->filter(fn ($child) => ! $child->children || $child->children->isEmpty()) : collect();

    // 子要素の全アイテムリスト（ブランチノード群 + 末端グループ[もしあれば]）
    $hasLeafGroup = $leafUnits->isNotEmpty();
    $totalChildrenCount = $branchUnits->count() + ($hasLeafGroup ? 1 : 0);
@endphp

<div class="tree-node-branch flex items-start relative">
    <!-- ノードカード本体 -->
    <div class="org-node-card shrink-0 p-2.5 rounded-xl border transition relative z-10
        {{ $level === 1 ? ($isPrint ? 'border-slate-800 bg-slate-100' : 'border-slate-300 bg-emerald-50/70 hover:border-emerald-500 hover:shadow-md') : '' }}
        {{ $level === 2 ? ($isPrint ? 'border-slate-600 bg-white' : 'border-slate-200 bg-white hover:border-indigo-400 hover:shadow-md') : '' }}
        {{ $level >= 3 ? ($isPrint ? 'border-slate-400 bg-slate-50' : 'border-slate-200 bg-slate-50/80 hover:bg-white hover:border-indigo-400 hover:shadow-xs') : '' }}
        w-44 sm:w-48 {{ $isPrint ? '' : 'cursor-pointer' }}"
        @if(!$isPrint) data-open-detail-modal="{{ $unit->id }}" @endif>
        
        <div class="font-bold {{ $level === 1 ? 'text-xs text-emerald-950' : 'text-xs text-slate-800' }} truncate">
            {{ $unit->name }}
        </div>
        @if($unit->staff->isNotEmpty())
            <div class="text-[11px] {{ $level === 1 ? 'text-emerald-800' : 'text-indigo-600' }} mt-0.5 truncate">
                {{ $unit->staffNamesDisplay }}
            </div>
        @endif
        @if($unit->short_description)
            <div class="text-[10px] text-slate-500 mt-0.5 truncate">
                {{ $unit->short_description }}
            </div>
        @endif
    </div>

    <!-- 子ノード群がある場合：右側へ展開 -->
    @if($totalChildrenCount > 0)
        <!-- 親幹ブリッジ線: 親カードの右端中央から子ツリーの分岐点へ接続 -->
        <div class="tree-stem-bridge shrink-0 w-5 h-0.5 mt-5 {{ $isPrint ? 'bg-slate-500' : 'bg-slate-300' }}"></div>

        <!-- 子ツリーコンテナ -->
        <div class="tree-children-container flex flex-col flex-1 min-w-[200px]">
            @php $childIndex = 0; @endphp

            <!-- 1. 非末端組織群 (ブランチ) -->
            @foreach($branchUnits as $branch)
                @php
                    $isFirst = ($childIndex === 0);
                    $isLast = ($childIndex === $totalChildrenCount - 1);
                    $childIndex++;
                @endphp
                <div class="tree-child-row flex items-start relative {{ ! $isLast ? 'pb-3' : '' }}">
                    <!-- 分岐コネクタ（左側の縦線＋水平接続線） -->
                    <div class="tree-branch-connector shrink-0 w-5 relative self-stretch">
                        @if($totalChildrenCount === 1)
                            <!-- 単一子: 水平直線のみ -->
                            <div class="tree-connector-direct absolute left-0 top-5 w-5 h-0.5 {{ $isPrint ? 'bg-slate-500' : 'bg-slate-300' }}"></div>
                        @else
                            <!-- 複数子: 位置に応じたエルボー/T字コネクタ -->
                            <!-- 上半分縦線: 先頭以外で描画 -->
                            @if(! $isFirst)
                                <div class="tree-connector-line-top absolute left-0 top-0 w-0.5 h-5 {{ $isPrint ? 'bg-slate-500' : 'bg-slate-300' }}"></div>
                            @endif

                            <!-- 下半分縦線: 末尾以外で描画（末尾ノードの下にはみ出さない） -->
                            @if(! $isLast)
                                <div class="tree-connector-line-bottom absolute left-0 top-5 bottom-0 w-0.5 {{ $isPrint ? 'bg-slate-500' : 'bg-slate-300' }}"></div>
                            @endif

                            <!-- 水平接続線 (カードへ入る線) -->
                            <div class="tree-connector-horizontal absolute left-0 top-5 w-5 h-0.5 {{ $isPrint ? 'bg-slate-500' : 'bg-slate-300' }} {{ $isFirst ? 'rounded-tl-sm' : '' }} {{ $isLast ? 'rounded-bl-sm' : '' }}"></div>
                        @endif
                    </div>

                    <!-- 子ノード再帰描画 -->
                    <div class="flex-1">
                        @include('organizations.components.tree-node', ['unit' => $branch, 'level' => $level + 1, 'isPrint' => $isPrint])
                    </div>
                </div>
            @endforeach

            <!-- 2. 末端組織群 (子を持たない葉ノードまとめ枠) -->
            @if($hasLeafGroup)
                @php
                    $isFirst = ($childIndex === 0);
                    $isLast = true; // 末端グループは必ず最後
                @endphp
                <div class="tree-child-row flex items-start relative">
                    <!-- 分岐コネクタ（グループ枠へ入る線） -->
                    <div class="tree-branch-connector shrink-0 w-5 relative self-stretch">
                        @if($totalChildrenCount === 1)
                            <!-- 単一グループ: 水平直線のみ -->
                            <div class="tree-connector-direct absolute left-0 top-5 w-5 h-0.5 {{ $isPrint ? 'bg-slate-500' : 'bg-slate-300' }}"></div>
                        @else
                            <!-- 上半分縦線 -->
                            @if(! $isFirst)
                                <div class="tree-connector-line-top absolute left-0 top-0 w-0.5 h-5 {{ $isPrint ? 'bg-slate-500' : 'bg-slate-300' }}"></div>
                            @endif
                            <!-- 水平接続線 (末尾エルボー) -->
                            <div class="tree-connector-horizontal absolute left-0 top-5 w-5 h-0.5 rounded-bl-sm {{ $isPrint ? 'bg-slate-500' : 'bg-slate-300' }}"></div>
                        @endif
                    </div>

                    <!-- 末端組織グループまとめ枠 -->
                    <div class="tree-leaf-group flex-1 flex flex-wrap gap-2.5 items-start p-2.5 rounded-xl {{ $isPrint ? 'bg-slate-50 border border-slate-400' : 'bg-slate-50/70 border border-dashed border-slate-300' }}">
                        @foreach($leafUnits as $leaf)
                            <div class="org-node-card shrink-0 p-2 rounded-lg border {{ $isPrint ? 'border-slate-400 bg-white' : 'border-slate-200 bg-white hover:border-indigo-400 hover:shadow-xs' }} min-w-28 max-w-44 transition {{ $isPrint ? '' : 'cursor-pointer' }}"
                                @if(!$isPrint) data-open-detail-modal="{{ $leaf->id }}" @endif>
                                <div class="font-bold text-[11px] text-slate-800 truncate" title="{{ $leaf->name }}">{{ $leaf->name }}</div>
                                @if($leaf->staff->isNotEmpty())
                                    <div class="text-[10px] text-indigo-600 mt-0.5 truncate" title="{{ $leaf->staffNamesDisplay }}">{{ $leaf->staffNamesDisplay }}</div>
                                @endif
                                @if($leaf->short_description)
                                    <div class="text-[9px] text-slate-500 mt-0.5 truncate" title="{{ $leaf->short_description }}">{{ $leaf->short_description }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
