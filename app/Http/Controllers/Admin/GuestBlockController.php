<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuestBlock;
use App\Services\AuditService;
use App\Services\GuestRequestGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GuestBlockController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->input('status'), ['unblocked', 'all'], true) ? $request->input('status') : 'active';
        $search = trim((string) $request->input('q'));

        $blocks = GuestBlock::query()
            ->with('unblocker')
            ->when($status === 'active', fn ($q) => $q->whereNull('unblocked_at'))
            ->when($status === 'unblocked', fn ($q) => $q->whereNotNull('unblocked_at'))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('ip', 'like', "%{$search}%")
                        ->orWhere('device_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");

                    if (preg_match('/^(?:blok-)?(\d+)$/i', $search, $m)) {
                        $q->orWhere('id', (int) $m[1]);
                    }
                });
            })
            ->latest('blocked_at')
            ->paginate(20)
            ->withQueryString();

        $topReasons = GuestBlock::query()
            ->where('blocked_at', '>=', now()->subDays(30))
            ->selectRaw('reason, count(*) as total')
            ->groupBy('reason')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return view('admin.guest-blocks.index', [
            'blocks' => $blocks,
            'status' => $status,
            'search' => $search,
            'counts' => [
                'active' => GuestBlock::query()->active()->count(),
                'unblocked' => GuestBlock::query()->whereNotNull('unblocked_at')->count(),
                'all' => GuestBlock::query()->count(),
            ],
            'maxAttempts' => max(1, (int) GuestBlock::query()->max('attempts_count')),
            'topReasons' => $topReasons->map(fn ($row) => [
                'label' => GuestBlock::REASONS[$row->reason] ?? $row->reason,
                'total' => (int) $row->total,
                'tone' => $row->reason === 'manual' ? 'line-strong' : (str_starts_with((string) $row->reason, 'bot') ? 'status-in-progress-dot' : 'status-new-dot'),
            ]),
            'protection' => [
                'captcha' => filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key')),
                'alert' => (bool) config('guest_limits.alert_enabled'),
                'whitelist' => count(config('guest_limits.whitelist_ips', [])),
                'currentIp' => $request->ip(),
            ],
        ]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:ip,email,phone'],
            'value' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $value = trim($data['value']);

        $valid = match ($data['type']) {
            'ip' => filter_var($value, FILTER_VALIDATE_IP) !== false,
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'phone' => (bool) preg_match('/^\+998 \d{2} \d{3} \d{2} \d{2}$/', $value),
        };

        if (! $valid) {
            $hint = $data['type'] === 'phone' ? ' Format: +998 90 123 45 67' : '';

            return back()->withInput()->withErrors(['value' => "Qiymat noto'g'ri.{$hint}"]);
        }

        $block = GuestBlock::create([
            'scope' => $data['type'] === 'ip' ? GuestBlock::SCOPE_IP : GuestBlock::SCOPE_CONTACT,
            'reason' => 'manual',
            'description' => ($data['note'] ?? null) ?: null,
            'ip' => $data['type'] === 'ip' ? $value : null,
            'email' => $data['type'] === 'email' ? Str::lower($value) : null,
            'phone' => $data['type'] === 'phone' ? $value : null,
            'attempts_count' => 0,
            'blocked_at' => now(),
        ]);

        $audit->log($request->user()->id, 'guest_block.created', "BLOK-{$block->id} qo'lda yaratildi", $block, [
            'type' => $data['type'],
            'value' => $value,
        ]);

        return back()->with('status', "BLOK-{$block->id} yaratildi.");
    }

    public function unblock(Request $request, GuestBlock $guestBlock, GuestRequestGuard $guard, AuditService $audit): RedirectResponse
    {
        if ($guestBlock->isActive()) {
            $guard->unblock($guestBlock, $request->user()->id);

            $audit->log($request->user()->id, 'guest_block.unblocked', "BLOK-{$guestBlock->id} blokdan chiqarildi", $guestBlock, [
                'ip' => $guestBlock->ip,
                'device_id' => $guestBlock->device_id,
            ]);
        }

        return back()->with('status', "BLOK-{$guestBlock->id} blokdan chiqarildi.");
    }
}
