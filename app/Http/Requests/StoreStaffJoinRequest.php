<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStaffJoinRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'kana' => ['required', 'string', 'max:255'],
            'line_user_id' => ['nullable', 'string', 'max:64'],
            'line_display_name' => ['nullable', 'string', 'max:100'],
            'line_picture_url' => ['nullable', 'string', 'max:500'],
            'manual_line_display_name' => ['nullable', 'string', 'max:100'],
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
            'name' => 'お名前（氏名）',
            'kana' => 'フリガナ',
            'line_display_name' => 'LINE表示名',
            'manual_line_display_name' => 'LINE表示名',
        ];
    }
}
