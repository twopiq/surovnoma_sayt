<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;
use Throwable;

class KpiAuthController extends Controller
{
    private const COOKIE_NAME = 'kpi_auth';

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => "Login va parolni to'ldiring.",
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $validator->validated();

        $identifier = trim($credentials['login']);
        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('login', $identifier)
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => "Login yoki parol noto'g'ri.",
            ], 422);
        }

        if (! $this->canUseKpi($user)) {
            return response()->json([
                'message' => 'KPI tizimiga faqat admin foydalanuvchi kira oladi.',
            ], 403);
        }

        $minutes = (bool) ($credentials['remember'] ?? false) ? 60 * 24 * 14 : 60 * 2;
        $token = Crypt::encryptString(json_encode([
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes($minutes)->timestamp,
        ], JSON_THROW_ON_ERROR));

        return response()
            ->json([
                'user' => $this->userPayload($user),
            ])
            ->withCookie($this->makeCookie($token, $minutes, $request));
    }

    public function me(Request $request): JsonResponse
    {
        $user = $this->userFromRequest($request);

        if (! $user) {
            return response()->json([
                'message' => 'Avtorizatsiya talab qilinadi.',
            ], 401);
        }

        return response()->json([
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        return response()
            ->json(['ok' => true])
            ->withCookie($this->forgetCookie($request));
    }

    private function userFromRequest(Request $request): ?User
    {
        $token = $request->cookie(self::COOKIE_NAME);

        if (! $token) {
            return null;
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($payload) || ($payload['expires_at'] ?? 0) < now()->timestamp) {
            return null;
        }

        $user = User::query()->find($payload['user_id'] ?? null);

        return $user && $this->canUseKpi($user) ? $user : null;
    }

    private function canUseKpi(User $user): bool
    {
        return $user->isApproved()
            && $user->hasRole(UserRole::Admin->value);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'login' => $user->login ?: $user->email,
            'role' => UserRole::Admin->value,
            'source' => 'survey-api',
        ];
    }

    private function makeCookie(string $token, int $minutes, Request $request): SymfonyCookie
    {
        return Cookie::make(
            self::COOKIE_NAME,
            $token,
            $minutes,
            '/',
            $this->cookieDomain(),
            $this->cookieSecure($request),
            true,
            false,
            $this->cookieSameSite(),
        );
    }

    private function forgetCookie(Request $request): SymfonyCookie
    {
        return Cookie::make(
            self::COOKIE_NAME,
            '',
            -2628000,
            '/',
            $this->cookieDomain(),
            $this->cookieSecure($request),
            true,
            false,
            $this->cookieSameSite(),
        );
    }

    private function cookieDomain(): ?string
    {
        $domain = trim((string) env('KPI_API_COOKIE_DOMAIN', ''));

        return $domain !== '' ? $domain : null;
    }

    private function cookieSecure(Request $request): bool
    {
        $configured = env('KPI_API_COOKIE_SECURE');

        if ($configured !== null) {
            return filter_var($configured, FILTER_VALIDATE_BOOL);
        }

        return $request->isSecure();
    }

    private function cookieSameSite(): string
    {
        return trim((string) env('KPI_API_COOKIE_SAME_SITE', 'lax')) ?: 'lax';
    }
}
