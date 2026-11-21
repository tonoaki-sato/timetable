<?php

namespace App\Http\Requests;

use App\Models\SiteBaseMap;
use App\Support\Validation\SiteBaseMapValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSiteBaseMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SiteBaseMap::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(SiteBaseMapValidationRules::prepareInput($this));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return SiteBaseMapValidationRules::rules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return SiteBaseMapValidationRules::attributes();
    }
}
