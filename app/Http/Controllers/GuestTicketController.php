<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\GuestBlock;
use App\Models\Ticket;
use App\Services\GuestRequestGuard;
use App\Services\TicketService;
use App\Support\TicketFileUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GuestTicketController extends Controller
{
    public function __construct(
        protected TicketService $ticketService,
        protected GuestRequestGuard $guard,
    ) {
    }

    public function create(Request $request): Response|View
    {
        if ($block = $this->guard->activeBlock($request)) {
            return $this->blockedResponse($block);
        }

        return view('guest.create', [
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'formStartedToken' => $this->guard->formStartedToken(),
            'captchaSiteKey' => $this->guard->captchaEnabled() ? config('services.turnstile.site_key') : null,
        ]);
    }

    public function store(Request $request): Response|View|RedirectResponse
    {
        if ($block = $this->guard->inspect($request)) {
            return $this->blockedResponse($block);
        }

        if ($this->guard->submittedTooFast($request)) {
            return back()->withInput()->withErrors([
                'description' => "Forma juda tez yuborildi. Iltimos, bir necha soniyadan keyin qayta urinib ko'ring.",
            ]);
        }

        if (! $this->guard->captchaPassed($request)) {
            return back()->withInput()->withErrors([
                'captcha' => "Tekshiruvdan o'tilmadi. Iltimos, qayta urinib ko'ring.",
            ]);
        }

        $maxFiles = (int) config('guest_limits.max_files');
        $maxKb = (int) config('guest_limits.max_file_size_kb');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^\S+(?:\s+\S+)+$/'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'phone' => ['required', 'regex:/^\+998 \d{2} \d{3} \d{2} \d{2}$/'],
            'department' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'description' => ['required', 'string', 'min:30'],
            ...TicketFileUpload::optionalRules('attachments', $maxFiles, $maxKb),
        ], array_merge([
            'name.regex' => "F.I.Sh. kamida ism va familiyadan iborat bo'lishi kerak.",
            'email.required' => 'Email manzilini kiriting.',
            'phone.required' => 'Telefon raqamini kiriting.',
            'phone.regex' => "Telefon raqami +998 99 999 99 99 ko'rinishida bo'lishi va 9 ta raqamdan iborat bo'lishi kerak.",
            'category_id.required' => 'Muammo kategoriyasini tanlang.',
            'category_id.exists' => "Tanlangan kategoriya topilmadi yoki faol emas.",
        ], TicketFileUpload::messages('attachments', $maxFiles, $maxKb)));

        if ($block = $this->guard->inspectContact($request, $data['email'], $data['phone'] ?? null)) {
            return $this->blockedResponse($block);
        }

        if ($this->guard->isDuplicateDescription($data['description'])) {
            return back()->withInput()->withErrors([
                'description' => "Bunday murojaat yaqinda yuborilgan. Holatini «Avvalgi murojaatni kuzatish» orqali tekshiring.",
            ]);
        }

        $this->guard->recordSubmission($request, $data['email'], $data['phone'] ?? null);
        $this->guard->rememberDescription($data['description']);

        [$ticket, $trackingCode] = $this->ticketService->create([
            'channel' => 'guest',
            'category_id' => $data['category_id'],
            'requester_name' => $data['name'],
            'requester_email' => $data['email'] ?? null,
            'requester_phone' => $data['phone'] ?? null,
            'requester_department' => $data['department'] ?? null,
            'requester_job_title' => $data['job_title'] ?? null,
            'description' => $data['description'],
        ], null, $request->file('attachments', []));

        session()->put("guest_ticket_access.{$ticket->id}", true);

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
            'category',
            'assignedExecutor',
            'slaProfile',
        ]);

        return view('guest.show', compact('ticket'));
    }

    protected function blockedResponse(GuestBlock $block): Response
    {
        return response()->view('guest.blocked', ['block' => $block], 429);
    }
}
