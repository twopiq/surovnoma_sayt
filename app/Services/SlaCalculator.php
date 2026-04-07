<?php

namespace App\Services;

use App\Models\HolidayException;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SlaCalculator
{
    public function calculateDeadline(Carbon $start, int $durationMinutes): Carbon
    {
        $remaining = $durationMinutes;
        $cursor = $start->copy()->seconds(0);

        while ($remaining > 0) {
            $window = $this->workWindowForMoment($cursor);

            if ($window === null) {
                $cursor = $this->nextWorkWindowStart($cursor);
                continue;
            }

            [$windowStart, $windowEnd] = $window;

            if ($cursor->lt($windowStart)) {
                $cursor = $windowStart->copy();
            }

            $available = $cursor->diffInMinutes($windowEnd, false);

            if ($available <= 0) {
                $cursor = $this->nextWorkWindowStart($cursor->copy()->addDay()->startOfDay());
                continue;
            }

            if ($remaining <= $available) {
                return $cursor->copy()->addMinutes($remaining);
            }

            $remaining -= $available;
            $cursor = $this->nextWorkWindowStart($windowEnd->copy()->addMinute());
        }

        return $cursor;
    }

    public function defaultSchedules(): Collection
    {
        return collect(range(1, 7))->map(function (int $weekday): array {
            $isWorkingDay = $weekday <= 5;

            return [
                'weekday' => $weekday,
                'starts_at' => $isWorkingDay ? '08:30:00' : null,
                'ends_at' => $isWorkingDay ? '17:30:00' : null,
                'is_working_day' => $isWorkingDay,
            ];
        });
    }

    protected function workWindowForMoment(Carbon $moment): ?array
    {
        $holiday = HolidayException::query()
            ->whereDate('date', $moment->toDateString())
            ->first();

        if ($holiday && ! $holiday->is_working_override) {
            return null;
        }

        $schedule = WorkSchedule::query()->where('weekday', $moment->dayOfWeekIso)->first();

        if (! $schedule || ! $schedule->is_working_day) {
            return null;
        }

        $startsAt = $holiday?->starts_at ?: $schedule->starts_at;
        $endsAt = $holiday?->ends_at ?: $schedule->ends_at;

        if (! $startsAt || ! $endsAt) {
            return null;
        }

        return [
            Carbon::parse($moment->toDateString().' '.$startsAt, $moment->timezone),
            Carbon::parse($moment->toDateString().' '.$endsAt, $moment->timezone),
        ];
    }

    protected function nextWorkWindowStart(Carbon $moment): Carbon
    {
        $cursor = $moment->copy();

        for ($i = 0; $i < 15; $i++) {
            $window = $this->workWindowForMoment($cursor);

            if ($window !== null) {
                if ($cursor->lessThan($window[0])) {
                    return $window[0];
                }

                if ($cursor->betweenIncluded($window[0], $window[1])) {
                    return $cursor;
                }
            }

            $cursor = $cursor->copy()->addDay()->startOfDay();
        }

        return $moment;
    }
}
