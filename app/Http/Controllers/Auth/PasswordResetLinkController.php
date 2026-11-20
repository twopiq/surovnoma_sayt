<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\PasswordResetUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password', [
            'telegramEnabled' => filled(config('services.telegram_bot.token')),
        ]);
    }

    /**
     * Tiklash havolasini foydalanuvchining emailiga yuboradi. Email yoki login qabul qilinadi.
     * Javob har doim bir xil: akkaunt borligini oshkor qilmaymiz.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'string', 'max:255']], [], ['email' => __('Email yoki login')]);

        $user = PasswordResetUsers::find($request->string('email'));

        if ($user) {
            try {
                Password::sendResetLink(['email' => $user->email]);
            } catch (Throwable $e) {
                Log::error('Parolni tiklash xatini yuborib bo\'lmadi', ['user_id' => $user->id, 'error' => $e->getMessage()]);

                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => __("Xatni yuborib bo'lmadi. Birozdan keyin urinib ko'ring yoki administratorga murojaat qiling.")]);
            }
        }

        return back()->with('status', __("Agar bunday akkaunt mavjud bo'lsa, unga biriktirilgan email manzilga parolni tiklash havolasi yuborildi."));
    }
}
