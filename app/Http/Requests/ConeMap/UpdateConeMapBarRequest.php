<?php

namespace App\Http\Requests\ConeMap;

use App\Enums\ConeBarStyle;
use App\Models\SiteBaseMap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConeMapBarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', SiteBaseMap::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bar_style' => ['nullable', 'string', Rule::in(ConeBarStyle::values())],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
