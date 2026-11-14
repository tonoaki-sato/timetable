<?php

namespace App\Http\Requests;

use App\Models\GozaStore;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGozaStoreRequest extends FormRequest
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
            'store_name' => ['required', 'string', 'max:255'],
            'representative_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:50'],
            'product_items' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(array_keys(GozaStore::STATUSES))],
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
            'store_name' => '出店名・屋号',
            'representative_name' => '出店代表者氏名',
            'emergency_contact' => '緊急連絡先',
            'product_items' => '販売品目',
            'status' => '出店ステータス',
            'notes' => '備考',
        ];
    }
}
