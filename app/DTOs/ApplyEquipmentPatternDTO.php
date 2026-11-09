<?php

namespace App\DTOs;

readonly class ApplyEquipmentPatternDTO
{
    public function __construct(
        public int $patternId,
        public ?int $stagingLocationId = null,
        public ?int $nightLocation1Id = null,
        public ?int $nightLocation2Id = null,
        public ?int $witnessStaffId = null,
        public bool $overrideExisting = true,
    ) {}

    /**
     * リクエスト等の配列データからインスタンスを生成
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            patternId: (int) $data['pattern_id'],
            stagingLocationId: ! empty($data['staging_location_id']) ? (int) $data['staging_location_id'] : null,
            nightLocation1Id: ! empty($data['night_location_1_id']) ? (int) $data['night_location_1_id'] : null,
            nightLocation2Id: ! empty($data['night_location_2_id']) ? (int) $data['night_location_2_id'] : null,
            witnessStaffId: ! empty($data['witness_staff_id']) ? (int) $data['witness_staff_id'] : null,
            overrideExisting: isset($data['override_existing']) ? (bool) $data['override_existing'] : true,
        );
    }
}
