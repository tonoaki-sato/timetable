<?php

namespace App\Http\Requests\ConeMap;

use App\Enums\BarricadeType;
use App\Models\SiteBaseMap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateConeMapBarricadesRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.pos_x' => ['nullable', 'numeric'],
            'items.*.pos_y' => ['nullable', 'numeric'],
            'items.*.rotation' => ['nullable', 'integer', 'min:0', 'max:359'],
            'items.*.barricade_type' => ['nullable', 'string', Rule::in(BarricadeType::values())],
        ];
    }
}
