@if(isset($selfRegistrationSetting) && isset($selfRegistrationStatus))
<div class="{{ trim(($wrapperClass ?? '') . ' p-2.5 bg-slate-800/90 border border-slate-700/70 rounded-xl space-y-2') }}">
    <div class="flex items-center justify-between">
        <span class="text-xs font-bold text-slate-200 flex items-center space-x-1.5">
            <span>🌐</span>
            <span>{{ $cardTitle ?? 'Webセルフ登録受付' }}</span>
        </span>
        @if($selfRegistrationStatus['status'] === 'open')
        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 flex items-center space-x-1">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>受付中</span>
        </span>
        @elseif($selfRegistrationStatus['status'] === 'upcoming')
        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-500/20 text-amber-300 border border-amber-400/30">
            受付前(予約)
        </span>
        @elseif($selfRegistrationStatus['status'] === 'closed')
        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-600/30 text-slate-300 border border-slate-500/30">
            受付終了
        </span>
        @else
        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-500/20 text-red-300 border border-red-400/30">
            停止中(手動)
        </span>
        @endif
    </div>

    <div class="flex items-center space-x-1.5 pt-1">
        @if($selfRegistrationStatus['status'] === 'closed')
        <button type="button" data-toggle-target="#registrationSettingModal"
            class="w-full py-1 px-2 bg-slate-700 hover:bg-slate-600 text-slate-200 border border-slate-600 rounded-lg text-[10px] font-bold transition flex items-center justify-center space-x-1 cursor-pointer">
            <span>⚙️</span>
            <span>受付期間を再設定</span>
        </button>
        @else
        <form action="{{ route('feature-settings.toggle', 'staff_self_registration') }}" method="POST" class="flex-1">
            @csrf
            @method('PATCH')
            @if($selfRegistrationSetting->is_enabled)
            <button type="submit" class="w-full py-1 px-2 bg-red-500/20 hover:bg-red-500/30 text-red-300 border border-red-500/40 rounded-lg text-[10px] font-bold transition flex items-center justify-center space-x-1 cursor-pointer">
                <span>⏹️</span>
                <span>{{ $selfRegistrationStatus['status'] === 'upcoming' ? '受付予約を停止' : '即時停止' }}</span>
            </button>
            @else
            <button type="submit" class="w-full py-1 px-2 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/40 rounded-lg text-[10px] font-bold transition flex items-center justify-center space-x-1 cursor-pointer">
                <span>▶️</span>
                <span>即時再開</span>
            </button>
            @endif
        </form>

        <button type="button" data-toggle-target="#registrationSettingModal"
            class="py-1 px-2 bg-slate-700 hover:bg-slate-600 text-slate-200 border border-slate-600 rounded-lg text-[10px] font-bold transition flex items-center space-x-1 cursor-pointer whitespace-nowrap">
            <span>⚙️</span>
            <span>詳細設定</span>
        </button>
        @endif
    </div>
</div>
@endif
