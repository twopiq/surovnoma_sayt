<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Throwable;

abstract class KpiBaseController extends Controller
{
    protected const COOKIE_NAME = 'kpi_auth';

    protected function userFromRequest(Request $request): ?User
    {
        $token = $this->tokenFromRequest($request);

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

        return $user && $user->isApproved()
            && $user->hasAnyRole([UserRole::Admin->value, UserRole::Manager->value])
            ? $user
            : null;
    }

    protected function tokenFromRequest(Request $request): ?string
    {
        $bearerToken = $request->bearerToken();

        return $bearerToken ?: $request->cookie(self::COOKIE_NAME);
    }

    protected function parseMonth(Request $request): ?array
    {
        $raw = $request->query('month');

        if (! $raw || ! preg_match('/^(\d{4})-(\d{2})$/', $raw, $m)) {
            return null;
        }

        return [(int) $m[1], (int) $m[2]];
    }
}
