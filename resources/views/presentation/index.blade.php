<x-layouts.app title="幹事会向けプレゼンテーション資料">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <!-- ページヘッダー & ツールバー -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-slate-200">
            <div>
                <div class="flex items-center space-x-2.5">
                    <span class="text-3xl">📽️</span>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="bg-indigo-100 text-indigo-800 text-[10px] font-extrabold px-2 py-0.5 rounded-full">幹事会専用</span>
                            <span class="text-xs text-slate-500">情報一元管理のご協力提案</span>
                        </div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-0.5">まつり実行委員会 幹事会向けプレゼンテーション</h1>
                    </div>
                </div>
            </div>

            <!-- コントロールボタン群 -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- スライド切替セレクト -->
                <div class="relative">
                    <select id="slideSelect"
                        class="bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-700 py-1.5 pl-3 pr-8 focus:ring-2 focus:ring-indigo-500 shadow-xs cursor-pointer">
                        <!-- JSで動的生成 -->
                    </select>
                </div>

                <!-- 発表者ノート表示切替ボタン -->
                <button type="button" id="toggleNotesBtn"
                    class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 shadow-xs cursor-pointer"
                    title="発表者用トークスクリプトの表示・非表示を切替 (ショートカット: N)">
                    <span>🎙️</span>
                    <span id="notesBtnText">原稿ノート: ON</span>
                </button>

                <!-- 全画面プロジェクターモードボタン -->
                <button type="button" id="toggleFullscreenBtn"
                    class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center space-x-1.5 shadow-xs cursor-pointer"
                    title="プロジェクター・全画面モードで投影 (ショートカット: F)">
                    <span>🖥️</span>
                    <span>全画面投影</span>
                </button>

                <!-- 印刷 / PDF出力ボタン -->
                <button type="button" id="printPresentationBtn"
                    class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs font-bold transition flex items-center space-x-1.5 shadow-xs cursor-pointer print:hidden">
                    <span>🖨️</span>
                    <span>全スライド印刷 / PDF</span>
                </button>
            </div>
        </div>

        <!-- プレゼンテーション本体コンテナ -->
        <div id="presentationContainer" class="relative bg-slate-900 text-white rounded-3xl p-4 sm:p-8 shadow-2xl border border-slate-800 min-h-[580px] flex flex-col justify-between overflow-hidden">

            <!-- スライド上部ナビゲーション / プログレスバー -->
            <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-6">
                <div class="flex items-center space-x-3">
                    <span class="text-xs font-mono font-bold px-2 py-0.5 rounded bg-indigo-950 text-indigo-300 border border-indigo-700/60" id="slideBadge">
                        SLIDE 01 / 18
                    </span>
                    <span class="text-xs text-slate-400 font-medium truncate max-w-[200px] sm:max-w-md" id="slideSectionName">
                        表紙
                    </span>
                </div>
                <div class="flex items-center space-x-1.5 text-xs text-slate-400">
                    <span class="hidden sm:inline text-[11px] text-slate-500 mr-2">左右キー [← / →] または Space で進行</span>
                    <button type="button"
                        class="p-1.5 sm:px-3 sm:py-1 bg-slate-800 hover:bg-slate-700 rounded-lg text-slate-200 transition font-bold disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer" id="prevBtn">
                        ◀ 前へ
                    </button>
                    <button type="button"
                        class="p-1.5 sm:px-3 sm:py-1 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-white transition font-bold disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer" id="nextBtn">
                        次へ ▶
                    </button>
                    <!-- 全画面表示解除ボタン -->
                    <button type="button" id="exitFullscreenBtn"
                        class="hidden items-center space-x-1 p-1.5 sm:px-3 sm:py-1 bg-rose-900/70 hover:bg-rose-800 text-rose-100 border border-rose-500/60 rounded-lg transition font-bold cursor-pointer shadow-sm ml-2"
                        title="全画面表示を解除する (Escキーでも解除可能)">
                        <span>✕</span>
                        <span>全画面解除 (Esc)</span>
                    </button>
                </div>
            </div>

            <!-- プログレスバー -->
            <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden mb-6">
                <div id="progressBar" class="bg-indigo-500 h-full transition-all duration-300 ease-out w-0"></div>
            </div>

            <!-- ========================================================= -->
            <!-- 各スライド定義エリア (JSで1枚ずつ active にする) -->
            <!-- ========================================================= -->
            <div class="flex-1 flex flex-col justify-center">

                <!-- スライド 01: 表紙 -->
                <div class="slide-content" data-title="表紙: 幹事会向けご提案" data-section="導入">
                    <div class="text-center py-8 sm:py-12 max-w-4xl mx-auto">
                        <div class="inline-flex items-center space-x-2 bg-indigo-500/20 border border-indigo-500/40 px-3 py-1 rounded-full text-indigo-300 text-xs font-bold mb-4">
                            <span>🏮</span>
                            <span>令和8年度 まつり実行委員会 幹事会専用資料</span>
                        </div>
                        <h2 class="text-2xl sm:text-4xl lg:text-5xl font-black text-white tracking-tight leading-tight">
                            まつり運行の<span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-sky-300 to-indigo-300">「見える化」</span>で<br>
                            より適切な指示と安心の運営を実現するために
                        </h2>
                        <p class="text-sm sm:text-lg text-slate-300 mt-6 leading-relaxed max-w-2xl mx-auto">
                            〜現場対応に追われながらも的確に動くために、<br class="hidden sm:inline">各部会が持つ運行情報・ノウハウの集約にご協力をお願いします〜
                        </p>
                        <div class="mt-8 pt-6 border-t border-slate-800/80 flex items-center justify-center space-x-6 text-xs text-slate-400">
                            <div>発表：まつり実行委員会 運営本部（佐藤）</div>
                            <div>•</div>
                            <div>想定時間：約15分</div>
                        </div>
                    </div>
                </div>

                <!-- スライド 02: 本日の目的とゴール -->
                <div class="slide-content hidden" data-title="本日の目的とゴール" data-section="第1部: 現状の課題">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-xs text-indigo-400 font-bold uppercase tracking-wider mb-1">Purpose & Goal</div>
                        <h2 class="text-xl sm:text-3xl font-extrabold text-white">本日の目的とゴール</h2>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1">まつり全体の運行を見える化し、私自身がより適切な指示を出せるようにする</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div class="bg-slate-800/80 border border-slate-700 p-5 rounded-2xl">
                                <div class="flex items-center space-x-2 text-indigo-400 font-bold text-sm mb-3">
                                    <span>🎯</span>
                                    <span>本日のゴール</span>
                                </div>
                                <ul class="space-y-3 text-xs sm:text-sm text-slate-300">
                                    <li class="flex items-start space-x-2">
                                        <span class="text-indigo-400 font-bold">1.</span>
                                        <span>当日の<strong class="text-white">「私の混乱状況（4つの実態）」</strong>を共有する</span>
                                    </li>
                                    <li class="flex items-start space-x-2">
                                        <span class="text-indigo-400 font-bold">2.</span>
                                        <span>運行情報を一元化することで<strong class="text-white">「何が変わり、どう指示しやすくなるか」</strong>を知る</span>
                                    </li>
                                    <li class="flex items-start space-x-2">
                                        <span class="text-indigo-400 font-bold">3.</span>
                                        <span>具体的に提供していただきたい<strong class="text-white">「3つの情報」</strong>と提出方法を共有する</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="bg-gradient-to-br from-indigo-950/70 to-slate-900 border border-indigo-700/50 p-5 rounded-2xl flex flex-col justify-center text-center">
                                <span class="text-3xl mb-2">🤝</span>
                                <div class="text-sm font-bold text-indigo-200">幹事の皆様へのお願い</div>
                                <p class="text-base sm:text-lg font-black text-white mt-2 leading-relaxed">
                                    「システムへの直接入力は私（佐藤）が代行します。<br>
                                    皆様の頭の中やメモにある情報を教えてください！」
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- スライド 03: 当日の私の混乱状況 -->
                <div class="slide-content hidden" data-title="当日の私の混乱状況（実態）" data-section="第1部: 現状の課題">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-xs text-rose-400 font-bold uppercase tracking-wider mb-1">Actual Situation</div>
                        <h2 class="text-xl sm:text-3xl font-extrabold text-white">当日の私の混乱状況：現場で直面した4つの負荷</h2>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1">対面での応対に追われながら、運行状況の把握と指示出しに奔走していました</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                            <div class="bg-slate-800/80 border border-slate-700 p-4 sm:p-5 rounded-2xl">
                                <div class="flex items-center space-x-2 text-indigo-300 font-bold text-sm mb-1">
                                    <span>👥</span>
                                    <span>① 多様な関係者への対面応対</span>
                                </div>
                                <p class="text-xs text-slate-300 leading-relaxed">
                                    一般来場者、ござ市出店者、イベント参加者、ボランティア参加者、実行委員など、直接の問い合わせ・世間話・挨拶対応に常時追われていた。
                                </p>
                            </div>
                            <div class="bg-slate-800/80 border border-slate-700 p-4 sm:p-5 rounded-2xl">
                                <div class="flex items-center space-x-2 text-indigo-300 font-bold text-sm mb-1">
                                    <span>⏱️</span>
                                    <span>② 運行テーブルとスマホ時計の監視</span>
                                </div>
                                <p class="text-xs text-slate-300 leading-relaxed">
                                    紙の運行管理テーブルとスマホ（現在時刻の監視）を見ながら、常に運行状況の把握に神経を尖らせていた。
                                </p>
                            </div>
                            <div class="bg-slate-800/80 border border-slate-700 p-4 sm:p-5 rounded-2xl">
                                <div class="flex items-center space-x-2 text-indigo-300 font-bold text-sm mb-1">
                                    <span>🚦</span>
                                    <span>③ 通りの状況変化と人員指示出し</span>
                                </div>
                                <p class="text-xs text-slate-300 leading-relaxed">
                                    通行止め開始・解除前後、ござ市開始・終了前後など、通りの状況変化を見越して先回りで人員確保と指示出しをしていた。
                                </p>
                            </div>
                            <div class="bg-slate-800/80 border border-slate-700 p-4 sm:p-5 rounded-2xl">
                                <div class="flex items-center space-x-2 text-indigo-300 font-bold text-sm mb-1">
                                    <span>🛒</span>
                                    <span>④ 資機材管理と各リーダーへの相談確認</span>
                                </div>
                                <p class="text-xs text-slate-300 leading-relaxed">
                                    台車を勝手に持ち出されないよう気を配り、1日目と2日目とで異なる資機材の搬入・搬出の場所やタイミングを各リーダーに相談確認していた。
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- スライド 04: 見える化されていないことの課題 -->
                <div class="slide-content hidden" data-title="運行が見えないことによる指示の限界" data-section="第1部: 現状の課題">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-xs text-rose-400 font-bold uppercase tracking-wider mb-1">Root Causes</div>
                        <h2 class="text-xl sm:text-3xl font-extrabold text-white">なぜ混乱したのか？「見えない」ことによる指示の限界</h2>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1">情報が各リーダーの頭の中にしかなく、先回り指示が難しかった</p>

                        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-700 bg-slate-800/60 text-xs sm:text-sm">
                            <table class="w-full text-left">
                                <thead class="bg-slate-800 text-slate-300 font-bold border-b border-slate-700">
                                    <tr>
                                        <th class="p-3.5 w-1/3">これまでの運用</th>
                                        <th class="p-3.5">生じていた指示出しのボトルネック</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700/60 text-slate-300">
                                    <tr>
                                        <td class="p-3.5 font-bold text-indigo-300">紙テーブルと時計を見比べる</td>
                                        <td class="p-3.5">来場者対応や挨拶の合間では、<strong class="text-rose-400">リアルタイムの進行状況の把握が追いつかない</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="p-3.5 font-bold text-indigo-300">通行止め・ござ市の節目対応</td>
                                        <td class="p-3.5">「誰がどこに空いているか」が見えず、<strong class="text-rose-400">直前になって慌てて人員を確保・指示出し</strong>する</td>
                                    </tr>
                                    <tr>
                                        <td class="p-3.5 font-bold text-indigo-300">台車の管理</td>
                                        <td class="p-3.5">定位置や利用ルールが共有されず、<strong class="text-rose-400">台車を勝手に持ち出されないよう目を配り続ける</strong>負担が発生</td>
                                    </tr>
                                    <tr>
                                        <td class="p-3.5 font-bold text-indigo-300">1日目と2日目の搬入出の差</td>
                                        <td class="p-3.5">各リーダーの頭の中にしか手順がなく、<strong class="text-rose-400">搬入・搬出の場所やタイミングをその都度相談・確認</strong>する手間が発生</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- スライド 05: 私たちが目指す姿（ビジョン） -->
                <div class="slide-content hidden" data-title="私たちが目指す姿（ビジョン）" data-section="第1部: 現状の課題">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-xs text-emerald-400 font-bold uppercase tracking-wider mb-1">Vision</div>
                        <h2 class="text-xl sm:text-3xl font-extrabold text-white">目指すのは「状況把握に追われる運営」から<br>「運行の見える化で適切な指示が出せる運営」へ</h2>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1">画面一つで全体を把握し、ゆとりを持って的確な先回り指示を出す体制へ</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div class="bg-rose-950/30 border border-rose-500/40 p-5 rounded-2xl">
                                <div class="flex items-center space-x-2 text-rose-400 font-bold text-sm mb-2">
                                    <span>❌</span>
                                    <span>これまで（状況把握に忙殺）</span>
                                </div>
                                <p class="text-xs text-rose-200 mb-2">来場者対応の合間に紙と時計を見比べ、人を集め、台車や搬入出を各リーダーに都度相談</p>
                                <div class="bg-rose-900/40 p-3 rounded-xl border border-rose-700/50 text-center font-bold text-rose-300 text-xs sm:text-sm">
                                    「把握と確認に追われ、指示が後手に回る」
                                </div>
                            </div>

                            <div class="bg-emerald-950/30 border border-emerald-500/40 p-5 rounded-2xl">
                                <div class="flex items-center space-x-2 text-emerald-400 font-bold text-sm mb-2">
                                    <span>⭕</span>
                                    <span>これから（運行の見える化と的確な指示）</span>
                                </div>
                                <p class="text-xs text-emerald-200 mb-2">時間軸×レーンのマトリクス画面で現在進行と次のタスクが一目瞭然。通りの変化に先手指示</p>
                                <div class="bg-emerald-900/40 p-3 rounded-xl border border-emerald-700/50 text-center font-bold text-emerald-300 text-xs sm:text-sm">
                                    「全体が見えるから、余裕を持って適切な指示が出せる」
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- スライド 06: 変化① 運行状況の把握が一瞬で完了 -->
                <div class="slide-content hidden" data-title="変化①: 運行状況の把握が一瞬で完了" data-section="第2部: 見える化による変化">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-xs text-indigo-400 font-bold uppercase tracking-wider mb-1">Advantage 01</div>
                        <h2 class="text-xl sm:text-3xl font-extrabold text-white">運行状況の把握が一瞬で完了する</h2>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1">紙テーブルとスマホの時計を睨めっこする必要がなくなります</p>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                            <div class="bg-slate-800/80 border border-slate-700 p-5 rounded-2xl">
                                <span class="text-2xl">👀</span>
                                <h3 class="font-bold text-sm text-indigo-300 mt-2">一瞥で現在地が分かる</h3>
                                <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                                    画面を開くだけで「今どのレーンが進行中か」「予定通りか」が瞬時に判明。紙をめくって探す手間がゼロに。
                                </p>
                            </div>
                            <div class="bg-slate-800/80 border border-slate-700 p-5 rounded-2xl">
                                <span class="text-2xl">🤝</span>
                                <h3 class="font-bold text-sm text-indigo-300 mt-2">対面応対の合間に確認</h3>
                                <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                                    来場者や出店者への挨拶・問い合わせ対応の合間でも、画面をチラッと見るだけで運行全体の流れを把握。
                                </p>
                            </div>
                            <div class="bg-slate-800/80 border border-slate-700 p-5 rounded-2xl">
                                <span class="text-2xl">🎯</span>
                                <h3 class="font-bold text-sm text-indigo-300 mt-2">次の指示出しに専念</h3>
                                <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                                    「今何が起きているか」の確認に脳のメモリを奪われず、落ち着いて「次にどう動かすか」の指示出しに集中。
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- スライド 07: 変化② 通りの変化に先手で人員指示 -->
                <div class="slide-content hidden" data-title="変化②: 通りの変化に先手で人員指示" data-section="第2部: 見える化による変化">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-xs text-indigo-400 font-bold uppercase tracking-wider mb-1">Advantage 02</div>
                        <h2 class="text-xl sm:text-3xl font-extrabold text-white">通りの変化（通行止め・ござ市）に先手で人員指示が出せる</h2>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1">節目ごとの必要人員が可視化され、直前に慌てて人を集めずに済みます</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div class="bg-slate-800/60 border border-slate-700 p-5 rounded-2xl">
                                <div class="text-xs font-bold text-slate-400 mb-2">これまでの現場</div>
                                <ul class="space-y-2 text-xs sm:text-sm text-slate-300">
                                    <li>❌ 車両通行止めの開始・解除直前になって「誰がどこに立てるか」慌てて確認</li>
                                    <li>❌ ござ市の開始・終了時に人手が足りず、直前で人を呼び集めて指示</li>
                                    <li>❌ 誰がどの持ち場にいるか見えず、指示出しが常に後手に回る</li>
                                </ul>
                            </div>
                            <div class="bg-indigo-950/40 border border-indigo-500/50 p-5 rounded-2xl">
                                <div class="text-xs font-bold text-indigo-300 mb-2">運行見える化後</div>
                                <ul class="space-y-2 text-xs sm:text-sm text-slate-200">
                                    <li>⭕ 通行止めやござ市の前後に<strong class="text-indigo-300">「誰がどこに動くか」事前に時間軸で一覧化</strong></li>
                                    <li>⭕ ボランティアの手元スマホに担当タスクが表示され、<strong class="text-indigo-300">各自が迷わず持ち場へ移動</strong></li>
                                    <li>⭕ 指示を出す側も、15分〜30分前の段階で余裕を持って人員配置を確認・指示可能</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- スライド 08: 変化③ 台車の管理と搬入出の効率化 -->
                <div class="slide-content hidden" data-title="変化③: 台車管理と搬入出の効率化" data-section="第2部: 見える化による変化">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-xs text-indigo-400 font-bold uppercase tracking-wider mb-1">Advantage 03</div>
                        <h2 class="text-xl sm:text-3xl font-extrabold text-white">台車の勝手な持ち出し防止と、日別の搬入出をスムーズに</h2>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1">資機材ルールが明確になり、各リーダーへ都度相談に走る必要がなくなります</p>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                            <div class="bg-slate-800/80 border border-slate-700 p-4 sm:p-5 rounded-2xl">
                                <div class="text-2xl mb-1">🛒</div>
                                <h3 class="font-bold text-sm text-indigo-300">台車の定位置と持ち出しルール</h3>
                                <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                                    台車の配置場所と使用ルールをあらかじめ共有。勝手な持ち出しを防ぎ、資機材運搬の混乱を防止。
                                </p>
                            </div>
                            <div class="bg-slate-800/80 border border-slate-700 p-4 sm:p-5 rounded-2xl">
                                <div class="text-2xl mb-1">📅</div>
                                <h3 class="font-bold text-sm text-indigo-300">1日目・2日目の搬入出差を整理</h3>
                                <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                                    日によって異なる搬入・搬出の場所やタイミングを事前共有。当日に各リーダーへ確認に回る負担を解消。
                                </p>
                            </div>
                            <div class="bg-slate-800/80 border border-slate-700 p-4 sm:p-5 rounded-2xl">
                                <div class="text-2xl mb-1">⏱️</div>
                                <h3 class="font-bold text-sm text-indigo-300">貸切駐車場の時間制約遵守</h3>
                                <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                                    各貸切駐車場（3箇所）の利用可能時間をシステムで管理し、時間外配置や近隣トラブルを未然に防止。
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- スライド 09: 幹事のメリット④ 引き継ぎの劇的簡素化 -->
                <div class="slide-content hidden" data-title="幹事のメリット④: 引き継ぎの劇的簡素化" data-section="第2部: 幹事のメリット">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-xs text-indigo-400 font-bold uppercase tracking-wider mb-1">Benefit 04</div>
                        <h2 class="text-xl sm:text-3xl font-extrabold text-white">「来年の幹事への引き継ぎ資料作り」から解放されます</h2>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1">今年のデータがそのまま来年への資産に変わる</p>

                        <div class="bg-gradient-to-r from-indigo-950/60 to-slate-800 border border-indigo-700/60 p-6 rounded-2xl mt-6">
                            <div class="flex items-center space-x-3 text-indigo-300 font-bold text-base mb-2">
                                <span class="text-2xl">🔄</span>
                                <span>年間サイクル・データのワンクリック複製機能</span>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                                今年登録した<strong>「タイムテーブル」「タスク手順」「必要機材」「人員配置パターン」</strong>は、すべて来年度へボタン一つでコピーできます。
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4 pt-4 border-t border-slate-700/60 text-xs text-slate-300">
                                <div>✅ 毎年ゼロからWordを作る必要なし</div>
                                <div>✅ 「去年どうだった？」がすぐ分かる</div>
                                <div>✅ 幹事が交代してもノウハウが継承される</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- スライド 10: 画面イメージ① 本部全体マトリクス -->
                <div class="slide-content hidden" data-title="画面イメージ①: 本部全体マトリクス" data-section="第3部: 活用イメージ">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-xs text-indigo-400 font-bold uppercase tracking-wider mb-1">UI Preview 01</div>
                        <h2 class="text-xl sm:text-3xl font-extrabold text-white">幹事からいただいた情報は、こう「見える化」されます</h2>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1">時間軸 × 担当レーンで全体の進捗が丸見えになるマトリクス</p>

                        <div class="mt-4 border border-slate-700 rounded-2xl overflow-hidden bg-slate-950 shadow-lg">
                            <img src="{{ asset('images/manual/05_timetable_matrix.png') }}"
                                alt="本部マトリクス画面"
                                class="w-full max-h-[340px] object-cover object-top">
                        </div>
                        <div class="mt-3 flex items-center justify-between text-xs text-slate-400">
                            <span>💡 遅れている作業や人員の過不足が一目で分かり、本部員全員でフォローできます。</span>
                        </div>
                    </div>
                </div>

                <!-- スライド 11: 画面イメージ② 現場スマホ「マイシフト」 -->
                <div class="slide-content hidden" data-title="画面イメージ②: 現場スマホ「マイシフト」" data-section="第3部: 活用イメージ">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-xs text-indigo-400 font-bold uppercase tracking-wider mb-1">UI Preview 02</div>
                        <h2 class="text-xl sm:text-3xl font-extrabold text-white">ボランティアの手元に届き、幹事に聞かずに動けます</h2>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1">QRコードからパスワード不要で即座に専用マイシフトを表示</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4 items-center">
                            <div class="border border-slate-700 rounded-2xl overflow-hidden bg-slate-950 p-2 shadow-lg">
                                <img src="{{ asset('images/manual/11_myshift_mobile.png') }}"
                                    alt="現場マイシフト実画面"
                                    class="w-full max-h-[320px] object-contain mx-auto">
                            </div>
                            <div class="space-y-4 text-xs sm:text-sm text-slate-300">
                                <div class="bg-slate-800/80 p-4 rounded-xl border border-slate-700">
                                    <div class="font-bold text-white mb-1">🔑 ログイン不要</div>
                                    <p class="text-slate-400">アプリのインストールや面倒なパスワード入力は不要。QRコードをかざすだけ。</p>
                                </div>
                                <div class="bg-slate-800/80 p-4 rounded-xl border border-slate-700">
                                    <div class="font-bold text-white mb-1">🎯 自分だけの情報</div>
                                    <p class="text-slate-400">「何時に、どこで、誰と、何の仕事をするか」だけが大きく表示されます。</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- スライド 12: 画面イメージ③ QRスタッフ証の印刷 -->
                <div class="slide-content hidden" data-title="画面イメージ③: QRスタッフ証の発行" data-section="第3部: 活用イメージ">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-xs text-indigo-400 font-bold uppercase tracking-wider mb-1">UI Preview 03</div>
                        <h2 class="text-xl sm:text-3xl font-extrabold text-white">名簿情報から、A4用紙にワンクリック印刷</h2>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1">前日に切り分けてネームホルダーに入れるだけで準備完了</p>

                        <div class="mt-4 border border-slate-700 rounded-2xl overflow-hidden bg-slate-950 shadow-lg">
                            <img src="{{ asset('images/manual/09_staff_print_cards.png') }}"
                                alt="スタッフ証印刷画面"
                                class="w-full max-h-[340px] object-cover object-top">
                        </div>
                        <div class="mt-3 text-xs text-slate-400">
                            <span>💡 名前・所属・個別QRコードが自動印字。前日・当日のスタッフ配布がスムーズになります。</span>
                        </div>
                    </div>
                </div>

                <!-- スライド 13: 幹事にお願いしたい3つの情報 -->
                <div class="slide-content hidden" data-title="幹事にお願いしたい「3つの情報」" data-section="第4部: ご協力のお願い">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-xs text-amber-400 font-bold uppercase tracking-wider mb-1">Requests</div>
                        <h2 class="text-xl sm:text-3xl font-extrabold text-white">幹事の皆様に教えていただきたい「3つの情報」</h2>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1">皆様の頭の中や手帳にある情報を共有してください</p>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                            <div class="bg-slate-800/90 border-2 border-indigo-500/50 p-5 rounded-2xl">
                                <div class="text-indigo-400 font-black text-sm mb-1">① 運行・手順情報</div>
                                <h3 class="font-bold text-base text-white">何時に・どこで・何をするか</h3>
                                <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                                    前日設営、当日本番（通行止め開始・解除前後、ござ市開始・終了前後）、撤収に至るタスクの流れと手順。
                                </p>
                            </div>
                            <div class="bg-slate-800/90 border-2 border-indigo-500/50 p-5 rounded-2xl">
                                <div class="text-indigo-400 font-black text-sm mb-1">② 人員・名簿情報</div>
                                <h3 class="font-bold text-base text-white">誰が・いつ手伝ってくれるか</h3>
                                <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                                    担当スタッフ・ボランティアの氏名、携帯番号、参加可能日・時間帯。
                                </p>
                            </div>
                            <div class="bg-slate-800/90 border-2 border-indigo-500/50 p-5 rounded-2xl">
                                <div class="text-indigo-400 font-black text-sm mb-1">③ 機材・場所ルール</div>
                                <h3 class="font-bold text-base text-white">何がどこにあり、どんな制約があるか</h3>
                                <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                                    台車の配置・利用ルール、1日目・2日目で異なる搬入出の場所と時間、駐車場の時間制約。
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- スライド 14: 具体的な情報例 -->
                <div class="slide-content hidden" data-title="ご提供いただきたい情報例" data-section="第4部: ご協力のお願い">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-xs text-amber-400 font-bold uppercase tracking-wider mb-1">Details</div>
                        <h2 class="text-xl sm:text-3xl font-extrabold text-white">具体的な内容例（日常メモのままでOKです）</h2>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1">完璧な形式である必要はありません</p>

                        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-700 bg-slate-800/60 text-xs sm:text-sm">
                            <table class="w-full text-left">
                                <thead class="bg-slate-800 text-slate-300 font-bold border-b border-slate-700">
                                    <tr>
                                        <th class="p-3 w-1/4">項目</th>
                                        <th class="p-3">教えていただきたい具体例</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700/60 text-slate-300">
                                    <tr>
                                        <td class="p-3 font-bold text-indigo-300">① 運行・タスク</td>
                                        <td class="p-3">
                                             ・前日設営の開始時間と手順（看板設置、テント組み立て等）<br>
                                             ・車両通行止め開始・解除前後の最優先配置と手順<br>
                                             ・ござ市開始・終了前後の通り状況変化に合わせた人員指示手順
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="p-3 font-bold text-indigo-300">② 人員名簿</td>
                                        <td class="p-3">
                                            ・スタッフの氏名、携帯番号、所属部会<br>
                                            ・参加可能日（前日・当日・撤収日）と午前/午後などの希望
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="p-3 font-bold text-indigo-300">③ 機材・場所</td>
                                        <td class="p-3">
                                            ・台車の定位置と持ち出しルール（勝手な持ち出し防止）<br>
                                            ・1日目と2日目で異なる資機材搬入・搬出の場所・時間・順序<br>
                                            ・カラーコーン保管場所（番所地下等）、貸切駐車場3箇所の利用可能時間帯と鍵の担当者
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- スライド 15: 入力代行サポート -->
                <div class="slide-content hidden" data-title="入力代行サポート体制" data-section="第4部: ご協力のお願い">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-xs text-emerald-400 font-bold uppercase tracking-wider mb-1">Support</div>
                        <h2 class="text-xl sm:text-3xl font-extrabold text-white">幹事の皆様の入力負担はゼロに近づけます</h2>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1">パソコンが苦手でも大丈夫！私（佐藤）が全面代行します</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div class="bg-indigo-950/40 border border-indigo-700/60 p-5 rounded-2xl">
                                <div class="flex items-center space-x-2 text-indigo-300 font-bold text-sm mb-3">
                                    <span>📝</span>
                                    <span>どんな形式でも大歓迎</span>
                                </div>
                                <ul class="space-y-3 text-xs sm:text-sm text-slate-300">
                                    <li>📸 去年の<strong class="text-white">手書きメモ</strong>をスマホで撮ってLINE送信</li>
                                    <li>📎 お手持ちの<strong class="text-white">Excel・Word・PDF</strong>をそのまま送付</li>
                                    <li>☕ 私（佐藤）が<strong class="text-white">口頭で15分ヒアリング</strong>（雑談でOK）</li>
                                </ul>
                            </div>
                            <div class="bg-emerald-950/40 border border-emerald-700/60 p-5 rounded-2xl">
                                <div class="flex items-center space-x-2 text-emerald-300 font-bold text-sm mb-3">
                                    <span>💻</span>
                                    <span>私（佐藤）が代行します</span>
                                </div>
                                <ul class="space-y-3 text-xs sm:text-sm text-slate-300">
                                    <li>⚙️ いただいた資料をもとに、<strong class="text-emerald-300">システム入力は私（佐藤）が代行</strong></li>
                                    <li>🖨️ 登録完了後、画面印刷をお渡しします</li>
                                    <li>👀 幹事の皆様は<strong class="text-emerald-300">「内容に間違いがないかチェック」</strong>するだけ</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- スライド 16: 本番までのスケジュール -->
                <div class="slide-content hidden" data-title="本番までのスケジュール" data-section="第5部: 今後の進め方">
                    <div class="max-w-4xl mx-auto">
                        <div class="text-xs text-indigo-400 font-bold uppercase tracking-wider mb-1">Schedule</div>
                        <h2 class="text-xl sm:text-3xl font-extrabold text-white">本番までのスケジュールとマイルストーン</h2>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1">無理のないステップで確実に進めます</p>

                        <div class="mt-6 space-y-3 text-xs sm:text-sm">
                            <div class="bg-slate-800/80 border border-indigo-500/40 p-4 rounded-xl flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <span class="bg-indigo-600 text-white font-bold px-2 py-0.5 rounded text-xs">Step 1</span>
                                    <span class="font-bold text-white">本日〜来週</span>
                                    <span class="text-slate-300">情報・メモ・名簿の共有受付（私・佐藤宛て）</span>
                                </div>
                                <span class="text-xs text-indigo-300 font-bold">幹事の皆様</span>
                            </div>
                            <div class="bg-slate-800/80 border border-slate-700 p-4 rounded-xl flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <span class="bg-slate-700 text-slate-300 font-bold px-2 py-0.5 rounded text-xs">Step 2</span>
                                    <span class="font-bold text-white">来週〜再来週</span>
                                    <span class="text-slate-300">私（佐藤）によるシステム代行入力・データ化</span>
                                </div>
                                <span class="text-xs text-slate-400">佐藤作業</span>
                            </div>
                            <div class="bg-slate-800/80 border border-slate-700 p-4 rounded-xl flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <span class="bg-slate-700 text-slate-300 font-bold px-2 py-0.5 rounded text-xs">Step 3</span>
                                    <span class="font-bold text-white">次回幹事会</span>
                                    <span class="text-slate-300">プロジェクターで登録画面の確認・修正受付</span>
                                </div>
                                <span class="text-xs text-indigo-300 font-bold">幹事会全体</span>
                            </div>
                            <div class="bg-emerald-950/40 border border-emerald-500/40 p-4 rounded-xl flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <span class="bg-emerald-600 text-white font-bold px-2 py-0.5 rounded text-xs">Step 4</span>
                                    <span class="font-bold text-white">本番直前〜当日</span>
                                    <span class="text-slate-300">スタッフ証印刷・前日設営・本番運行</span>
                                </div>
                                <span class="text-xs text-emerald-300 font-bold">安心の本番へ</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- スライド 17: まとめ -->
                <div class="slide-content hidden" data-title="まとめ: 幹事全員でつくる安心の運営" data-section="第5部: 今後の進め方">
                    <div class="max-w-4xl mx-auto text-center py-6">
                        <div class="text-xs text-indigo-400 font-bold uppercase tracking-wider mb-2">Conclusion</div>
                        <h2 class="text-2xl sm:text-4xl font-black text-white tracking-tight">
                            幹事全員の知恵を結集し、<br>
                            誰もがゆとりを持って楽しめるまつりへ
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8 text-left">
                            <div class="bg-slate-800/80 p-5 rounded-2xl border border-slate-700">
                                <div class="text-xl mb-1">🛡️</div>
                                <div class="font-bold text-white text-sm">的確な指示と安心</div>
                                <p class="text-xs text-slate-400 mt-1">状況把握や確認に追われず、落ち着いて適切な指示出しに専念できます。</p>
                            </div>
                            <div class="bg-slate-800/80 p-5 rounded-2xl border border-slate-700">
                                <div class="text-xl mb-1">💎</div>
                                <div class="font-bold text-white text-sm">ノウハウは最大の資産</div>
                                <p class="text-xs text-slate-400 mt-1">皆様の頭の中にある情報こそが、このまつりを支える財産です。</p>
                            </div>
                            <div class="bg-slate-800/80 p-5 rounded-2xl border border-slate-700">
                                <div class="text-xl mb-1">🤝</div>
                                <div class="font-bold text-white text-sm">入力は私（佐藤）が代行</div>
                                <p class="text-xs text-slate-400 mt-1">難しい作業はありません。まずはメモやExcelをお寄せください。</p>
                            </div>
                        </div>

                        <div class="mt-8 p-4 bg-indigo-950/60 border border-indigo-500/40 rounded-2xl max-w-xl mx-auto">
                            <p class="text-sm sm:text-base font-bold text-indigo-200">
                                「今年みんなで情報を作れば、来年からはずっと楽になります」<br>
                                ご協力を心よりお願い申し上げます！
                            </p>
                        </div>
                    </div>
                </div>

                <!-- スライド 18: 質疑応答・ご意見交換 -->
                <div class="slide-content hidden" data-title="ご意見・ご質問（質疑応答）" data-section="質疑応答">
                    <div class="max-w-4xl mx-auto text-center py-8">
                        <span class="text-4xl mb-3 inline-block">💬</span>
                        <h2 class="text-2xl sm:text-4xl font-black text-white">ご清聴ありがとうございました</h2>
                        <p class="text-sm sm:text-base text-slate-300 mt-3">ご質問・ご懸念点、何でもお聞かせください</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-8 max-w-2xl mx-auto text-left text-xs sm:text-sm">
                            <div class="bg-slate-800/70 p-4 rounded-xl border border-slate-700">
                                <div class="font-bold text-indigo-300 mb-1">❓ よくあるご質問例</div>
                                <p class="text-slate-400">「うちの部会は担当者が直前まで決まらないが大丈夫？」<br>→ 未確定の枠だけ空けておき、直前登録でOKです。</p>
                            </div>
                            <div class="bg-slate-800/70 p-4 rounded-xl border border-slate-700">
                                <div class="font-bold text-indigo-300 mb-1">📮 情報の送付先</div>
                                <p class="text-slate-400">幹事会LINEグループ、または私（佐藤）宛てにお気軽にお送りください。</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ========================================================= -->
            <!-- 発表者用トークスクリプト (画面下部アコーディオン) -->
            <!-- ========================================================= -->
            <div id="speakerNotesArea" class="mt-6 pt-4 border-t border-slate-800 bg-slate-950/80 -mx-4 sm:-mx-8 -mb-4 sm:-mb-8 p-4 sm:p-6 transition-all duration-200">
                <div class="flex items-center justify-between text-xs text-amber-400 font-bold mb-2">
                    <div class="flex items-center space-x-2">
                        <span>🎙️</span>
                        <span>発表者用トークスクリプト（このスライドでの語りかけ）</span>
                    </div>
                    <span class="text-[10px] text-slate-500 font-normal">プロジェクター全画面時は非表示にできます (キー: N)</span>
                </div>
                <div id="speakerNotesText" class="text-xs sm:text-sm text-amber-200/90 leading-relaxed max-h-32 overflow-y-auto pr-2">
                    <!-- JSで現在スライドのスクリプトを注入 -->
                </div>
            </div>

        </div>

        <!-- 印刷用 全スライド一覧 (画面上は非表示、印刷時のみブロック表示) -->
        <div class="hidden print:block space-y-8 mt-8">
            <!-- 印刷CSS制御用 -->
        </div>

    </div>

    @vite(['resources/js/presentation.js'])
</x-layouts.app>
