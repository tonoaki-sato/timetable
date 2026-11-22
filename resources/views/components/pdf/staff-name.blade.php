@props([
    'staff',
])
@php
    $mark = $staff->presenter()->pdfMark();
@endphp
@if($mark !== '')<span class="pdf-symbol pdf-symbol-prefix">{{ $mark }}</span>@endif{{ $staff->name }}