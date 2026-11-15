<?php

namespace App\Http\Requests;

use App\Enums\BoothCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLayoutBoothRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:255'],
            'category' => ['required', Rule::enum(BoothCategory::class)],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'goza_store_id' => ['nullable', 'exists:vendors,id'],
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
            'name' => 'ブース表示名・屋号',
            'category' => '出店区分',
            'vendor_id' => '出店者',
            'product_items' => '取扱品目・活動内容',
            'adjacent_landmark' => '近接店舗・建物',
            'notes' => '特記事項・メモ',
        ];
    }
}
