<?php

namespace App\Http\Requests;

use App\Models\Lane;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLaneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $lane = $this->route('lane');

        return $lane instanceof Lane
            ? ($this->user()?->can('update', $lane) ?? false)
            : ($this->user()?->can('update', Lane::class) ?? false);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('name') && is_string($this->input('name'))) {
            $merge['name'] = trim($this->input('name'));
        }

        if ($this->has('icon')) {
            $icon = $this->input('icon');
            $merge['icon'] = (is_string($icon) && trim($icon) === '') ? null : (is_string($icon) ? trim($icon) : $icon);
        }

        if ($this->has('description')) {
            $description = $this->input('description');
            $merge['description'] = (is_string($description) && trim($description) === '') ? null : $description;
        }

        if ($this->has('is_active')) {
            $merge['is_active'] = $this->boolean('is_active');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $lane = $this->route('lane');
        $laneId = $lane instanceof Lane ? $lane->id : $lane;

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('lanes', 'name')->ignore($laneId)],
            'color' => ['required', 'string', Rule::in(array_keys(Lane::COLOR_OPTIONS))],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * 属性名の日本語化
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '班名',
            'color' => 'テーマカラー',
            'icon' => 'アイコン',
            'description' => '標準役割概要',
            'is_active' => '有効フラグ',
        ];
    }
}
