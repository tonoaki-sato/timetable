@props([
    'unit',
    'emptyText' => '',
    'multiline' => false,
    'showAffiliation' => false,
])
@php
    $items = $unit->presenter()->pdfStaffItems();
@endphp
@if($items === [])
{{ $emptyText }}@elseif($multiline)
@foreach($items as $item)<div class="standalone-staff-item{{ $item['mark'] !== '' ? ' has-mark' : '' }}">@if($item['mark'] !== '')<span class="pdf-symbol pdf-symbol-prefix">{{ $item['mark'] }}</span>@endif<span class="standalone-staff-name">{{ $item['name'] }}</span>@if($showAffiliation && $item['affiliation'] !== '')<span class="standalone-staff-affiliation">（{{ $item['affiliation'] }}）</span>@endif</div>@endforeach
@else
@foreach($items as $item){{ $loop->first ? '' : ', ' }}@if($item['mark'] !== '')<span class="pdf-symbol pdf-symbol-prefix">{{ $item['mark'] }}</span>@endif{{ $item['name'] }}@endforeach
@endif