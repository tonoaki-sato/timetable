<?php

namespace App\DTOs;

use App\Support\TableQueryOptions;

readonly class LayoutItemFilterDTO
{
    public function __construct(
        public ?string $itemType = null,
        public ?int $areaId = null,
        public ?string $category = null,
        public ?string $search = null,
        public ?string $sort = null,
        public ?string $direction = null,
        public int $perPage = 50,
    ) {}

    /**
     * リクエスト等の配列データからインスタンスを生成
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            itemType: isset($data['item_type']) && $data['item_type'] !== '' ? (string) $data['item_type'] : null,
            areaId: isset($data['area_id']) && $data['area_id'] !== '' ? (int) $data['area_id'] : null,
            category: isset($data['category']) && $data['category'] !== '' ? (string) $data['category'] : null,
            search: isset($data['search']) && $data['search'] !== '' ? (string) $data['search'] : null,
            sort: isset($data['sort']) && $data['sort'] !== '' ? (string) $data['sort'] : null,
            direction: isset($data['direction']) && $data['direction'] !== '' ? (string) $data['direction'] : null,
            perPage: TableQueryOptions::resolvePerPage($data['per_page'] ?? null, 50, [25, 50, 100]),
        );
    }
}
