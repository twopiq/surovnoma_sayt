<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Services\TicketService;
use App\Support\TicketFileUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExecutorTicketController extends Controller
{
    public function __construct(protected TicketService $ticketService)
    {
    }

    public function index(): View
    {
        $executor = auth()->user();

        $myTickets = Ticket::query()
            ->with(['assignedExecutor', 'requester', 'category'])
            ->where('assigned_executor_id', $executor->id)
            ->whereNotIn('status', [
                TicketStatus::Completed->value,
                TicketStatus::Closed->value,
                TicketStatus::Rejected->value,
            ])
            ->latest('deadline_at')
            ->paginate(12, ['*'], 'my_page');

        $availableTickets = Ticket::query()
            ->with(['assignedExecutor', 'requester', 'category'])
            ->whereNull('assigned_executor_id')
            ->whereIn('status', [TicketStatus::New->value, TicketStatus::Assigned->value, TicketStatus::Returned->value])
            ->latest('deadline_at')
            ->paginate(12, ['*'], 'available_page');

        return view('executor.index', [
            'myTickets' => $myTickets,
            'availableTickets' => $availableTickets,
            'workloadSummary' => $executor->executorWorkloadSummary(),
        ]);
    }

    public function archive(): View
    {
        $tickets = Ticket::query()
            ->with(['assignedExecutor', 'requester', 'category'])
            ->where('assigned_executor_id', auth()->id())
            ->whereIn('status', [
                TicketStatus::Completed->value,
                TicketStatus::Closed->value,
            ])
            ->latest('completed_at')
            ->paginate(15);

        return view('executor.archive', compact('tickets'));
    }

    public function show(Ticket $ticket): View
    {
        $executor = auth()->user();

        abort_unless($ticket->canExecutorAccess($executor), 403);

        $ticket->load(['comments.user', 'attachments', 'requester', 'assignedDepartment', 'category', 'returnRequests']);

        return view('executor.show', [
            'ticket' => $ticket,
            'claimEvaluation' => $this->ticketService->evaluateExecutorClaim($ticket, $executor),
        ]);
    }

    public function start(Ticket $ticket): RedirectResponse
    {
        $executor = auth()->user();

        abort_unless($ticket->canExecutorAccess($executor), 403);

        if (! $ticket->canExecutorClaimBy($executor)) {
            return back()->withErrors([
                'claim' => "Bu murojaatni hozir qabul qilib bo'lmaydi.",
            ]);
        }

        $wasReturned = $ticket->status === TicketStatus::Returned;

        try {
            $this->ticketService->claimForExecutor($ticket, $executor);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', $wasReturned ? 'Murojaat qayta qabul qilindi.' : 'Murojaat qabul qilindi.');
    }

    public function complete(Request $request, Ticket $ticket): RedirectResponse
    {
        $executor = auth()->user();

        abort_unless($ticket->assigned_executor_id === $executor->id, 403);

        if (! $ticket->canExecutorCompleteBy($executor)) {
            return back()->withErrors([
                'complete' => "Bu murojaatni hozir bajarildi deb yuborib bo'lmaydi.",
            ]);
        }

        $data = $request->validate([
            'note' => ['nullable', 'string'],
            ...TicketFileUpload::requiredRules('proofs'),
        ], TicketFileUpload::messages('proofs'));

        $this->ticketService->complete($ticket, $executor, $request->file('proofs', []), $data['note'] ?? null);

        return redirect()
            ->route('executor.tickets.index')
            ->with('status', 'Murojaat bajarildi deb yuborildi.');
    }

    public function requestReturn(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->assigned_executor_id === auth()->id(), 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5'],
        ]);

        $this->ticketService->requestReturn($ticket, auth()->user(), $data['reason']);

        return back()->with('status', "Qaytarish so'rovi yuborildi.");
    }

    public function comment(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->assigned_executor_id === auth()->id(), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:3'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $this->ticketService->addComment($ticket, auth()->user(), $data['body'], $request->boolean('is_public'));

        return back()->with('status', "Izoh qo'shildi.");
    }
}
