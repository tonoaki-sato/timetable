<?php

namespace App\Http\Requests\ConeMap;

use App\Enums\BarricadeType;
use App\Models\SiteBaseMap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConeMapBarricadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SiteBaseMap::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'barricade_type' => ['required', 'string', Rule::in(BarricadeType::values())],
            'pos_x' => ['required', 'numeric'],
            'pos_y' => ['required', 'numeric'],
            'rotation' => ['nullable', 'integer', 'min:0', 'max:359'],
            'label' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
