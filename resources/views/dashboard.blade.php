<x-layouts.app title="本部運行ダッシュボード">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- 1. 最上部: 本部運行サマリーヘッダー -->
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 rounded-2xl p-6 text-white shadow-lg border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <span class="px-3 py-1 bg-indigo-500/20 text-indigo-300 border border-indigo-400/30 rounded-full text-xs font-semibold tracking-wide">
                    2026年度 まつり本番運行
                </span>
                <span class="flex items-center space-x-1.5 text-xs text-emerald-400 font-mono">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>システム正常稼働中</span>
                </span>
            </div>
            <h2 class="text-2xl font-bold tracking-tight">本部運行総合ダッシュボード</h2>
            <p class="text-sm text-slate-300 mt-1">
                ログイン中: <strong class="text-white">{{ Auth::user()->name ?? '本部スタッフ' }}</strong>
                ({{ Auth::user()?->isAdmin() ? '管理者' : 'オペレーター' }})
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="bg-slate-800/80 backdrop-blur px-4 py-2 rounded-xl border border-slate-700/80 text-right">
                <div class="text-[11px] text-slate-400 font-mono">現在日時</div>
                <div class="text-lg font-bold font-mono text-white">{{ $nowDatetime ?? '10月17日 (土) 10:25' }}</div>
            </div>
            <a href="{{ route('timetable.index') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center space-x-1.5">
                <span>📅 タイムテーブル全体マトリクスへ</span>
            </a>
        </div>
    </div>

    <!-- 2. 主要KPIステータスカード群 (4カラム) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- フェーズ -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500">運行フェーズ</p>
                <h3 class="text-xl font-bold text-slate-900 mt-1">1日目 (Day 1)</h3>
                <span class="text-[11px] text-emerald-600 font-medium">午前セッション進行中</span>
            </div>
            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-2xl">
                🏮
            </div>
        </div>

        <!-- 道路交通規制 -->
        <div class="bg-white p-5 rounded-xl border border-red-200 shadow-xs flex items-center justify-between relative overflow-hidden">
            <div class="absolute top-0 right-0 w-2 h-full bg-red-500"></div>
            <div>
                <p class="text-xs font-medium text-red-600 font-bold">🚨 道路交通規制中</p>
                <h3 class="text-xl font-bold text-slate-900 mt-1">車両通行止め</h3>
                <span class="text-[11px] text-slate-500 font-mono">10:00 〜 17:30 (解除まで7h)</span>
            </div>
            <div class="w-12 h-12 bg-red-50 text-red-600 rounded-xl flex items-center justify-center text-2xl">
                ⛔
            </div>
        </div>

        <!-- 人員アサイン充足率 -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500">現場人員充足状況</p>
                <div class="flex items-baseline space-x-1.5 mt-1">
                    <h3 class="text-xl font-bold text-slate-900">{{ $assignedPeopleCount ?? 38 }}</h3>
                    <span class="text-xs text-slate-500">/ {{ $requiredPeopleCount ?? 40 }}名配置済</span>
                </div>
                <span class="text-[11px] text-red-600 font-bold">⚠️ 2名不足（要アサイン）</span>
            </div>
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-2xl">
                👥
            </div>
        </div>

        <!-- 現場機材要請 -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500">現場機材調整要請</p>
                <h3 class="text-xl font-bold text-slate-900 mt-1">{{ count($equipmentRequests ?? [1]) }} 件 <span class="text-xs text-amber-600 font-bold">(対応中)</span></h3>
                <span class="text-[11px] text-slate-500">予備コーン残: 18本</span>
            </div>
            <div class="w-12 h-12 bg-cyan-50 text-cyan-600 rounded-xl flex items-center justify-center text-2xl">
                🔺
            </div>
        </div>
    </div>

    <!-- 3. メイングリッド (2カラム: Now & Next / アラート・要請) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- 左側2カラム: Now & Next ビュー (F-106) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Now: 現在進行中のタスク -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <div class="flex items-center space-x-2">
                        <span class="flex h-2.5 w-2.5 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                        </span>
                        <h3 class="text-base font-bold text-slate-900">⚡ NOW : 現在進行中のタスク</h3>
                    </div>
                    <span class="text-xs text-slate-500 font-mono">10:00 〜 10:45 スロット</span>
                </div>

                <div class="space-y-3">
                    @forelse($nowTasks ?? [] as $task)
                    <div class="p-3.5 {{ $task['is_critical'] ?? false ? 'bg-red-50/60 border-red-200' : 'bg-white border-slate-200' }} rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2">
                                @if($task['is_critical'] ?? false)
                                <span class="px-2 py-0.5 bg-red-600 text-white font-bold text-[10px] rounded">🚨 最優先設置</span>
                                @endif
                                @if($task['requires_review'] ?? false)
                                <span class="px-2 py-0.5 bg-amber-100 text-amber-900 border border-amber-300 font-semibold text-[10px] rounded">🔍 漆原さん確認済</span>
                                @endif
                                <span class="text-xs font-semibold text-slate-600">{{ $task['lane'] ?? '' }}</span>
                            </div>
                            <h4 class="font-bold text-slate-900 text-sm">{{ $task['title'] }}</h4>
                            <p class="text-xs text-slate-500 font-mono">📍 {{ $task['location'] ?? '' }} | 📦 {{ $task['equipment'] ?? '' }}</p>
                        </div>
                        <div class="flex sm:flex-col items-center sm:items-end justify-between gap-2 shrink-0">
                            <span class="text-xs font-medium px-2 py-0.5 bg-slate-100 rounded text-slate-700">👥 {{ $task['assigned_count'] ?? 0 }}名配置済</span>
                            <span class="text-xs font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-md">{{ $task['status_text'] ?? '進行中' }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="p-3.5 bg-red-50/60 rounded-xl border border-red-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-1">
                            <span class="px-2 py-0.5 bg-red-600 text-white font-bold text-[10px] rounded">🚨 最優先設置</span>
                            <h4 class="font-bold text-slate-900 text-sm">通行止め出入り口・支道接続点 最優先コーン設置</h4>
                            <p class="text-xs text-slate-600 font-mono">📍 出入り口・支道接続点 | 📦 単管プレート・コーンバー</p>
                        </div>
                        <span class="text-xs font-bold text-emerald-700 bg-emerald-100 px-2.5 py-1 rounded-md">完了報告済</span>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Next: 次に控えるタスク -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <div class="flex items-center space-x-2">
                        <span class="text-base">⏳</span>
                        <h3 class="text-base font-bold text-slate-900">NEXT : 次に控えるタスク (11:00開始)</h3>
                    </div>
                    <span class="text-xs text-indigo-600 font-bold">あと 35分後 開始</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                        <div class="flex items-center justify-between text-xs text-slate-500 font-mono mb-1">
                            <span>11:00 - 12:00</span>
                            <span class="font-bold text-slate-700">衛生班 (有江さん)</span>
                        </div>
                        <h5 class="font-bold text-slate-900 text-xs">ゴミ箱チェック（圧縮・袋交換）</h5>
                        <p class="text-[11px] text-slate-500 mt-1">📍 全ゴミステーション【必要: 3名】</p>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                        <div class="flex items-center justify-between text-xs text-slate-500 font-mono mb-1">
                            <span>11:00 - 12:00</span>
                            <span class="font-bold text-slate-700">給水班 (宮永さん)</span>
                        </div>
                        <h5 class="font-bold text-slate-900 text-xs">給水設備 定期点検 (第1回)</h5>
                        <p class="text-[11px] text-slate-500 mt-1">📍 税務署前・北川駐車場【必要: 2名】</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- 右側1カラム: アラート監視 & 現場要請 -->
        <div class="space-y-6">

            <!-- 1. 駐車場利用時間制約監視 (F-405) -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-1.5">
                        <span>🅿️</span>
                        <span>駐車場利用時間制約監視</span>
                    </h3>
                    <span class="text-[10px] text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded font-bold">違反なし</span>
                </div>
                <div class="space-y-2 text-xs">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-slate-800">北川駐車場</div>
                            <div class="text-[10px] text-slate-500 font-mono">Day 0 13:00 〜 Day 3 12:00</div>
                        </div>
                        <span class="text-[11px] font-bold text-emerald-700">利用中 (適正)</span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-slate-800">駅前南駐車場</div>
                            <div class="text-[10px] text-slate-500 font-mono">Day 1 09:00 〜 Day 1 18:00</div>
                        </div>
                        <span class="text-[11px] font-bold text-amber-700">本日18:00返却必須</span>
                    </div>
                </div>
            </div>

            <!-- 2. 現場機材調整・追加要請 (F-407) -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-1.5">
                        <span>🔺</span>
                        <span>現場機材調整要請</span>
                    </h3>
                    <span class="text-[10px] bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded">未完了 1件</span>
                </div>
                <p class="text-[11px] text-slate-500">正規連絡ルート: 住民/出店者 → 漆原さん/萩原さん → コーン担当</p>

                <div class="p-3 bg-amber-50/60 rounded-xl border border-amber-200 text-xs space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-amber-900">【要請】コーン追加 2本・バー 1本</span>
                        <span class="text-[10px] font-mono text-amber-700">10:18 受付</span>
                    </div>
                    <p class="text-slate-700">場所: <strong>ガスト前 店舗前</strong>（客列整理用）</p>
                    <div class="flex items-center justify-between text-[11px] text-slate-600 pt-1 border-t border-amber-200/60">
                        <span>窓口: 漆原さん 承認済</span>
                        <span class="text-indigo-600 font-bold">設備班 運搬中</span>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
</x-layouts.app>