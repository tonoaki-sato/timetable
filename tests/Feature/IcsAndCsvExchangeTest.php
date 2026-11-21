<?php

namespace Tests\Feature;

use App\Enums\EquipmentCheckoutRule;
use App\Models\Equipment;
use App\Models\Lane;
use App\Models\Location;
use App\Models\Schedule;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class IcsAndCsvExchangeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
        ]);
    }

    /**
     * 単一スケジュール・スケジュール一覧・マイシフトの .ics (iCalendar) エクスポートが正しいこと
     */
    public function test_ics_exports_return_valid_icalendar_payloads(): void
    {
        $schedule = Schedule::factory()->create([
            'title' => '本祭事前テスト会議',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-10',
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'location' => 'コミュニティルーム',
            'created_by' => $this->admin->id,
        ]);

        $singleRes = $this->actingAs($this->admin)->get(route('schedules.ics', $schedule));
        $singleRes->assertStatus(200);
        $this->assertStringStartsWith('text/calendar', (string) $singleRes->headers->get('Content-Type'));
        $content = (string) $singleRes->getContent();
        $this->assertStringContainsString("BEGIN:VCALENDAR\r\n", $content);
        $this->assertStringContainsString('TZID:Asia/Tokyo', $content);
        $this->assertStringContainsString('BEGIN:VEVENT', $content);
        $this->assertStringContainsString('DTSTART;TZID=Asia/Tokyo:20261010T140000', $content);
        $this->assertStringContainsString('SUMMARY:本祭事前テスト会議', $content);
        $this->assertStringContainsString('END:VCALENDAR', $content);

        $allRes = $this->actingAs($this->admin)->get(route('schedules.ics.all', ['tab' => 'all']));
        $allRes->assertStatus(200);
        $this->assertStringContainsString('SUMMARY:本祭事前テスト会議', (string) $allRes->getContent());

        // スタッフマイシフト .ics
        $staff = Staff::factory()->create(['name' => 'シフトICS担当', 'is_active' => true]);
        $timetable = Timetable::factory()->create(['target_date' => '2026-10-18', 'created_by' => $this->admin->id]);
        $lane = Lane::factory()->create(['timetable_id' => $timetable->id, 'name' => '受付班']);
        $task = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '受付設営作業',
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $staff->id,
        ]);

        $shiftRes = $this->get(route('staff.myshift.ics', ['token' => $staff->access_token]));
        $shiftRes->assertStatus(200);
        $shiftContent = (string) $shiftRes->getContent();
        $this->assertStringContainsString('SUMMARY:[受付班] 受付設営作業', $shiftContent);
        $this->assertStringContainsString('DTSTART;TZID=Asia/Tokyo:20261018T083000', $shiftContent);

        // 無効トークンは404
        $this->get(route('staff.myshift.ics', ['token' => 'invalid-token-value']))->assertStatus(404);
    }

    /**
     * 出展・出店者CSVのエクスポート（BOM・数式サニタイズ）およびトランザクション付きインポートが機能すること
     */
    public function test_vendors_csv_export_and_transactional_import(): void
    {
        Vendor::create([
            'name' => '=DANGEROUS_VENDOR',
            'contact_person' => '危険テスト',
            'contact' => '090-0000-0000',
            'notes' => '+FORMULA',
        ]);

        $exportRes = $this->actingAs($this->admin)->get(route('vendors.export-csv'));
        $exportRes->assertStatus(200);
        $csvOutput = $exportRes->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csvOutput);
        $this->assertStringContainsString("'=DANGEROUS_VENDOR", $csvOutput);
        $this->assertStringContainsString("'+FORMULA", $csvOutput);

        // 正常CSVインポート（4列新規登録）
        $validCsv = "出展・出店者名,窓口担当者,連絡先,特記事項・備考\n"
            ."新規やきそば屋,山田太郎,090-1111-2222,電源1口希望\n";
        $validFile = UploadedFile::fake()->createWithContent('vendors.csv', $validCsv);

        $importRes = $this->actingAs($this->admin)->post(route('vendors.import-csv'), [
            'csv_file' => $validFile,
        ]);
        $importRes->assertRedirect(route('vendors.index'));
        $importRes->assertSessionHas('success');
        $this->assertDatabaseHas('vendors', [
            'name' => '新規やきそば屋',
            'contact_person' => '山田太郎',
            'contact' => '090-1111-2222',
        ]);

        // バリデーションエラー行を含むCSVはロールバックされ1行目も登録されないこと
        $invalidCsv = "出展・出店者名,窓口担当者,連絡先,特記事項・備考\n"
            ."ロールバック予定店舗,担当者,090-3333-4444,\n"
            .",名称なしエラー行,090-5555-6666,\n";
        $invalidFile = UploadedFile::fake()->createWithContent('vendors_invalid.csv', $invalidCsv);

        $errorRes = $this->actingAs($this->admin)->post(route('vendors.import-csv'), [
            'csv_file' => $invalidFile,
        ]);
        $errorRes->assertSessionHasErrors('csv_file');
        $this->assertDatabaseMissing('vendors', ['name' => 'ロールバック予定店舗']);
    }

    /**
     * 資機材CSVのエクスポート・認可・日本語ラベル対応インポートおよびロールバックが機能すること
     */
    public function test_equipments_csv_export_import_and_authorization(): void
    {
        $banshoWarehouse = Location::factory()->create(['name' => '番所倉庫']);
        $banshoUnderground = Location::factory()->create(['name' => '番所地下倉庫']);

        Equipment::factory()->create([
            'name' => '@式テスト機材',
            'type' => 'committee',
            'checkout_rule' => EquipmentCheckoutRule::PermissionRequired->value,
            'quantity' => 12,
            'unit' => '個',
            'storage_location_id' => $banshoWarehouse->id,
            'created_by' => $this->admin->id,
        ]);

        $exportRes = $this->actingAs($this->admin)->get(route('equipments.export-csv'));
        $exportRes->assertStatus(200);
        $csvOutput = $exportRes->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csvOutput);
        $this->assertStringContainsString("'@式テスト機材", $csvOutput);
        $this->assertStringContainsString('実行委員会管理（自前保有）', $csvOutput);
        $this->assertStringContainsString('本陣許可制', $csvOutput);
        $this->assertStringContainsString('番所倉庫', $csvOutput);

        // 日本語表記・内部キー混在の正常CSVインポート（9列）
        $validCsv = "ID,備品名,区分,持ち出しルール,基準数量,単位,概数フラグ,定常保管場所,備考\n"
            .",CSV登録テント,イベント会社レンタル,班長限定,4,張,0,番所倉庫,業者搬入\n"
            .",CSV登録コーン,committee,free,30,本,1,番所地下倉庫,予備分\n";
        $validFile = UploadedFile::fake()->createWithContent('equipments.csv', $validCsv);

        // 一般ユーザー (userロール) には作成権限がないため 403
        $this->actingAs($this->regularUser)
            ->post(route('equipments.import-csv'), ['csv_file' => $validFile])
            ->assertStatus(403);

        $importRes = $this->actingAs($this->admin)->post(route('equipments.import-csv'), [
            'csv_file' => UploadedFile::fake()->createWithContent('equipments.csv', $validCsv),
        ]);
        $importRes->assertRedirect(route('equipments.index'));
        $importRes->assertSessionHas('success');

        $this->assertDatabaseHas('equipments', [
            'name' => 'CSV登録テント',
            'type' => 'rental',
            'checkout_rule' => 'leader_only',
            'quantity' => 4,
            'storage_location_id' => null, // rentalは定常保管場所をnull正規化
        ]);
        $this->assertDatabaseHas('equipments', [
            'name' => 'CSV登録コーン',
            'type' => 'committee',
            'checkout_rule' => 'free',
            'quantity' => 30,
            'is_approximate_quantity' => true,
            'storage_location_id' => $banshoUnderground->id,
        ]);

        // 未登録の拠点名を含むCSVはバリデーションエラーとなり全体ロールバックされること
        $unknownLocationCsv = "ID,備品名,区分,持ち出しルール,基準数量,単位,概数フラグ,定常保管場所,備考\n"
            .",ロールバック予定機材,committee,free,10,個,0,番所倉庫,\n"
            .",未登録拠点機材,committee,free,5,個,0,存在しない倉庫,\n";
        $unknownLocationRes = $this->actingAs($this->admin)->post(route('equipments.import-csv'), [
            'csv_file' => UploadedFile::fake()->createWithContent('equipments_unknown_loc.csv', $unknownLocationCsv),
        ]);
        $unknownLocationRes->assertSessionHasErrors('csv_file');
        $this->assertDatabaseMissing('equipments', ['name' => 'ロールバック予定機材']);

        // 数量エラー行を含む場合は全体ロールバック
        $invalidCsv = "ID,備品名,区分,持ち出しルール,基準数量,単位,概数フラグ,定常保管場所,備考\n"
            .",ロールバック予定機材,committee,free,10,個,0,番所倉庫,\n"
            .",数量不正機材,committee,free,0,個,0,番所倉庫,\n";
        $errorRes = $this->actingAs($this->admin)->post(route('equipments.import-csv'), [
            'csv_file' => UploadedFile::fake()->createWithContent('equipments_invalid.csv', $invalidCsv),
        ]);
        $errorRes->assertSessionHasErrors('csv_file');
        $this->assertDatabaseMissing('equipments', ['name' => 'ロールバック予定機材']);
    }
}
