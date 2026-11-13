@props([
    'unit',
    'level' => 1,
    'isPrint' => false,
])

@php
    // 子を持つ非末端組織（ブランチ）と、子を持たない末端組織（葉ノード）に分類
    $branchUnits = $unit->children ? $unit->children->filter(fn ($child) => $child->children && $child->children->isNotEmpty()) : collect();
    $leafUnits = $unit->children ? $unit->children->filter(fn ($child) => ! $child->children || $child->children->isEmpty()) : collect();
@endphp

<div class="tree-node-branch flex items-start space-x-4 relative">
    <!-- 親からの水平接続線 (レベル2以降) -->
    @if($level > 1)
        <div class="absolute -left-4 top-4 w-4 h-0.5 {{ $isPrint ? 'bg-slate-400' : 'bg-slate-300' }}"></div>
    @endif

    <!-- ノードカード本体 -->
    <div class="org-node-card shrink-0 p-2.5 rounded-xl border transition 
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
    @if($unit->children && $unit->children->isNotEmpty())
        <div class="tree-children-connector relative pl-4 border-l-2 {{ $isPrint ? 'border-slate-400' : 'border-slate-300' }} flex flex-col space-y-3 flex-1 min-w-[200px]">
            <!-- 1. 非末端組織群 (子を持つブランチ: 1つずつ縦に並び、右へさらに展開) -->
            @foreach($branchUnits as $branch)
                @include('organizations.components.tree-node', ['unit' => $branch, 'level' => $level + 1, 'isPrint' => $isPrint])
            @endforeach

            <!-- 2. 末端組織群 (子を持たない葉ノード: 横並び＋折り返しでグループ化して表示) -->
            @if($leafUnits->isNotEmpty())
                <div class="tree-leaf-group relative flex flex-wrap gap-2.5 items-start p-2.5 rounded-xl {{ $isPrint ? 'bg-slate-50 border border-slate-300' : 'bg-slate-50/70 border border-dashed border-slate-200' }}">
                    <!-- グループへの水平接続線 -->
                    <div class="absolute -left-4 top-4 w-4 h-0.5 {{ $isPrint ? 'bg-slate-400' : 'bg-slate-300' }}"></div>

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
            @endif
        </div>
    @endif
</div>

