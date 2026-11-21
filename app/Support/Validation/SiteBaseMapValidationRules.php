<?php

namespace App\Support\Validation;

use App\Models\SiteBaseMap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class SiteBaseMapValidationRules
{
    /**
     * 白地図マスターの共通バリデーションルールを取得
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function rules(?SiteBaseMap $ignoreMap = null): array
    {
        $svgFileRule = $ignoreMap === null
            ? ['required_without:svg_path', 'nullable', 'file', 'max:10240', self::svgExtensionRule()]
            : ['nullable', 'file', 'max:10240', self::svgExtensionRule()];

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('site_base_maps', 'name')->ignore($ignoreMap),
            ],
            'cad_version' => ['required', 'string', 'max:50'],
            'paper_size' => ['required', 'string', 'max:20'],
            'scale_ratio' => ['required', 'numeric', 'min:0.1', 'max:500'],
            'width_meters' => ['required', 'numeric', 'min:1', 'max:10000'],
            'height_meters' => ['required', 'numeric', 'min:1', 'max:10000'],
            'viewbox' => ['nullable', 'string', 'max:50', 'regex:/^\s*-?\d+(?:\.\d+)?\s+-?\d+(?:\.\d+)?\s+\d+(?:\.\d+)?\s+\d+(?:\.\d+)?\s*$/'],
            'svg_file' => $svgFileRule,
            'svg_path' => ['nullable', 'string', 'max:255', self::safeAssetPathRule('svg')],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'pdf_path' => ['nullable', 'string', 'max:255', self::safeAssetPathRule('pdf')],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * 属性名の日本語化配列を取得
     *
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'name' => '白地図名称',
            'cad_version' => 'CAD版数',
            'paper_size' => '用紙サイズ',
            'scale_ratio' => '縮尺比率',
            'width_meters' => '実空間横幅',
            'height_meters' => '実空間縦幅',
            'viewbox' => 'SVG viewBox',
            'svg_file' => 'SVG白地図ファイル',
            'svg_path' => 'SVGファイルパス',
            'pdf_file' => 'PDF原本ファイル',
            'pdf_path' => 'PDF原本パス',
            'is_default' => 'デフォルト設定',
            'is_active' => '有効フラグ',
            'notes' => '特記事項・CADメモ',
        ];
    }

    /**
     * バリデーション前処理用の正規化配列を生成
     *
     * @return array<string, mixed>
     */
    public static function prepareInput(Request $request): array
    {
        $merged = [
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ];

        foreach (['svg_path', 'pdf_path', 'viewbox'] as $nullableStrField) {
            if ($request->has($nullableStrField)) {
                $val = $request->input($nullableStrField);
                $trimmed = is_string($val) ? trim($val) : null;
                $merged[$nullableStrField] = ($trimmed === null || $trimmed === '') ? null : $trimmed;
            }
        }

        return $merged;
    }

    /**
     * SVGファイルの拡張子検証クロージャ
     */
    protected static function svgExtensionRule(): \Closure
    {
        return static function (string $attribute, mixed $value, \Closure $fail): void {
            if (! $value instanceof UploadedFile) {
                return;
            }

            if (strtolower($value->getClientOriginalExtension()) !== 'svg') {
                $fail('SVG白地図ファイルには .svg 形式のファイルを指定してください。');
            }
        };
    }

    /**
     * 既存アセットパス指定時のパストラバーサル防止・実ファイル検証クロージャ
     */
    protected static function safeAssetPathRule(string $extension): \Closure
    {
        return static function (string $attribute, mixed $value, \Closure $fail) use ($extension): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            if (str_starts_with($value, '/') || str_contains($value, '..') || str_contains($value, '\\')) {
                $fail('ファイルパスに不正な文字またはトラバーサルが含まれています。');

                return;
            }

            if (! str_starts_with($value, 'maps/')) {
                $fail('ファイルパスは maps/ で始まる相対パスを指定してください。');

                return;
            }

            if (strtolower(pathinfo($value, PATHINFO_EXTENSION)) !== strtolower($extension)) {
                $fail("ファイルの拡張子は .{$extension} である必要があります。");

                return;
            }

            $mapsRealRoot = realpath(public_path('maps'));
            $targetReal = realpath(public_path($value));
            if ($mapsRealRoot === false
                || $targetReal === false
                || ! str_starts_with($targetReal, $mapsRealRoot.DIRECTORY_SEPARATOR)
                || ! is_file($targetReal)
            ) {
                $fail('指定されたファイルが存在しません。');
            }
        };
    }
}
