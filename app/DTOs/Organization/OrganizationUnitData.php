<?php

namespace App\DTOs\Organization;

readonly class OrganizationUnitData
{
    /**
     * @param  array<int, int|array{staff_id: int, role?: ?string, display_order?: int}>  $staffAssignments
     */
    public function __construct(
        public string $name,
        public ?int $parentId = null,
        public ?string $shortDescription = null,
        public ?string $description = null,
        public array $staffAssignments = [],
    ) {}

    /**
     * バリデーション済み配列からDTOを生成
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            parentId: isset($data['parent_id']) && $data['parent_id'] !== '' ? (int) $data['parent_id'] : null,
            shortDescription: isset($data['short_description']) && $data['short_description'] !== '' ? (string) $data['short_description'] : null,
            description: isset($data['description']) && $data['description'] !== '' ? (string) $data['description'] : null,
            staffAssignments: (array) ($data['staff_assignments'] ?? $data['staff_ids'] ?? []),
        );
    }

    /**
     * モデル保存用配列に変換
     *
     * @return array{
     *     name: string,
     *     parent_id: ?int,
     *     short_description: ?string,
     *     description: ?string
     * }
     */
    public function toModelAttributes(): array
    {
        return [
            'name' => $this->name,
            'parent_id' => $this->parentId,
            'short_description' => $this->shortDescription,
            'description' => $this->description,
        ];
    }
}
