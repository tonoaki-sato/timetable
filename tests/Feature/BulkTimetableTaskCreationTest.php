<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkTimetableTaskCreationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 作業タスク新規作成画面が表示され、指定したレーンと開始時刻が初期反映されること
     */
    public function test_create_screen_can_be_rendered_with_selected_lane_and_start_time(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $timetable = Timetable::factory()->create(['created_by' => $user->id]);
        $leadStaff = Staff::factory()->create(['name' => '班長 太郎']);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン',
            'lead_staff_id' => $leadStaff->id,
        ]);

        $response = $this->actingAs($user)->get(route('timetable-tasks.create', [
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'start_time' => '10:30',
        ]));

        $response->assertStatus(200);
        $response->assertSee('カラーコーン');
        $response->assertSee('班長 太郎');
        $response->assertSee('10:30');
        $response->assertSee('11:30');
    }

    /**
     * 選択したレーンに対して複数の作業タスクを一括登録でき、主担当スタッフが全タスクに自動アサインされること
     */
    public function test_can_register_multiple_tasks_in_bulk_for_selected_lane_with_auto_assign_lead(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $timetable = Timetable::factory()->create(['created_by' => $user->id]);
        $leadStaff = Staff::factory()->create(['name' => '佐藤 リーダー']);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '衛生',
            'lead_staff_id' => $leadStaff->id,
        ]);

        $payload = [
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'auto_assign_lead' => '1',
            'tasks' => [
                [
                    'title' => '集合・事前ミーティング',
                    'location' => '本陣',
                    'start_time' => '07:00',
                    'end_time' => '07:30',
                    'required_people_count' => 0,
                    'is_at_least' => '0',
                    'content' => '朝礼と注意事項共有',
                    'status' => 'not_started',
                    'is_critical' => '0',
                    'requires_review' => '0',
                ],
                [
                    'title' => 'ゴミ箱準備・設置',
                    'location' => '北川製粉所横',
                    'start_time' => '07:30',
                    'end_time' => '10:00',
                    'required_people_count' => 4,
                    'is_at_least' => '1',
                    'content' => 'ブルーシート・袋設置',
                    'status' => 'not_started',
                    'is_critical' => '0',
                    'requires_review' => '1',
                ],
                [
                    'title' => '最終撤収・清掃',
                    'location' => '会場全域',
                    'start_time' => '17:00',
                    'end_time' => '18:30',
                    'required_people_count' => 6,
                    'is_at_least' => '0',
                    'content' => '完全撤去確認',
                    'status' => 'not_started',
                    'is_critical' => '1',
                    'requires_review' => '1',
                ],
            ],
        ];

        $response = $this->actingAs($user)->post(route('timetable-tasks.store'), $payload);

        $response->assertRedirect(route('timetables.show', $timetable->id));
        $response->assertSessionHas('success', '「衛生」に 3 件の作業タスクを一括登録しました。');

        $createdTasks = TimetableTask::where('timetable_id', $timetable->id)
            ->where('lane_id', $lane->id)
            ->orderBy('start_time')
            ->get();

        $this->assertCount(3, $createdTasks);
        $this->assertSame('集合・事前ミーティング', $createdTasks[0]->title);
        $this->assertSame('ゴミ箱準備・設置', $createdTasks[1]->title);
        $this->assertTrue($createdTasks[1]->is_at_least);
        $this->assertTrue($createdTasks[1]->requires_review);
        $this->assertSame('最終撤収・清掃', $createdTasks[2]->title);
        $this->assertTrue($createdTasks[2]->is_critical);

        // 主担当スタッフが3件すべてのタスクに班リーダーとして自動アサインされていることを確認
        foreach ($createdTasks as $task) {
            $this->assertDatabaseHas('task_staff_assignments', [
                'timetable_task_id' => $task->id,
                'staff_id' => $leadStaff->id,
                'role_description' => '班リーダー',
                'attendance_status' => 'present',
            ]);
        }
    }

    /**
     * 従来の単一タスクフラット形式のPOSTリクエストでも後方互換で登録できること
     */
    public function test_backward_compatibility_for_single_flat_task_submission(): void
    {
        $user = User::factory()->create(['role' => 'operator', 'is_active' => true]);
        $timetable = Timetable::factory()->create(['created_by' => $user->id]);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '給水設備',
        ]);

        $response = $this->actingAs($user)->post(route('timetable-tasks.store'), [
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '給水タンク設置',
            'location' => '税務署前',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'required_people_count' => 3,
            'is_at_least' => '0',
            'is_critical' => '1',
            'requires_review' => '0',
            'status' => 'not_started',
        ]);

        $response->assertRedirect(route('timetables.show', $timetable->id));
        $response->assertSessionHas('success', '作業タスク「給水タンク設置」を登録しました。');

        $this->assertDatabaseHas('timetable_tasks', [
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '給水タンク設置',
            'required_people_count' => 3,
            'is_critical' => true,
        ]);
    }

    /**
     * 複数タスクのうち1件でもバリデーションエラーがある場合は1件も登録されないこと
     */
    public function test_validation_error_in_any_task_prevents_partial_creation(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $timetable = Timetable::factory()->create(['created_by' => $user->id]);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '巡回',
        ]);

        $response = $this->actingAs($user)->post(route('timetable-tasks.store'), [
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'tasks' => [
                [
                    'title' => '正常なタスク',
                    'start_time' => '09:00',
                    'end_time' => '10:00',
                    'required_people_count' => 2,
                    'status' => 'not_started',
                ],
                [
                    'title' => '終了時刻が逆転しているタスク',
                    'start_time' => '12:00',
                    'end_time' => '11:00', // invalid: before start_time
                    'required_people_count' => 2,
                    'status' => 'not_started',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['tasks.1.end_time']);
        $this->assertSame(0, TimetableTask::where('timetable_id', $timetable->id)->count());
    }
}

