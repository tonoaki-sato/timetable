<?php

namespace App\Http\Requests;

use App\Models\TimetableLane;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkStoreTimetableLanesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', TimetableLane::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lane_ids' => ['required', 'array', 'min:1'],
            'lane_ids.*' => ['required', 'integer', 'distinct', Rule::exists('lanes', 'id')->where('is_active', true)],
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
            'lane_ids' => '追加する担当班',
            'lane_ids.*' => '追加する担当班',
        ];
    }

    /**
     * カスタムエラーメッセージ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lane_ids.required' => '追加する担当班を1つ以上選択してください。',
            'lane_ids.min' => '追加する担当班を1つ以上選択してください。',
        ];
    }
}
