<?php

namespace App\Services;

use App\Models\HolidayException;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SlaCalculator
{
    public function workdayDurationMinutes(): int
    {
        $schedules = WorkSchedule::query()
            ->whereBetween('weekday', [1, 5])
            ->where('is_working_day', true)
            ->get();

        if ($schedules->isEmpty()) {
            $schedules = $this->defaultSchedules()
                ->filter(fn (array $schedule): bool => $schedule['weekday'] <= 5 && $schedule['is_working_day']);
        }

        $minutes = $schedules
            ->map(function ($schedule): int {
                $startsAt = data_get($schedule, 'starts_at');
                $endsAt = data_get($schedule, 'ends_at');

                if (! $startsAt || ! $endsAt) {
                    return 0;
                }

                return max(0, Carbon::parse($startsAt)->diffInMinutes(Carbon::parse($endsAt), false));
            })
            ->filter(fn (int $minutes): bool => $minutes > 0);

        return (int) max(1, round($minutes->avg() ?: (9 * 60)));
    }

    public function minutesFromWorkdays(float $workdays): int
    {
        return max(1, (int) round($workdays * $this->workdayDurationMinutes()));
    }

    public function workdaysFromMinutes(int $minutes): float
    {
        return round($minutes / $this->workdayDurationMinutes(), 2);
    }

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

        if ($moment->isWeekend() && ! $holiday?->is_working_override) {
            return null;
        }

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
