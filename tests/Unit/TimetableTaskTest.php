<?php

namespace Tests\Unit;

use App\Enums\TimetableTaskType;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Support\TimeSlotHelper;
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
        $this->assertSame('3名', $fixedTask->presenter()->requiredPeopleLabel());

        $atLeastTask = new TimetableTask([
            'required_people_count' => 3,
            'is_at_least' => true,
        ]);
        $this->assertSame('3名以上', $atLeastTask->presenter()->requiredPeopleLabel());
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

    /**
     * タスク種別の重複許容判定が正しく動作すること
     */
    public function test_task_type_overlap_rules(): void
    {
        $this->assertTrue(TimetableTaskType::Routine->allowsOverlapWith(TimetableTaskType::Spot));
        $this->assertTrue(TimetableTaskType::Spot->allowsOverlapWith(TimetableTaskType::Routine));
        $this->assertFalse(TimetableTaskType::Routine->allowsOverlapWith(TimetableTaskType::Routine));
        $this->assertFalse(TimetableTaskType::Spot->allowsOverlapWith(TimetableTaskType::Spot));
    }

    /**
     * 区間のマージ実拘束時間計算およびトラックパッキングが正確であること
     */
    public function test_time_slot_helper_merges_intervals_and_packs_tracks(): void
    {
        // 10:00-17:00 (420分) + 10:00-11:00 (内包) + 16:30-18:00 (90分のうち60分延長) = 480分 (8.0h)
        $merged = TimeSlotHelper::calculateMergedDurationMinutes([
            [600, 1020],
            [600, 660],
            [990, 1080],
        ]);
        $this->assertSame(480, $merged);

        // 重複区間が別トラックへ逃がされ、重ならない区間はトラック0に収まること
        $packed = TimeSlotHelper::packIntervalsIntoTracks([
            ['id' => 1, 'start_min' => 540, 'end_min' => 660],
            ['id' => 2, 'start_min' => 600, 'end_min' => 720],
            ['id' => 3, 'start_min' => 660, 'end_min' => 780],
        ]);
        $this->assertSame(2, $packed['tracks_count']);
        $byId = collect($packed['items'])->keyBy('id');
        $this->assertSame(0, $byId[1]['sub_track']);
        $this->assertTrue($byId[1]['has_overlap']);
        $this->assertSame(1, $byId[2]['sub_track']);
        $this->assertTrue($byId[2]['has_overlap']);
        $this->assertSame(0, $byId[3]['sub_track']);
        $this->assertTrue($byId[3]['has_overlap']);
    }
}
