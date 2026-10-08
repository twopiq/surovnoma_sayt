<?php

namespace App\TelegramBot;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use App\Support\Locales;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TelegramUpdateHandler
{
    public function __construct(
        protected TelegramSdkBot $bot,
        protected TicketService $ticketService,
    ) {
    }

    public function handle(array $update): void
    {
        if (! $this->telegramSchemaReady()) {
            return;
        }

        if (isset($update['callback_query']) && is_array($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);

            return;
        }

        $message = $update['message'] ?? $update['edited_message'] ?? null;

        if (! is_array($message)) {
            return;
        }

        $chatId = $message['chat']['id'] ?? null;
        $text = trim((string) ($message['text'] ?? ''));
        $contactPhone = trim((string) ($message['contact']['phone_number'] ?? ''));

        if (! $chatId || ($text === '' && $contactPhone === '')) {
            return;
        }

        $chatId = (string) $chatId;
        Locales::apply($this->telegramLocale($chatId));

        if ($this->isMainMenuText($text)) {
            Cache::forget($this->stateKey($chatId));
            $this->openMainMenu($chatId, $this->userByChat($chatId));

            return;
        }

        if ($this->isSettingsText($text)) {
            Cache::forget($this->stateKey($chatId));
            $this->sendSettingsMenu($chatId, $this->userByChat($chatId));

            return;
        }

        if ($this->isLanguageText($text)) {
            Cache::forget($this->stateKey($chatId));
            $this->sendLanguageMenu($chatId);

            return;
        }

        if (($chosen = $this->languageChoice($text)) !== null) {
            Cache::forget($this->stateKey($chatId));
            $this->setTelegramLocale($chatId, $chosen);
            Locales::apply($chosen);
            $this->sendSettingsMenu($chatId, $this->userByChat($chatId));

            return;
        }

        if ($this->isLogoutText($text)) {
            Cache::forget($this->stateKey($chatId));
            $this->installDefaultKeyboard($chatId);
            $this->handleUnlink($chatId);

            return;
        }

        if (Str::startsWith($text, ['/cancel', '/bekor'])) {
            Cache::forget($this->stateKey($chatId));
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Bekor qilindi'),
                __('Joriy amal bekor qilindi.'),
                null,
                $this->menuButtons($this->userByChat($chatId), $chatId),
            ));

            return;
        }

        if (Str::startsWith($text, '/start')) {
            $this->installDefaultKeyboard($chatId);
            $this->handleStart($chatId, $text, $message);

            return;
        }

        if (Str::startsWith($text, ['/menu', '/help'])) {
            $this->openMainMenu($chatId, $this->userByChat($chatId));

            return;
        }

        if (Str::startsWith($text, '/profile')) {
            $this->sendProfile($chatId);

            return;
        }

        if (Str::startsWith($text, ['/on', '/notifications_on'])) {
            $this->setNotifications($chatId, true);

            return;
        }

        if (Str::startsWith($text, ['/off', '/notifications_off'])) {
            $this->setNotifications($chatId, false);

            return;
        }

        if (Str::startsWith($text, '/unlink')) {
            $this->handleUnlink($chatId);

            return;
        }

        $state = Cache::get($this->stateKey($chatId));

        if (is_array($state)) {
            $this->handleStateInput($chatId, $contactPhone !== '' ? $contactPhone : $text, $message, $this->userByChat($chatId), $state);

            return;
        }

        $this->sendMenu($chatId, $this->userByChat($chatId));
    }

    protected function handleCallback(array $callback): void
    {
        $chatId = $callback['message']['chat']['id'] ?? null;
        $callbackId = $callback['id'] ?? null;
        $data = (string) ($callback['data'] ?? '');

        if (! $chatId) {
            return;
        }

        $chatId = (string) $chatId;
        Locales::apply($this->telegramLocale($chatId));
        $user = $this->userByChat($chatId);

        if ($callbackId) {
            $this->bot->answerCallbackQuery((string) $callbackId);
        }

        if (Str::startsWith($data, 'guest:category:')) {
            $this->selectCategoryForCreate($chatId, null, (int) Str::after($data, 'guest:category:'), 'guest');

            return;
        }

        if (Str::startsWith($data, 'requester:category:')) {
            $this->selectCategoryForCreate($chatId, $user, (int) Str::after($data, 'requester:category:'), 'requester');

            return;
        }

        if (Str::startsWith($data, 'executor:claim:')) {
            $this->claimTicketFromBot($chatId, $user, (int) Str::after($data, 'executor:claim:'));

            return;
        }

        if (Str::startsWith($data, 'executor:complete:')) {
            $this->askExecutorComplete($chatId, $user, (int) Str::after($data, 'executor:complete:'));

            return;
        }

        if (Str::startsWith($data, 'executor:return:')) {
            $this->askExecutorReturn($chatId, $user, (int) Str::after($data, 'executor:return:'));

            return;
        }

        if (Str::startsWith($data, 'executor:comment:')) {
            $this->askExecutorComment($chatId, $user, (int) Str::after($data, 'executor:comment:'));

            return;
        }

        match ($data) {
            'profile' => $this->sendProfile($chatId),
            'notifications:toggle' => $this->toggleNotifications($chatId),
            'notifications:on' => $this->setNotifications($chatId, true),
            'notifications:off' => $this->setNotifications($chatId, false),
            'link' => $this->sendLinkHelp($chatId),
            'unlink:ask' => $this->askUnlink($chatId, $user),
            'unlink:confirm' => $this->handleUnlink($chatId),
            'unlink:cancel' => $this->sendMenu($chatId, $user),
            'guest:create' => $this->sendCategoryPicker($chatId, null, 'guest'),
            'guest:track' => $this->askGuestTrack($chatId),
            'requester:tickets' => $this->sendRequesterTickets($chatId, $user),
            'requester:create' => $this->sendCategoryPicker($chatId, $user, 'requester'),
            'executor:tasks' => $this->sendExecutorTasks($chatId, $user),
            'executor:available' => $this->sendExecutorAvailableTickets($chatId, $user, false),
            'executor:overdue' => $this->sendExecutorAvailableTickets($chatId, $user, true),
            'operator:tickets' => $this->sendOperatorTickets($chatId, $user),
            'admin:summary' => $this->sendAdminSummary($chatId, $user),
            'admin:overdue' => $this->sendAdminOverdue($chatId, $user),
            'admin:users' => $this->sendAdminUsers($chatId, $user),
            'manager:summary' => $this->sendManagerSummary($chatId, $user),
            default => $this->sendMenu($chatId, $user),
        };
    }

    protected function handleStart(string $chatId, string $text, array $message): void
    {
        $token = trim((string) preg_replace('/^\/start(?:@\S+)?\s*/', '', $text));

        if ($token === '') {
            $this->sendGreeting($chatId, $this->userByChat($chatId));

            return;
        }

        $user = User::query()
            ->where('telegram_link_token', $token)
            ->first();

        if (! $user) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Assalomu alaykum!'),
                __("Ulash kodi noto'g'ri yoki yangilangan. Saytdagi Sozlamalar sahifasidan Telegram botni qayta oching."),
                null,
                $this->menuButtons(null, $chatId),
            ));

            return;
        }

        $from = $message['from'] ?? [];
        $chat = $message['chat'] ?? [];

        $result = $user->linkTelegramChat($chatId, $from['username'] ?? $chat['username'] ?? null);

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Assalomu alaykum!'),
            match ($result) {
                'added' => __("Qo'shimcha Telegram akkaunt ulandi. Tizim xabarlari endi bu chatga ham, avval ulangan chatlaringizga ham yuboriladi."),
                'already' => __("Bu chat allaqachon profilingizga ulangan. Tizim xabarlari shu chatga keladi."),
                default => __("Muvaffaqiyatli ulandingiz. Telegram akkauntingiz sayt profilingizga bog'landi. Endi yangi tizim xabarlari shu chatga avtomatik yuboriladi."),
            },
            null,
            $this->menuButtons($user->fresh()),
        ));
    }

    protected function sendGreeting(string $chatId, ?User $user): void
    {
        if (! $user) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Assalomu alaykum!'),
                __("RTT Markazi botiga xush kelibsiz. Akkaunt ulanmagan chatda faqat bir martalik mehmon murojaati yuborish yoki tracking kod orqali holatni tekshirish mumkin. Doimiy foydalanish uchun saytdan ro'yxatdan o'ting."),
                null,
                $this->menuButtons(null, $chatId),
            ));

            return;
        }

        $status = $this->notificationsEnabled($user) ? __('yoqilgan') : __("o'chirilgan");

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Assalomu alaykum!'),
            __('RTT Markazi botiga xush kelibsiz. Akkauntingiz ulangan. Telegram xabarnomalari: :status.', ['status' => $status]),
            null,
            $this->menuButtons($user),
        ));
    }

    protected function sendMenu(string $chatId, ?User $user): void
    {
        if (! $user) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                'RTT Markazi',
                __("Akkaunt ulanmagan. Mehmon murojaati yuborish bir marta ishlaydi; keyin saytdan ro'yxatdan o'ting."),
                null,
                $this->menuButtons(null, $chatId),
            ));

            return;
        }

        $status = $this->notificationsEnabled($user) ? __('yoqilgan') : __("o'chirilgan");

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'RTT Markazi',
            __('Akkaunt ulangan. Telegram xabarnomalari: :status.', ['status' => $status]),
            null,
            $this->menuButtons($user),
        ));
    }

    protected function sendProfile(string $chatId): void
    {
        $user = $this->userByChat($chatId);

        if (! $user) {
            $this->sendMenu($chatId, null);

            return;
        }

        $lines = [
            __('F.I.O.').': '.$user->name,
            __('Login').': '.($user->login ?: '-'),
            'Email: '.$user->email,
            __('Telefon').': '.($user->phone ?: '-'),
            __('Lavozim').': '.($user->job_title ?: '-'),
            __("Bo'lim").': '.($user->department?->name ?: '-'),
            __('Rol').': '.$user->display_role,
            __('Bandlik').': '.($user->availability_status?->label() ?? '-'),
            __('Telegram xabarnomalari').': '.($this->notificationsEnabled($user) ? __('Yoqilgan') : __("O'chirilgan")),
        ];

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Profil ma\'lumotlari'),
            implode("\n", $lines),
            null,
            $this->menuButtons($user),
        ));
    }

    protected function setNotifications(string $chatId, bool $enabled): void
    {
        $user = $this->userByChat($chatId);

        if (! $user) {
            $this->sendMenu($chatId, null);

            return;
        }

        $user->forceFill([
            'telegram_notifications_enabled' => $enabled,
        ])->save();

        $this->bot->sendMessage($chatId, new TelegramMessage(
            $enabled ? __('Xabarnomalar yoqildi') : __("Xabarnomalar o'chirildi"),
            $enabled
                ? __('Yangi tizim xabarlari shu chatga yuboriladi.')
                : __("Yangi tizim xabarlari Telegramga yuborilmaydi. Saytdagi Bildirishnomalar bo'limida ko'rinaveradi."),
            null,
            $this->menuButtons($user->fresh()),
        ));
    }

    protected function toggleNotifications(string $chatId): void
    {
        $user = $this->userByChat($chatId);

        if (! $user) {
            $this->sendMenu($chatId, null);

            return;
        }

        $this->setNotifications($chatId, ! $this->notificationsEnabled($user));
    }

    protected function openMainMenu(string $chatId, ?User $user): void
    {
        $this->installDefaultKeyboard($chatId);
        $this->sendMenu($chatId, $user);
    }

    protected function installDefaultKeyboard(string $chatId): void
    {
        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Menyu'),
            __('Pastki menyu yangilandi.'),
            null,
            [],
            $this->defaultReplyKeyboard($chatId),
        ));
    }

    protected function sendSettingsMenu(string $chatId, ?User $user): void
    {
        $linked = $user ? __('ulangan') : __('ulanmagan');
        $language = Locales::AVAILABLE[$this->telegramLocale($chatId)][0];

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Sozlamalar'),
            __('Akkaunt').': '.$linked."\n".__('Til').': '.$language,
            null,
            [],
            $this->settingsReplyKeyboard($chatId, $user),
        ));
    }

    protected function sendLanguageMenu(string $chatId): void
    {
        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Til'),
            __('Bot interfeysi tilini tanlang.'),
            null,
            [],
            $this->languageReplyKeyboard($chatId),
        ));
    }

    protected function handleStateInput(string $chatId, string $text, array $message, ?User $user, array $state): void
    {
        match ($state['mode'] ?? null) {
            'guest:track' => $this->trackGuestTicket($chatId, $text),
            'guest:create:phone' => $this->collectGuestPhone($chatId, $text, $state),
            'guest:create:description' => $this->createGuestTicketFromText($chatId, $text, $message, $state),
            'requester:create:description' => $this->createRequesterTicketFromText($chatId, $text, $user, $state),
            'executor:complete:note' => $this->completeExecutorTicketFromText($chatId, $text, $user, $state),
            'executor:return:reason' => $this->returnExecutorTicketFromText($chatId, $text, $user, $state),
            'executor:comment:body' => $this->commentExecutorTicketFromText($chatId, $text, $user, $state),
            default => $this->sendMenu($chatId, $user),
        };
    }

    protected function sendCategoryPicker(string $chatId, ?User $user, string $mode): void
    {
        if ($mode === 'guest' && $this->guestTicketAlreadyCreated($chatId)) {
            $this->sendRegisterOffer($chatId);

            return;
        }

        if ($mode === 'requester' && ! $user?->hasSystemRole(UserRole::Requester)) {
            $this->sendMenu($chatId, $user);

            return;
        }

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($categories->isEmpty()) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Kategoriya topilmadi'),
                __('Hozircha faol muammo kategoriyalari mavjud emas.'),
                null,
                $this->menuButtons($user),
            ));

            return;
        }

        $prefix = $mode === 'guest' ? 'guest:category:' : 'requester:category:';
        $buttons = $categories
            ->map(fn (Category $category): array => [
                ['text' => Str::limit($category->name, 36), 'callback_data' => $prefix.$category->id],
            ])
            ->values()
            ->all();

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Kategoriya tanlang'),
            $mode === 'guest'
                ? __("Mehmon murojaati uchun muammo kategoriyasini tanlang.")
                : __("Yangi murojaat uchun muammo kategoriyasini tanlang."),
            null,
            $buttons,
        ));
    }

    protected function selectCategoryForCreate(string $chatId, ?User $user, int $categoryId, string $mode): void
    {
        $category = Category::query()
            ->where('is_active', true)
            ->find($categoryId);

        if (! $category) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Kategoriya topilmadi'),
                __('Tanlangan kategoriya faol emas yoki topilmadi.'),
                null,
                $this->menuButtons($user),
            ));

            return;
        }

        if ($mode === 'guest') {
            Cache::put($this->stateKey($chatId), [
                'mode' => 'guest:create:phone',
                'category_id' => $category->id,
            ], now()->addMinutes(20));

            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Telefon raqam'),
                __('Kategoriya').': '.$category->name."\n".__('Murojaatni qabul qilishdan oldin telefon raqamingizni yuboring.')."\n".__('Format: :format', ['format' => '+998 99 999 99 99'])."\n".__('Bekor qilish uchun /cancel yuboring.'),
                null,
                [],
                $this->phoneReplyKeyboard($chatId),
            ));

            return;
        }

        Cache::put($this->stateKey($chatId), [
            'mode' => 'requester:create:description',
            'category_id' => $category->id,
        ], now()->addMinutes(20));

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Tavsif yozing'),
            __('Kategoriya').': '.$category->name."\n".__('Muammoni kamida 30 belgida yozing.').' '.__('Bekor qilish uchun /cancel yuboring.'),
        ));
    }

    protected function collectGuestPhone(string $chatId, string $text, array $state): void
    {
        $phone = $this->normalizeUzbekPhone($text);

        if (! $phone) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __("Telefon raqam noto'g'ri"),
                __('Telefon raqamini +998 99 999 99 99 formatida yuboring yoki pastdagi tugma orqali raqamni ulashing.')."\n".__('Bekor qilish uchun /cancel yuboring.'),
                null,
                [],
                $this->phoneReplyKeyboard($chatId),
            ));

            return;
        }

        Cache::put($this->stateKey($chatId), [
            'mode' => 'guest:create:description',
            'category_id' => $state['category_id'] ?? null,
            'requester_phone' => $phone,
        ], now()->addMinutes(20));

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Tavsif yozing'),
            __('Telefon').': '.$phone."\n".__('Endi muammoni kamida 30 belgida yozing.').' '.__('Bekor qilish uchun /cancel yuboring.'),
            null,
            [],
            $this->defaultReplyKeyboard($chatId),
        ));
    }

    protected function createGuestTicketFromText(string $chatId, string $text, array $message, array $state): void
    {
        if ($this->guestTicketAlreadyCreated($chatId)) {
            Cache::forget($this->stateKey($chatId));
            $this->sendRegisterOffer($chatId);

            return;
        }

        if (mb_strlen($text) < 30) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Tavsif qisqa'),
                __("Iltimos, muammoni kamida 30 belgida yozing. Bekor qilish uchun /cancel yuboring."),
            ));

            return;
        }

        $from = $message['from'] ?? [];
        $name = trim(implode(' ', array_filter([
            $from['first_name'] ?? null,
            $from['last_name'] ?? null,
        ]))) ?: (($from['username'] ?? null) ? '@'.$from['username'] : 'Telegram guest');

        [$ticket, $trackingCode] = $this->ticketService->create([
            'channel' => 'guest',
            'category_id' => $state['category_id'] ?? null,
            'requester_name' => $name,
            'requester_email' => null,
            'requester_phone' => $state['requester_phone'] ?? null,
            'description' => $text,
        ]);

        if (! empty($state['requester_phone'])) {
            $this->ticketService->addComment(
                $ticket,
                null,
                __('Telefon raqamim: :phone', ['phone' => $this->phoneForComment($state['requester_phone'])], 'uz'),
                // ($state['requester_phone']),
                true,
            );
        }

        Cache::forget($this->stateKey($chatId));
        Cache::forever($this->guestCreatedKey($chatId), $ticket->id);

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Mehmon murojaati yuborildi'),
            __('Murojaat raqami').': '.$ticket->reference."\n".__('Tracking kod').': '.$trackingCode."\n".__("Holatni tekshirish uchun shu ikki qiymatni saqlab qo'ying.")."\n\n".__("Doimiy kabinet va to'liq imkoniyatlar uchun saytdan ro'yxatdan o'ting."),
            route('register'),
            [
                [
                    ['text' => __("Holatni tekshirish"), 'callback_data' => 'guest:track'],
                ],
            ],
        ));
    }

    protected function createRequesterTicketFromText(string $chatId, string $text, ?User $user, array $state): void
    {
        if (! $user?->hasSystemRole(UserRole::Requester)) {
            Cache::forget($this->stateKey($chatId));
            $this->sendMenu($chatId, $user);

            return;
        }

        if (mb_strlen($text) < 30) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Tavsif qisqa'),
                __("Iltimos, muammoni kamida 30 belgida yozing. Bekor qilish uchun /cancel yuboring."),
            ));

            return;
        }

        [$ticket] = $this->ticketService->create([
            'channel' => 'requester',
            'category_id' => $state['category_id'] ?? null,
            'requester_id' => $user->id,
            'requester_name' => $user->name,
            'requester_email' => $user->email,
            'requester_phone' => $user->phone,
            'requester_department' => $user->department?->name,
            'requester_job_title' => $user->job_title,
            'description' => $text,
        ], $user);

        Cache::forget($this->stateKey($chatId));

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Murojaat yuborildi'),
            __('Murojaat raqami').': '.$ticket->reference."\n".__('Holat').': '.$ticket->status->label(),
            route('tickets.show', $ticket),
            $this->menuButtons($user),
        ));
    }

    protected function askGuestTrack(string $chatId): void
    {
        Cache::put($this->stateKey($chatId), [
            'mode' => 'guest:track',
        ], now()->addMinutes(10));

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Murojaat holatini tekshirish'),
            __('Murojaat raqami va tracking kodni bitta xabarda yuboring.')."\n".__('Masalan').': RTT-20260525-0001 ABCD1234'."\n".__('Bekor qilish uchun /cancel.'),
        ));
    }

    protected function trackGuestTicket(string $chatId, string $text): void
    {
        $parts = preg_split('/\s+/', trim($text), 2);
        $reference = $parts[0] ?? '';
        $code = $parts[1] ?? '';

        $ticket = Ticket::query()
            ->with(['category', 'slaProfile'])
            ->where('reference', $reference)
            ->first();

        if (! $ticket || ! $code || ! $this->ticketService->verifyGuestCode($ticket, $code)) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Topilmadi'),
                __("Murojaat raqami yoki tracking kod noto'g'ri. Qayta urinib ko'ring yoki /cancel yuboring."),
            ));

            return;
        }

        Cache::forget($this->stateKey($chatId));

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Murojaat holati'),
            $this->ticketSummary($ticket),
            null,
            $this->menuButtons(null, $chatId),
        ));
    }

    protected function sendRequesterTickets(string $chatId, ?User $user): void
    {
        if (! $user?->hasSystemRole(UserRole::Requester)) {
            $this->sendMenu($chatId, $user);

            return;
        }

        $tickets = Ticket::query()
            ->with(['category', 'slaProfile'])
            ->where('requester_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        $body = $tickets->isEmpty()
            ? __("Sizda hali murojaatlar yo'q.")
            : $tickets->map(fn (Ticket $ticket): string => $this->ticketLine($ticket))->implode("\n\n");

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Murojaatlarim'),
            $body,
            route('tickets.index'),
            $this->menuButtons($user),
        ));
    }

    protected function sendExecutorTasks(string $chatId, ?User $user): void
    {
        if (! $user?->hasSystemRole(UserRole::Executor)) {
            $this->sendMenu($chatId, $user);

            return;
        }

        $tickets = Ticket::query()
            ->with(['category', 'slaProfile'])
            ->where('assigned_executor_id', $user->id)
            ->whereIn('status', [
                TicketStatus::Assigned->value,
                TicketStatus::InProgress->value,
                TicketStatus::Returned->value,
            ])
            ->orderByRaw('deadline_at is null')
            ->orderBy('deadline_at')
            ->limit(5)
            ->get();

        if ($tickets->isEmpty()) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Mening vazifalarim'),
                __("Sizga biriktirilgan faol murojaat yo'q."),
                route('executor.tickets.index'),
                $this->menuButtons($user),
            ));

            return;
        }

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Mening vazifalarim'),
            $tickets->map(fn (Ticket $ticket): string => $this->ticketLine($ticket))->implode("\n\n"),
            route('executor.tickets.index'),
            $this->executorTicketActionButtons($tickets, $user),
        ));
    }

    protected function sendExecutorAvailableTickets(string $chatId, ?User $user, bool $overdueOnly): void
    {
        if (! $user?->hasSystemRole(UserRole::Executor)) {
            $this->sendMenu($chatId, $user);

            return;
        }

        $tickets = Ticket::query()
            ->with(['category', 'slaProfile'])
            ->whereNull('assigned_executor_id')
            ->when(
                $overdueOnly,
                fn ($query) => $query->where('status', TicketStatus::Overdue->value),
                fn ($query) => $query->whereIn('status', [
                    TicketStatus::New->value,
                    TicketStatus::Assigned->value,
                    TicketStatus::Returned->value,
                ]),
            )
            ->orderByRaw('deadline_at is null')
            ->orderBy('deadline_at')
            ->latest('created_at')
            ->limit(5)
            ->get();

        if ($tickets->isEmpty()) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                $overdueOnly ? __('Kechikkan murojaatlar') : __("Bo'sh murojaatlar"),
                $overdueOnly ? __("Qabul qilish mumkin bo'lgan kechikkan murojaat yo'q.") : __("Qabul qilish mumkin bo'lgan bo'sh murojaat yo'q."),
                route('executor.tickets.index'),
                $this->menuButtons($user),
            ));

            return;
        }

        $buttons = $tickets->map(fn (Ticket $ticket): array => [
            ['text' => __('Olish').': '.$ticket->reference, 'callback_data' => 'executor:claim:'.$ticket->id],
        ])->values()->all();

        $this->bot->sendMessage($chatId, new TelegramMessage(
            $overdueOnly ? __('Kechikkan murojaatlar') : __("Bo'sh murojaatlar"),
            $tickets->map(fn (Ticket $ticket): string => $this->ticketLine($ticket))->implode("\n\n"),
            route('executor.tickets.index'),
            $buttons,
        ));
    }

    protected function claimTicketFromBot(string $chatId, ?User $user, int $ticketId): void
    {
        if (! $user?->hasSystemRole(UserRole::Executor)) {
            $this->sendMenu($chatId, $user);

            return;
        }

        $ticket = Ticket::query()->find($ticketId);

        if (! $ticket || ! $ticket->canExecutorAccess($user)) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Murojaat olinmadi'),
                __('Bu murojaat topilmadi yoki siz uchun ochiq emas.'),
                null,
                $this->menuButtons($user),
            ));

            return;
        }

        try {
            $updated = $this->ticketService->claimForExecutor($ticket, $user, __('Telegram orqali qabul qilindi.', [], 'uz'));
        } catch (\Throwable $exception) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Murojaat olinmadi'),
                $exception->getMessage(),
                null,
                $this->menuButtons($user),
            ));

            return;
        }

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Murojaat qabul qilindi'),
            $this->ticketSummary($updated),
            route('executor.tickets.show', $updated),
            $this->executorTicketActionButtons(collect([$updated]), $user),
        ));
    }

    protected function askExecutorComplete(string $chatId, ?User $user, int $ticketId): void
    {
        $ticket = $this->executorTicketForAction($chatId, $user, $ticketId);

        if (! $ticket) {
            return;
        }

        if (! $ticket->canExecutorCompleteBy($user)) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Bajarib bo\'lmaydi'),
                __("Bu murojaatni hozir bajarildi deb yuborib bo'lmaydi. Avval uni qabul qiling."),
                null,
                $this->executorTicketActionButtons(collect([$ticket]), $user),
            ));

            return;
        }

        Cache::put($this->stateKey($chatId), [
            'mode' => 'executor:complete:note',
            'ticket_id' => $ticket->id,
        ], now()->addMinutes(20));

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Bajarish izohi'),
            __(":ref bo'yicha bajarilgan ishni qisqa yozing.", ['ref' => $ticket->reference]).' '.__('Bekor qilish uchun /cancel yuboring.'),
        ));
    }

    protected function completeExecutorTicketFromText(string $chatId, string $text, ?User $user, array $state): void
    {
        $ticket = $this->executorTicketForAction($chatId, $user, (int) ($state['ticket_id'] ?? 0));

        if (! $ticket) {
            Cache::forget($this->stateKey($chatId));

            return;
        }

        if (mb_strlen($text) < 3) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Izoh qisqa'),
                __("Bajarilgan ish bo'yicha kamida 3 belgi yozing. Bekor qilish uchun /cancel yuboring."),
            ));

            return;
        }

        try {
            $updated = $this->ticketService->complete($ticket, $user, [], $text);
        } catch (\Throwable $exception) {
            Cache::forget($this->stateKey($chatId));
            $this->bot->sendMessage($chatId, new TelegramMessage(
                'Bajarilmadi',
                $exception->getMessage(),
                null,
                $this->menuButtons($user),
            ));

            return;
        }

        Cache::forget($this->stateKey($chatId));

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Murojaat bajarildi'),
            $this->ticketSummary($updated),
            route('executor.tickets.show', $updated),
            $this->menuButtons($user),
        ));
    }

    protected function askExecutorReturn(string $chatId, ?User $user, int $ticketId): void
    {
        $ticket = $this->executorTicketForAction($chatId, $user, $ticketId);

        if (! $ticket) {
            return;
        }

        if ($ticket->assigned_executor_id !== $user?->id || ! in_array($ticket->status, [TicketStatus::Assigned, TicketStatus::InProgress], true)) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Qaytarib bo\'lmaydi'),
                __("Bu murojaatni hozir qaytarib bo'lmaydi."),
                null,
                $this->executorTicketActionButtons(collect([$ticket]), $user),
            ));

            return;
        }

        Cache::put($this->stateKey($chatId), [
            'mode' => 'executor:return:reason',
            'ticket_id' => $ticket->id,
        ], now()->addMinutes(20));

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Qaytarish sababi'),
            __(':ref nega bajara olmasligingizni yozing — murojaat sizdan olinib, umumiy navbatga qaytadi.', ['ref' => $ticket->reference]).' '.__('Bekor qilish uchun /cancel yuboring.'),
        ));
    }

    protected function returnExecutorTicketFromText(string $chatId, string $text, ?User $user, array $state): void
    {
        $ticket = $this->executorTicketForAction($chatId, $user, (int) ($state['ticket_id'] ?? 0));

        if (! $ticket) {
            Cache::forget($this->stateKey($chatId));

            return;
        }

        if (mb_strlen($text) < 5) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Sabab qisqa'),
                __("Qaytarish sababini kamida 5 belgida yozing. Bekor qilish uchun /cancel yuboring."),
            ));

            return;
        }

        try {
            $updated = $this->ticketService->requestReturn($ticket, $user, $text);
        } catch (\Throwable $exception) {
            Cache::forget($this->stateKey($chatId));
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __("Qaytarib bo'lmadi"),
                $exception->getMessage(),
                null,
                $this->menuButtons($user),
            ));

            return;
        }

        Cache::forget($this->stateKey($chatId));

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Murojaat navbatga qaytarildi'),
            $this->ticketSummary($updated),
            route('executor.tickets.show', $updated),
            $this->menuButtons($user),
        ));
    }

    protected function askExecutorComment(string $chatId, ?User $user, int $ticketId): void
    {
        $ticket = $this->executorTicketForAction($chatId, $user, $ticketId);

        if (! $ticket) {
            return;
        }

        Cache::put($this->stateKey($chatId), [
            'mode' => 'executor:comment:body',
            'ticket_id' => $ticket->id,
        ], now()->addMinutes(20));

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Izoh yozing'),
            __(":ref uchun izoh matnini yuboring. Izoh murojaatchiga ko'rinadi.", ['ref' => $ticket->reference]).' '.__('Bekor qilish uchun /cancel yuboring.'),
        ));
    }

    protected function commentExecutorTicketFromText(string $chatId, string $text, ?User $user, array $state): void
    {
        $ticket = $this->executorTicketForAction($chatId, $user, (int) ($state['ticket_id'] ?? 0));

        if (! $ticket) {
            Cache::forget($this->stateKey($chatId));

            return;
        }

        if (mb_strlen($text) < 3) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Izoh qisqa'),
                __("Izohni kamida 3 belgida yozing. Bekor qilish uchun /cancel yuboring."),
            ));

            return;
        }

        $this->ticketService->addComment($ticket, $user, $text, true);
        Cache::forget($this->stateKey($chatId));

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __("Izoh qo'shildi"),
            $this->ticketSummary($ticket->fresh()),
            route('executor.tickets.show', $ticket),
            $this->executorTicketActionButtons(collect([$ticket->fresh()]), $user),
        ));
    }

    protected function sendOperatorTickets(string $chatId, ?User $user): void
    {
        if (! $user?->hasSystemRole(UserRole::Operator)) {
            $this->sendMenu($chatId, $user);

            return;
        }

        $tickets = Ticket::query()
            ->with(['category', 'slaProfile'])
            ->where('operator_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        $body = $tickets->isEmpty()
            ? __("Siz operator sifatida yaratgan murojaatlar hali yo'q.")
            : $tickets->map(fn (Ticket $ticket): string => $this->ticketLine($ticket))->implode("\n\n");

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Operator murojaatlari'),
            $body,
            route('operator.tickets.index'),
            [
                [
                    ['text' => __('Saytda yaratish'), 'url' => route('operator.tickets.create')],
                ],
                ...$this->menuButtons($user),
            ],
        ));
    }

    protected function executorTicketForAction(string $chatId, ?User $user, int $ticketId): ?Ticket
    {
        if (! $user?->hasSystemRole(UserRole::Executor)) {
            $this->sendMenu($chatId, $user);

            return null;
        }

        $ticket = Ticket::query()->find($ticketId);

        if (! $ticket || ! $ticket->canExecutorAccess($user)) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Murojaat topilmadi'),
                __('Bu murojaat topilmadi yoki siz uchun ochiq emas.'),
                null,
                $this->menuButtons($user),
            ));

            return null;
        }

        return $ticket;
    }

    protected function executorTicketActionButtons(iterable $tickets, User $user): array
    {
        $buttons = [];

        foreach ($tickets as $ticket) {
            if (! $ticket instanceof Ticket) {
                continue;
            }

            $row = [];

            if ($ticket->canExecutorClaimBy($user)) {
                $row[] = ['text' => $ticket->executorClaimLabel().': '.$ticket->reference, 'callback_data' => 'executor:claim:'.$ticket->id];
            }

            if ($ticket->canExecutorCompleteBy($user)) {
                $row[] = ['text' => __('Bajarish').': '.$ticket->reference, 'callback_data' => 'executor:complete:'.$ticket->id];
            }

            if (
                $ticket->assigned_executor_id === $user->id
                && in_array($ticket->status, [TicketStatus::Assigned, TicketStatus::InProgress], true)
            ) {
                $row[] = ['text' => __('Qaytarish').': '.$ticket->reference, 'callback_data' => 'executor:return:'.$ticket->id];
            }

            if ($ticket->assigned_executor_id === $user->id) {
                $row[] = ['text' => __('Izoh').': '.$ticket->reference, 'callback_data' => 'executor:comment:'.$ticket->id];
            }

            foreach (array_chunk($row, 2) as $chunk) {
                $buttons[] = $chunk;
            }
        }

        return $buttons !== [] ? $buttons : $this->menuButtons($user);
    }

    protected function sendAdminSummary(string $chatId, ?User $user): void
    {
        if (! $user?->hasSystemRole(UserRole::Admin)) {
            $this->sendMenu($chatId, $user);

            return;
        }

        $lines = [
            __('Yangi').': '.Ticket::query()->where('status', TicketStatus::New->value)->count(),
            __('Jarayonda').': '.Ticket::query()->where('status', TicketStatus::InProgress->value)->count(),
            __('Kechikkan').': '.Ticket::query()->where('status', TicketStatus::Overdue->value)->count(),
            __('Bajarilgan').': '.Ticket::query()->where('status', TicketStatus::Completed->value)->count(),
            __('Tasdiq kutayotgan foydalanuvchilar').': '.User::query()->whereNull('approved_at')->where('is_active', true)->count(),
        ];

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Admin xulosasi'),
            implode("\n", $lines),
            route('admin.dispatch.tickets'),
            $this->menuButtons($user),
        ));
    }

    protected function sendAdminOverdue(string $chatId, ?User $user): void
    {
        if (! $user?->hasSystemRole(UserRole::Admin)) {
            $this->sendMenu($chatId, $user);

            return;
        }

        $tickets = Ticket::query()
            ->with(['category', 'slaProfile'])
            ->where('status', TicketStatus::Overdue->value)
            ->orderBy('deadline_at')
            ->limit(5)
            ->get();

        $body = $tickets->isEmpty()
            ? __("Kechikkan murojaat yo'q.")
            : $tickets->map(fn (Ticket $ticket): string => $this->ticketLine($ticket))->implode("\n\n");

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Kechikkan murojaatlar'),
            $body,
            route('admin.dispatch.tickets', ['overdue' => 1]),
            $this->menuButtons($user),
        ));
    }

    protected function sendAdminUsers(string $chatId, ?User $user): void
    {
        if (! $user?->hasSystemRole(UserRole::Admin)) {
            $this->sendMenu($chatId, $user);

            return;
        }

        $pending = User::query()
            ->whereNull('approved_at')
            ->where('is_active', true)
            ->latest()
            ->limit(5)
            ->get(['name', 'email', 'created_at']);

        $body = $pending->isEmpty()
            ? __("Tasdiq kutayotgan foydalanuvchi yo'q.")
            : $pending->map(fn (User $pendingUser): string => "{$pendingUser->name}\n{$pendingUser->email}\n{$pendingUser->created_at?->format('d.m.Y H:i')}")->implode("\n\n");

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Foydalanuvchilar'),
            $body,
            route('admin.users.index'),
            $this->menuButtons($user),
        ));
    }

    protected function sendManagerSummary(string $chatId, ?User $user): void
    {
        if (! $user?->hasSystemRole(UserRole::Manager)) {
            $this->sendMenu($chatId, $user);

            return;
        }

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $completed = Ticket::query()
            ->whereBetween('completed_at', [$monthStart, $monthEnd])
            ->count();
        $overdueCompleted = Ticket::query()
            ->whereBetween('completed_at', [$monthStart, $monthEnd])
            ->whereNotNull('deadline_at')
            ->whereColumn('completed_at', '>', 'deadline_at')
            ->count();

        $lines = [
            __('Oy').': '.$monthStart->translatedFormat('F Y'),
            __('Yakunlangan').': '.$completed,
            __('Kechikib yakunlangan').': '.$overdueCompleted,
            __('Faol murojaatlar').': '.Ticket::query()->whereIn('status', [
                TicketStatus::New->value,
                TicketStatus::Assigned->value,
                TicketStatus::InProgress->value,
                TicketStatus::Returned->value,
                TicketStatus::Overdue->value,
            ])->count(),
        ];

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Rahbar xulosasi'),
            implode("\n", $lines),
            route('manager.dashboard'),
            $this->menuButtons($user),
        ));
    }

    protected function sendRegisterOffer(string $chatId): void
    {
        $this->bot->sendMessage($chatId, new TelegramMessage(
            __("Ro'yxatdan o'ting"),
            "Bu chatda mehmon murojaati yuborish imkoniyati bir marta ishlaydi. Keyingi murojaatlarni kabinet orqali yuborish va kuzatish uchun saytdan ro'yxatdan o'ting.",
            route('register'),
            [
                [
                    ['text' => __('Murojaat holatini tekshirish'), 'callback_data' => 'guest:track'],
                ],
            ],
        ));
    }

    protected function ticketLine(Ticket $ticket): string
    {
        return implode("\n", array_filter([
            "{$ticket->reference} - {$ticket->status->label()}",
            __('Kategoriya').': '.($ticket->category?->name ?: '-'),
            __('Muhimlik').': '.$ticket->priority->label(),
            __('Muddat').': '.$ticket->deadlineLabel(),
        ]));
    }

    protected function ticketSummary(Ticket $ticket): string
    {
        return implode("\n", [
            __('Raqam').': '.$ticket->reference,
            __('Holat').': '.$ticket->status->label(),
            __('Kategoriya').': '.($ticket->category?->name ?: '-'),
            __('Muhimlik').': '.$ticket->priority->label(),
            __('Qabul qilingan').': '.$ticket->receivedAtLabel(),
            __('Berilgan muddat').': '.$ticket->slaDurationLabel(),
            __('Tugash muddati').': '.$ticket->deadlineLabel(),
        ]);
    }

    protected function defaultReplyKeyboard(string $chatId): array
    {
        return [
            [
                $this->keyboardLabel($chatId, 'main'),
                $this->keyboardLabel($chatId, 'settings'),
            ],
        ];
    }

    protected function settingsReplyKeyboard(string $chatId, ?User $user): array
    {
        $keyboard = [
            [
                $this->keyboardLabel($chatId, 'main'),
            ],
            [
                $this->keyboardLabel($chatId, 'language'),
            ],
        ];

        if ($user) {
            $keyboard[] = [
                $this->keyboardLabel($chatId, 'logout'),
            ];
        }

        return $keyboard;
    }

    protected function languageReplyKeyboard(string $chatId): array
    {
        return [
            collect(Locales::AVAILABLE)->map(fn (array $meta) => $meta[0])->values()->all(),
            [
                $this->keyboardLabel($chatId, 'settings'),
                $this->keyboardLabel($chatId, 'main'),
            ],
        ];
    }

    protected function phoneReplyKeyboard(string $chatId): array
    {
        return [
            [
                [
                    'text' => __('Telefon raqamni ulash'),
                    'request_contact' => true,
                ],
            ],
            [
                $this->keyboardLabel($chatId, 'main'),
                $this->keyboardLabel($chatId, 'settings'),
            ],
        ];
    }

    protected function normalizeUzbekPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if (Str::startsWith($digits, '998')) {
            $digits = Str::substr($digits, 3);
        }

        if (Str::startsWith($digits, '8') && strlen($digits) === 10) {
            $digits = Str::substr($digits, 1);
        }

        if (! preg_match('/^\d{9}$/', $digits)) {
            return null;
        }

        return sprintf(
            '+998 %s %s %s %s',
            Str::substr($digits, 0, 2),
            Str::substr($digits, 2, 3),
            Str::substr($digits, 5, 2),
            Str::substr($digits, 7, 2),
        );
    }

    protected function phoneForComment(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if (Str::startsWith($digits, '998')) {
            $digits = Str::substr($digits, 3);
        }

        if (! preg_match('/^\d{9}$/', $digits)) {
            return $phone;
        }

        return sprintf(
            '+998-%s-%s-%s-%s',
            Str::substr($digits, 0, 2),
            Str::substr($digits, 2, 3),
            Str::substr($digits, 5, 2),
            Str::substr($digits, 7, 2),
        );
    }

    protected function keyboardLabel(string $chatId, string $key): string
    {
        $locale = $this->telegramLocale($chatId);

        return match ($key) {
            'main' => __('Asosiy menyu', [], $locale),
            'settings' => __('Sozlamalar', [], $locale),
            'language' => __('Tilni almashtirish', [], $locale),
            'logout' => __('Hisobdan chiqish', [], $locale),
            default => $key,
        };
    }

    protected function isMainMenuText(string $text): bool
    {
        return $this->matchesKeyboardText($text, $this->inAllLocales('Asosiy menyu'));
    }

    protected function isSettingsText(string $text): bool
    {
        return $this->matchesKeyboardText($text, $this->inAllLocales('Sozlamalar'));
    }

    protected function isLanguageText(string $text): bool
    {
        return $this->matchesKeyboardText($text, $this->inAllLocales('Tilni almashtirish'));
    }

    protected function isLogoutText(string $text): bool
    {
        return $this->matchesKeyboardText($text, $this->inAllLocales('Hisobdan chiqish'));
    }

    /** Til tugmasi bosilgan bo'lsa — tanlangan til kodi (uz/ru/en), aks holda null. */
    protected function languageChoice(string $text): ?string
    {
        $aliases = ['uz' => ['Uzbek', 'Uzbekcha'], 'ru' => ['Russian', 'Ruscha'], 'en' => ['Inglizcha', 'Английский']];

        foreach (Locales::AVAILABLE as $code => [$name]) {
            if ($this->matchesKeyboardText($text, [$name, ...($aliases[$code] ?? [])])) {
                return $code;
            }
        }

        return null;
    }

    /** Pastki tugma matni har qanday tilda kelishi mumkin (chat tili keyin o'zgargan bo'lsa ham). */
    protected function inAllLocales(string $key): array
    {
        return collect(array_keys(Locales::AVAILABLE))->map(fn (string $locale) => __($key, [], $locale))->unique()->values()->all();
    }

    protected function matchesKeyboardText(string $text, array $labels): bool
    {
        $normalized = Str::lower(trim($text));

        foreach ($labels as $label) {
            if ($normalized === Str::lower($label)) {
                return true;
            }
        }

        return false;
    }

    protected function stateKey(string $chatId): string
    {
        return 'telegram:state:'.$chatId;
    }

    protected function localeKey(string $chatId): string
    {
        return 'telegram:locale:'.$chatId;
    }

    protected function telegramLocale(string $chatId): string
    {
        $locale = Cache::get($this->localeKey($chatId));

        if (! Locales::isSupported($locale)) {
            // Chat tili tanlanmagan bo'lsa — ulangan foydalanuvchining sayt tili
            $locale = $this->userByChat($chatId)?->locale;
        }

        return Locales::isSupported($locale) ? $locale : 'uz';
    }

    protected function setTelegramLocale(string $chatId, string $locale): void
    {
        Cache::forever($this->localeKey($chatId), Locales::isSupported($locale) ? $locale : 'uz');
    }

    protected function guestCreatedKey(string $chatId): string
    {
        return 'telegram:guest-created:'.$chatId;
    }

    protected function guestTicketAlreadyCreated(string $chatId): bool
    {
        return $chatId !== '' && Cache::has($this->guestCreatedKey($chatId));
    }

    protected function currentRequestChatId(): string
    {
        $chatId = request()->input('message.chat.id')
            ?? request()->input('edited_message.chat.id')
            ?? request()->input('callback_query.message.chat.id');

        return $chatId ? (string) $chatId : '';
    }

    protected function sendLinkHelp(string $chatId): void
    {
        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Saytdagi akkauntni ulash'),
            implode("\n", [
                __('1. Saytga login va parolingiz bilan kiring.'),
                __("2. Sozlamalar sahifasidagi «Telegram ulanishi» bo'limini oching."),
                __("3. «Telegramda ulash» tugmasini bosing — shu bot ochiladi."),
                __("4. Botda «Start» ni bosing: akkaunt avtomatik ulanadi."),
                '',
                __("Telegram boshqa qurilmada bo'lsa, Sozlamalardagi havolani nusxalab, o'sha qurilmada oching."),
            ]),
            route('app.settings'),
            $this->menuButtons(null, $chatId),
        ));
    }

    protected function askUnlink(string $chatId, ?User $user): void
    {
        if (! $user) {
            $this->sendMenu($chatId, null);

            return;
        }

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Akkauntdan chiqish'),
            __('Bu Telegram chat «:name» akkauntidan uziladi va tizim xabarlari bu yerga kelmaydi. Davom etasizmi?', ['name' => $user->name]),
            null,
            [[
                ['text' => __('Ha, chiqish'), 'callback_data' => 'unlink:confirm'],
                ['text' => __('Bekor qilish'), 'callback_data' => 'unlink:cancel'],
            ]],
        ));
    }

    protected function handleUnlink(string $chatId): void
    {
        $user = $this->userByChat($chatId);

        if (! $user) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                __('Akkaunt topilmadi'),
                __("Bu Telegram chat hali hech bir akkauntga ulanmagan."),
                null,
                $this->menuButtons(null, $chatId),
            ));

            return;
        }

        $user->unlinkTelegramChat($chatId);

        $this->bot->sendMessage($chatId, new TelegramMessage(
            __('Telegram uzildi'),
            __("Bu chatga tizim xabarlari yuborilmaydi. Qayta ulash uchun saytdagi Sozlamalar bo'limidan Telegram botni oching."),
            null,
            $this->menuButtons(null, $chatId),
        ));
    }

    protected function menuButtons(?User $user, ?string $chatId = null): array
    {
        if (! $user) {
            $buttons = [];
            $chatId ??= $this->currentRequestChatId();

            if (! $this->guestTicketAlreadyCreated($chatId)) {
                $buttons[] = [
                    ['text' => __('Mehmon murojaati yuborish'), 'callback_data' => 'guest:create'],
                ];
            }

            $buttons[] = [
                ['text' => __('Murojaat holatini tekshirish'), 'callback_data' => 'guest:track'],
            ];
            $buttons[] = [
                ['text' => __('Saytdagi akkauntni ulash'), 'callback_data' => 'link'],
            ];
            $buttons[] = [
                ['text' => __("Ro'yxatdan o'tish"), 'url' => route('register')],
            ];

            return $buttons;
        }

        $buttons = [];

        if ($user->hasSystemRole(UserRole::Requester)) {
            $buttons[] = [
                ['text' => __('Murojaatlarim'), 'callback_data' => 'requester:tickets'],
                ['text' => __('Yangi murojaat'), 'callback_data' => 'requester:create'],
            ];
        }

        if ($user->hasSystemRole(UserRole::Executor)) {
            $buttons[] = [
                ['text' => __('Mening vazifalarim'), 'callback_data' => 'executor:tasks'],
            ];
            $buttons[] = [
                ['text' => __("Bo'shlar"), 'callback_data' => 'executor:available'],
                ['text' => __('Kechikkanlar'), 'callback_data' => 'executor:overdue'],
            ];
        }

        if ($user->hasSystemRole(UserRole::Operator)) {
            $buttons[] = [
                ['text' => __('Operator murojaatlari'), 'callback_data' => 'operator:tickets'],
                ['text' => __('Saytda yaratish'), 'url' => route('operator.tickets.create')],
            ];
        }

        if ($user->hasSystemRole(UserRole::Admin)) {
            $buttons[] = [
                ['text' => __('Admin xulosa'), 'callback_data' => 'admin:summary'],
                ['text' => __('Kechikkanlar'), 'callback_data' => 'admin:overdue'],
            ];
            $buttons[] = [
                ['text' => __('Foydalanuvchilar'), 'callback_data' => 'admin:users'],
                ['text' => __('Saytda ochish'), 'url' => route('admin.dispatch.tickets')],
            ];
        }

        if ($user->hasSystemRole(UserRole::Manager)) {
            $buttons[] = [
                ['text' => __('Rahbar xulosa'), 'callback_data' => 'manager:summary'],
                ['text' => __('Hisobot'), 'url' => route('manager.dashboard')],
            ];
        }

        $notificationButton = $this->notificationsEnabled($user)
            ? ['text' => __("Xabarnomani o'chirish"), 'callback_data' => 'notifications:toggle']
            : ['text' => __('Xabarnomani yoqish'), 'callback_data' => 'notifications:toggle'];

        $buttons[] = [
            ['text' => __("Profilni ko'rish"), 'callback_data' => 'profile'],
            $notificationButton,
        ];
        $buttons[] = [
            ['text' => __('Saytdagi akkauntdan chiqish'), 'callback_data' => 'unlink:ask'],
        ];

        return $buttons;
    }

    protected function userByChat(string $chatId): ?User
    {
        $user = User::query()
            ->with('department')
            ->where('telegram_chat_id', $chatId)
            ->first();

        if ($user || ! \Illuminate\Support\Facades\Schema::hasTable('user_telegram_accounts')) {
            return $user;
        }

        // Adminning qo'shimcha Telegram akkaunti
        return User::query()
            ->with('department')
            ->whereHas('telegramAccounts', fn ($query) => $query->where('chat_id', $chatId))
            ->first();
    }

    protected function notificationsEnabled(User $user): bool
    {
        return $user->telegram_notifications_enabled !== false;
    }

    protected function telegramSchemaReady(): bool
    {
        foreach ([
            'telegram_chat_id',
            'telegram_username',
            'telegram_link_token',
            'telegram_linked_at',
            'telegram_notifications_enabled',
        ] as $column) {
            if (! Schema::hasColumn('users', $column)) {
                return false;
            }
        }

        return true;
    }
}
