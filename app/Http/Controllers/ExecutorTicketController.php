<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExecutorTicketController extends Controller
{
    public function __construct(protected TicketService $ticketService)
    {
    }

    public function index(): View
    {
        $tickets = Ticket::query()
            ->where('assigned_executor_id', auth()->id())
            ->latest('deadline_at')
            ->paginate(12);

        return view('executor.index', compact('tickets'));
    }

    public function show(Ticket $ticket): View
    {
        abort_unless($ticket->assigned_executor_id === auth()->id(), 403);

        $ticket->load(['comments.user', 'attachments', 'requester', 'assignedDepartment', 'category', 'returnRequests']);

        return view('executor.show', compact('ticket'));
    }

    public function start(Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->assigned_executor_id === auth()->id(), 403);

        if (! $ticket->canExecutorClaim()) {
            return back()->with('status', "Bu murojaatni hozir qayta qabul qilib bo'lmaydi.");
        }

        $wasReturned = $ticket->status === TicketStatus::Returned;

        $this->ticketService->markInProgress($ticket, auth()->user());

        return back()->with('status', $wasReturned ? 'Murojaat qayta qabul qilindi.' : 'Murojaat qabul qilindi.');
    }

    public function complete(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->assigned_executor_id === auth()->id(), 403);

        $data = $request->validate([
            'note' => ['nullable', 'string'],
            'proofs' => ['required', 'array', 'min:1'],
            'proofs.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,doc,docx'],
        ], [
            'proofs.required' => 'Kamida bitta tasdiqlovchi fayl yuklash kerak.',
            'proofs.min' => 'Kamida bitta tasdiqlovchi fayl yuklash kerak.',
            'proofs.*.mimes' => "Fayl formati noto'g'ri. Faqat JPG, JPEG, PNG, PDF, DOC va DOCX formatlariga ruxsat beriladi.",
            'proofs.*.max' => 'Har bir fayl hajmi 5 MB dan oshmasligi kerak.',
        ]);

        $this->ticketService->complete($ticket, auth()->user(), $request->file('proofs', []), $data['note'] ?? null);

        return back()->with('status', 'Murojaat bajarildi deb yuborildi.');
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
