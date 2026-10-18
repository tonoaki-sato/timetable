<?php

namespace Tests\Unit;

use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableTaskTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 人員ラベルが正しくフォーマットされること
     */
    public function test_required_people_label_formatting(): void
    {
        $fixedTask = new TimetableTask([
            'required_people_count' => 3,
            'is_at_least' => false,
        ]);
        $this->assertSame('3名', $fixedTask->required_people_label);

        $atLeastTask = new TimetableTask([
            'required_people_count' => 3,
            'is_at_least' => true,
        ]);
        $this->assertSame('3名以上', $atLeastTask->required_people_label);
    }

    /**
     * 固定人数および「〇〇名以上」の場合の充足判定と不足人数計算が正確であること
     */
    public function test_staffing_satisfaction_and_shortage_calculation(): void
    {
        $timetable = Timetable::factory()->create();

        // 1. 固定3名のタスク
        $fixedTask = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'required_people_count' => 3,
            'is_at_least' => false,
        ]);

        $this->assertSame(0, $fixedTask->assigned_count);
        $this->assertFalse($fixedTask->is_satisfied);
        $this->assertSame(3, $fixedTask->shortage_count);

        // 2名アサイン
        $staff1 = Staff::factory()->create();
        $staff2 = Staff::factory()->create();
        TaskStaffAssignment::create([
            'timetable_task_id' => $fixedTask->id,
            'staff_id' => $staff1->id,
            'attendance_status' => 'assigned',
        ]);
        TaskStaffAssignment::create([
            'timetable_task_id' => $fixedTask->id,
            'staff_id' => $staff2->id,
            'attendance_status' => 'assigned',
        ]);

        $fixedTask->unsetRelation('assignments');
        $this->assertSame(2, $fixedTask->assigned_count);
        $this->assertFalse($fixedTask->is_satisfied);
        $this->assertSame(1, $fixedTask->shortage_count);

        // 3人目をアサイン -> 充足
        $staff3 = Staff::factory()->create();
        TaskStaffAssignment::create([
            'timetable_task_id' => $fixedTask->id,
            'staff_id' => $staff3->id,
            'attendance_status' => 'assigned',
        ]);

        $fixedTask->unsetRelation('assignments');
        $this->assertSame(3, $fixedTask->assigned_count);
        $this->assertTrue($fixedTask->is_satisfied);
        $this->assertSame(0, $fixedTask->shortage_count);

        // 2. 「3名以上」のタスク
        $atLeastTask = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'required_people_count' => 3,
            'is_at_least' => true,
        ]);

        // 4名アサイン（超過してもOK・充足判定はTrue）
        $staff4 = Staff::factory()->create();
        TaskStaffAssignment::create(['timetable_task_id' => $atLeastTask->id, 'staff_id' => $staff1->id, 'attendance_status' => 'assigned']);
        TaskStaffAssignment::create(['timetable_task_id' => $atLeastTask->id, 'staff_id' => $staff2->id, 'attendance_status' => 'assigned']);
        TaskStaffAssignment::create(['timetable_task_id' => $atLeastTask->id, 'staff_id' => $staff3->id, 'attendance_status' => 'assigned']);
        TaskStaffAssignment::create(['timetable_task_id' => $atLeastTask->id, 'staff_id' => $staff4->id, 'attendance_status' => 'assigned']);

        $atLeastTask->unsetRelation('assignments');
        $this->assertSame(4, $atLeastTask->assigned_count);
        $this->assertTrue($atLeastTask->is_satisfied);
        $this->assertSame(0, $atLeastTask->shortage_count);

        // 1名が欠席（absent）になった場合 -> 3名となり依然充足
        TaskStaffAssignment::where('timetable_task_id', $atLeastTask->id)
            ->where('staff_id', $staff4->id)
            ->update(['attendance_status' => 'absent']);

        $atLeastTask->unsetRelation('assignments');
        $this->assertSame(3, $atLeastTask->assigned_count);
        $this->assertTrue($atLeastTask->is_satisfied);
    }
}
