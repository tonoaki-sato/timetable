@props([
    'unit',
    'emptyText' => '',
])
@php
    $items = $unit->presenter()->pdfStaffItems();
@endphp
@if($items === [])
{{ $emptyText }}@else
@foreach($items as $item){{ $loop->first ? '' : ', ' }}@if($item['mark'] !== '')<span class="pdf-symbol pdf-symbol-prefix">{{ $item['mark'] }}</span>@endif{{ $item['name'] }}@endforeach
@endif