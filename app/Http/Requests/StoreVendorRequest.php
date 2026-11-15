<?php

namespace App\Http\Requests;

use App\Enums\BoothCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorRequest extends FormRequest
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
            'representative_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'initial_booth_id' => ['nullable', 'integer', 'exists:layout_booths,id'],
            'initial_category' => ['nullable', Rule::enum(BoothCategory::class)],
            'initial_product_items' => ['nullable', 'string', 'max:1000'],
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
            'name' => '出店名・屋号',
            'representative_name' => '出店代表者氏名',
            'emergency_contact' => '緊急連絡先',
            'notes' => '備考',
            'initial_booth_id' => '割当コマ',
            'initial_category' => '出店区分',
            'initial_product_items' => '販売品目',
        ];
    }
}
