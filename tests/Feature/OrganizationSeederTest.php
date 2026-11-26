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
     * 全71件の組織ユニットと全101件の所属スタッフが正しく登録されること
     */
    public function test_organization_seeder_seeds_all_units_and_staff_correctly(): void
    {
        $this->seed([StaffSeeder::class, OrganizationSeeder::class]);

        // 1. スタッフ全46名（更新済みメンバーおよびID 46を含む）が登録されていること
        $this->assertSame(46, Staff::count());
        $this->assertDatabaseHas('staff', [
            'id' => 5,
            'name' => '岡本 睦良',
            'affiliation' => '副実行委員長',
        ]);
        $this->assertDatabaseHas('staff', [
            'id' => 8,
            'name' => '萩原 繁夫',
            'affiliation' => '保土ケ谷駅西口商店街会長',
        ]);
        $this->assertDatabaseHas('staff', [
            'id' => 24,
            'name' => '寺井 智之',
            'affiliation' => '実行委員長',
        ]);
        $this->assertDatabaseHas('staff', [
            'id' => 39,
            'name' => '譲原 純孝',
            'affiliation' => '新武蔵警備保障',
            'staff_attribute' => 'external',
        ]);
        $this->assertDatabaseHas('staff', [
            'id' => 40,
            'name' => '山本 昇市',
            'affiliation' => '前実行委員長',
            'staff_attribute' => 'executive_board',
        ]);
        $this->assertDatabaseHas('staff', [
            'id' => 41,
            'name' => '蛇目 泰子',
            'kana' => 'ジャノメ ヤスコ',
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
        $this->assertDatabaseHas('staff', [
            'id' => 46,
            'name' => '高野 圭子',
            'affiliation' => 'リリーズ西',
            'staff_attribute' => 'general',
        ]);
        $this->assertSame(12, Staff::where('staff_attribute', 'executive')->count());
        $this->assertSame(3, Staff::where('staff_attribute', 'executive_board')->count());
        $this->assertSame(3, Staff::where('staff_attribute', 'external')->count());

        // 2. 組織ユニット全71件が登録されていること
        $this->assertSame(71, OrganizationUnit::count());

        // ルート役員（実行委員長、幹事、顧問、相談役）
        $rootNames = OrganizationUnit::whereNull('parent_id')->pluck('name')->toArray();
        $this->assertEqualsCanonicalizing(['実行委員長', '幹事', '顧問', '相談役'], $rootNames);

        // 削除されたユニットが存在しないこと
        foreach ([2, 3, 6, 48, 61] as $deletedId) {
            $this->assertDatabaseMissing('organization_units', ['id' => $deletedId]);
        }

        // 幹事会メンバー15名
        $this->assertSame(
            [1, 2, 5, 7, 8, 13, 15, 17, 22, 24, 26, 29, 30, 35, 40],
            Staff::whereIn('staff_attribute', ['executive', 'executive_board'])->orderBy('id')->pluck('id')->all()
        );

        // 企業ブースの親変更（イベント配下）および帷子茶屋の更新
        $kigyoBooth = OrganizationUnit::find(41);
        $this->assertNotNull($kigyoBooth);
        $this->assertSame('企業ブース', $kigyoBooth->name);
        $this->assertSame(16, $kigyoBooth->parent_id);

        $this->assertDatabaseHas('organization_units', [
            'id' => 53,
            'name' => '帷子茶屋',
            'short_description' => '帷子2丁目自治会高松会長宛',
        ]);

        // 新設ユニット（イベント会社、図面、幹事、キッズ村配下）が登録されていること
        $this->assertDatabaseHas('organization_units', ['id' => 86, 'name' => 'イベント会社']);
        $this->assertDatabaseHas('organization_units', ['id' => 87, 'name' => '図面']);
        $this->assertDatabaseHas('organization_units', ['id' => 88, 'name' => '幹事', 'parent_id' => null]);
        foreach ([
            89 => 'けん玉・ダーツ',
            90 => 'ハンドメイド',
            91 => 'たしろ保育室',
            92 => 'にゃとわん',
            93 => '無線体験',
            94 => 'バルーンアート',
            95 => '消防団',
            96 => '保土ケ谷駅',
        ] as $unitId => $unitName) {
            $this->assertDatabaseHas('organization_units', [
                'id' => $unitId,
                'parent_id' => 58,
                'name' => $unitName,
            ]);
        }

        // 3. 組織所属スタッフ全101件が登録されていること
        $this->assertSame(101, OrganizationUnitStaff::count());

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

        $this->assertSame(46, Staff::count());
        $this->assertSame(71, OrganizationUnit::count());
        $this->assertSame(101, OrganizationUnitStaff::count());
    }

    /**
     * OrganizationSeeder を複数回実行しても冪等性が保たれること
     */
    public function test_organization_seeder_is_idempotent(): void
    {
        $this->seed([StaffSeeder::class, OrganizationSeeder::class]);
        $this->assertSame(71, OrganizationUnit::count());
        $this->assertSame(101, OrganizationUnitStaff::count());

        // 再実行
        $this->seed(OrganizationSeeder::class);
        $this->assertSame(71, OrganizationUnit::count());
        $this->assertSame(101, OrganizationUnitStaff::count());
    }
}
