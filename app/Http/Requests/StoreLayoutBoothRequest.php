<?php

namespace App\Http\Requests;

use App\Enums\BoothCategory;
use App\Enums\BoothStatus;
use App\Enums\BoothType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLayoutBoothRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'booth_code' => ['required', 'string', 'max:30'],
            'layout_area_id' => ['nullable', 'exists:layout_areas,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'category' => ['required', Rule::enum(BoothCategory::class)],
            'booth_type' => ['required', Rule::enum(BoothType::class)],
            'status' => ['required', Rule::enum(BoothStatus::class)],
            'product_items' => ['nullable', 'string', 'max:1000'],
            'adjacent_landmark' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * バリデーションエラーメッセージのカスタマイズ
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'booth_code' => 'コマ番号',
            'layout_area_id' => '所属エリア',
            'name' => 'ブース表示名・屋号',
            'category' => '出店区分',
            'booth_type' => '形態',
            'status' => '配置状態',
            'vendor_id' => '出展・出店者',
            'product_items' => '取扱品目・展示内容',
            'adjacent_landmark' => '近接店舗・建物',
            'notes' => '特記事項・メモ',
        ];
    }
}
