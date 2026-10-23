<?php

namespace Tests\Feature;

use App\Models\Meeting;
use Database\Seeders\MeetingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * MeetingSeeder が正常に実行され、会議データが正しく登録されること
     */
    public function test_meeting_seeder_seeds_data_correctly(): void
    {
        $this->seed(MeetingSeeder::class);

        // 会議データが20件登録されていること
        $this->assertGreaterThanOrEqual(20, Meeting::count());

        // 代表的な会議が存在すること
        $meeting1 = Meeting::where('title', '幹事会反省会 in居酒屋')->first();
        $this->assertNotNull($meeting1);
        $this->assertSame('2026-11-07', $meeting1->meeting_date->format('Y-m-d'));
        $this->assertStringStartsWith('19:00', $meeting1->start_time);
        $this->assertStringStartsWith('20:00', $meeting1->end_time);

        $meeting2 = Meeting::where('title', '第1回幹事会（年間日程案策定）')->first();
        $this->assertNotNull($meeting2);
        $this->assertSame('2027-01-02', $meeting2->meeting_date->format('Y-m-d'));
        $this->assertStringStartsWith('18:00', $meeting2->start_time);
        $this->assertStringStartsWith('20:00', $meeting2->end_time);
        $this->assertSame('コミュニティルーム', $meeting2->location);

        $meeting3 = Meeting::where('title', '第1回総会（新年度発足・役員選出・前年度会計報告）')->first();
        $this->assertNotNull($meeting3);
        $this->assertSame('2027-02-27', $meeting3->meeting_date->format('Y-m-d'));
        $this->assertStringStartsWith('18:00', $meeting3->start_time);

        // 全レコードの必須カラムが満たされていること
        foreach (Meeting::all() as $meeting) {
            $this->assertNotEmpty($meeting->title);
            $this->assertNotNull($meeting->meeting_date);
            $this->assertNotEmpty($meeting->start_time);
            $this->assertNotNull($meeting->created_by);
        }
    }

    /**
     * シーダーが複数回実行されても重複登録されず冪等性が担保されること
     */
    public function test_meeting_seeder_is_idempotent(): void
    {
        $this->seed(MeetingSeeder::class);
        $firstRunCount = Meeting::count();

        // 2回目の実行
        $this->seed(MeetingSeeder::class);
        $secondRunCount = Meeting::count();

        $this->assertSame($firstRunCount, $secondRunCount);
    }

    /**
     * DatabaseSeeder 全体がエラーなく実行完了できること
     */
    public function test_database_seeder_runs_without_errors(): void
    {
        $this->seed();
        $this->assertGreaterThan(0, Meeting::count());
    }
}
