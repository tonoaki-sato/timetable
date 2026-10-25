// まつり実行委員会 幹事会向けプレゼンテーション制御スクリプト

document.addEventListener('DOMContentLoaded', () => {
    // 全スライドのトークスクリプト定義
    const speakerNotes = [
        /* 01 */ "幹事の皆様、いつもまつりの準備にご尽力いただき本当にありがとうございます。本日は、幹事の皆様に新しいITシステムを使わせるためではなく、「当日に幹事の皆様へ集中する電話対応や指示出しの負担を劇的に減らすために、皆様が持つ知恵と情報を事前に集約させてほしい」という切実なお願いとご提案に参りました。15分ほどお時間をいただき、ご説明させていただきます。",
        /* 02 */ "本日のゴールは3つです。まず、当日幹事の皆様がいかに大変かという課題を共有し、情報を集約すれば幹事自身がどれだけ楽になるかをお伝えします。そして最後にお願いしたい情報をお伝えしますが、パソコンでの難しい入力作業は事務局が代行しますので、まずは皆様がお持ちの情報を共有していただくことへのご協力をお願いしたいと考えています。",
        /* 03 */ "本番当日、幹事の皆様の電話や無線が鳴りっぱなしで、自分の部会の様子を見に行く暇も、ゆっくりお昼を食べる暇もない…そんな経験はありませんでしょうか。「あのコーンどこにある？」「鍵は誰が持ってる？」といった質問がすべて皆様個人にかかってきてしまう。これは皆様の責任ではなく、情報が幹事個人の頭の中にしか入っていない「仕組み」に原因があります。",
        /* 04 */ "情報が幹事個人の手帳やLINEメモにあると、現場スタッフは「幹事さんに聞くしかない」状態になります。もし幹事さんが別の対応で電話に出られないと、現場の手が止まってしまいます。また、貸切駐車場の時間制限や機材の保管場所が共有されていないと、近隣からのクレームや機材紛失につながり、結局その火消しも幹事の皆様が背負うことになってしまいます。",
        /* 05 */ "私たちが目指すのは、「幹事さんに聞かないと動けない祭り」から、「システムを見ればスタッフも本部も自律して動ける祭り」への転換です。幹事の皆様がお持ちの貴重な情報をあらかじめ一つのプラットフォームに集めることで、当日の皆様への問い合わせを激減させ、ゆとりを持ってまつり全体を見渡せる状態を作りたいのです。",
        /* 06 */ "では、情報を提供していただくと幹事の皆様にどんなメリットがあるのかをご説明します。第一に、当日の電話確認が激減します。「機材がどこにあるか」「誰が鍵を持っているか」は本部オペレーターが画面を見れば一発で分かるので、幹事の皆様へ電話を取り次ぐ必要がなくなります。現場スタッフも自分のスマホで確認できるため、幹事への質問が格段に減ります。",
        /* 07 */ "メリットの二つ目は、スタッフの出欠管理です。当日の朝、紙の名簿を見ながら「○○さん来てる？」「まだ来てない！」と幹事さんが探して電話をかける光景をよく見かけます。事前に名簿とシフトを預けていただければ、スタッフはQRコードで出勤ボタンを押すだけ。誰が来ていないかは本部画面に即座に出るため、確認電話も本部で代行できます。",
        /* 08 */ "三つ目は、機材と駐車場のトラブル防止です。特に貸切駐車場は地主さんとの約束時間があり、1分でも早く置いたり遅く撤収したりすると来年借りられなくなるリスクがあります。また、カラーコーンや給水設備も「誰かが勝手に持っていって足りなくなった」という事故が多発します。幹事の皆様が把握しているルールや保管場所を共有していただければ、システム上で制約違反を防止できます。",
        /* 09 */ "そして四つ目のメリットが、次年度への引き継ぎです。お祭りが終わった後、疲れた体で引き継ぎノートや報告書を作るのは大変な負担です。今年一度システムに情報を集めてしまえば、来年度はボタン一つで今年の全データをコピーできます。「去年は何時に何を準備したか」が完全に残るため、後任の方に引き継ぐ際も「この画面を見て」で完了します。",
        /* 10 */ "ここからは、集めた情報が実際にどう使われるかを画面でお見せします。まずこちらは本部マトリクス画面です。皆様からいただいたタスクや時間がここに並びます。何時からどのレーンで何をするかが一目で分かり、進んでいるタスクは緑色、注意が必要なものは黄色と色が変わります。本部員全員が同じ画面を見ているので、誰でも現状を把握できます。",
        /* 11 */ "こちらは現場のボランティアスタッフが見るスマートフォンの画面です。事前のアプリインストールや難しいログインは一切不要です。当日配るスタッフ証のQRコードをカメラで読み取るだけで、「自分の今日の予定」がすぐ出ます。「次は何をすればいいですか？」と幹事さんに聞きに来る必要がなくなる仕組みです。",
        /* 12 */ "名簿情報を共有していただければ、本部システムからワンクリックでこのようなスタッフ証が印刷できます。名前や所属だけでなく、その人専用のQRコードも印刷されているため、前日や当日の朝に配るだけで、スタッフ管理の準備がすべて整います。",
        /* 13 */ "それでは、本日一番お願いしたい本題です。幹事の皆様に共有していただきたい情報は、大きく分けてこの3点です。1つ目は担当業務のタイムスケジュールと手順。2つ目は手伝ってくれるスタッフの名簿と参加時間。3つ目は管理している機材の場所や駐車場の制限ルールです。「こんなことまで共有する必要ある？」と思われる小さなルールほど、当日のトラブル防止に直結します。",
        /* 14 */ "具体的にはこのような内容です。例えば交通警備担当の幹事様なら「通行止め直後にどの交差点からコーンを置くか」、会場設営担当の幹事様なら「番所地下のどこに何の機材があるか」、出店担当の幹事様なら「給水設備の配置ルール」などです。すでに頭の中や手帳にある情報をそのまま教えていただければ大丈夫です。",
        /* 15 */ "「システムに登録してくださいと言われても、パソコンを打つ時間がないし面倒だ」と思われるかもしれません。ご安心ください。直接入力していただく必要は一切ありません。お手元の手書きメモを写メでLINEしていただくだけでも、使い慣れたExcelファイルをそのまま送っていただくだけでも結構です。事務局がすべてシステムに入力しますので、幹事の皆様には「これで合ってるよ」と確認していただくだけで大丈夫です。",
        /* 16 */ "本番までのスケジュールです。本日趣旨をお伝えしましたので、今週から来週にかけてお手元の情報をお寄せいただきたいと考えております。集まった情報を事務局で入力し、次回の幹事会では「皆様の情報がシステム上に綺麗に入った状態」をプロジェクターで見ていただきます。「ここを直してほしい」という修正を受け付け、本番を迎えます。",
        /* 17 */ "最後にお伝えしたいのは、この取り組みは幹事の皆様の負担を減らし、皆様自身がお祭りを誇らしく、ゆとりを持って楽しんでいただくためのものだということです。皆様の頭の中にあるノウハウをお借りできれば、必ず当日驚くほどスムーズに運営が回ります。どうかご理解とご協力をよろしくお願い申し上げます。",
        /* 18 */ "以上で説明を終わります。ご清聴ありがとうございました。「自分の部会のこの情報はどこに送ればいい？」「こういうやり方はできる？」など、気になる点やご不安があれば何でもご質問ください。"
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
