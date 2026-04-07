<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperatorTicketController extends Controller
{
    public function __construct(protected TicketService $ticketService)
    {
    }

    public function index(): View
    {
        $tickets = Ticket::query()
            ->where('operator_id', auth()->id())
            ->latest()
            ->paginate(12);

        return view('operator.index', compact('tickets'));
    }

    public function create(): View
    {
        return view('operator.create');
    }

    public function store(Request $request): RedirectResponse
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

        [$ticket] = $this->ticketService->create([
            'channel' => 'operator',
            'operator_id' => auth()->id(),
            'requester_name' => $data['name'],
            'requester_email' => $data['email'] ?? null,
            'requester_phone' => $data['phone'] ?? null,
            'requester_department' => $data['department'] ?? null,
            'requester_job_title' => $data['job_title'] ?? null,
            'description' => $data['description'],
        ], auth()->user(), $request->file('attachments', []));

        return redirect()->route('operator.tickets.show', $ticket)->with('status', 'Murojaat operator orqali yaratildi.');
    }

    public function show(Ticket $ticket): View
    {
        abort_unless($ticket->operator_id === auth()->id(), 403);

        $ticket->load([
            'comments' => fn ($query) => $query->where('is_public', true)->latest(),
            'attachments',
        ]);

        return view('operator.show', compact('ticket'));
    }
}
