<?php

namespace App\Presenters;

use App\Enums\StaffAttribute;
use App\Models\Staff;

class StaffPresenter
{
    public function __construct(
        protected Staff $staff
    ) {}

    /**
     * スタッフ属性 Enum を取得
     */
    public function attributeEnum(): StaffAttribute
    {
        return $this->staff->staff_attribute_enum;
    }

    /**
     * スタッフ属性の日本語ラベルを取得
     */
    public function attributeLabel(): string
    {
        return $this->attributeEnum()->label();
    }

    /**
     * スタッフ属性バッジの Tailwind CSS クラスを取得
     */
    public function attributeBadgeClasses(): string
    {
        return $this->attributeEnum()->badgeClasses();
    }

    /**
     * 幹事または幹事会かどうか
     */
    public function isExecutiveGroup(): bool
    {
        return $this->attributeEnum()->isExecutiveGroup();
    }

    /**
     * PDF出力用の記号（幹事: ★ / 幹事会: ☆ / それ以外: 空文字）を取得
     */
    public function pdfMark(): string
    {
        return $this->attributeEnum()->pdfMark();
    }

    /**
     * PDF出力用の氏名（幹事・幹事会のみ先頭に記号付与）を取得
     */
    public function pdfDisplayName(): string
    {
        return $this->pdfMark().$this->staff->name;
    }

    /**
     * 画面表示用の属性付き氏名を取得（一般は括弧省略、それ以外は括弧付き）
     */
    public function nameWithAttribute(): string
    {
        $attribute = $this->attributeEnum();
        if ($attribute === StaffAttribute::General) {
            return $this->staff->name;
        }

        return "{$this->staff->name}（{$attribute->label()}）";
    }

    /**
     * 専用マイシフトURLを取得
     */
    public function myShiftUrl(): string
    {
        return route('staff.myshift', ['token' => $this->staff->access_token]);
    }
}
