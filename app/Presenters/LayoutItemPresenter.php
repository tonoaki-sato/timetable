<?php

namespace App\Presenters;

use App\Models\LayoutItem;

class LayoutItemPresenter
{
    public function __construct(
        protected LayoutItem $item
    ) {}

    /**
     * 表示用の名称（直接の名称または出店者名）を取得
     */
    public function displayName(): string
    {
        if (! empty($this->item->name)) {
            return (string) $this->item->name;
        }

        if ($this->item->isBooth() && $this->item->vendor !== null && ! empty($this->item->vendor->name)) {
            return (string) $this->item->vendor->name;
        }

        return $this->item->isBooth() ? '(未割当)' : '現場設備';
    }

    /**
     * ステータスバッジのCSSクラスを取得
     */
    public function statusBadgeClass(): string
    {
        return $this->item->status->badgeClasses();
    }

    /**
     * 区分・種別のアイコンを取得
     */
    public function typeIcon(): string
    {
        if ($this->item->isBooth()) {
            return $this->item->boothCategory()?->icon() ?? '🎪';
        }

        return $this->item->facilityType()?->icon() ?? '🚰';
    }

    /**
     * 区分・種別の日本語ラベルを取得
     */
    public function typeLabel(): string
    {
        if ($this->item->isBooth()) {
            return $this->item->boothCategory()?->label() ?? '出店';
        }

        return $this->item->facilityType()?->label() ?? '設備';
    }

    /**
     * 区分・種別バッジのCSSクラスを取得
     */
    public function typeBadgeClasses(): string
    {
        if ($this->item->isBooth()) {
            return $this->item->boothCategory()?->badgeClasses() ?? 'bg-indigo-50 text-indigo-700 border-indigo-200';
        }

        return $this->item->facilityType()?->badgeClasses() ?? 'bg-cyan-50 text-cyan-800 border-cyan-200';
    }
}
