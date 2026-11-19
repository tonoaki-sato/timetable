<?php

namespace App\Http\Requests;

use App\Models\Vendor;
use App\Support\Validation\VendorValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $vendor = $this->route('vendor');

        return $vendor instanceof Vendor
            ? ($this->user()?->can('update', $vendor) ?? false)
            : ($this->user()?->can('update', Vendor::class) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return VendorValidationRules::rules();
    }

    /**
     * 属性名の日本語化
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return VendorValidationRules::attributes();
    }
}
