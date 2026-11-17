<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSiteMapIconRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'icon_key' => ['nullable', 'string', 'max:50', 'alpha_dash', 'unique:site_map_icon_definitions,icon_key'],
            'category' => ['required', 'string', 'in:booth,tent,vehicle,facility,area,note,other'],
            'width_meters' => ['required', 'numeric', 'min:0.5', 'max:100'],
            'height_meters' => ['required', 'numeric', 'min:0.5', 'max:100'],
            'is_resizable' => ['nullable', 'boolean'],
            'is_rotatable' => ['nullable', 'boolean'],
            'rotation_step' => ['nullable', 'integer', 'in:45,90,180'],
            'has_hygiene_circle' => ['nullable', 'boolean'],
            'is_hygiene_target' => ['nullable', 'boolean'],
            'default_color' => ['nullable', 'string', 'max:30'],
            'png_file' => ['required', 'file', 'mimes:png', 'max:5120'],
        ];
    }
}
