<?php

namespace App\Http\Requests;

use App\Models\OrganizationUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReorderOrganizationUnitsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', OrganizationUnit::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ];
    }

    /**
     * バリデーション後の整合性チェック（単一 IN クエリで存在確認）
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $uniqueIds = array_values(array_unique($this->orderedIds()));
                if ($uniqueIds !== [] && OrganizationUnit::whereIn('id', $uniqueIds)->count() !== count($uniqueIds)) {
                    $validator->errors()->add('ids', '指定された組織の一部が見つかりません。');
                }
            },
        ];
    }

    /**
     * 整数化済みの並び替えIDリストを取得
     *
     * @return array<int, int>
     */
    public function orderedIds(): array
    {
        $raw = $this->input('ids', []);
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_map('intval', $raw));
    }
}
