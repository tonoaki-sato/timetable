<?php

namespace Tests\Feature;

use App\Enums\BoothCategory;
use App\Enums\BoothType;
use App\Enums\LayoutItemStatus;
use App\Enums\LayoutItemType;
use App\Models\Equipment;
use App\Models\LayoutArea;
use App\Models\LayoutItem;
use App\Models\LayoutMap;
use App\Models\Schedule;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableSortingAndPaginationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'MMMテスト管理者',
            'email' => 'admin-sort@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    /**
     * スタッフ一覧で列ソート・不正パラメータフォールバック・表示件数切替が機能すること
     */
    public function test_staff_index_supports_sorting_and_per_page_whitelist(): void
    {
        Staff::factory()->create(['name' => 'AAA佐藤', 'kana' => 'アアアサトウ']);
        Staff::factory()->create(['name' => 'ZZZ山本', 'kana' => 'ンンンヤマモト']);

        $ascRes = $this->actingAs($this->admin)->get(route('staff.index', ['sort' => 'name', 'direction' => 'asc']));
        $ascRes->assertStatus(200);
        $ascRes->assertSeeInOrder(['AAA佐藤', 'ZZZ山本']);

        $descRes = $this->actingAs($this->admin)->get(route('staff.index', ['sort' => 'name', 'direction' => 'desc']));
        $descRes->assertStatus(200);
        $descRes->assertSeeInOrder(['ZZZ山本', 'AAA佐藤']);

        // 不正カラム指定時はデフォルト (name asc) へフォールバック
        $invalidSortRes = $this->actingAs($this->admin)->get(route('staff.index', ['sort' => 'password;DROP TABLE staff', 'direction' => 'invalid']));
        $invalidSortRes->assertStatus(200);
        $invalidSortRes->assertSeeInOrder(['AAA佐藤', 'ZZZ山本']);

        // per_page 検証（有効値・無効値）
        $per30Res = $this->actingAs($this->admin)->get(route('staff.index', ['per_page' => 30]));
        $per30Res->assertStatus(200);
        $this->assertSame(30, $per30Res->viewData('staffMembers')->perPage());

        $perInvalidRes = $this->actingAs($this->admin)->get(route('staff.index', ['per_page' => 9999]));
        $perInvalidRes->assertStatus(200);
        $this->assertSame(15, $perInvalidRes->viewData('staffMembers')->perPage());
    }

    /**
     * 出展・出店者一覧で列ソートおよび表示件数切替が機能すること
     */
    public function test_vendors_index_supports_sorting_and_per_page(): void
    {
        Vendor::create(['name' => 'AAA出展社', 'contact_person' => '田中', 'contact' => '090-1111-1111']);
        Vendor::create(['name' => 'ZZZ出展社', 'contact_person' => '鈴木', 'contact' => '090-9999-9999']);

        $descRes = $this->actingAs($this->admin)->get(route('vendors.index', ['sort' => 'name', 'direction' => 'desc', 'per_page' => 50]));
        $descRes->assertStatus(200);
        $descRes->assertSeeInOrder(['ZZZ出展社', 'AAA出展社']);
        $this->assertSame(50, $descRes->viewData('vendors')->perPage());
    }

    /**
     * 機材台帳一覧で列ソートおよび表示件数切替が機能すること
     */
    public function test_equipments_index_supports_sorting_and_per_page(): void
    {
        Equipment::factory()->create(['name' => '少数量備品', 'quantity' => 5, 'display_order' => 10, 'created_by' => $this->admin->id]);
        Equipment::factory()->create(['name' => '多数量備品', 'quantity' => 500, 'display_order' => 20, 'created_by' => $this->admin->id]);

        $descRes = $this->actingAs($this->admin)->get(route('equipments.index', ['sort' => 'quantity', 'direction' => 'desc', 'per_page' => 50]));
        $descRes->assertStatus(200);
        $descRes->assertSeeInOrder(['多数量備品', '少数量備品']);
        $this->assertSame(50, $descRes->viewData('equipments')->perPage());
    }

    /**
     * スケジュール一覧でスコープの既定順序を上書きして列ソートおよび表示件数切替が機能すること
     */
    public function test_schedules_index_supports_sorting_overriding_upcoming_scope(): void
    {
        Schedule::factory()->create([
            'title' => 'ZZZ先の日程',
            'start_date' => now()->addDays(2)->toDateString(),
            'status' => 'planned',
            'created_by' => $this->admin->id,
        ]);
        Schedule::factory()->create([
            'title' => 'AAA後の日程',
            'start_date' => now()->addDays(10)->toDateString(),
            'status' => 'planned',
            'created_by' => $this->admin->id,
        ]);

        // 既定は日付昇順なので ZZZ が先だが、title asc 指定時は AAA が先に来る
        $res = $this->actingAs($this->admin)->get(route('schedules.index', ['sort' => 'title', 'direction' => 'asc', 'per_page' => 25]));
        $res->assertStatus(200);
        $res->assertSeeInOrder(['AAA後の日程', 'ZZZ先の日程']);
        $this->assertSame(25, $res->viewData('schedules')->perPage());
    }

    /**
     * ユーザー一覧で列ソートおよび表示件数切替が機能すること
     */
    public function test_users_index_supports_sorting_and_per_page(): void
    {
        User::factory()->create(['name' => 'AAAユーザー', 'email' => 'aaa@example.com']);
        User::factory()->create(['name' => 'ZZZユーザー', 'email' => 'zzz@example.com']);

        $res = $this->actingAs($this->admin)->get(route('users.index', ['sort' => 'name', 'direction' => 'asc', 'per_page' => 30]));
        $res->assertStatus(200);
        $res->assertSeeInOrder(['AAAユーザー', 'ZZZユーザー']);
        $this->assertSame(30, $res->viewData('users')->perPage());
    }

    /**
     * 屋台・設備配置一覧で列ソートおよび表示件数切替が機能すること
     */
    public function test_booths_index_supports_sorting_and_per_page(): void
    {
        $timetable = Timetable::factory()->create(['created_by' => $this->admin->id]);
        $map = LayoutMap::create([
            'timetable_id' => $timetable->id,
            'name' => '会場配置図',
            'version_tag' => 'v1.0',
            'created_by' => $this->admin->id,
        ]);
        $area = LayoutArea::create([
            'layout_map_id' => $map->id,
            'zone_code' => 'ZONE_A',
            'name' => 'メイン通りA列',
            'display_order' => 10,
        ]);

        LayoutItem::create([
            'layout_map_id' => $map->id,
            'layout_area_id' => $area->id,
            'item_type' => LayoutItemType::Booth,
            'code' => 'A01',
            'name' => '早番ブース',
            'category' => BoothCategory::GozaCookedFood->value,
            'booth_type' => BoothType::Tent->value,
            'status' => LayoutItemStatus::Confirmed,
            'display_order' => 1,
        ]);
        LayoutItem::create([
            'layout_map_id' => $map->id,
            'layout_area_id' => $area->id,
            'item_type' => LayoutItemType::Booth,
            'code' => 'Z99',
            'name' => '遅番ブース',
            'category' => BoothCategory::GozaCookedFood->value,
            'booth_type' => BoothType::Tent->value,
            'status' => LayoutItemStatus::Confirmed,
            'display_order' => 2,
        ]);

        $res = $this->actingAs($this->admin)->get(route('timetables.layouts.booths', [
            'timetable' => $timetable,
            'sort' => 'code',
            'direction' => 'desc',
            'per_page' => 25,
        ]));
        $res->assertStatus(200);
        $res->assertSeeInOrder(['Z99', 'A01']);
        $this->assertSame(25, $res->viewData('items')->perPage());
    }
}
