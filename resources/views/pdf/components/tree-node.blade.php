@props([
    'unit',
    'level' => 1,
])

@php
    $branchUnits = $unit->children ? $unit->children->filter(fn ($child) => $child->children && $child->children->isNotEmpty()) : collect();
    $leafUnits = $unit->children ? $unit->children->filter(fn ($child) => ! $child->children || $child->children->isEmpty()) : collect();

    $hasLeafGroup = $leafUnits->isNotEmpty();
    $totalChildrenCount = $branchUnits->count() + ($hasLeafGroup ? 1 : 0);
@endphp

@if($totalChildrenCount === 0)
    <div class="card card-level-{{ min($level, 3) }}">
        <div class="card-title">{{ $unit->name }}</div>
        @if($unit->staff->isNotEmpty())
            <div class="card-staff">{{ $unit->staffNamesDisplay }}</div>
        @endif
        @if($unit->short_description)
            <div class="card-desc">{{ $unit->short_description }}</div>
        @endif
    </div>
@else
    <table class="node-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="node-card-td">
                <div class="card card-level-{{ min($level, 3) }}">
                    <div class="card-title">{{ $unit->name }}</div>
                    @if($unit->staff->isNotEmpty())
                        <div class="card-staff">{{ $unit->staffNamesDisplay }}</div>
                    @endif
                    @if($unit->short_description)
                        <div class="card-desc">{{ $unit->short_description }}</div>
                    @endif
                </div>
            </td>
            <td class="bridge-td">
                <div class="stem-bridge"></div>
            </td>
            <td class="children-td">
                <table class="children-table" cellpadding="0" cellspacing="0">
                    @php $childIndex = 0; @endphp
                    @foreach($branchUnits as $branch)
                        @php
                            $isFirst = ($childIndex === 0);
                            $isLast = ($childIndex === $totalChildrenCount - 1);
                            $childIndex++;
                        @endphp
                        <tr>
                            <td class="connector-td">
                                @if($totalChildrenCount === 1)
                                    <div class="connector-line-direct"></div>
                                @else
                                    @if(! $isFirst)
                                        <div class="connector-vertical-top"></div>
                                    @endif
                                    @if(! $isLast)
                                        <div class="connector-vertical-bottom"></div>
                                    @endif
                                    <div class="connector-horizontal"></div>
                                @endif
                            </td>
                            <td class="child-content-td">
                                @include('pdf.components.tree-node', ['unit' => $branch, 'level' => $level + 1])
                            </td>
                        </tr>
                    @endforeach

                    @if($hasLeafGroup)
                        @php
                            $isFirst = ($childIndex === 0);
                            $isLast = true;
                        @endphp
                        <tr>
                            <td class="connector-td">
                                @if($totalChildrenCount === 1)
                                    <div class="connector-line-direct"></div>
                                @else
                                    @if(! $isFirst)
                                        <div class="connector-vertical-top"></div>
                                    @endif
                                    <div class="connector-horizontal"></div>
                                @endif
                            </td>
                            <td class="child-content-td">
                                <div class="leaf-group">
                                    @foreach($leafUnits as $leaf)
                                        <div class="leaf-card">
                                            <div class="leaf-title">{{ $leaf->name }}</div>
                                            @if($leaf->staff->isNotEmpty())
                                                <div class="leaf-staff">{{ $leaf->staffNamesDisplay }}</div>
                                            @endif
                                            @if($leaf->short_description)
                                                <div class="leaf-desc">{{ $leaf->short_description }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>
@endif

