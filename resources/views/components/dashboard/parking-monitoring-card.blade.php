@props(['context'])

@php
    $cardBorderClass = match (true) {
        $context->hasViolations() => 'border-red-300 ring-1 ring-red-200',
        $context->hasWarnings() => 'border-amber-300 ring-1 ring-amber-200',
        default => 'border-slate-200 shadow-sm',
    };
@endphp

<div class="bg-white rounded-xl border {{ $cardBorderClass }} p-5 space-y-3 transition duration-150">
    <!-- ヘッダー -->
    <div class="flex items-center justify-between gap-2">
        <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-1.5 truncate">
            <span>🅿️</span>
            <span>駐車場利用時間制約監視</span>
            <span class="text-[10px] font-mono text-slate-400 font-normal">F-405</span>
        </h3>
        <span class="text-[10px] px-2 py-0.5 rounded-full whitespace-nowrap {{ $context->summaryBadgeClasses() }}">
            {{ $context->summaryBadgeText() }}
        </span>
    </div>

    <!-- 監視拠点リスト -->
    <div class="space-y-2.5 text-xs">
        @forelse($context->locations as $locStatus)
            <div class="p-3 rounded-xl border {{ $locStatus->status->containerClasses() }} transition flex flex-col gap-1.5">
                <!-- 拠点タイトル行 -->
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="flex items-center space-x-1.5">
                            <span class="text-sm shrink-0">{{ $locStatus->status->icon() }}</span>
                            <span class="font-bold text-slate-900 truncate">{{ $locStatus->location->name }}</span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-200/70 text-slate-600 shrink-0 font-medium">
                                {{ $locStatus->location->type_label }}
                            </span>
                        </div>
                        <div class="text-[10px] text-slate-500 font-mono mt-0.5">
                            {{ $locStatus->periodLabel }}
                        </div>
                    </div>
                    <span class="text-[10px] px-2 py-0.5 rounded shrink-0 {{ $locStatus->status->badgeClasses() }}">
                        {{ $locStatus->statusDetail }}
                    </span>
                </div>

                <!-- サブ情報（カウントダウン & 保管機材サマリー） -->
                <div class="flex flex-wrap items-center justify-between text-[11px] text-slate-600 gap-1 pt-0.5 border-t border-slate-200/60 font-mono">
                    <span class="truncate">
                        @if($locStatus->storedEquipmentCount > 0)
                            保管機材: <strong class="text-slate-800">{{ $locStatus->storedEquipmentCount }}品目</strong>
                        @else
                            <span class="text-slate-400">機材滞在なし</span>
                        @endif
                    </span>
                    @if($locStatus->countdownText)
                        <span class="font-bold {{ $locStatus->status->isAlert() ? 'text-red-700' : 'text-slate-500' }}">
                            {{ $locStatus->countdownText }}
                        </span>
                    @endif
                </div>

                <!-- 違反メッセージ -->
                @if($locStatus->hasViolations())
                    <div class="p-2 rounded-lg bg-red-100/90 border border-red-200 text-red-800 text-[11px] space-y-0.5 leading-snug">
                        @foreach($locStatus->violations as $violation)
                            <div class="flex items-start space-x-1">
                                <span class="shrink-0 text-red-600">🚨</span>
                                <span>{{ $violation }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- 警告メッセージ -->
                @if($locStatus->hasWarnings())
                    <div class="p-2 rounded-lg bg-amber-100/90 border border-amber-200 text-amber-900 text-[11px] space-y-0.5 leading-snug">
                        @foreach($locStatus->warnings as $warning)
                            <div class="flex items-start space-x-1">
                                <span class="shrink-0 text-amber-600">⚠️</span>
                                <span>{{ $warning }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-slate-400 text-center text-xs">
                監視対象の貸切駐車場が登録されていません。
            </div>
        @endforelse
    </div>

    <!-- フッターリンク -->
    <div class="pt-1 flex items-center justify-end">
        <a href="{{ route('equipments.index', ['tab' => 'locations']) }}"
           class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 flex items-center space-x-1 group">
            <span>拠点別保管 & 貸切スケジュール</span>
            <span class="transition group-hover:translate-x-0.5">→</span>
        </a>
    </div>
</div>

