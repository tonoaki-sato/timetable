<?php

namespace App\Presenters;

use App\Models\Lane;

class LanePresenter
{
    public function __construct(
        protected Lane $lane
    ) {}

    /**
     * カラーバッジ表示用のCSSクラス配列を取得
     *
     * @return array{badge: string, border: string, bg: string, text: string}
     */
    public function colorClasses(): array
    {
        return match ($this->lane->color) {
            'amber' => [
                'badge' => 'bg-amber-100 text-amber-800 border-amber-300',
                'border' => 'border-amber-300',
                'bg' => 'bg-amber-50',
                'text' => 'text-amber-800',
            ],
            'sky' => [
                'badge' => 'bg-sky-100 text-sky-800 border-sky-300',
                'border' => 'border-sky-300',
                'bg' => 'bg-sky-50',
                'text' => 'text-sky-800',
            ],
            'emerald' => [
                'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                'border' => 'border-emerald-300',
                'bg' => 'bg-emerald-50',
                'text' => 'text-emerald-800',
            ],
            'purple' => [
                'badge' => 'bg-purple-100 text-purple-800 border-purple-300',
                'border' => 'border-purple-300',
                'bg' => 'bg-purple-50',
                'text' => 'text-purple-800',
            ],
            'pink' => [
                'badge' => 'bg-pink-100 text-pink-800 border-pink-300',
                'border' => 'border-pink-300',
                'bg' => 'bg-pink-50',
                'text' => 'text-pink-800',
            ],
            'red' => [
                'badge' => 'bg-rose-100 text-rose-800 border-rose-300',
                'border' => 'border-rose-300',
                'bg' => 'bg-rose-50',
                'text' => 'text-rose-800',
            ],
            'orange' => [
                'badge' => 'bg-orange-100 text-orange-800 border-orange-300',
                'border' => 'border-orange-300',
                'bg' => 'bg-orange-50',
                'text' => 'text-orange-800',
            ],
            'indigo' => [
                'badge' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                'border' => 'border-indigo-300',
                'bg' => 'bg-indigo-50',
                'text' => 'text-indigo-800',
            ],
            'teal' => [
                'badge' => 'bg-teal-100 text-teal-800 border-teal-300',
                'border' => 'border-teal-300',
                'bg' => 'bg-teal-50',
                'text' => 'text-teal-800',
            ],
            'yellow' => [
                'badge' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                'border' => 'border-yellow-500',
                'bg' => 'bg-yellow-50',
                'text' => 'text-yellow-800',
            ],
            default => [
                'badge' => 'bg-slate-100 text-slate-800 border-slate-300',
                'border' => 'border-slate-300',
                'bg' => 'bg-slate-50',
                'text' => 'text-slate-800',
            ],
        };
    }

    /**
     * PDF進行表ヘッダー用の班長表記（例: '（佐藤さん）'）
     */
    public function pdfLeadSuffix(): string
    {
        $leadName = $this->lane->lead_short_name;
        if ($leadName === '') {
            return '';
        }

        return (str_ends_with($leadName, 'さん') || str_ends_with($leadName, '外'))
            ? "（{$leadName}）"
            : "（{$leadName}さん）";
    }
}
