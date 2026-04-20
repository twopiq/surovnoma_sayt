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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
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

        return view('admin.dispatch.tickets', [
            'tickets' => $query->paginate(15)->withQueryString(),
            'statuses' => $this->activeStatuses(),
            'priorities' => TicketPriority::cases(),
        ]);
    }

    public function archive(Request $request): View
    {
        $query = $this->filteredTicketQuery($request, $this->archiveStatuses(), allowOverdueFilter: false)
            ->latest('completed_at')
            ->latest();

        return view('admin.dispatch.archive', [
            'tickets' => $query->paginate(15)->withQueryString(),
            'statuses' => $this->archiveStatuses(),
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
            'priorities' => TicketPriority::cases(),
            'availabilityLabels' => collect(AvailabilityStatus::cases())->mapWithKeys(fn (AvailabilityStatus $case) => [$case->value => $case->label()]),
        ]);
    }

    public function assign(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'assigned_department_id' => ['nullable', 'exists:departments,id'],
            'assigned_executor_id' => ['nullable', 'exists:users,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'priority' => ['required', Rule::in(array_column(TicketPriority::cases(), 'value'))],
            'note' => ['nullable', 'string'],
        ]);

        $executor = isset($data['assigned_executor_id']) ? User::find($data['assigned_executor_id']) : null;

        if ($executor && $executor->availability_status === AvailabilityStatus::Vacation) {
            return back()->withErrors([
                'assigned_executor_id' => 'Taʼtildagi ijrochiga vazifa biriktirib bo‘lmaydi.',
            ]);
        }

        $this->ticketService->assign(
            $ticket,
            auth()->user(),
            $data['assigned_department_id'] ?? null,
            $data['assigned_executor_id'] ?? null,
            TicketPriority::from($data['priority']),
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

    public function exportCsv(Request $request)
    {
        if ($request->boolean('archive')) {
            $query = $this->filteredTicketQuery($request, $this->archiveStatuses(), allowOverdueFilter: false)
                ->latest('completed_at')
                ->latest();
        } else {
            $query = $this->filteredTicketQuery($request, $this->activeStatuses())
                ->latest();
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="tickets-export.csv"',
        ];

        $callback = function () use ($query): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Reference', 'Requester', 'Category', 'Priority', 'Status', 'Department', 'Executor', 'Deadline']);

            foreach ($query->cursor() as $ticket) {
                fputcsv($handle, [
                    $ticket->reference,
                    $ticket->requester_name,
                    $ticket->category?->name,
                    $ticket->priority->value,
                    $ticket->status->value,
                    $ticket->assignedDepartment?->name,
                    $ticket->assignedExecutor?->name,
                    optional($ticket->deadline_at)->toDateTimeString(),
                ]);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }

    private function filteredTicketQuery(Request $request, array $allowedStatuses, bool $allowOverdueFilter = true)
    {
        $allowedStatusValues = array_map(fn (TicketStatus $status): string => $status->value, $allowedStatuses);

        $query = Ticket::query()
            ->with(['assignedDepartment', 'assignedExecutor', 'requester', 'category'])
            ->whereIn('status', $allowedStatusValues);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
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
            TicketStatus::Rejected,
        ];
    }

    private function archiveStatuses(): array
    {
        return [
            TicketStatus::Completed,
            TicketStatus::Closed,
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
