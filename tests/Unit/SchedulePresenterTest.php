<?php

namespace Tests\Unit;

use App\Models\Schedule;
use App\Presenters\SchedulePresenter;
use Tests\TestCase;

class SchedulePresenterTest extends TestCase
{
    /**
     * 日付未設定時のpdfDateおよびpdfTimeの挙動検証
     */
    public function test_pdf_date_and_time_without_dates(): void
    {
        $schedule = new Schedule([
            'start_date' => null,
            'end_date' => null,
            'start_time' => null,
            'end_time' => null,
        ]);

        $presenter = new SchedulePresenter($schedule);

        $this->assertSame('-', $presenter->pdfDate());
        $this->assertSame('-', $presenter->pdfTime());
    }

    /**
     * 単日開催・時間帯ありのpdfDateおよびpdfTimeの挙動検証
     */
    public function test_pdf_date_and_time_single_day_with_time_range(): void
    {
        $schedule = new Schedule([
            'start_date' => '2027-01-10', // 日曜日
            'end_date' => null,
            'start_time' => '18:00:00',
            'end_time' => '20:00:00',
        ]);

        $presenter = new SchedulePresenter($schedule);

        $this->assertSame('2027/01/10(日)', $presenter->pdfDate());
        $this->assertSame('18:00 〜 20:00', $presenter->pdfTime());
    }

    /**
     * 複数日開催（期間）のpdfDateおよびpdfTimeの挙動検証
     */
    public function test_pdf_date_and_time_multi_day_period(): void
    {
        $schedule = new Schedule([
            'start_date' => '2027-05-15', // 土曜日
            'end_date' => '2027-07-31',   // 土曜日
            'start_time' => null,
            'end_time' => null,
        ]);

        $presenter = new SchedulePresenter($schedule);

        $this->assertSame('2027/05/15(土) 〜 2027/07/31(土)', $presenter->pdfDate());
        $this->assertSame('-', $presenter->pdfTime());
    }

    /**
     * 開始時間のみ設定されている場合のpdfTimeの挙動検証
     */
    public function test_pdf_time_with_start_time_only(): void
    {
        $schedule = new Schedule([
            'start_date' => '2027-03-01',
            'start_time' => '09:00:00',
            'end_time' => null,
        ]);

        $presenter = new SchedulePresenter($schedule);

        $this->assertSame('09:00 〜', $presenter->pdfTime());
    }
}
