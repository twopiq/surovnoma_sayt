<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Models\HolidayException;
use App\Models\SlaProfile;
use App\Models\WorkSchedule;
use App\Services\SlaCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SlaSettingsController extends Controller
{
    public function __construct(protected SlaCalculator $slaCalculator)
    {
    }

    public function index(): View
    {
        return $this->deadlines();
    }

    public function deadlines(): View
    {
        return view('admin.sla.index', [
            'profiles' => SlaProfile::query()->orderBy('duration_minutes')->get(),
            'priorities' => TicketPriority::assignableCases(),
            'workdayMinutes' => $this->slaCalculator->workdayDurationMinutes(),
        ]);
    }

    public function workSchedule(): View
    {
        return view('admin.sla.work-schedule', [
            'schedules' => WorkSchedule::query()->orderBy('weekday')->get(),
            'holidays' => HolidayException::query()->orderBy('date')->get(),
            'weekdayLabels' => $this->weekdayLabels(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->updateDeadlineProfiles($request);
        $this->updateWorkScheduleRows($request);

        return back()->with('status', 'SLA sozlamalari saqlandi.');
    }

    public function updateDeadlines(Request $request): RedirectResponse
    {
        $this->updateDeadlineProfiles($request);

        return back()->with('status', 'Deadline sozlamalari saqlandi.');
    }

    public function updateWorkSchedule(Request $request): RedirectResponse
    {
        $this->updateWorkScheduleRows($request);

        return back()->with('status', 'Ish kunlari saqlandi.');
    }

    protected function updateDeadlineProfiles(Request $request): void
    {
        $data = $request->validate([
            'profiles' => ['required', 'array'],
            'profiles.*.priority' => ['required', 'string'],
            'profiles.*.name' => ['required', 'string'],
            'profiles.*.deadline_days' => ['required', 'numeric', 'min:0.01', 'max:365'],
            'profiles.*.warning_minutes' => ['required', 'integer', 'min:1'],
        ]);

        foreach ($data['profiles'] as $profileData) {
            SlaProfile::query()->updateOrCreate(
                ['priority' => $profileData['priority']],
                [
                    'name' => $profileData['name'],
                    'duration_minutes' => $this->slaCalculator->minutesFromWorkdays((float) $profileData['deadline_days']),
                    'warning_minutes' => $profileData['warning_minutes'],
                    'description' => $profileData['name'],
                    'is_active' => true,
                ],
            );
        }
    }

    protected function updateWorkScheduleRows(Request $request): void
    {
        foreach ($request->input('schedule', []) as $weekday => $scheduleData) {
            WorkSchedule::query()->updateOrCreate(
                ['weekday' => $weekday],
                [
                    'starts_at' => $scheduleData['starts_at'] ?: null,
                    'ends_at' => $scheduleData['ends_at'] ?: null,
                    'is_working_day' => array_key_exists('is_working_day', $scheduleData),
                ],
            );
        }
    }

    public function bootstrapDefaults(): RedirectResponse
    {
        foreach ($this->slaCalculator->defaultSchedules() as $schedule) {
            WorkSchedule::query()->updateOrCreate(
                ['weekday' => $schedule['weekday']],
                $schedule,
            );
        }

        return back()->with('status', 'Standart ish kalendari yaratildi.');
    }

    protected function weekdayLabels(): array
    {
        return [
            1 => 'Dushanba',
            2 => 'Seshanba',
            3 => 'Chorshanba',
            4 => 'Payshanba',
            5 => 'Juma',
            6 => 'Shanba',
            7 => 'Yakshanba',
        ];
    }
}
