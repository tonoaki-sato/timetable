@props([
    'title' => '入力内容をご確認ください',
])

@if($errors->any())
    <div {{ $attributes->merge(['class' => 'p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl space-y-1 shadow-2xs']) }} role="alert">
        <div class="font-bold flex items-center space-x-1.5 text-red-800">
            <span>⚠️</span>
            <span>{{ $title }}</span>
        </div>
        <ul class="list-disc list-inside text-[11px] pl-2 space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
