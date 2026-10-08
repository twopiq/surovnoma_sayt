<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditService;
use App\Support\BackupSchedule;
use App\Support\DatabaseBackupManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BackupController extends Controller
{
    public function __construct(protected DatabaseBackupManager $backups)
    {
    }

    public function index(): View
    {
        $supported = $this->backups->isSupported();
        $list = $supported ? $this->backups->list() : collect();
        $lastScheduled = $list->first(fn (array $backup) => str_starts_with($backup['name'], 'scheduled-'));
        $lastScheduledAt = $lastScheduled ? Carbon::parse($lastScheduled['modified_at']) : null;

        return view('admin.backups.index', [
            'supported' => $supported,
            'driver' => $this->backups->driver(),
            'backups' => $list,
            'totalSize' => $list->sum('size'),
            'settings' => [
                'enabled' => BackupSchedule::enabled(),
                'frequency' => BackupSchedule::frequency(),
                'daily_at' => BackupSchedule::dailyAt(),
                'keep' => BackupSchedule::keep(),
            ],
            'frequencies' => array_map(fn (string $label) => __($label), BackupSchedule::FREQUENCIES),
            'lastScheduledAt' => $lastScheduledAt,
            'health' => BackupSchedule::health($lastScheduledAt),
            'backupPath' => config('database-backup.path'),
        ]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        try {
            $backup = $this->backups->backup('manual', BackupSchedule::keep());
        } catch (Throwable $e) {
            return back()->withErrors(['backup' => $e->getMessage()]);
        }

        $name = basename($backup['path']);
        $audit->log($request->user()->id, 'backup.created', __('Zahira nusxa olindi: :name', ['name' => $name], 'uz'), null, ['size' => $backup['size']]);

        return back()->with('status', __('Zahira nusxa olindi: :name', ['name' => $name]));
    }

    public function download(Request $request, string $backup, AuditService $audit): BinaryFileResponse
    {
        try {
            $path = $this->backups->pathFor($backup);
        } catch (Throwable) {
            abort(404);
        }

        $audit->log($request->user()->id, 'backup.downloaded', __('Zahira yuklab olindi: :name', ['name' => $backup], 'uz'), null, ['ip' => $request->ip()]);

        return response()->download($path, basename($path), [
            'Content-Type' => str_ends_with($path, '.gz') ? 'application/gzip' : 'application/vnd.sqlite3',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function restore(Request $request, string $backup): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        $user = $request->user();

        if (! Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors(['restore' => __("Parol noto'g'ri — tiklash bekor qilindi.")]);
        }

        try {
            $result = $this->backups->restore($backup, createSafetyBackup: true);
        } catch (Throwable $e) {
            return back()->withErrors(['restore' => $e->getMessage()]);
        }

        // Baza almashgani uchun audit jadvaliga emas, log fayliga yoziladi
        Log::warning('Baza zahiradan tiklandi', [
            'backup' => $backup,
            'safety_backup' => $result['safety_backup'] ? basename($result['safety_backup']) : null,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip' => $request->ip(),
        ]);

        // Tiklangan bazada sessiyalar boshqacha bo'lishi mumkin — qayta kirish so'raladi
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', __('Baza «:backup» nusxasidan tiklandi. Xavfsizlik nusxasi: :safety. Qaytadan kiring.', [
            'backup' => $backup,
            'safety' => $result['safety_backup'] ? basename($result['safety_backup']) : '—',
        ]));
    }

    public function destroy(Request $request, string $backup, AuditService $audit): RedirectResponse
    {
        try {
            $this->backups->delete($backup);
        } catch (Throwable) {
            abort(404);
        }

        $audit->log($request->user()->id, 'backup.deleted', __("Zahira o'chirildi: :name", ['name' => $backup], 'uz'));

        return back()->with('status', __("Zahira o'chirildi: :name", ['name' => $backup]));
    }

    public function updateSettings(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'frequency' => ['required', Rule::in(array_keys(BackupSchedule::FREQUENCIES))],
            'daily_at' => ['nullable', 'date_format:H:i'],
            'keep' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        SystemSetting::put('backup.enabled', (bool) $data['enabled']);
        SystemSetting::put('backup.frequency', $data['frequency']);
        SystemSetting::put('backup.daily_at', $data['daily_at'] ?? '02:00');
        SystemSetting::put('backup.keep', (int) $data['keep']);

        $audit->log($request->user()->id, 'backup.settings', __('Avtomatik zahira sozlamalari yangilandi', [], 'uz'), null, $data);

        return back()->with('status', __('Avtomatik zahira sozlamalari saqlandi.'));
    }
}
