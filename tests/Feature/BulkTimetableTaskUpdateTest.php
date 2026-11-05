<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkTimetableTaskUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;

    protected Timetable $timetable;

    protected Lane $lane;

    protected Staff $leadStaff;

    protected Staff $fixedStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create(['role' => 'operator', 'is_active' => true]);
        $this->timetable = Timetable::factory()->create([
            'name' => '2026年秋まつり本祭',
            'created_by' => $this->operator->id,
        ]);

        $this->leadStaff = Staff::factory()->create(['name' => '班長 太郎', 'affiliation' => '青年部']);
        $this->fixedStaff = Staff::factory()->create(['name' => '固定 花子']);

        $this->lane = Lane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'name' => '警備班',
            'lead_staff_id' => $this->leadStaff->id,
        ]);
        $this->lane->fixedStaff()->attach($this->fixedStaff->id);
    }

    /**
     * 班別作業タスク一括編集画面が正常に描画され、班情報および全タスクが表示されること
     */
    public function test_bulk_edit_screen_renders_all_lane_tasks_and_lane_info(): void
    {
        $task1 = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '朝の規制コーン設置',
            'start_time' => '07:30:00',
            'end_time' => '09:00:00',
            'required_people_count' => 3,
            'is_at_least' => false,
            'is_critical' => true,
        ]);

        $task2 = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '交差点定点警戒',
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'required_people_count' => 4,
            'is_at_least' => true,
            'is_critical' => false,
        ]);

        $response = $this->actingAs($this->operator)
            ->get(route('timetables.lanes.tasks.edit', ['timetable' => $this->timetable, 'lane' => $this->lane]));

        $response->assertStatus(200);

        // パンくずリストおよびタイトル
        $response->assertSee('タイムテーブル一覧');
        $response->assertSee($this->timetable->name);
        $response->assertSee('タスク一覧');
        $response->assertSee('「警備班」作業タスク一括編集');

        // 班サマリー情報（班名、班長、固定スタッフ）
        $response->assertSee('警備班');
        $response->assertSee('班長:');
        $response->assertSee('班長 太郎');
        $response->assertSee('青年部');
        $response->assertSee('固定 花子');

        // タスク削除に関する案内表示
        $response->assertSee('既存タスクの削除は誤操作防止のため、タスク一覧画面の各行「🗑️ 削除」ボタンから実行してください。');

        // 各タスクの情報と登録済みバッジ
        $response->assertSee('朝の規制コーン設置');
        $response->assertSee('07:30');
        $response->assertSee('09:00');
        $response->assertSee('交差点定点警戒');
        $response->assertSee('09:00');
        $response->assertSee('12:00');
        $response->assertSee('登録済みタスク');

        // 既存タスクカードには削除ボタンは存在しないこと
        $response->assertDontSee('data-action="remove-new-task-row"', false);
    }

    /**
     * 複数タスクの属性を一括で更新できること
     */
    public function test_can_update_multiple_tasks_in_bulk_for_lane(): void
    {
        $task1 = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '更新前タスク1',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'required_people_count' => 2,
            'is_at_least' => false,
            'is_critical' => false,
            'status' => 'not_started',
        ]);

        $task2 = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '更新前タスク2',
            'start_time' => '09:00:00',
            'end_time' => '10:30:00',
            'required_people_count' => 2,
            'is_at_least' => false,
            'is_critical' => false,
            'status' => 'not_started',
        ]);

        $payload = [
            'tasks' => [
                [
                    'id' => $task1->id,
                    'title' => '更新後タスク1（コーン設置）',
                    'location' => '北門通り',
                    'start_time' => '07:45',
                    'end_time' => '09:15',
                    'required_people_count' => 3,
                    'is_at_least' => '1',
                    'is_critical' => '1',
                    'content' => 'カラーコーンとバーを20組設置',
                    'status' => 'in_progress',
                ],
                [
                    'id' => $task2->id,
                    'title' => '更新後タスク2（車両迂回案内）',
                    'location' => '中央交差点',
                    'start_time' => '09:15',
                    'end_time' => '11:00',
                    'required_people_count' => 4,
                    'is_at_least' => '0',
                    'is_critical' => '0',
                    'content' => '迂回路案内看板を掲示',
                    'status' => 'not_started',
                ],
            ],
        ];

        $response = $this->actingAs($this->operator)
            ->put(route('timetables.lanes.tasks.update', ['timetable' => $this->timetable, 'lane' => $this->lane]), $payload);

        $response->assertRedirect(route('timetables.tasks.index', $this->timetable));
        $response->assertSessionHas('success', '「警備班」の作業タスク（2件）を一括更新しました。');

        $this->assertDatabaseHas('timetable_tasks', [
            'id' => $task1->id,
            'title' => '更新後タスク1（コーン設置）',
            'location' => '北門通り',
            'start_time' => '07:45',
            'end_time' => '09:15',
            'required_people_count' => 3,
            'is_at_least' => true,
            'is_critical' => true,
            'status' => 'in_progress',
        ]);

        $this->assertDatabaseHas('timetable_tasks', [
            'id' => $task2->id,
            'title' => '更新後タスク2（車両迂回案内）',
            'location' => '中央交差点',
            'start_time' => '09:15',
            'end_time' => '11:00',
            'required_people_count' => 4,
            'is_at_least' => false,
            'is_critical' => false,
            'status' => 'not_started',
        ]);
    }

    /**
     * 既存タスクの更新と新規タスクの追加枠登録を同時に行えること
     */
    public function test_can_add_new_task_alongside_updating_existing_tasks_in_bulk(): void
    {
        $existingTask = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '既存タスク',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $payload = [
            'tasks' => [
                [
                    'id' => $existingTask->id,
                    'title' => '既存タスク更新済み',
                    'location' => '会場東側',
                    'start_time' => '08:00',
                    'end_time' => '09:00',
                    'required_people_count' => 2,
                    'is_at_least' => '0',
                    'is_critical' => '0',
                    'status' => 'not_started',
                ],
                [
                    // 新規追加枠（id なし）
                    'title' => '新規追加タスク（パトロール）',
                    'location' => '会場南側',
                    'start_time' => '09:00',
                    'end_time' => '10:30',
                    'required_people_count' => 3,
                    'is_at_least' => '1',
                    'is_critical' => '1',
                    'status' => 'not_started',
                ],
            ],
        ];

        $response = $this->actingAs($this->operator)
            ->put(route('timetables.lanes.tasks.update', ['timetable' => $this->timetable, 'lane' => $this->lane]), $payload);

        $response->assertRedirect(route('timetables.tasks.index', $this->timetable));
        $response->assertSessionHas('success', '「警備班」の作業タスク（2件）を一括更新しました。');

        $this->assertDatabaseHas('timetable_tasks', [
            'id' => $existingTask->id,
            'title' => '既存タスク更新済み',
        ]);

        $createdTask = TimetableTask::where('title', '新規追加タスク（パトロール）')
            ->where('lane_id', $this->lane->id)
            ->firstOrFail();

        $this->assertSame('会場南側', $createdTask->location);
        $this->assertSame(3, $createdTask->required_people_count);
        $this->assertTrue($createdTask->is_at_least);
        $this->assertTrue($createdTask->is_critical);

        // 新規作成されたタスクにも班長および固定スタッフが自動アサインされていること
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $createdTask->id,
            'staff_id' => $this->leadStaff->id,
            'role_description' => '班長',
        ]);
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $createdTask->id,
            'staff_id' => $this->fixedStaff->id,
            'role_description' => '固定スタッフ',
        ]);
    }

    /**
     * タイムテーブルと班の所属関係が不一致の場合は404が返ること
     */
    public function test_bulk_edit_aborts_404_if_lane_does_not_belong_to_timetable(): void
    {
        $otherTimetable = Timetable::factory()->create();

        $response = $this->actingAs($this->operator)
            ->get(route('timetables.lanes.tasks.edit', ['timetable' => $otherTimetable, 'lane' => $this->lane]));

        $response->assertStatus(404);

        $responsePut = $this->actingAs($this->operator)
            ->put(route('timetables.lanes.tasks.update', ['timetable' => $otherTimetable, 'lane' => $this->lane]), [
                'tasks' => [
                    [
                        'title' => '不正リクエスト',
                        'start_time' => '08:00',
                        'end_time' => '09:00',
                        'required_people_count' => 1,
                        'status' => 'not_started',
                    ],
                ],
            ]);

        $responsePut->assertStatus(404);
    }

    /**
     * バリデーションエラー時は更新されずエラーセッションが返ること
     */
    public function test_validation_errors_prevent_bulk_update(): void
    {
        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '変更前タスク',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $response = $this->actingAs($this->operator)
            ->put(route('timetables.lanes.tasks.update', ['timetable' => $this->timetable, 'lane' => $this->lane]), [
                'tasks' => [
                    [
                        'id' => $task->id,
                        'title' => '', // 必須違反
                        'start_time' => '10:00',
                        'end_time' => '09:00', // 終了時刻が開始時刻より前
                        'required_people_count' => -1, // 0以上必須
                        'status' => 'invalid_status',
                    ],
                ],
            ]);

        $response->assertSessionHasErrors([
            'tasks.0.title',
            'tasks.0.end_time',
            'tasks.0.required_people_count',
            'tasks.0.status',
        ]);

        $task->refresh();
        $this->assertSame('変更前タスク', $task->title);
    }
}
