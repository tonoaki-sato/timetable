<?php

namespace App\DTOs;

use App\Support\TableQueryOptions;

readonly class VendorFilterDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $sort = null,
        public ?string $direction = null,
        public int $perPage = 15,
    ) {}

    /**
     * リクエスト等の配列データからインスタンスを生成
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            search: isset($data['search']) && $data['search'] !== '' ? (string) $data['search'] : null,
            sort: isset($data['sort']) && $data['sort'] !== '' ? (string) $data['sort'] : null,
            direction: isset($data['direction']) && $data['direction'] !== '' ? (string) $data['direction'] : null,
            perPage: TableQueryOptions::resolvePerPage($data['per_page'] ?? null, 15, [15, 30, 50, 100]),
        );
    }
}
