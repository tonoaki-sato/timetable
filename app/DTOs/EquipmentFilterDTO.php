<?php

namespace App\DTOs;

readonly class EquipmentFilterDTO
{
    public function __construct(
        public ?string $type = null,
        public ?string $checkoutRule = null,
        public ?string $keyword = null,
    ) {}

    /**
     * リクエスト等の配列データからインスタンスを生成
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: ! empty($data['type']) ? (string) $data['type'] : null,
            checkoutRule: ! empty($data['checkout_rule']) ? (string) $data['checkout_rule'] : null,
            keyword: ! empty($data['keyword']) ? trim((string) $data['keyword']) : null,
        );
    }
}
