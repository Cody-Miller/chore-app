<?php

namespace Tests\Unit;

use App\Models\Pill;
use Carbon\Carbon;
use Tests\TestCase;

class HasEffectiveDateTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function effective_date_before_cutoff_returns_previous_day()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-16 02:30:00'));

        $effectiveDate = Pill::getEffectiveDate();

        $this->assertEquals('2024-01-15', $effectiveDate->toDateString());
    }

    /** @test */
    public function effective_date_at_cutoff_returns_current_day()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-16 03:00:00'));

        $effectiveDate = Pill::getEffectiveDate();

        $this->assertEquals('2024-01-16', $effectiveDate->toDateString());
    }

    /** @test */
    public function effective_date_after_cutoff_returns_current_day()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-16 14:00:00'));

        $effectiveDate = Pill::getEffectiveDate();

        $this->assertEquals('2024-01-16', $effectiveDate->toDateString());
    }

    /** @test */
    public function effective_day_start_is_at_cutoff_hour()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-16 10:00:00'));

        $start = Pill::getEffectiveDayStart();

        $this->assertEquals('2024-01-16 03:00:00', $start->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function effective_day_end_is_at_cutoff_hour_next_day_minus_one_second()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-16 10:00:00'));

        $end = Pill::getEffectiveDayEnd();

        $this->assertEquals('2024-01-17 02:59:59', $end->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function effective_day_boundaries_span_midnight_correctly()
    {
        // At 2:30 AM on Jan 16, we're still in Jan 15's effective day
        Carbon::setTestNow(Carbon::parse('2024-01-16 02:30:00'));

        $start = Pill::getEffectiveDayStart();
        $end = Pill::getEffectiveDayEnd();

        // Effective date is Jan 15, so boundaries are Jan 15 03:00 to Jan 16 02:59:59
        $this->assertEquals('2024-01-15 03:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-01-16 02:59:59', $end->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function effective_date_string_returns_correct_format()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-16 02:30:00'));

        $dateString = Pill::getEffectiveDateString();

        $this->assertEquals('2024-01-15', $dateString);
    }

    /** @test */
    public function timestamp_at_midnight_belongs_to_previous_effective_day()
    {
        // A pill given at exactly midnight belongs to previous day
        $timestamp = Carbon::parse('2024-01-16 00:05:00');
        $effectiveDate = Pill::getEffectiveDate($timestamp);

        $this->assertEquals('2024-01-15', $effectiveDate->toDateString());
    }

    /** @test */
    public function timestamp_at_259am_belongs_to_previous_effective_day()
    {
        $timestamp = Carbon::parse('2024-01-16 02:59:59');
        $effectiveDate = Pill::getEffectiveDate($timestamp);

        $this->assertEquals('2024-01-15', $effectiveDate->toDateString());
    }

    /** @test */
    public function timestamp_at_3am_belongs_to_current_effective_day()
    {
        $timestamp = Carbon::parse('2024-01-16 03:00:00');
        $effectiveDate = Pill::getEffectiveDate($timestamp);

        $this->assertEquals('2024-01-16', $effectiveDate->toDateString());
    }
}
