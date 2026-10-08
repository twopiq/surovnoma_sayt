<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AvailabilityStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use App\Support\TableExport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DispatchController extends Controller
{
    public function __construct(protected TicketService $ticketService)
    {
    }

    public function index(): View
    {
        return view('admin.dispatch.index');
    }

    public function tickets(Request $request): View
    {
        $query = $this->filteredTicketQuery($request, $this->activeStatuses())->latest();
        $tickets = $query->paginate(20)->withQueryString();

        $selected = $request->filled('ticket')
            ? Ticket::query()->with(['requester', 'category', 'assignedExecutor', 'assignedDepartment', 'slaProfile'])->find($request->integer('ticket'))
            : $tickets->first();

        // Holat chiplari uchun sonlar: holat filtrisiz, qolgan filtrlar bilan
        $statusCounts = $this->filteredTicketQuery($request->duplicate(\Illuminate\Support\Arr::except($request->query(), ['status', 'overdue'])), $this->activeStatuses())
            ->reorder()
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.dispatch.tickets', [
            'tickets' => $tickets,
            'selected' => $selected,
            'statusCounts' => $statusCounts,
            'overdueCount' => $this->filteredTicketQuery($request->duplicate([...\Illuminate\Support\Arr::except($request->query(), ['status']), 'overdue' => 1]), $this->activeStatuses())->count(),
            'chipStatuses' => [TicketStatus::New, TicketStatus::Assigned, TicketStatus::InProgress, TicketStatus::Returned],
            'statuses' => $this->activeStatuses(),
            'priorities' => TicketPriority::assignableCases(),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'executors' => User::query()->role('executor')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public const ARCHIVE_PERIODS = ['7' => '7 kun', '30' => '30 kun', '90' => '90 kun', 'all' => 'Hammasi'];

    public function archive(Request $request): View
    {
        $period = array_key_exists((string) $request->query('period'), self::ARCHIVE_PERIODS) ? (string) $request->query('period') : '30';
        $finishedAt = 'coalesce(completed_at, rejected_at, updated_at)';

        $query = $this->filteredTicketQuery($request, $this->archiveStatuses(), allowOverdueFilter: false)
            ->when($period !== 'all', fn ($q) => $q->whereRaw("{$finishedAt} >= ?", [now()->subDays((int) $period)->startOfDay()]))
            ->orderByRaw("{$finishedAt} desc");

        $tickets = $query->paginate(30)->withQueryString();

        $selected = $request->filled('ticket')
            ? Ticket::query()->with(['assignedExecutor:id,name', 'category:id,name', 'histories.user:id,name'])->find($request->integer('ticket'))
            : $tickets->first()?->load('histories.user:id,name');

        $statusCounts = $this->filteredTicketQuery($request->duplicate(\Illuminate\Support\Arr::except($request->query(), ['status'])), $this->archiveStatuses(), allowOverdueFilter: false)
            ->when($period !== 'all', fn ($q) => $q->whereRaw("{$finishedAt} >= ?", [now()->subDays((int) $period)->startOfDay()]))
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.dispatch.archive', [
            'tickets' => $tickets,
            'groups' => $tickets->getCollection()->groupBy(fn (Ticket $ticket) => $this->archiveGroup($ticket->finishedAt())),
            'selected' => $selected,
            'statusCounts' => $statusCounts,
            'statuses' => $this->archiveStatuses(),
            'period' => $period,
            'periods' => self::ARCHIVE_PERIODS,
            'priorities' => TicketPriority::assignableCases(),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'executors' => User::query()->role('executor')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function archiveGroup(?\Illuminate\Support\Carbon $date): string
    {
        return match (true) {
            $date === null => 'Avvalroq',
            $date->isToday() => 'Bugun',
            $date->isYesterday() => 'Kecha',
            $date->gte(now()->startOfWeek()) => 'Shu hafta',
            $date->gte(now()->startOfMonth()) => 'Shu oy',
            default => 'Avvalroq',
        };
    }

    public function status(Request $request, string $status): View
    {
        $ticketStatus = collect(TicketStatus::cases())->firstWhere('value', $status);

        abort_unless($ticketStatus instanceof TicketStatus, 404);

        $query = Ticket::query()
            ->with(['assignedDepartment', 'assignedExecutor', 'requester', 'category', 'slaProfile', 'attachments:id,ticket_id,original_name'])
            ->where('status', $ticketStatus->value)
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')))
            ->latest();

        return view('admin.dispatch.status', [
            'tickets' => $query->paginate(15)->withQueryString(),
            'status' => $ticketStatus,
            'priorities' => TicketPriority::cases(),
        ]);
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load([
            'comments.user',
            'attachments',
            'requester',
            'assignedDepartment',
            'assignedExecutor',
            'category',
            'slaProfile',
            'assignments',
            'histories.user',
            'returnRequests.executor',
        ]);

        return view('admin.dispatch.show', [
            'ticket' => $ticket,
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'executors' => User::query()
                ->role('executor')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'priorities' => TicketPriority::assignableCases(),
            'availabilityLabels' => collect(AvailabilityStatus::cases())->mapWithKeys(fn (AvailabilityStatus $case) => [$case->value => $case->label()]),
        ]);
    }

    public function assign(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'assigned_department_id' => ['nullable', 'exists:departments,id'],
            'assigned_executor_id' => ['nullable', 'exists:users,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'priority' => ['required', Rule::in(array_column(TicketPriority::assignableCases(), 'value'))],
            'note' => ['nullable', 'string'],
            'confirm_overload' => ['nullable', 'boolean'],
            'confirmed_overload_executor_id' => ['nullable', 'integer'],
            'confirmed_overload_priority' => ['nullable', 'string'],
        ]);

        $executor = isset($data['assigned_executor_id']) ? User::find($data['assigned_executor_id']) : null;
        $priority = TicketPriority::from($data['priority']);

        if ($executor && $executor->availability_status === AvailabilityStatus::Vacation) {
            return back()->withErrors([
                'assigned_executor_id' => 'Taʼtildagi ijrochiga vazifa biriktirib bo‘lmaydi.',
            ]);
        }

        if ($executor) {
            $usedUnits = $executor->currentExecutorWorkloadUnits($ticket->id);
            $newUnits = $priority->workloadUnits();
            $totalUnits = $usedUnits + $newUnits;

            $isConfirmedOverload = $request->boolean('confirm_overload')
                && (int) $request->input('confirmed_overload_executor_id') === $executor->id
                && $request->input('confirmed_overload_priority') === $priority->value;

            if ($totalUnits > User::EXECUTOR_MAX_WORKLOAD_UNITS && ! $isConfirmedOverload) {
                return back()
                    ->withInput()
                    ->with('overload_warning', [
                        'executor' => $executor->name,
                        'used_units' => $usedUnits,
                        'new_units' => $newUnits,
                        'total_units' => $totalUnits,
                        'max_units' => User::EXECUTOR_MAX_WORKLOAD_UNITS,
                        'overload_units' => $totalUnits - User::EXECUTOR_MAX_WORKLOAD_UNITS,
                    ]);
            }
        }

        $this->ticketService->assign(
            $ticket,
            auth()->user(),
            $data['assigned_department_id'] ?? null,
            $data['assigned_executor_id'] ?? null,
            $priority,
            $data['category_id'] ?? null,
            $data['note'] ?? null,
        );

        return back()->with('status', 'Murojaat taqsimlandi.');
    }

    public function reject(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5'],
        ]);

        $this->ticketService->reject($ticket, auth()->user(), $data['reason']);

        return redirect()->route('admin.dispatch.index')->with('status', 'Murojaat rad etildi va yopildi.');
    }

    public function close(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'note' => ['nullable', 'string'],
        ]);

        $this->ticketService->close($ticket, auth()->user(), $data['note'] ?? null);

        return redirect()->route('admin.dispatch.archive')->with('status', 'Murojaat yopildi va arxivga joylandi.');
    }

    public function export(Request $request)
    {
        if ($request->boolean('archive')) {
            $query = $this->filteredTicketQuery($request, $this->archiveStatuses(), allowOverdueFilter: false)
                ->latest('completed_at')
                ->latest();
            $title = 'Murojaatlar arxivi';
            $filename = 'murojaatlar-arxivi';
        } else {
            $query = $this->filteredTicketQuery($request, $this->activeStatuses())
                ->latest();
            $title = 'Faol murojaatlar';
            $filename = 'faol-murojaatlar';
        }

        $format = (string) $request->query('format', 'excel');
        $headings = ['Murojaat raqami', 'Sarlavha', 'Murojaatchi', 'Kategoriya', 'Muhimlik', 'Holat', "Bo'lim", 'Ijrochi', 'Yaratilgan vaqt', 'Muddat', 'Yakunlangan vaqt'];
        $rows = (function () use ($query): \Generator {
            foreach ($query->cursor() as $ticket) {
                yield [
                    $ticket->reference,
                    $ticket->title ?? $ticket->description,
                    $ticket->requester_name,
                    $ticket->category?->name,
                    $ticket->priority->label(),
                    $ticket->status->label(),
                    $ticket->assignedDepartment?->name,
                    $ticket->assignedExecutor?->name,
                    $ticket->created_at,
                    $ticket->deadline_at,
                    $ticket->completed_at,
                ];
            }
        })();

        return TableExport::download($format, $filename, $title, $headings, $rows, [
            'Eksport qilingan vaqt' => now(),
            'Format' => strtolower($format) === 'csv' ? 'CSV' : 'Excel',
        ]);
    }

    private function filteredTicketQuery(Request $request, array $allowedStatuses, bool $allowOverdueFilter = true)
    {
        $allowedStatusValues = array_map(fn (TicketStatus $status): string => $status->value, $allowedStatuses);

        $query = Ticket::query()
            ->with(['assignedDepartment', 'assignedExecutor', 'requester', 'category', 'slaProfile', 'attachments:id,ticket_id,original_name'])
            ->whereIn('status', $allowedStatusValues);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('executor_id')) {
            $query->where('assigned_executor_id', $request->integer('executor_id'));
        }

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('reference', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%");
            });
        }

        if ($allowOverdueFilter && $request->boolean('overdue')) {
            $query->whereNotNull('deadline_at')
                ->where('deadline_at', '<', now())
                ->whereNotIn('status', [
                    TicketStatus::Closed->value,
                    TicketStatus::Rejected->value,
                ]);
        }

        return $query;
    }

    private function activeStatuses(): array
    {
        return [
            TicketStatus::New,
            TicketStatus::Assigned,
            TicketStatus::InProgress,
            TicketStatus::Returned,
            TicketStatus::Overdue,
        ];
    }

    private function archiveStatuses(): array
    {
        return [
            TicketStatus::Completed,
            TicketStatus::Closed,
            TicketStatus::Rejected,
        ];
    }

    public function comment(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:3'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $this->ticketService->addComment($ticket, auth()->user(), $data['body'], $request->boolean('is_public'));

        return back()->with('status', 'Izoh qo‘shildi.');
    }
}
