<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuestTicketController extends Controller
{
    public function __construct(protected TicketService $ticketService)
    {
    }

    public function create(): View
    {
        return view('guest.create');
    }

    public function store(Request $request): View
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'department' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:30'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,doc,docx'],
        ], [
            'attachments.max' => "Ko'pi bilan 5 ta fayl yuklash mumkin.",
            'attachments.*.mimes' => "Fayl formati noto'g'ri. Faqat JPG, JPEG, PNG, PDF, DOC va DOCX formatlariga ruxsat beriladi.",
            'attachments.*.max' => 'Har bir fayl hajmi 5 MB dan oshmasligi kerak.',
        ]);

        [$ticket, $trackingCode] = $this->ticketService->create([
            'channel' => 'guest',
            'requester_name' => $data['name'],
            'requester_email' => $data['email'] ?? null,
            'requester_phone' => $data['phone'] ?? null,
            'requester_department' => $data['department'] ?? null,
            'requester_job_title' => $data['job_title'] ?? null,
            'description' => $data['description'],
        ], null, $request->file('attachments', []));

        return view('guest.created', compact('ticket', 'trackingCode'));
    }

    public function track(): View
    {
        return view('guest.track');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string'],
            'tracking_code' => ['required', 'string'],
        ]);

        $ticket = Ticket::query()->where('reference', $data['reference'])->first();

        if (! $ticket || ! $this->ticketService->verifyGuestCode($ticket, $data['tracking_code'])) {
            return back()->withErrors([
                'reference' => "Kiritilgan ID yoki maxfiy kod noto'g'ri.",
            ])->withInput();
        }

        session()->put("guest_ticket_access.{$ticket->id}", true);

        return redirect()->route('guest.tickets.show', $ticket);
    }

    public function show(Ticket $ticket): View
    {
        abort_unless(session("guest_ticket_access.{$ticket->id}") === true, 403);

        $ticket->load([
            'comments' => fn ($query) => $query->where('is_public', true)->latest(),
            'attachments',
        ]);

        return view('guest.show', compact('ticket'));
    }
}
