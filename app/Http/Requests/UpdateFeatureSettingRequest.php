<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFeatureSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user && $user->is_active && ($user->isAdmin() || $user->isOperator()));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'is_enabled' => ['required', 'boolean'],
            'opened_at' => ['nullable', 'date'],
            'closed_at' => ['nullable', 'date', 'after_or_equal:opened_at'],
            'notice_message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'is_enabled' => '有効/無効',
            'opened_at' => '利用可能開始日時',
            'closed_at' => '利用可能終了日時',
            'notice_message' => '案内メッセージ',
        ];
    }
}
