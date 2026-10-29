<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWorkflowNavigatorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);
    }

    /**
     * タイムテーブル未登録時は Step 2 推奨が表示されること
     */
    public function test_shows_step_2_when_no_timetable_exists(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('まつり運行準備ナビゲーター');
        $response->assertSee('運行日（タイムテーブル）を登録しましょう');
        $response->assertSee(route('timetables.create'));
    }

    /**
     * タイムテーブルはあるが班長未設定の場合は Step 3 推奨が表示されること
     */
    public function test_shows_step_3_when_lanes_have_no_lead_staff(): void
    {
        $timetable = Timetable::factory()->create([
            'name' => '本祭1日目',
            'status' => 'active',
        ]);
        Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン',
            'lead_staff_id' => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('担当班の編成・班長・固定スタッフを登録しましょう');
        $response->assertSee(route('timetables.lanes.index', $timetable));
    }

    /**
     * 班長が設定されているがタスクが未登録の場合は Step 4 推奨が表示されること
     */
    public function test_shows_step_4_when_no_tasks_exist(): void
    {
        $timetable = Timetable::factory()->create([
            'name' => '本祭1日目',
            'status' => 'active',
        ]);
        $lead = Staff::factory()->create(['is_active' => true]);
        Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン',
            'lead_staff_id' => $lead->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('作業タスクを登録しましょう');
        $response->assertSee(route('timetable-tasks.create', ['timetable_id' => $timetable->id]));
    }

    /**
     * 人員不足タスクがある場合は Step 5 推奨が表示されること
     */
    public function test_shows_step_5_when_tasks_have_shortage(): void
    {
        $timetable = Timetable::factory()->create([
            'name' => '本祭1日目',
            'status' => 'active',
        ]);
        $lead = Staff::factory()->create(['is_active' => true]);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン',
            'lead_staff_id' => $lead->id,
        ]);

        // 必要人数2名で未配置のタスクを作成
        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'required_people_count' => 2,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('余剰スタッフを配置しましょう');
        $response->assertSee(route('timetable.index', ['timetable_id' => $timetable->id]));
    }
}
