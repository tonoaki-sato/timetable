<?php

namespace Tests\Feature;

use App\DTOs\LocationListItemData;
use App\Enums\LocationCurrentStatus;
use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\LocationType;
use App\Models\User;
use App\Services\LocationAvailabilityService;
use App\Services\LocationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LocationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private LocationType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_can_view_location_index_screen(): void
    {
        $location = Location::factory()->create([
            'location_type_id' => $this->type->id,
            'name' => '番所',
            'notes' => '鍵は暗証番号4920で開錠。',
        ]);

        LocationSchedule::create([
            'location_id' => $location->id,
            'start_at' => now()->addHour(),
            'end_at' => now()->addHours(3),
            'availability_status' => 'available',
            'title' => '午後枠',
            'note' => '鍵受取後に開放',
        ]);

        $response = $this->actingAs($this->admin)->get(route('locations.index'));

        $response->assertOk()
            ->assertSee('拠点マスター')
            ->assertSee('番所')
            ->assertSee('鍵は暗証番号4920で開錠。')
            ->assertSee('午後枠')
            ->assertSee('編集')
            ->assertSee('削除')
            // KPIサマリーカードは非表示
            ->assertDontSee('登録拠点総数')
            ->assertDontSee('終日利用可能（常設）')
            ->assertDontSee('時間枠指定（貸切等）')
            // 表示順入力欄・管理画面リンク・枠管理ボタンが存在しないこと
            ->assertDontSee('name="display_order"', false)
            ->assertDontSee('利用可能枠の管理画面を開く')
            ->assertDontSee('>枠管理<', false)
            // モーダル・利用枠フォーム・Viteアセットの存在確認
            ->assertSee('modal-location-detail')
            ->assertSee('modal-location-form')
            ->assertSee('btn-open-create-location')
            ->assertSee('schedules-form-container')
            ->assertSee('add-schedule-row-btn')
            ->assertSee('detail-upcoming-list')
            ->assertSee('detail-past-list')
            ->assertSee('locations-');
    }

    /**
     * 詳細モーダル用に、現在日時を含まない過去枠と現在日時を含む未来枠が正しく分離されること
     */
    public function test_detail_modal_splits_past_and_upcoming_schedules_based_on_now(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 12:00:00', 'Asia/Tokyo'));

        $location = Location::factory()->create([
            'location_type_id' => $this->type->id,
            'name' => 'テスト集約スペース',
            'is_all_day' => false,
        ]);

        // 1. 過去枠（現在日時より前に終了）
        LocationSchedule::create([
            'location_id' => $location->id,
            'start_at' => '2026-11-21 08:00:00',
            'end_at' => '2026-11-21 11:59:00',
            'availability_status' => 'available',
            'title' => '終了済み午前枠',
        ]);

        // 2. さらに古い過去枠（過去枠の降順確認用）
        LocationSchedule::create([
            'location_id' => $location->id,
            'start_at' => '2026-11-20 09:00:00',
            'end_at' => '2026-11-20 17:00:00',
            'availability_status' => 'unavailable',
            'title' => '前日調整枠',
        ]);

        // 3. 現在進行中枠（start_at <= now <= end_at -> 現在・今後へ分類 + is_current = true）
        LocationSchedule::create([
            'location_id' => $location->id,
            'start_at' => '2026-11-21 11:00:00',
            'end_at' => '2026-11-21 14:00:00',
            'availability_status' => 'available',
            'title' => '現在進行中枠',
        ]);

        // 4. 未来枠（start_at > now -> 現在・今後へ分類 + is_current = false）
        LocationSchedule::create([
            'location_id' => $location->id,
            'start_at' => '2026-11-22 09:00:00',
            'end_at' => '2026-11-22 17:00:00',
            'availability_status' => 'unavailable',
            'title' => '翌日予約枠',
        ]);

        $viewData = app(LocationService::class)->getIndexViewData();
        /** @var LocationListItemData $item */
        $item = $viewData['locationsWithStatus']->firstWhere('location.id', $location->id);
        $this->assertNotNull($item);

        // サービス側分類検証
        $this->assertCount(2, $item->upcomingSchedules);
        $this->assertSame(['現在進行中枠', '翌日予約枠'], $item->upcomingSchedules->pluck('title')->all());

        $this->assertCount(2, $item->pastSchedules);
        $this->assertSame(['終了済み午前枠', '前日調整枠'], $item->pastSchedules->pluck('title')->all());

        // ペイロード検証
        $detailPayload = $item->toDetailSchedulesPayload($viewData['now']);
        $this->assertCount(2, $detailPayload['upcoming']);
        $this->assertTrue($detailPayload['upcoming'][0]['is_current']);
        $this->assertFalse($detailPayload['upcoming'][1]['is_current']);
        $this->assertCount(2, $detailPayload['past']);

        $formPayload = $item->toFormSchedulesPayload($viewData['now']);
        $this->assertCount(4, $formPayload);
        $this->assertTrue($formPayload[0]['is_past']);
        $this->assertFalse($formPayload[2]['is_past']);

        // レスポンスHTMLにJSONデータが出力されていること
        $response = $this->actingAs($this->admin)->get(route('locations.index'));
        $response->assertOk()
            ->assertSee('data-detail-schedules', false)
            ->assertSee('data-schedules', false)
            ->assertSee('現在進行中枠')
            ->assertSee('終了済み午前枠');
    }

    public function test_can_create_new_location(): void
    {
        $response = $this->actingAs($this->admin)->post(route('locations.store'), [
            'name' => '税務署前駐車場',
            'location_type_id' => $this->type->id,
            'notes' => "【連絡先】090-0000-0000\n【注意事項】夜間施錠必須",
            'is_all_day' => false,
            'display_order' => 999, // 無視され自動採番されること
            'is_active' => true,
        ]);

        $location = Location::where('name', '税務署前駐車場')->first();
        $this->assertNotNull($location);
        $this->assertEquals($this->type->id, $location->location_type_id);
        $this->assertEquals(10, $location->display_order);
        $this->assertStringContainsString('090-0000-0000', $location->notes);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $location->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $location->updated_at);

        $response->assertRedirect(route('locations.index'));
    }

    public function test_can_update_location(): void
    {
        $location = Location::factory()->create([
            'location_type_id' => $this->type->id,
            'name' => '旧拠点名',
            'notes' => '旧メモ',
            'display_order' => 50,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('locations.update', $location), [
            'name' => '新拠点名',
            'location_type_id' => $this->type->id,
            'notes' => '更新されたメモ内容',
            'is_all_day' => true,
            'display_order' => 999, // フォーム送信されても無視され既存値を維持
            'is_active' => false,
        ]);

        $response->assertRedirect(route('locations.index'));
        $location->refresh();
        $this->assertEquals('新拠点名', $location->name);
        $this->assertEquals('更新されたメモ内容', $location->notes);
        $this->assertTrue($location->is_all_day);
        $this->assertFalse($location->is_active);
        $this->assertEquals(50, $location->display_order);
    }

    public function test_can_delete_location(): void
    {
        $location = Location::factory()->create([
            'location_type_id' => $this->type->id,
            'name' => '削除対象拠点',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('locations.destroy', $location));

        $response->assertRedirect(route('locations.index'));
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
            'location_type_id' => $this->type->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('location-types.destroy', $this->type));

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('location_types', ['id' => $this->type->id]);
    }

    public function test_evaluate_status_at_specific_times(): void
    {
        $location = Location::factory()->create([
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

        $availabilityService = app(LocationAvailabilityService::class);

        // 1. 午前枠内（10:00）: 利用可能
        $evalMorning = $availabilityService->evaluateLocationStatus($location, Carbon::parse('2026-10-10 10:00:00'));
        $this->assertEquals(LocationCurrentStatus::Available, $evalMorning->status);

        // 2. 枠外の時間（14:00）: 利用時間外
        $evalAfternoon = $availabilityService->evaluateLocationStatus($location, Carbon::parse('2026-10-10 14:00:00'));
        $this->assertEquals(LocationCurrentStatus::UnavailableOutOfHours, $evalAfternoon->status);

        // 3. 夜間枠内（19:00）: 利用不可設定のため利用不可
        $evalNight = $availabilityService->evaluateLocationStatus($location, Carbon::parse('2026-10-10 19:00:00'));
        $this->assertEquals(LocationCurrentStatus::UnavailablePending, $evalNight->status);
        $this->assertStringContainsString('未確保（調整中）のため利用できません', $evalNight->detail);

        // 4. 全期間終了後（10/11 00:00）: 枠未登録（利用期間終了後）
        $evalEnded = $availabilityService->evaluateLocationStatus($location, Carbon::parse('2026-10-11 00:00:00'));
        $this->assertEquals(LocationCurrentStatus::NoSchedule, $evalEnded->status);
        $this->assertStringContainsString('すべての利用可能期間が終了しました', $evalEnded->detail);
    }

    public function test_all_day_location_is_always_available(): void
    {
        $location = Location::factory()->create([
            'location_type_id' => $this->type->id,
            'is_all_day' => true,
            'is_active' => true,
        ]);

        $eval = app(LocationAvailabilityService::class)->evaluateLocationStatus($location, Carbon::parse('2026-10-10 14:00:00'));
        $this->assertEquals(LocationCurrentStatus::Available, $eval->status);
        $this->assertStringContainsString('終日利用可能', $eval->detail);
    }

    public function test_location_index_and_type_index_render_cleanly_when_no_location_types_exist(): void
    {
        LocationType::query()->delete();

        $this->actingAs($this->admin)
            ->get(route('locations.index'))
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
        $loc1 = Location::factory()->create(['display_order' => 10]);
        $loc2 = Location::factory()->create(['display_order' => 20]);
        $loc3 = Location::factory()->create(['display_order' => 30]);

        $response = $this->actingAs($this->admin)->postJson(route('locations.reorder'), [
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
     * 拠点登録時に利用枠を一括保存でき、display_orderが自動採番されること
     */
    public function test_can_create_location_with_schedules_and_auto_display_order(): void
    {
        $response = $this->actingAs($this->admin)->post(route('locations.store'), [
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

        $location = Location::where('name', '番所本部')->first();
        $this->assertNotNull($location);
        $this->assertEquals(10, $location->display_order); // 自動採番
        $this->assertCount(2, $location->schedules);
        $response->assertRedirect(route('locations.index'));
    }

    /**
     * 拠点編集モーダル経由で利用枠を一括更新・削除・新規追加および全削除できること
     */
    public function test_can_update_location_with_schedules_including_delete_all(): void
    {
        $location = Location::factory()->create([
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

        // 1. schedule1を更新、schedule2は送信せず削除、新規枠3を追加
        $response = $this->actingAs($this->admin)->put(route('locations.update', $location), [
            'name' => '番所本部更新',
            'location_type_id' => $this->type->id,
            'is_all_day' => false,
            'is_active' => true,
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

        $response->assertRedirect(route('locations.index'));

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

        // 2. schedules を空にして送信すると全枠が削除されること
        $clearRes = $this->actingAs($this->admin)->put(route('locations.update', $location), [
            'name' => '番所本部更新',
            'location_type_id' => $this->type->id,
            'is_all_day' => false,
            'is_active' => true,
        ]);
        $clearRes->assertRedirect(route('locations.index'));
        $this->assertCount(0, $location->fresh()->schedules);
    }

    /**
     * 他拠点に属するスケジュールIDを編集送信しても拒否されること（IDOR防止）
     */
    public function test_cannot_update_another_locations_schedule_id(): void
    {
        $locationA = Location::factory()->create(['location_type_id' => $this->type->id]);
        $locationB = Location::factory()->create(['location_type_id' => $this->type->id]);
        $otherSchedule = LocationSchedule::create([
            'location_id' => $locationB->id,
            'start_at' => '2026-10-11 08:00:00',
            'end_at' => '2026-10-11 12:00:00',
            'availability_status' => 'available',
            'title' => '他拠点枠',
        ]);

        $response = $this->actingAs($this->admin)->put(route('locations.update', $locationA), [
            'name' => $locationA->name,
            'location_type_id' => $this->type->id,
            'is_all_day' => false,
            'is_active' => true,
            'schedules' => [
                [
                    'id' => $otherSchedule->id,
                    'start_at' => '2026-10-11 09:00:00',
                    'end_at' => '2026-10-11 11:00:00',
                    'availability_status' => 'available',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('schedules.0.id');
        $this->assertSame($locationB->id, $otherSchedule->fresh()->location_id);
    }

    /**
     * モーダル完結化に伴い廃止された独立ページおよび個別スケジュールルートが存在しないこと
     */
    public function test_obsolete_location_pages_and_routes_do_not_exist(): void
    {
        $location = Location::factory()->create([
            'location_type_id' => $this->type->id,
        ]);

        $this->assertFalse(Route::has('locations.create'));
        $this->assertFalse(Route::has('locations.show'));
        $this->assertFalse(Route::has('locations.edit'));
        $this->assertFalse(Route::has('locations.schedules.store'));
        $this->assertFalse(Route::has('locations.schedules.update'));
        $this->assertFalse(Route::has('locations.schedules.update-status'));
        $this->assertFalse(Route::has('locations.schedules.destroy'));

        $this->actingAs($this->admin)->get('/locations/create')->assertStatus(405);
        $this->actingAs($this->admin)->get("/locations/{$location->id}")->assertStatus(405);
        $this->actingAs($this->admin)->get("/locations/{$location->id}/edit")->assertNotFound();
    }

    /**
     * 一覧画面で本日の利用可能枠が3種類（利用可（確保済）/一部未確保/本日枠無し）で正しく表示されること
     */
    public function test_locations_daily_availability_status_display(): void
    {
        $today = Carbon::today()->format('Y-m-d');

        // 1. 終日利用可能拠点 -> 利用可（確保済）
        Location::factory()->create([
            'location_type_id' => $this->type->id,
            'name' => '終日利用拠点A',
            'is_all_day' => true,
        ]);

        // 2. スロットあり（全て確保済） -> 利用可（確保済）
        $loc2 = Location::factory()->create([
            'location_type_id' => $this->type->id,
            'name' => '時間枠確保済拠点B',
            'is_all_day' => false,
        ]);
        LocationSchedule::create([
            'location_id' => $loc2->id,
            'start_at' => "{$today} 09:00:00",
            'end_at' => "{$today} 17:00:00",
            'availability_status' => 'available',
        ]);

        // 3. スロットあり（一部未確保） -> 一部未確保
        $loc3 = Location::factory()->create([
            'location_type_id' => $this->type->id,
            'name' => '一部未確保拠点C',
            'is_all_day' => false,
        ]);
        LocationSchedule::create([
            'location_id' => $loc3->id,
            'start_at' => "{$today} 09:00:00",
            'end_at' => "{$today} 12:00:00",
            'availability_status' => 'available',
        ]);
        LocationSchedule::create([
            'location_id' => $loc3->id,
            'start_at' => "{$today} 13:00:00",
            'end_at' => "{$today} 17:00:00",
            'availability_status' => 'unavailable',
        ]);

        // 4. スロットなし -> 本日枠無し
        Location::factory()->create([
            'location_type_id' => $this->type->id,
            'name' => '枠未登録拠点D',
            'is_all_day' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('locations.index'));

        $response->assertOk();
        $response->assertSee('利用可（確保済）');
        $response->assertSee('一部未確保');
        $response->assertSee('本日枠無し');
    }
}
