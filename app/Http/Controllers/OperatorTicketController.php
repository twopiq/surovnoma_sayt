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

class OperatorTicketController extends Controller
{
    public function __construct(protected TicketService $ticketService)
    {
    }

    public function index(): View
    {
        $tickets = Ticket::query()
            ->with('category')
            ->where('operator_id', auth()->id())
            ->latest()
            ->paginate(12);

        return view('operator.index', compact('tickets'));
    }

    public function create(): View
    {
        return view('operator.create', [
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'regex:/^\+998 \d{2} \d{3} \d{2} \d{2}$/'],
            'department' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'description' => ['required', 'string', 'min:30'],
            ...TicketFileUpload::optionalRules('attachments'),
        ], array_merge([
            'category_id.required' => 'Muammo kategoriyasini tanlang.',
            'category_id.exists' => "Tanlangan kategoriya topilmadi yoki faol emas.",
            'phone.regex' => "Telefon raqami +998 99 999 99 99 ko'rinishida bo'lishi kerak.",
        ], TicketFileUpload::messages('attachments')));

        [$ticket] = $this->ticketService->create([
            'channel' => 'operator',
            'operator_id' => auth()->id(),
            'category_id' => $data['category_id'],
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
            'category',
        ]);

        return view('operator.show', compact('ticket'));
    }
}
