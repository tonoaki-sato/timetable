<?php

namespace App\Http\Requests\ConeMap;

use App\Enums\BarricadeType;
use App\Models\SiteBaseMap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConeMapBarricadeRequest extends FormRequest
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
            'barricade_type' => ['nullable', 'string', Rule::in(BarricadeType::values())],
            'pos_x' => ['nullable', 'numeric'],
            'pos_y' => ['nullable', 'numeric'],
            'rotation' => ['nullable', 'integer', 'min:0', 'max:359'],
            'label' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
