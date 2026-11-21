<?php

namespace App\Presenters;

use App\Models\TimetableLane;

class TimetableLanePresenter
{
    public function __construct(
        protected TimetableLane $timetableLane
    ) {}

    /**
     * カラーバッジ表示用のCSSクラス配列を取得（担当班マスター設定に委譲）
     *
     * @return array{badge: string, border: string, bg: string, text: string}
     */
    public function colorClasses(): array
    {
        return LanePresenter::colorClassesFor($this->timetableLane->color);
    }

    /**
     * PDF進行表ヘッダー用の班長表記（例: '（佐藤さん）'）
     */
    public function pdfLeadSuffix(): string
    {
        $leadName = $this->timetableLane->lead_short_name;
        if ($leadName === '') {
            return '';
        }

        return (str_ends_with($leadName, 'さん') || str_ends_with($leadName, '外'))
            ? "（{$leadName}）"
            : "（{$leadName}さん）";
    }
}
