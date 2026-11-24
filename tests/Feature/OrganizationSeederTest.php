<?php

namespace Tests\Feature;

use App\Models\OrganizationUnit;
use App\Models\OrganizationUnitStaff;
use App\Models\Staff;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\StaffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * StaffSeeder および OrganizationSeeder が正常に動作し、
     * 全67件の組織ユニットと全83件の所属スタッフが正しく登録されること
     */
    public function test_organization_seeder_seeds_all_units_and_staff_correctly(): void
    {
        $this->seed([StaffSeeder::class, OrganizationSeeder::class]);

        // 1. スタッフ全45名（追加されたID 39〜45を含む）が登録されていること
        $this->assertSame(45, Staff::count());
        $this->assertDatabaseHas('staff', [
            'id' => 39,
            'name' => '譲原 純孝',
            'affiliation' => '新武蔵警備保障',
            'staff_attribute' => 'external',
        ]);
        $this->assertDatabaseHas('staff', [
            'id' => 43,
            'name' => '引地 虹輝',
        ]);
        $this->assertDatabaseHas('staff', [
            'id' => 44,
            'name' => '伊藤 さきこ',
        ]);
        $this->assertDatabaseHas('staff', [
            'id' => 45,
            'name' => '鈴木 せいや',
            'affiliation' => 'ヨコハマン',
            'staff_attribute' => 'external',
        ]);
        $this->assertSame(12, Staff::where('staff_attribute', 'executive')->count());
        $this->assertSame(3, Staff::where('staff_attribute', 'executive_board')->count());
        $this->assertSame(3, Staff::where('staff_attribute', 'external')->count());

        // 2. 組織ユニット全67件が登録されていること
        $this->assertSame(67, OrganizationUnit::count());

        // ルート役員（実行委員長、副実行委員長、会計、議長、顧問、相談役）
        $rootNames = OrganizationUnit::whereNull('parent_id')->pluck('name')->toArray();
        $this->assertContains('実行委員長', $rootNames);
        $this->assertContains('副実行委員長', $rootNames);
        $this->assertContains('会計', $rootNames);
        $this->assertContains('議長', $rootNames);
        $this->assertContains('顧問', $rootNames);
        $this->assertContains('相談役', $rootNames);

        // 更新されたスタッフ属性・所属・氏名（山本 昇市 / 幹事会メンバー15名）
        $this->assertDatabaseHas('staff', [
            'id' => 40,
            'name' => '山本 昇市',
            'staff_attribute' => 'executive_board',
        ]);
        $this->assertSame(
            [1, 2, 5, 7, 8, 13, 15, 17, 22, 24, 26, 29, 30, 35, 40],
            Staff::whereIn('staff_attribute', ['executive', 'executive_board'])->orderBy('id')->pluck('id')->all()
        );

        // 企業ブースの親変更（イベント配下）
        $kigyoBooth = OrganizationUnit::find(41);
        $this->assertNotNull($kigyoBooth);
        $this->assertSame('企業ブース', $kigyoBooth->name);
        $this->assertSame(16, $kigyoBooth->parent_id);

        // 新設ユニット（イベント会社、図面）が登録されていること
        $this->assertDatabaseHas('organization_units', ['id' => 86, 'name' => 'イベント会社']);
        $this->assertDatabaseHas('organization_units', ['id' => 87, 'name' => '図面']);

        // 3. 組織所属スタッフ全84件が登録されていること
        $this->assertSame(84, OrganizationUnitStaff::count());

        // 新規スタッフ（ID 39〜44）の組織割当が正しいこと
        $yuzuharaAssignment = OrganizationUnitStaff::where('staff_id', 39)->first();
        $this->assertNotNull($yuzuharaAssignment);
        $this->assertSame('警備', $yuzuharaAssignment->organizationUnit->name);

        $hikijiAssignment = OrganizationUnitStaff::where('staff_id', 43)->first();
        $this->assertNotNull($hikijiAssignment);
        $this->assertSame('巡回', $hikijiAssignment->organizationUnit->name);

        $itoAssignment = OrganizationUnitStaff::where('staff_id', 44)->first();
        $this->assertNotNull($itoAssignment);
        $this->assertSame('アシスタント', $itoAssignment->organizationUnit->name);

        // 4. 親子階層関係が正しく保持されていること
        $soumu = OrganizationUnit::where('name', '総務')->first();
        $this->assertNotNull($soumu);
        $this->assertSame('実行委員長', $soumu->parent->name);

        $honjin = OrganizationUnit::where('name', '本陣')->first();
        $this->assertNotNull($honjin);
        $this->assertSame($soumu->id, $honjin->parent_id);

        $setsubi = OrganizationUnit::where('name', '設営・設備')->first();
        $this->assertNotNull($setsubi);
        $eventKaisha = OrganizationUnit::where('name', 'イベント会社')->first();
        $this->assertSame($setsubi->id, $eventKaisha->parent_id);
    }

    /**
     * DatabaseSeeder 全体を実行した際に、組織データが正しくシードされること
     */
    public function test_database_seeder_runs_organization_seeder_successfully(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(45, Staff::count());
        $this->assertSame(67, OrganizationUnit::count());
        $this->assertSame(84, OrganizationUnitStaff::count());
    }

    /**
     * OrganizationSeeder を複数回実行しても冪等性が保たれること
     */
    public function test_organization_seeder_is_idempotent(): void
    {
        $this->seed([StaffSeeder::class, OrganizationSeeder::class]);
        $this->assertSame(67, OrganizationUnit::count());
        $this->assertSame(84, OrganizationUnitStaff::count());

        // 再実行
        $this->seed(OrganizationSeeder::class);
        $this->assertSame(67, OrganizationUnit::count());
        $this->assertSame(84, OrganizationUnitStaff::count());
    }
}
