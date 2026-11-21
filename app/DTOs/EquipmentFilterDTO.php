<?php

namespace App\DTOs;

use App\Support\TableQueryOptions;

readonly class EquipmentFilterDTO
{
    public function __construct(
        public ?string $type = null,
        public ?string $checkoutRule = null,
        public ?string $keyword = null,
        public ?string $sort = null,
        public ?string $direction = null,
        public int $perPage = 20,
    ) {}

    /**
     * リクエスト等の配列データからインスタンスを生成
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $resolvedSort = TableQueryOptions::resolveSort(
            isset($data['sort']) && $data['sort'] !== '' ? (string) $data['sort'] : null,
            isset($data['direction']) && $data['direction'] !== '' ? (string) $data['direction'] : null,
            ['display_order', 'name', 'type', 'checkout_rule', 'quantity', 'storage_location_id'],
            'display_order',
            'asc',
        );

        return new self(
            type: ! empty($data['type']) ? (string) $data['type'] : null,
            checkoutRule: ! empty($data['checkout_rule']) ? (string) $data['checkout_rule'] : null,
            keyword: ! empty($data['keyword']) ? trim((string) $data['keyword']) : null,
            sort: $resolvedSort['sort'],
            direction: $resolvedSort['direction'],
            perPage: TableQueryOptions::resolvePerPage($data['per_page'] ?? null, 20, [20, 50, 100]),
        );
    }
}
