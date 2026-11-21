<?php

namespace App\Http\Requests;

use App\Models\SiteBaseMap;
use App\Support\Validation\SiteBaseMapValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteBaseMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        $siteBaseMap = $this->route('site_base_map');

        return $siteBaseMap instanceof SiteBaseMap
            ? ($this->user()?->can('update', $siteBaseMap) ?? false)
            : false;
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
        $siteBaseMap = $this->route('site_base_map');

        return SiteBaseMapValidationRules::rules($siteBaseMap instanceof SiteBaseMap ? $siteBaseMap : null);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return SiteBaseMapValidationRules::attributes();
    }
}
