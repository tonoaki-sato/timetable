<?php

namespace App\Http\Requests;

use App\Models\OrganizationUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationUnitRequest extends FormRequest
{
    /**
     * 認可判定
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', OrganizationUnit::class) ?? false;
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
            'parent_id' => ['nullable', 'integer', 'exists:organization_units,id'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'staff_assignments' => ['nullable', 'array'],
            'staff_assignments.*.staff_id' => ['required', 'integer', 'exists:staff,id'],
            'staff_assignments.*.role' => ['nullable', 'string', 'max:100'],
            'staff_assignments.*.display_order' => ['nullable', 'integer'],
            'staff_ids' => ['nullable', 'array'],
            'staff_ids.*' => ['integer', 'exists:staff,id'],
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
            'name' => '名前',
            'parent_id' => '親組織',
            'short_description' => '説明',
            'description' => '詳細説明',
            'staff_assignments' => '担当スタッフ',
        ];
    }
}

