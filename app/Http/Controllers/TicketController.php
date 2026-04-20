<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Ticket;
use App\Services\TicketService;
use App\Support\TicketFileUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(protected TicketService $ticketService)
    {
    }

    public function index(): View
    {
        $tickets = Ticket::query()
            ->with('category')
            ->visibleTo(auth()->user())
            ->latest()
            ->paginate(12);

        return view('tickets.index', compact('tickets'));
    }

    public function create(): View
    {
        return view('tickets.create', [
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'description' => ['required', 'string', 'min:30'],
            ...TicketFileUpload::optionalRules('attachments'),
        ], array_merge([
            'category_id.required' => 'Muammo kategoriyasini tanlang.',
            'category_id.exists' => "Tanlangan kategoriya topilmadi yoki faol emas.",
        ], TicketFileUpload::messages('attachments')));

        [$ticket] = $this->ticketService->create([
            'channel' => 'requester',
            'category_id' => $data['category_id'],
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
            'category',
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
