<?php

namespace App\Http\Requests;

use App\Enums\FacilityType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLayoutFacilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
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
            'facility_type' => ['required', Rule::enum(FacilityType::class)],
            'status' => ['required', Rule::in(['予定', '確定'])],
            'location_description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'name' => '設備名称',
            'facility_type' => '設備種別',
            'status' => '設置ステータス',
            'location_description' => '設置場所の目安',
            'notes' => '特記事項',
        ];
    }
}
