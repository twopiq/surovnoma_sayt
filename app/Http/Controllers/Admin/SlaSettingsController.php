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
        return view('admin.sla.index', [
            'profiles' => SlaProfile::query()->orderBy('duration_minutes')->get(),
            'schedules' => WorkSchedule::query()->orderBy('weekday')->get(),
            'holidays' => HolidayException::query()->orderBy('date')->get(),
            'priorities' => TicketPriority::cases(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'profiles' => ['required', 'array'],
            'profiles.*.priority' => ['required', 'string'],
            'profiles.*.name' => ['required', 'string'],
            'profiles.*.duration_minutes' => ['required', 'integer', 'min:1'],
            'profiles.*.warning_minutes' => ['required', 'integer', 'min:1'],
        ]);

        foreach ($data['profiles'] as $profileData) {
            SlaProfile::query()->updateOrCreate(
                ['priority' => $profileData['priority']],
                [
                    'name' => $profileData['name'],
                    'duration_minutes' => $profileData['duration_minutes'],
                    'warning_minutes' => $profileData['warning_minutes'],
                    'description' => $profileData['name'],
                    'is_active' => true,
                ],
            );
        }

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

        return back()->with('status', 'SLA sozlamalari saqlandi.');
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
}
