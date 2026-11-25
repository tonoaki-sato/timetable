<?php

namespace App\Http\Requests\ConeMap;

use App\Enums\ConeColor;
use App\Models\SiteBaseMap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConeMapConeRequest extends FormRequest
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
            'pos_x' => ['nullable', 'numeric'],
            'pos_y' => ['nullable', 'numeric'],
            'color' => ['nullable', 'string', Rule::in(ConeColor::values())],
            'label' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
