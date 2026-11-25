<?php

namespace App\Http\Requests\ConeMap;

use App\Enums\ConeColor;
use App\Models\SiteBaseMap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateConeMapConesRequest extends FormRequest
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
            'items.*.color' => ['nullable', 'string', Rule::in(ConeColor::values())],
        ];
    }
}
