<?php

namespace App\DTOs;

use App\Support\TableQueryOptions;

readonly class ScheduleFilterDTO
{
    public function __construct(
        public ?string $category = null,
        public ?string $status = null,
        public ?string $search = null,
        public ?int $staffId = null,
        public string $tab = 'upcoming',
        public int $perPage = 100,
        public ?string $sort = null,
        public ?string $direction = null,
    ) {}

    /**
     * リクエスト等の配列データからインスタンスを生成
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            category: isset($data['category']) && $data['category'] !== '' ? (string) $data['category'] : null,
            status: isset($data['status']) && $data['status'] !== '' ? (string) $data['status'] : null,
            search: isset($data['search']) && $data['search'] !== '' ? (string) $data['search'] : null,
            staffId: isset($data['staff_id']) && $data['staff_id'] !== '' ? (int) $data['staff_id'] : null,
            tab: isset($data['tab']) && $data['tab'] !== '' ? (string) $data['tab'] : 'upcoming',
            perPage: TableQueryOptions::resolvePerPage($data['per_page'] ?? null, 100, [25, 50, 100]),
            sort: isset($data['sort']) && $data['sort'] !== '' ? (string) $data['sort'] : null,
            direction: isset($data['direction']) && $data['direction'] !== '' ? (string) $data['direction'] : null,
        );
    }
}
