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
     * 全64件の組織ユニットと全82件の所属スタッフが正しく登録されること
     */
    public function test_organization_seeder_seeds_all_units_and_staff_correctly(): void
    {
        $this->seed([StaffSeeder::class, OrganizationSeeder::class]);

        // 1. スタッフ全43名（追加されたID 39〜43を含む）が登録されていること
        $this->assertSame(43, Staff::count());
        $this->assertDatabaseHas('staff', [
            'id' => 39,
            'name' => '譲原 純孝',
            'affiliation' => '新武蔵警備保障',
        ]);
        $this->assertDatabaseHas('staff', [
            'id' => 43,
            'name' => 'ひきじ',
        ]);

        // 2. 組織ユニット全64件が登録されていること
        $this->assertSame(64, OrganizationUnit::count());

        // ルート役員（実行委員長、福実行委員長、会計、議長、顧問、相談役）
        $rootNames = OrganizationUnit::whereNull('parent_id')->pluck('name')->toArray();
        $this->assertContains('実行委員長', $rootNames);
        $this->assertContains('福実行委員長', $rootNames);
        $this->assertContains('会計', $rootNames);
        $this->assertContains('議長', $rootNames);
        $this->assertContains('顧問', $rootNames);
        $this->assertContains('相談役', $rootNames);

        // 3. 組織所属スタッフ全82件が登録されていること
        $this->assertSame(82, OrganizationUnitStaff::count());

        // 新規スタッフ（ID 39〜43）の組織割当が正しいこと
        $yuzuharaAssignment = OrganizationUnitStaff::where('staff_id', 39)->first();
        $this->assertNotNull($yuzuharaAssignment);
        $this->assertSame('警備', $yuzuharaAssignment->organizationUnit->name);

        $hikijiAssignment = OrganizationUnitStaff::where('staff_id', 43)->first();
        $this->assertNotNull($hikijiAssignment);
        $this->assertSame('巡回', $hikijiAssignment->organizationUnit->name);

        // 4. 親子階層関係が正しく保持されていること
        $soumu = OrganizationUnit::where('name', '総務')->first();
        $this->assertNotNull($soumu);
        $this->assertSame('実行委員長', $soumu->parent->name);

        $honjin = OrganizationUnit::where('name', '本陣')->first();
        $this->assertNotNull($honjin);
        $this->assertSame($soumu->id, $honjin->parent_id);
    }

    /**
     * DatabaseSeeder 全体を実行した際に、組織データが正しくシードされること
     */
    public function test_database_seeder_runs_organization_seeder_successfully(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(43, Staff::count());
        $this->assertSame(64, OrganizationUnit::count());
        $this->assertSame(82, OrganizationUnitStaff::count());
    }

    /**
     * OrganizationSeeder を複数回実行しても冪等性が保たれること
     */
    public function test_organization_seeder_is_idempotent(): void
    {
        $this->seed([StaffSeeder::class, OrganizationSeeder::class]);
        $this->assertSame(64, OrganizationUnit::count());
        $this->assertSame(82, OrganizationUnitStaff::count());

        // 再実行
        $this->seed(OrganizationSeeder::class);
        $this->assertSame(64, OrganizationUnit::count());
        $this->assertSame(82, OrganizationUnitStaff::count());
    }
}
