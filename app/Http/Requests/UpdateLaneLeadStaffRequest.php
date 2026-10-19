<?php

namespace App\Http\Requests;

use App\Models\Lane;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLaneLeadStaffRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Lane|null $lane */
        $lane = $this->route('lane');

        return $this->user()?->can('update', $lane ?? Lane::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lead_staff_id' => ['nullable', 'integer', 'exists:staff,id'],
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
            'lead_staff_id' => '主担当スタッフ',
        ];
    }
}
