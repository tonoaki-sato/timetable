<?php

namespace App\Http\Requests;

use App\Models\Lane;
use App\Presenters\LanePresenter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLaneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Lane::class) ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $icon = $this->input('icon');
        $description = $this->input('description');

        $this->merge([
            'name' => is_string($name) ? trim($name) : $name,
            'icon' => (is_string($icon) && trim($icon) === '') ? null : (is_string($icon) ? trim($icon) : $icon),
            'description' => (is_string($description) && trim($description) === '') ? null : $description,
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:lanes,name'],
            'color' => ['required', 'string', Rule::in(array_keys(LanePresenter::COLOR_OPTIONS))],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
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
