<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AvailabilityStatus;
use App\Enums\TicketPriority;
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

    public function index(Request $request): View
    {
        $query = Ticket::query()->with(['assignedDepartment', 'assignedExecutor', 'requester'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        if ($request->boolean('overdue')) {
            $query->whereNotNull('deadline_at')->where('deadline_at', '<', now())->whereNotIn('status', ['closed', 'rejected']);
        }

        return view('admin.dispatch.index', [
            'tickets' => $query->paginate(15)->withQueryString(),
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

        return back()->with('status', 'Murojaat yopildi.');
    }

    public function exportCsv(Request $request)
    {
        $query = Ticket::query()->with(['assignedDepartment', 'assignedExecutor', 'requester'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="tickets-export.csv"',
        ];

        $callback = function () use ($query): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Reference', 'Requester', 'Priority', 'Status', 'Department', 'Executor', 'Deadline']);

            foreach ($query->cursor() as $ticket) {
                fputcsv($handle, [
                    $ticket->reference,
                    $ticket->requester_name,
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
