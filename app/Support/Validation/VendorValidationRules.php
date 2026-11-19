<?php

namespace App\Support\Validation;

use Illuminate\Contracts\Validation\ValidationRule;

class VendorValidationRules
{
    /**
     * 出展・出店者の共通バリデーションルールを取得
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * 属性名の日本語化配列を取得
     *
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'name' => '出展・出店者名',
            'contact_person' => '窓口担当者名',
            'contact' => '連絡先',
            'notes' => '特記事項・備考',
        ];
    }
}
