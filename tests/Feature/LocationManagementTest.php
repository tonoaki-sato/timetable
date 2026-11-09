<?php

namespace Tests\Feature;

use App\Enums\LocationCurrentStatus;
use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\LocationType;
use App\Models\Timetable;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private LocationType $type;

    private Timetable $timetable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->timetable = Timetable::factory()->create([
            'name' => 'テスト本番日',
            'target_date' => '2026-10-11',
        ]);

        $this->type = LocationType::create([
            'name' => 'community_space',
            'label' => 'コミュニティスペース',
            'description' => '地域交流・待機スペース',
            'color' => 'indigo',
            'display_order' => 10,
            'is_active' => true,
        ]);
    }

    public function test_can_view_location_index_screen(): void
    {
        $location = Location::factory()->create([
            'timetable_id' => $this->timetable->id,
            'location_type_id' => $this->type->id,
            'name' => '番所',
            'notes' => '鍵は暗証番号4920で開錠。',
        ]);

        $response = $this->actingAs($this->admin)->get(route('timetables.locations.index', $this->timetable));

        $response->assertOk()
            ->assertSee('拠点一覧')
            ->assertSee('番所')
            ->assertDontSee('<th>種別</th>', false)
            ->assertSee(route('timetables.locations.show', [$this->timetable, $location]))
            ->assertSee('編集')
            ->assertSee('削除');

        // メモは詳細画面で確認可能
        $showResponse = $this->actingAs($this->admin)->get(route('timetables.locations.show', [$this->timetable, $location]));
        $showResponse->assertOk()
            ->assertSee('鍵は暗証番号4920で開錠。')
            ->assertSee('基本情報を編集');

        // 編集画面のキャンセルボタンは拠点一覧画面を指す
        $editResponse = $this->actingAs($this->admin)->get(route('timetables.locations.edit', [$this->timetable, $location]));
        $editResponse->assertOk()
            ->assertSee(route('timetables.locations.index', $this->timetable))
            ->assertSee('キャンセル');
    }

    public function test_can_create_new_location(): void
    {
        $response = $this->actingAs($this->admin)->post(route('timetables.locations.store', $this->timetable), [
            'name' => '税務署前駐車場',
            'location_type_id' => $this->type->id,
            'notes' => "【連絡先】090-0000-0000\n【注意事項】夜間施錠必須",
            'is_all_day' => false,
            'display_order' => 20,
            'is_active' => true,
        ]);

        $location = Location::where('timetable_id', $this->timetable->id)->where('name', '税務署前駐車場')->first();
        $this->assertNotNull($location);
        $this->assertEquals($this->type->id, $location->location_type_id);
        $this->assertEquals($this->timetable->id, $location->timetable_id);
        $this->assertStringContainsString('090-0000-0000', $location->notes);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $location->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $location->updated_at);

        $response->assertRedirect(route('timetables.locations.show', [$this->timetable, $location]));
    }

    public function test_can_update_location(): void
    {
        $location = Location::factory()->create([
            'timetable_id' => $this->timetable->id,
            'location_type_id' => $this->type->id,
            'name' => '旧拠点名',
            'notes' => '旧メモ',
        ]);

        $response = $this->actingAs($this->admin)->put(route('timetables.locations.update', [$this->timetable, $location]), [
            'name' => '新拠点名',
            'location_type_id' => $this->type->id,
            'notes' => '更新されたメモ内容',
            'is_all_day' => true,
            'display_order' => 5,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('timetables.locations.index', $this->timetable));
        $location->refresh();
        $this->assertEquals('新拠点名', $location->name);
        $this->assertEquals('更新されたメモ内容', $location->notes);
        $this->assertTrue($location->is_all_day);
    }

    public function test_can_delete_location(): void
    {
        $location = Location::factory()->create([
            'timetable_id' => $this->timetable->id,
            'location_type_id' => $this->type->id,
            'name' => '削除対象拠点',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('timetables.locations.destroy', [$this->timetable, $location]));

        $response->assertRedirect(route('timetables.locations.index', $this->timetable));
        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
    }

    public function test_can_manage_location_types(): void
    {
        // 1. タイプ追加
        $response = $this->actingAs($this->admin)->post(route('location-types.store'), [
            'name' => 'parking',
            'label' => '駐車場',
            'description' => '搬出入・中継用駐車場',
            'color' => 'amber',
            'display_order' => 20,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('location-types.index'));
        $parkingType = LocationType::where('name', 'parking')->first();
        $this->assertNotNull($parkingType);
        $this->assertEquals('駐車場', $parkingType->label);

        // 2. タイプ更新
        $this->actingAs($this->admin)->put(route('location-types.update', $parkingType), [
            'name' => 'parking',
            'label' => '専用駐車場',
            'description' => '更新された説明',
            'color' => 'amber',
            'display_order' => 25,
            'is_active' => true,
        ]);
        $parkingType->refresh();
        $this->assertEquals('専用駐車場', $parkingType->label);

        // 3. タイプ削除
        $this->actingAs($this->admin)->delete(route('location-types.destroy', $parkingType));
        $this->assertDatabaseMissing('location_types', ['id' => $parkingType->id]);
    }

    public function test_cannot_delete_location_type_with_associated_locations(): void
    {
        Location::factory()->create([
            'timetable_id' => $this->timetable->id,
            'location_type_id' => $this->type->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('location-types.destroy', $this->type));

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('location_types', ['id' => $this->type->id]);
    }

    public function test_can_add_multiple_time_slots_on_same_day(): void
    {
        $location = Location::factory()->create([
            'timetable_id' => $this->timetable->id,
            'location_type_id' => $this->type->id,
            'name' => '番所前スペース',
        ]);

        // 午前スロット（利用可能）
        $response1 = $this->actingAs($this->admin)->post(route('timetables.locations.schedules.store', [$this->timetable, $location]), [
            'start_at' => '2026-10-10 09:00:00',
            'end_at' => '2026-10-10 12:00:00',
            'availability_status' => 'available',
            'title' => '午前準備枠',
            'note' => '搬入受取用',
        ]);
        $response1->assertRedirect(route('timetables.locations.show', [$this->timetable, $location]));

        // 夜間スロット（利用不可）
        $response2 = $this->actingAs($this->admin)->post(route('timetables.locations.schedules.store', [$this->timetable, $location]), [
            'start_at' => '2026-10-10 18:00:00',
            'end_at' => '2026-10-10 21:00:00',
            'availability_status' => 'unavailable',
            'title' => '夜間枠',
            'note' => '模擬店準備のため利用不可',
        ]);
        $response2->assertRedirect(route('timetables.locations.show', [$this->timetable, $location]));

        $this->assertCount(2, $location->schedules);
        $schedules = $location->schedules()->orderBy('start_at')->get();
        $this->assertEquals('available', $schedules[0]->availability_status);
        $this->assertEquals('unavailable', $schedules[1]->availability_status);
    }

    public function test_can_update_schedule_availability_status_quickly(): void
    {
        $location = Location::factory()->create([
            'timetable_id' => $this->timetable->id,
            'location_type_id' => $this->type->id,
        ]);
        $schedule = LocationSchedule::create([
            'location_id' => $location->id,
            'start_at' => '2026-10-10 09:00:00',
            'end_at' => '2026-10-10 12:00:00',
            'availability_status' => 'unavailable',
            'title' => '午前枠',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('timetables.locations.schedules.update-status', [$this->timetable, $location, $schedule]), [
            'availability_status' => 'available',
        ]);

        $response->assertRedirect(route('timetables.locations.show', [$this->timetable, $location]));
        $schedule->refresh();
        $this->assertEquals('available', $schedule->availability_status);
    }

    public function test_evaluate_status_at_specific_times(): void
    {
        $location = Location::factory()->create([
            'timetable_id' => $this->timetable->id,
            'location_type_id' => $this->type->id,
            'is_all_day' => false,
            'is_active' => true,
        ]);

        // 午前枠: 09:00 - 12:00 (利用可能)
        LocationSchedule::create([
            'location_id' => $location->id,
            'start_at' => Carbon::parse('2026-10-10 09:00:00'),
            'end_at' => Carbon::parse('2026-10-10 12:00:00'),
            'availability_status' => 'available',
            'title' => '午前枠',
        ]);

        // 夜間枠: 18:00 - 21:00 (利用不可)
        LocationSchedule::create([
            'location_id' => $location->id,
            'start_at' => Carbon::parse('2026-10-10 18:00:00'),
            'end_at' => Carbon::parse('2026-10-10 21:00:00'),
            'availability_status' => 'unavailable',
            'title' => '夜間枠',
        ]);

        // 1. 午前枠内（10:00）: 利用可能
        $evalMorning = $location->evaluateStatusAt(Carbon::parse('2026-10-10 10:00:00'));
        $this->assertEquals(LocationCurrentStatus::Available, $evalMorning->status);

        // 2. 枠外の時間（14:00）: 利用時間外
        $evalAfternoon = $location->evaluateStatusAt(Carbon::parse('2026-10-10 14:00:00'));
        $this->assertEquals(LocationCurrentStatus::UnavailableOutOfHours, $evalAfternoon->status);

        // 3. 夜間枠内（19:00）: 利用不可設定のため利用不可
        $evalNight = $location->evaluateStatusAt(Carbon::parse('2026-10-10 19:00:00'));
        $this->assertEquals(LocationCurrentStatus::UnavailablePending, $evalNight->status);
        $this->assertStringContainsString('未確保（調整中）のため利用できません', $evalNight->detail);

        // 4. 全期間終了後（10/11 00:00）: 枠未登録（利用期間終了後）
        $evalEnded = $location->evaluateStatusAt(Carbon::parse('2026-10-11 00:00:00'));
        $this->assertEquals(LocationCurrentStatus::NoSchedule, $evalEnded->status);
        $this->assertStringContainsString('すべての利用可能期間が終了しました', $evalEnded->detail);
    }

    public function test_all_day_location_is_always_available(): void
    {
        $location = Location::factory()->create([
            'timetable_id' => $this->timetable->id,
            'location_type_id' => $this->type->id,
            'is_all_day' => true,
            'is_active' => true,
        ]);

        $eval = $location->evaluateStatusAt(Carbon::parse('2026-10-10 14:00:00'));
        $this->assertEquals(LocationCurrentStatus::Available, $eval->status);
        $this->assertStringContainsString('終日利用可能', $eval->detail);
    }

    public function test_location_create_and_type_index_render_cleanly_when_no_location_types_exist(): void
    {
        LocationType::query()->delete();

        $this->actingAs($this->admin)
            ->get(route('timetables.locations.create', $this->timetable))
            ->assertOk()
            ->assertSee('拠点タイプ（種別マスタ）がまだ登録されていません');

        $this->actingAs($this->admin)
            ->get(route('location-types.index'))
            ->assertOk()
            ->assertSee('登録されている拠点タイプがありません');
    }

    /**
     * 拠点のドラッグ＆ドロップ並び替えAPIが正常に動作すること
     */
    public function test_can_reorder_locations(): void
    {
        $loc1 = Location::factory()->create(['timetable_id' => $this->timetable->id, 'display_order' => 10]);
        $loc2 = Location::factory()->create(['timetable_id' => $this->timetable->id, 'display_order' => 20]);
        $loc3 = Location::factory()->create(['timetable_id' => $this->timetable->id, 'display_order' => 30]);

        $response = $this->actingAs($this->admin)->postJson(route('timetables.locations.reorder', $this->timetable), [
            'location_ids' => [$loc3->id, $loc1->id, $loc2->id],
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => '拠点の表示順を更新しました。',
            ]);

        $this->assertEquals(10, $loc3->fresh()->display_order);
        $this->assertEquals(20, $loc1->fresh()->display_order);
        $this->assertEquals(30, $loc2->fresh()->display_order);
    }

    /**
     * 拠点登録画面から利用可能時間帯スロットを一括保存でき、display_orderが自動採番されること
     */
    public function test_can_create_location_with_schedules(): void
    {
        $response = $this->actingAs($this->admin)->post(route('timetables.locations.store', $this->timetable), [
            'name' => '番所本部',
            'location_type_id' => $this->type->id,
            'is_all_day' => false,
            'is_active' => true,
            'schedules' => [
                [
                    'start_at' => '2026-10-11 08:00:00',
                    'end_at' => '2026-10-11 12:00:00',
                    'availability_status' => 'available',
                    'title' => '午前準備',
                    'note' => '鍵の受取完了',
                ],
                [
                    'start_at' => '2026-10-11 13:00:00',
                    'end_at' => '2026-10-11 18:00:00',
                    'availability_status' => 'unavailable',
                    'title' => '午後撤収',
                    'note' => '前面道路規制中',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $location = Location::where('timetable_id', $this->timetable->id)->where('name', '番所本部')->first();
        $this->assertNotNull($location);
        $this->assertEquals(10, $location->display_order); // 自動採番
        $this->assertCount(2, $location->schedules);
        $response->assertRedirect(route('timetables.locations.show', [$this->timetable, $location]));
    }

    /**
     * 拠点編集画面から利用可能時間帯スロットを一括更新・削除・新規追加できること
     */
    public function test_can_update_location_with_schedules(): void
    {
        $location = Location::factory()->create([
            'timetable_id' => $this->timetable->id,
            'location_type_id' => $this->type->id,
            'is_all_day' => false,
            'display_order' => 50,
        ]);

        $schedule1 = LocationSchedule::create([
            'location_id' => $location->id,
            'start_at' => '2026-10-11 08:00:00',
            'end_at' => '2026-10-11 12:00:00',
            'availability_status' => 'available',
            'title' => '旧枠1',
        ]);
        $schedule2 = LocationSchedule::create([
            'location_id' => $location->id,
            'start_at' => '2026-10-11 13:00:00',
            'end_at' => '2026-10-11 17:00:00',
            'availability_status' => 'available',
            'title' => '旧枠2（削除対象）',
        ]);

        // schedule1を更新、schedule2は送信せず削除、新規枠3を追加
        $response = $this->actingAs($this->admin)->put(route('timetables.locations.update', [$this->timetable, $location]), [
            'name' => '番所本部更新',
            'location_type_id' => $this->type->id,
            'is_all_day' => false,
            'schedules' => [
                [
                    'id' => $schedule1->id,
                    'start_at' => '2026-10-11 08:30:00',
                    'end_at' => '2026-10-11 12:30:00',
                    'availability_status' => 'unavailable',
                    'title' => '更新後枠1',
                    'note' => '利用不可に変更',
                ],
                [
                    'start_at' => '2026-10-11 18:00:00',
                    'end_at' => '2026-10-11 21:00:00',
                    'availability_status' => 'available',
                    'title' => '新規枠3',
                ],
            ],
        ]);

        $response->assertRedirect(route('timetables.locations.index', $this->timetable));

        $location->refresh();
        $this->assertEquals(50, $location->display_order); // 既存の表示順を維持
        $this->assertCount(2, $location->schedules);
        $this->assertDatabaseHas('location_schedules', [
            'id' => $schedule1->id,
            'title' => '更新後枠1',
            'availability_status' => 'unavailable',
        ]);
        $this->assertDatabaseMissing('location_schedules', [
            'id' => $schedule2->id,
        ]);
        $this->assertDatabaseHas('location_schedules', [
            'location_id' => $location->id,
            'title' => '新規枠3',
        ]);
    }

    /**
     * 詳細画面から削除カード、時間帯追加リンク、基本仕様の編集リンクが削除されていること
     */
    public function test_location_show_screen_has_no_delete_card_or_add_schedule_link(): void
    {
        $location = Location::factory()->create([
            'timetable_id' => $this->timetable->id,
            'location_type_id' => $this->type->id,
            'name' => 'テスト対象拠点',
        ]);

        $response = $this->actingAs($this->admin)->get(route('timetables.locations.show', [$this->timetable, $location]));

        $response->assertOk()
            ->assertDontSee('この拠点を完全に削除する')
            ->assertDontSee('data-action="open-schedule-modal"')
            ->assertDontSee('✏️ 編集する');
    }

    /**
     * 登録・編集画面に表示順フォームが存在しないこと
     */
    public function test_create_and_edit_screens_have_no_display_order_input(): void
    {
        $location = Location::factory()->create([
            'timetable_id' => $this->timetable->id,
            'location_type_id' => $this->type->id,
        ]);

        $createRes = $this->actingAs($this->admin)->get(route('timetables.locations.create', $this->timetable));
        $createRes->assertOk()->assertDontSee('name="display_order"', false);

        $editRes = $this->actingAs($this->admin)->get(route('timetables.locations.edit', [$this->timetable, $location]));
        $editRes->assertOk()->assertDontSee('name="display_order"', false);
    }

    /**
     * 登録・編集画面に「新しいタイプを追加」リンクが存在しないこと
     */
    public function test_create_and_edit_screens_have_no_add_type_link(): void
    {
        $location = Location::factory()->create([
            'timetable_id' => $this->timetable->id,
            'location_type_id' => $this->type->id,
        ]);

        $createRes = $this->actingAs($this->admin)->get(route('timetables.locations.create', $this->timetable));
        $createRes->assertOk()->assertDontSee('新しいタイプを追加');

        $editRes = $this->actingAs($this->admin)->get(route('timetables.locations.edit', [$this->timetable, $location]));
        $editRes->assertOk()->assertDontSee('新しいタイプを追加');
    }

    /**
     * 一覧画面で本日の利用可能枠が3種類（利用可（確保済）/一部未確保/本日枠無し）で正しく表示されること
     */
    public function test_locations_daily_availability_status_display(): void
    {
        // 1. 終日利用可能拠点 -> 利用可（確保済）
        $loc1 = Location::factory()->create([
            'timetable_id' => $this->timetable->id,
            'location_type_id' => $this->type->id,
            'name' => '終日利用拠点A',
            'is_all_day' => true,
        ]);

        // 2. スロットあり（全て確保済） -> 利用可（確保済）
        $loc2 = Location::factory()->create([
            'timetable_id' => $this->timetable->id,
            'location_type_id' => $this->type->id,
            'name' => '時間枠確保済拠点B',
            'is_all_day' => false,
        ]);
        LocationSchedule::create([
            'location_id' => $loc2->id,
            'start_at' => '2026-10-11 09:00:00',
            'end_at' => '2026-10-11 17:00:00',
            'availability_status' => 'available',
        ]);

        // 3. スロットあり（一部未確保） -> 一部未確保
        $loc3 = Location::factory()->create([
            'timetable_id' => $this->timetable->id,
            'location_type_id' => $this->type->id,
            'name' => '一部未確保拠点C',
            'is_all_day' => false,
        ]);
        LocationSchedule::create([
            'location_id' => $loc3->id,
            'start_at' => '2026-10-11 09:00:00',
            'end_at' => '2026-10-11 12:00:00',
            'availability_status' => 'available',
        ]);
        LocationSchedule::create([
            'location_id' => $loc3->id,
            'start_at' => '2026-10-11 13:00:00',
            'end_at' => '2026-10-11 17:00:00',
            'availability_status' => 'unavailable',
        ]);

        // 4. スロットなし -> 本日枠無し
        $loc4 = Location::factory()->create([
            'timetable_id' => $this->timetable->id,
            'location_type_id' => $this->type->id,
            'name' => '枠未登録拠点D',
            'is_all_day' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('timetables.locations.index', $this->timetable));

        $response->assertOk();
        $response->assertSee('利用可（確保済）');
        $response->assertSee('一部未確保');
        $response->assertSee('本日枠無し');
    }
}
