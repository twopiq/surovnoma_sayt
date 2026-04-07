<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(protected TicketService $ticketService)
    {
    }

    public function index(): View
    {
        $tickets = Ticket::query()
            ->visibleTo(auth()->user())
            ->latest()
            ->paginate(12);

        return view('tickets.index', compact('tickets'));
    }

    public function create(): View
    {
        return view('tickets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'min:30'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,doc,docx'],
        ], [
            'attachments.max' => "Ko'pi bilan 5 ta fayl yuklash mumkin.",
            'attachments.*.mimes' => "Fayl formati noto'g'ri. Faqat JPG, JPEG, PNG, PDF, DOC va DOCX formatlariga ruxsat beriladi.",
            'attachments.*.max' => 'Har bir fayl hajmi 5 MB dan oshmasligi kerak.',
        ]);

        [$ticket] = $this->ticketService->create([
            'channel' => 'requester',
            'requester_id' => auth()->id(),
            'requester_name' => auth()->user()->name,
            'requester_email' => auth()->user()->email,
            'requester_phone' => auth()->user()->phone,
            'requester_department' => auth()->user()->department?->name,
            'requester_job_title' => auth()->user()->job_title,
            'description' => $data['description'],
        ], auth()->user(), $request->file('attachments', []));

        return redirect()->route('tickets.show', $ticket)->with('status', 'Murojaat yuborildi.');
    }

    public function show(Ticket $ticket): View
    {
        abort_unless($ticket->requester_id === auth()->id(), 403);

        $ticket->load([
            'comments' => fn ($query) => $query->where('is_public', true)->latest(),
            'attachments',
        ]);

        return view('tickets.show', compact('ticket'));
    }

    public function comment(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->requester_id === auth()->id(), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:3'],
        ]);

        $this->ticketService->addComment($ticket, auth()->user(), $data['body'], true);

        return back()->with('status', "Izoh qo'shildi.");
    }
}
