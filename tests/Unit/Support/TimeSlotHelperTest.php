<?php

namespace Tests\Unit\Support;

use App\Models\Timetable;
use App\Support\TimeSlotHelper;
use Tests\TestCase;

class TimeSlotHelperTest extends TestCase
{
    public function test_generate_for_timetable_returns_default_range_when_null(): void
    {
        $slots = TimeSlotHelper::generateForTimetable(null);

        $this->assertCount(26, $slots);
        $this->assertSame('07:00', $slots[0]['time']);
        $this->assertSame('19:30', $slots[25]['time']);
        $this->assertSame(['status' => 'open', 'label' => '-'], $slots[0]['road']);
        $this->assertSame(['status' => 'inactive', 'label' => '-'], $slots[0]['festival']);
    }

    public function test_generate_for_timetable_rounds_to_half_hour_boundaries_and_includes_periods(): void
    {
        $timetable = new Timetable([
            'start_time' => '08:45:00',
            'end_time' => '11:40:00',
            'road_periods' => [
                ['start_time' => '10:00', 'end_time' => '11:30', 'status' => 'closed', 'label' => '通行止め'],
            ],
            'festival_periods' => [
                ['start_time' => '11:00', 'end_time' => '12:00', 'status' => 'active', 'label' => '開催中'],
            ],
        ]);

        $slots = TimeSlotHelper::generateForTimetable($timetable);

        $this->assertSame('08:30', $slots[0]['time']);
        $this->assertSame('12:00', $slots[count($slots) - 1]['time']);
        $this->assertCount(8, $slots);

        $slotsByTime = collect($slots)->keyBy('time');
        $this->assertSame(['status' => 'open', 'label' => '-'], $slotsByTime->get('09:30')['road']);
        $this->assertSame(['status' => 'closed', 'label' => '通行止め'], $slotsByTime->get('10:00')['road']);
        $this->assertSame(['status' => 'active', 'label' => '開催中'], $slotsByTime->get('11:00')['festival']);
    }

    public function test_generate_for_timetable_caps_at_forty_eight_slots(): void
    {
        $timetable = new Timetable([
            'start_time' => '00:00:00',
            'end_time' => '23:59:00',
        ]);

        $slots = TimeSlotHelper::generateForTimetable($timetable);

        $this->assertCount(48, $slots);
        $this->assertSame('00:00', $slots[0]['time']);
        $this->assertSame('23:30', $slots[47]['time']);
    }

    public function test_hhmm_to_minutes_parses_valid_and_invalid_strings(): void
    {
        $this->assertSame(0, TimeSlotHelper::hhmmToMinutes('00:00'));
        $this->assertSame(510, TimeSlotHelper::hhmmToMinutes('08:30'));
        $this->assertSame(1185, TimeSlotHelper::hhmmToMinutes('19:45:00'));
        $this->assertSame(0, TimeSlotHelper::hhmmToMinutes('invalid'));
    }
}
