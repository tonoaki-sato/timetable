<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\TimetableTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskStaffAssignment>
 */
class TaskStaffAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'timetable_task_id' => TimetableTask::factory(),
            'staff_id' => Staff::factory(),
            'role_description' => fake()->randomElement(['班長', '誘導員', '記録係', '給水補助', '衛生美化員']),
            'attendance_status' => 'assigned',
            'notes' => null,
        ];
    }

    /**
     * 受付済・現場着
     */
    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'attendance_status' => 'checked_in',
        ]);
    }

    /**
     * 欠席・キャンセル
     */
    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'attendance_status' => 'absent',
        ]);
    }
}
