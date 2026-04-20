<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Notifications\TicketStatusNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^\S+(?:\s+\S+)+$/'],
            'phone' => ['required', 'regex:/^\+998 \d{2} \d{3} \d{2} \d{2}$/'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'name.regex' => "F.I.Sh. kamida ism va familiyadan iborat bo'lishi kerak.",
            'phone.required' => 'Telefon raqamini kiriting.',
            'phone.regex' => "Telefon raqami +998 99 999 99 99 ko'rinishida bo'lishi va 9 ta raqamdan iborat bo'lishi kerak.",
        ]);

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'job_title' => $request->job_title,
            'department_id' => $request->department_id,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Role::findOrCreate(UserRole::Requester->value, 'web');
        $user->assignRole(UserRole::Requester->value);

        if (Role::query()->where('name', UserRole::Admin->value)->where('guard_name', 'web')->exists()) {
            User::role(UserRole::Admin->value)->get()->each(
                fn (User $admin) => $admin->notify(new TicketStatusNotification(
                    "Yangi foydalanuvchi ro'yxatdan o'tdi",
                    "{$user->name} ({$user->email}) tasdiqlash uchun kutilmoqda.",
                    route('admin.users.index'),
                    ['kind' => 'user_registration_pending', 'user_id' => $user->id],
                ))
            );
        }

        event(new Registered($user));

        $request->session()->put('pending_approval_email', $user->email);
        $request->session()->put('pending_approval_rejected', false);

        return redirect(route('pending-approval', absolute: false));
    }
}
