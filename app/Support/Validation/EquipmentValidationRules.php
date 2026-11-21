<?php

namespace App\Support\Validation;

use App\Enums\EquipmentCheckoutRule;
use App\Enums\EquipmentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class EquipmentValidationRules
{
    /**
     * 資機材マスターの共通バリデーションルールを取得
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(EquipmentType::class)],
            'checkout_rule' => ['nullable', Rule::enum(EquipmentCheckoutRule::class)],
            'quantity' => ['required', 'integer', 'min:1'],
            'is_approximate_quantity' => ['boolean'],
            'unit' => ['required', 'string', 'max:20'],
            'storage_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:0'],
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
            'name' => '備品名',
            'type' => '区分',
            'checkout_rule' => '持ち出しルール',
            'quantity' => '基準数量',
            'is_approximate_quantity' => '概数フラグ',
            'unit' => '単位',
            'storage_location_id' => '定常保管場所',
            'description' => '備考',
        ];
    }
}
