<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteMapIconRequest extends FormRequest
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
            'category' => ['required', 'string', 'in:booth,tent,vehicle,facility,area,note,other'],
            'width_meters' => ['required', 'numeric', 'min:0.5', 'max:100'],
            'height_meters' => ['required', 'numeric', 'min:0.5', 'max:100'],
            'is_resizable' => ['nullable', 'boolean'],
            'is_rotatable' => ['nullable', 'boolean'],
            'rotation_step' => ['nullable', 'integer', 'in:45,90,180'],
            'has_hygiene_circle' => ['nullable', 'boolean'],
            'is_hygiene_target' => ['nullable', 'boolean'],
            'default_color' => ['nullable', 'string', 'max:30'],
            'png_file' => ['nullable', 'file', 'mimes:png', 'max:5120'],
        ];
    }
}
