<?php

namespace Tests\Unit;

use App\Models\WorkSchedule;
use App\Services\SlaCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlaCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_skips_non_working_days_when_calculating_deadline(): void
    {
        foreach (app(SlaCalculator::class)->defaultSchedules() as $schedule) {
            WorkSchedule::query()->create($schedule);
        }

        $deadline = app(SlaCalculator::class)->calculateDeadline(
            Carbon::parse('2026-04-10 16:30:00'),
            180
        );

        $this->assertSame('2026-04-13 10:30:00', $deadline->format('Y-m-d H:i:s'));
    }
}
