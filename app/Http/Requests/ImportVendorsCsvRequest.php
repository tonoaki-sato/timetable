<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportVendorsCsvRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && (bool) $this->user()->is_active;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
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
            'csv_file' => 'CSVファイル',
        ];
    }
}
