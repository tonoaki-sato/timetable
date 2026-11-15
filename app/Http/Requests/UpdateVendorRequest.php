<?php

namespace App\Http\Requests;

use App\Enums\BoothCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorRequest extends FormRequest
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
            'booths' => ['nullable', 'array'],
            'booths.*.id' => ['required_with:booths', 'integer', 'exists:layout_booths,id'],
            'booths.*.category' => ['nullable', Rule::enum(BoothCategory::class)],
            'booths.*.product_items' => ['nullable', 'string', 'max:1000'],
            'booths.*.unassign' => ['nullable', 'boolean'],
            'add_booth_id' => ['nullable', 'integer', 'exists:layout_booths,id'],
            'add_category' => ['nullable', Rule::enum(BoothCategory::class)],
            'add_product_items' => ['nullable', 'string', 'max:1000'],
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
            'add_booth_id' => '追加割当コマ',
            'add_category' => '追加出店区分',
            'add_product_items' => '追加販売品目',
        ];
    }
}
