<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPlan;
use App\Models\LogisticsLocation;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentLogisticsPlan>
 */
class EquipmentLogisticsPlanFactory extends Factory
{
    protected $model = EquipmentLogisticsPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipment_id' => Equipment::factory(),
            'target_date' => '2026-10-10',
            'day_phase' => 'pre_day',
            'action_type' => 'staging_move',
            'title' => fake()->randomElement(['倉庫出し・中継移動', '業者受取・立会', '現場設置', '夜間保管移動', '倉庫返還・格納', 'イベント会社搬入設営']),
            'quantity' => fake()->numberBetween(1, 20),
            'start_time' => '13:00',
            'end_time' => '14:00',
            'from_location' => '番所地下倉庫',
            'to_location' => '税務署前駐車場',
            'from_location_id' => LogisticsLocation::factory(),
            'to_location_id' => LogisticsLocation::factory(),
            'operator_type' => 'committee',
            'witness_required' => false,
            'witness_staff_id' => null,
            'contact_info' => null,
            'status' => 'planned',
            'notes' => fake()->optional()->sentence(),
            'display_order' => fake()->numberBetween(1, 10),
        ];
    }

    /**
     * 立会必須
     */
    public function withWitness(): static
    {
        return $this->state(fn (array $attributes) => [
            'witness_required' => true,
            'witness_staff_id' => Staff::factory(),
            'contact_info' => '090-0000-0000 (ドライバー連絡先)',
        ]);
    }

    /**
     * イベント会社作業
     */
    public function eventCompany(): static
    {
        return $this->state(fn (array $attributes) => [
            'operator_type' => 'event_company',
            'witness_required' => false,
            'action_type' => 'vendor_setup',
        ]);
    }
}
