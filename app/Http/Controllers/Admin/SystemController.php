<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use App\Support\LogReader;
use App\Support\SystemHealth;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SystemController extends Controller
{
    public function health(SystemHealth $health): View
    {
        $checks = collect($health->checks());

        return view('admin.system.health', [
            'groups' => $checks->groupBy('group'),
            'summary' => $checks->countBy('status'),
            'checkedAt' => now(),
        ]);
    }

    public function logs(Request $request, LogReader $reader): View
    {
        $tab = $request->query('tab') === 'server' ? 'server' : 'audit';

        return view('admin.system.logs', [
            'tab' => $tab,
            ...($tab === 'server' ? $this->serverLogs($request, $reader) : $this->auditLogs($request)),
        ]);
    }

    public function download(Request $request, string $file, LogReader $reader, AuditService $audit): BinaryFileResponse
    {
        $path = $reader->path($file);
        abort_unless($path, 404);

        $audit->log($request->user()->id, 'system.log_downloaded', "Log fayli yuklab olindi: {$file}", null, ['ip' => $request->ip()]);

        return response()->download($path, basename($path), ['Cache-Control' => 'private, no-store']);
    }

    private function auditLogs(Request $request): array
    {
        $filters = [
            'event' => (string) $request->query('event', ''),
            'user_id' => $request->integer('user_id') ?: null,
            'q' => trim((string) $request->query('q', '')),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ];

        $logs = AuditLog::query()
            ->when($filters['event'] !== '', fn ($q) => $q->where('event', $filters['event']))
            ->when($filters['user_id'], fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['q'] !== '', fn ($q) => $q->where('description', 'like', '%'.$filters['q'].'%'))
            ->when(preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['from']), fn ($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when(preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['to']), fn ($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        $userIds = $logs->getCollection()->pluck('user_id')->filter()->unique();

        return [
            'logs' => $logs,
            'users' => User::query()->whereIn('id', $userIds)->pluck('name', 'id'),
            'filters' => $filters,
            'events' => AuditLog::query()->distinct()->orderBy('event')->pluck('event'),
            'actors' => User::query()->whereIn('id', AuditLog::query()->select('user_id')->whereNotNull('user_id')->distinct())->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function serverLogs(Request $request, LogReader $reader): array
    {
        $files = $reader->files();
        $file = (string) $request->query('file', $files->first()['name'] ?? '');
        $level = in_array($request->query('level'), LogReader::LEVELS, true) ? (string) $request->query('level') : null;
        $search = trim((string) $request->query('q', '')) ?: null;

        return [
            'files' => $files,
            'file' => $file,
            'level' => $level,
            'search' => $search,
            'entries' => $file !== '' ? $reader->entries($file, $level, $search) : collect(),
            'levels' => LogReader::LEVELS,
        ];
    }
}
