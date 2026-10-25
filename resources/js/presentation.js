// まつり実行委員会 幹事会向けプレゼンテーション制御スクリプト

document.addEventListener('DOMContentLoaded', () => {
    // 全スライドのトークスクリプト定義
    const speakerNotes = [
        /* 01 */ "幹事の皆様、いつもまつりの準備にご尽力いただき本当にありがとうございます。本日は、幹事の皆様に新しいITシステムを使っていただくためではなく、『まつり全体の運行を見える化することで、私自身がより適切な指示を出せるようにし、安心・安全なまつり運営を実現したい』という切実な想いと、そのために各部会の運行情報を集約させてほしいというお願いに参りました。15分ほどお時間をいただきご説明します。",
        /* 02 */ "本日のゴールは3つあります。1つ目は、前回のまつりで私が現場で直面したリアルな混乱状況を共有すること。2つ目は、運行情報を一元化することで『何が変わり、どう指示しやすくなるか』を知っていただくこと。そして3つ目に、具体的にご提供いただきたい『3つの情報』と提出方法を共有することです。システムへの直接入力はすべて私・佐藤が代行しますので、皆様の頭の中やメモにある情報を教えていただきたく存じます。",
        /* 03 */ "まず、当日の私の混乱状況です。現場では4つの大きな負荷が同時に押し寄せていました。第1に、一般来場者、ござ市出店者、イベント参加者、ボランティア、実行委員など、直接対面での問い合わせや世間話、挨拶対応がひっきりなしに続いたこと。第2に、その合間に紙の運行管理テーブルとスマホの時計を常に見比べ、運行全体の把握に神経を尖らせていたこと。第3に、通行止めの開始・解除やござ市の開始・終了など、通りの状況変化を見越して先回りで人員を確保し指示出しに奔走していたこと。そして第4に、台車が勝手に持ち出されないよう目を配りつつ、1日目と2日目で異なる搬入出の場所やタイミングを各リーダーへ都度相談・確認していたことです。",
        /* 04 */ "なぜこれほど混乱したのか。それは、運行情報が各リーダーの頭の中にしかなく、『今現場で何が起きているか』が全体に見える化されていなかったからです。紙テーブルとスマホ時計を睨めっこしても、対面応対の合間では把握が追いつきません。その結果、『誰が空いているか』が直前まで分からず、通行止めやござ市の節目対応で慌てて人を集めたり、台車の管理や日別の搬入出の確認に走り回るなど、指示が後手に回ってしまっていたのです。",
        /* 05 */ "私たちが目指すのは、『状況把握と確認に追われる運営』から、『運行全体の見える化で、私自身がゆとりを持って適切な指示を出せる運営』への転換です。幹事の皆様がお持ちのタスクや手順、機材のルールをあらかじめ一つの画面に集約することで、今どこで何が進み、次に誰を動かすべきかが一目で分かる体制をつくりたいと考えています。",
        /* 06 */ "情報を集めると何が変わるのか、1つ目の変化は『運行状況の把握が一瞬で完了すること』です。時間軸とレーンのマトリクス画面を見れば、現在の進捗がパッと分かります。紙を何枚もめくったりスマホ時計と見比べたりする必要はありません。来場者や出店者への挨拶対応の合間でもチラッと画面を見るだけで全体を把握でき、脳のメモリを『次にどう動かすか』の適切な指示出しに専念できるようになります。",
        /* 07 */ "2つ目の変化は、『通りの変化に先手で人員指示が出せること』です。車両通行止め開始・解除や、ござ市開始・終了の前後など、通りが激変するタイミングに合わせて、15分〜30分前の段階で『誰がどこに動くか』が画面上で一目瞭然になります。ボランティアの手元スマホにも担当タスクが表示されるため、直前に慌てて人を呼び集めることなく、余裕を持って的確な指示が出せるようになります。",
        /* 08 */ "3つ目の変化は、『台車の勝手な持ち出し防止と、日別搬入出のスムーズ化』です。台車の定位置と利用ルールをシステム上で共有しておくことで、勝手な持ち出しを防止します。また、1日目と2日目で搬入出の場所やタイミングが異なる点もあらかじめ登録しておくことで、私が当日に各リーダーへ確認に走り回る必要がなくなり、現場も迷わずスムーズに資機材を運べるようになります。貸切駐車場の時間制約違反も未然に防げます。",
        /* 09 */ "4つ目のメリットは、次年度への引き継ぎです。今年登録したタイムテーブルやタスク手順、人員配置、機材ルールは、来年ボタン一つで全データをコピーできます。『去年は何時にどこへ機材を運んだか』が完全に残るため、毎年ゼロから引き継ぎノートを作る苦労から解放されます。",
        /* 10 */ "ここからは画面イメージです。まずこちらが本部全体マトリクス画面です。皆様からいただいたタスクや運行時間が時間軸×レーンで並びます。今どのレーンが進行中か、遅れや人員不足がないかが色分けで一目で分かり、運行統括としても落ち着いて的確な指示を出すことができます。",
        /* 11 */ "こちらは現場のボランティアスタッフが見るスマホ画面『マイシフト』です。事前のアプリインストールやログインは不要で、QRコードを読み取るだけで『自分は何時にどこで何をするか』が大きく表示されます。現場スタッフが次に何をすべきか把握できるため、現場での迷いがなくなります。",
        /* 12 */ "名簿情報を事前に共有していただければ、本部システムからA4用紙でQR付きスタッフ証をワンクリック印刷できます。前日や当日の朝に切り分けて配るだけで、現場スタッフの案内や出勤確認がスムーズに完了します。",
        /* 13 */ "それでは、本日一番お願いしたい本題です。幹事の皆様に教えていただきたい情報は、この3点です。1つ目は担当業務の運行・手順情報。2つ目は手伝ってくれるスタッフの名簿と参加時間。3つ目は台車や資機材の置き場、日別の搬入出ルール、駐車場の時間制約です。皆様の頭の中にある情報こそが、適切な指示出しの基盤になります。",
        /* 14 */ "具体的にはこのような内容です。例えば『通行止め開始・解除前後の最優先配置と手順』『ござ市前後の人員指示手順』『台車の定位置と持ち出しルール』『1日目と2日目で異なる搬入出の場所・時間・順序』などです。手帳の走り書きや既存のメモのままで構いませんので、そのまま教えていただければと思います。",
        /* 15 */ "『パソコン入力が苦手だし、システムに登録する時間がない』とご心配される必要は全くありません。直接入力していただく必要はなく、すべて私・佐藤が代行入力します。手書きメモをスマホで撮ってLINEで送っていただくだけでも、既存のExcelやPDFを送っていただくだけでも、15分ほど口頭でお話しいただくだけでも大歓迎です。幹事の皆様には、後日印刷した画面を見て『これで合ってるよ』と確認していただくだけで大丈夫です。",
        /* 16 */ "今後のスケジュールです。本日趣旨をお伝えしましたので、今週から来週にかけてメモや名簿をお寄せいただきたいと考えています。集まった情報を私（佐藤）が責任を持って代行入力し、次回幹事会でプロジェクターに映して皆様と一緒に確認・修正します。その後、スタッフ証を印刷して本番を迎えます。",
        /* 17 */ "まとめです。この取り組みの目的は、まつり全体の運行を見える化することで、私自身がより適切な指示を出せるようにし、全員が安心・安全にまつりを運行することです。皆様の頭の中にあるノウハウをお借りできれば、当日の現場は劇的にスムーズになります。入力は私が代行しますので、ぜひご協力をお願い申し上げます。",
        /* 18 */ "以上でご説明を終わります。ご清聴ありがとうございました。『うちの部会はこんな手順だけど大丈夫？』『このメモでもいい？』など、気になる点やご質問があれば何でもお聞かせください。"
    ];

    let currentSlide = 0;
    const slides = document.querySelectorAll('.slide-content');
    const totalSlides = slides.length;
    if (totalSlides === 0) return;

    const slideSelect = document.getElementById('slideSelect');
    const toggleNotesBtn = document.getElementById('toggleNotesBtn');
    const toggleFullscreenBtn = document.getElementById('toggleFullscreenBtn');
    const printBtn = document.getElementById('printPresentationBtn');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const speakerNotesArea = document.getElementById('speakerNotesArea');
    const notesBtnText = document.getElementById('notesBtnText');
    const speakerNotesText = document.getElementById('speakerNotesText');
    const slideBadge = document.getElementById('slideBadge');
    const slideSectionName = document.getElementById('slideSectionName');
    const progressBar = document.getElementById('progressBar');
    const presentationContainer = document.getElementById('presentationContainer');
    const exitFullscreenBtn = document.getElementById('exitFullscreenBtn');

    let showSpeakerNotes = true;

    // セレクトボックスの初期化
    if (slideSelect) {
        slides.forEach((slide, idx) => {
            const opt = document.createElement('option');
            opt.value = idx;
            opt.text = `${String(idx + 1).padStart(2, '0')}. ${slide.getAttribute('data-title') || 'スライド ' + (idx + 1)}`;
            slideSelect.appendChild(opt);
        });

        slideSelect.addEventListener('change', (e) => {
            goToSlide(parseInt(e.target.value, 10));
        });
    }

    function updateSlideUI() {
        slides.forEach((slide, idx) => {
            if (idx === currentSlide) {
                slide.classList.remove('hidden');
            } else {
                slide.classList.add('hidden');
            }
        });

        const currentNum = String(currentSlide + 1).padStart(2, '0');
        const totalNum = String(totalSlides).padStart(2, '0');
        if (slideBadge) {
            slideBadge.innerText = `SLIDE ${currentNum} / ${totalNum}`;
        }

        const activeSlide = slides[currentSlide];
        if (slideSectionName && activeSlide) {
            const sectionName = activeSlide.getAttribute('data-section') || '';
            const titleName = activeSlide.getAttribute('data-title') || '';
            slideSectionName.innerText = `${sectionName} • ${titleName}`;
        }

        if (progressBar) {
            const percent = ((currentSlide + 1) / totalSlides) * 100;
            progressBar.style.width = `${percent}%`;
        }

        if (slideSelect) {
            slideSelect.value = currentSlide;
        }

        if (prevBtn) {
            prevBtn.disabled = (currentSlide === 0);
        }
        if (nextBtn) {
            nextBtn.disabled = (currentSlide === totalSlides - 1);
        }

        if (speakerNotesText) {
            speakerNotesText.innerText = speakerNotes[currentSlide] || "（このスライドの個別スクリプトはありません）";
        }
    }

    function nextSlide() {
        if (currentSlide < totalSlides - 1) {
            currentSlide++;
            updateSlideUI();
        }
    }

    function prevSlide() {
        if (currentSlide > 0) {
            currentSlide--;
            updateSlideUI();
        }
    }

    function goToSlide(index) {
        if (index >= 0 && index < totalSlides) {
            currentSlide = index;
            updateSlideUI();
        }
    }

    function toggleSpeakerNotes() {
        showSpeakerNotes = !showSpeakerNotes;
        if (speakerNotesArea && notesBtnText) {
            if (showSpeakerNotes) {
                speakerNotesArea.classList.remove('hidden');
                notesBtnText.innerText = '原稿ノート: ON';
            } else {
                speakerNotesArea.classList.add('hidden');
                notesBtnText.innerText = '原稿ノート: OFF';
            }
        }
    }

    function toggleFullscreen() {
        if (!presentationContainer) return;
        if (!document.fullscreenElement) {
            presentationContainer.requestFullscreen().catch(err => {
                alert(`全画面表示エラー: ${err.message}`);
            });
        } else {
            document.exitFullscreen();
        }
    }

    function handleFullscreenChange() {
        const isFullscreen = !!document.fullscreenElement;

        // スライド内の全画面解除ボタンの表示・非表示切替
        if (exitFullscreenBtn) {
            if (isFullscreen) {
                exitFullscreenBtn.classList.remove('hidden');
                exitFullscreenBtn.classList.add('inline-flex');
            } else {
                exitFullscreenBtn.classList.add('hidden');
                exitFullscreenBtn.classList.remove('inline-flex');
            }
        }

        // 外側ツールバーのボタンテキスト切替
        if (toggleFullscreenBtn) {
            const btnSpan = toggleFullscreenBtn.querySelector('span:last-child');
            if (btnSpan) {
                btnSpan.innerText = isFullscreen ? '全画面解除' : '全画面投影';
            }
        }
    }

    // イベントリスナーの登録（CSP 違反となるインラインイベントハンドラを全廃）
    if (toggleNotesBtn) {
        toggleNotesBtn.addEventListener('click', toggleSpeakerNotes);
    }
    if (toggleFullscreenBtn) {
        toggleFullscreenBtn.addEventListener('click', toggleFullscreen);
    }
    if (exitFullscreenBtn) {
        exitFullscreenBtn.addEventListener('click', () => {
            if (document.fullscreenElement) {
                document.exitFullscreen().catch(err => {
                    console.error('全画面解除エラー:', err);
                });
            }
        });
    }
    if (printBtn) {
        printBtn.addEventListener('click', () => window.print());
    }
    if (prevBtn) {
        prevBtn.addEventListener('click', prevSlide);
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', nextSlide);
    }

    document.addEventListener('fullscreenchange', handleFullscreenChange);

    // キーボードショートカット
    document.addEventListener('keydown', (e) => {
        if (['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;

        if (e.key === 'ArrowRight' || e.key === 'PageDown' || e.key === ' ') {
            e.preventDefault();
            nextSlide();
        } else if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
            e.preventDefault();
            prevSlide();
        } else if (e.key === 'Home') {
            e.preventDefault();
            goToSlide(0);
        } else if (e.key === 'End') {
            e.preventDefault();
            goToSlide(totalSlides - 1);
        } else if (e.key.toLowerCase() === 'f') {
            e.preventDefault();
            toggleFullscreen();
        } else if (e.key.toLowerCase() === 'n') {
            e.preventDefault();
            toggleSpeakerNotes();
        }
    });

    // 初期表示UI更新
    updateSlideUI();
});
