<?php

namespace App\Http\Controllers;

use App\Enums\AvailabilityStatus;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Department;
use App\Models\User;
use App\TelegramBot\TelegramSdkBot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load('department');

        return view('profile.edit', [
            'user' => $user,
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'is_active']),
            'availabilityStatuses' => AvailabilityStatus::cases(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function settings(Request $request, TelegramSdkBot $telegramBot): View
    {
        $user = $request->user();
        $telegramSchemaReady = $this->telegramSchemaReady();

        if ($telegramSchemaReady && ! $user->telegram_link_token) {
            $user->forceFill([
                'telegram_link_token' => Str::random(48),
            ])->save();
        }

        return view('app.settings', [
            'user' => $user->fresh(),
            'telegramBotUsername' => config('services.telegram_bot.username') ?: $telegramBot->getMeUsername(),
            'telegramBotConfigured' => is_string(config('services.telegram_bot.token')) && config('services.telegram_bot.token') !== '',
            'telegramSchemaReady' => $telegramSchemaReady,
        ]);
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updateEmail', [
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($request->user()->id),
            ],
        ]);

        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('app.settings')->with('status', 'email-updated');
    }

    public function regenerateTelegramLink(Request $request): RedirectResponse
    {
        if (! $this->telegramSchemaReady()) {
            return Redirect::route('app.settings')->with('status', 'telegram-migration-required');
        }

        $request->user()->forceFill([
            'telegram_link_token' => Str::random(48),
        ])->save();

        return Redirect::route('app.settings')->with('status', 'telegram-token-regenerated');
    }

    public function disconnectTelegram(Request $request): RedirectResponse
    {
        if (! $this->telegramSchemaReady()) {
            return Redirect::route('app.settings')->with('status', 'telegram-migration-required');
        }

        $request->user()->forceFill([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_link_token' => Str::random(48),
            'telegram_notifications_enabled' => true,
            'telegram_linked_at' => null,
        ])->save();

        return Redirect::route('app.settings')->with('status', 'telegram-disconnected');
    }

    protected function telegramSchemaReady(): bool
    {
        foreach ([
            'telegram_chat_id',
            'telegram_username',
            'telegram_link_token',
            'telegram_linked_at',
            'telegram_notifications_enabled',
        ] as $column) {
            if (! Schema::hasColumn('users', $column)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
