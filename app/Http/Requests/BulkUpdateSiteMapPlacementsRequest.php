<?php

namespace App\Http\Requests;

use App\Models\SiteBaseMap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateSiteMapPlacementsRequest extends FormRequest
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
            'items.*.width' => ['nullable', 'numeric', 'min:1'],
            'items.*.height' => ['nullable', 'numeric', 'min:1'],
            'items.*.rotation' => ['nullable', 'integer'],
            'items.*.is_rotation_locked' => ['nullable', 'boolean'],
            'items.*.color_theme' => ['nullable', 'string', 'max:30'],
            'items.*.display_text' => ['nullable', 'string', 'max:255'],
        ];
    }
}
