<?php

namespace Tests\Unit;

use App\Models\Timetable;
use PHPUnit\Framework\TestCase;

class TimetableTest extends TestCase
{
    /**
     * 道路予定の有無を正しく判定できること
     */
    public function test_has_road_periods(): void
    {
        $timetable = new Timetable;

        $timetable->road_periods = null;
        $this->assertFalse($timetable->hasRoadPeriods());

        $timetable->road_periods = [];
        $this->assertFalse($timetable->hasRoadPeriods());

        $timetable->road_periods = [
            ['start_time' => '', 'end_time' => '', 'label' => ''],
        ];
        $this->assertFalse($timetable->hasRoadPeriods());

        $timetable->road_periods = [
            ['start_time' => '10:00', 'end_time' => '18:00', 'label' => '通行止め', 'status' => 'closed'],
        ];
        $this->assertTrue($timetable->hasRoadPeriods());
    }

    /**
     * まつり運行予定の有無を正しく判定できること
     */
    public function test_has_festival_periods(): void
    {
        $timetable = new Timetable;

        $timetable->festival_periods = null;
        $this->assertFalse($timetable->hasFestivalPeriods());

        $timetable->festival_periods = [];
        $this->assertFalse($timetable->hasFestivalPeriods());

        $timetable->festival_periods = [
            ['start_time' => '', 'end_time' => '', 'label' => ''],
        ];
        $this->assertFalse($timetable->hasFestivalPeriods());

        $timetable->festival_periods = [
            ['start_time' => '11:00', 'end_time' => '17:00', 'label' => '開催中', 'status' => 'active'],
        ];
        $this->assertTrue($timetable->hasFestivalPeriods());
    }
}

