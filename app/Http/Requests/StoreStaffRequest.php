<?php

namespace App\Http\Requests;

use App\Models\Staff;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
{
    /**
     * 認可判定
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Staff::class) ?? false;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'kana' => ['nullable', 'string', 'max:255'],
            'affiliation' => ['nullable', 'string', 'max:100'],
            'staff_type' => ['nullable', 'string', 'in:fixed,fulfillment'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * 属性名の日本語化
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '氏名',
            'kana' => 'フリガナ',
            'affiliation' => '所属団体・地域会等',
            'staff_type' => 'スタッフ区分',
            'is_active' => '稼働可能フラグ',
        ];
    }
}
