<?php

namespace App\TelegramBot;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
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

        if ($this->isUzbekLanguageChoice($text) || $this->isRussianLanguageChoice($text)) {
            Cache::forget($this->stateKey($chatId));
            $this->setTelegramLocale($chatId, $this->isRussianLanguageChoice($text) ? 'ru' : 'uz');
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
                'Bekor qilindi',
                'Joriy amal bekor qilindi.',
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

        match ($data) {
            'profile' => $this->sendProfile($chatId),
            'notifications:toggle' => $this->toggleNotifications($chatId),
            'notifications:on' => $this->setNotifications($chatId, true),
            'notifications:off' => $this->setNotifications($chatId, false),
            'link' => $this->sendLinkHelp($chatId),
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
                'Assalomu alaykum!',
                "Ulash kodi noto'g'ri yoki yangilangan. Saytdagi Sozlamalar sahifasidan Telegram botni qayta oching.",
                null,
                $this->menuButtons(null, $chatId),
            ));

            return;
        }

        $from = $message['from'] ?? [];
        $chat = $message['chat'] ?? [];

        $user->forceFill([
            'telegram_chat_id' => $chatId,
            'telegram_username' => $from['username'] ?? $chat['username'] ?? null,
            'telegram_link_token' => Str::random(48),
            'telegram_notifications_enabled' => true,
            'telegram_linked_at' => now(),
        ])->save();

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Assalomu alaykum!',
            "Muvaffaqiyatli ulandingiz. Telegram akkauntingiz sayt profilingizga bog'landi. Endi yangi tizim xabarlari shu chatga avtomatik yuboriladi.",
            null,
            $this->menuButtons($user->fresh()),
        ));
    }

    protected function sendGreeting(string $chatId, ?User $user): void
    {
        if (! $user) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                'Assalomu alaykum!',
                "RTT Markazi botiga xush kelibsiz. Akkaunt ulanmagan chatda faqat bir martalik guest murojaat yuborish yoki tracking kod orqali holatni tekshirish mumkin. Doimiy foydalanish uchun saytdan ro'yxatdan o'ting.",
                null,
                $this->menuButtons(null, $chatId),
            ));

            return;
        }

        $status = $this->notificationsEnabled($user) ? 'yoqilgan' : "o'chirilgan";

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Assalomu alaykum!',
            "RTT Markazi botiga xush kelibsiz. Akkauntingiz ulangan. Telegram xabarnomalari: {$status}.",
            null,
            $this->menuButtons($user),
        ));
    }

    protected function sendMenu(string $chatId, ?User $user): void
    {
        if (! $user) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                'RTT Markazi',
                "Akkaunt ulanmagan. Guest murojaat yuborish bir marta ishlaydi; keyin saytdan ro'yxatdan o'ting.",
                null,
                $this->menuButtons(null, $chatId),
            ));

            return;
        }

        $status = $this->notificationsEnabled($user) ? 'yoqilgan' : "o'chirilgan";

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'RTT Markazi',
            "Akkaunt ulangan. Telegram xabarnomalari: {$status}.",
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
            'F.I.O.: '.$user->name,
            'Login: '.($user->login ?: '-'),
            'Email: '.$user->email,
            'Telefon: '.($user->phone ?: '-'),
            'Lavozim: '.($user->job_title ?: '-'),
            "Bo'lim: ".($user->department?->name ?: '-'),
            'Rol: '.$user->display_role,
            'Bandlik: '.($user->availability_status?->label() ?? '-'),
            'Telegram xabarnomalari: '.($this->notificationsEnabled($user) ? 'Yoqilgan' : "O'chirilgan"),
        ];

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Profil ma\'lumotlari',
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
            $enabled ? 'Xabarnomalar yoqildi' : "Xabarnomalar o'chirildi",
            $enabled
                ? 'Yangi tizim xabarlari shu chatga yuboriladi.'
                : "Yangi tizim xabarlari Telegramga yuborilmaydi. Saytdagi Bildirishnomalar bo'limida ko'rinaveradi.",
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
            $this->telegramLocale($chatId) === 'ru' ? 'Меню' : 'Menyu',
            $this->telegramLocale($chatId) === 'ru'
                ? 'Нижнее меню обновлено.'
                : 'Pastki menyu yangilandi.',
            null,
            [],
            $this->defaultReplyKeyboard($chatId),
        ));
    }

    protected function sendSettingsMenu(string $chatId, ?User $user): void
    {
        $isRussian = $this->telegramLocale($chatId) === 'ru';
        $linked = $user ? ($isRussian ? 'подключен' : 'ulangan') : ($isRussian ? 'не подключен' : 'ulanmagan');
        $language = $isRussian ? 'Русский' : "O'zbekcha";

        $this->bot->sendMessage($chatId, new TelegramMessage(
            $isRussian ? 'Настройки' : 'Sozlamalar',
            $isRussian
                ? "Аккаунт: {$linked}\nЯзык: {$language}"
                : "Akkaunt: {$linked}\nTil: {$language}",
            null,
            [],
            $this->settingsReplyKeyboard($chatId, $user),
        ));
    }

    protected function sendLanguageMenu(string $chatId): void
    {
        $isRussian = $this->telegramLocale($chatId) === 'ru';

        $this->bot->sendMessage($chatId, new TelegramMessage(
            $isRussian ? 'Язык' : 'Til',
            $isRussian ? 'Выберите язык интерфейса бота.' : 'Bot interfeysi tilini tanlang.',
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
                'Kategoriya topilmadi',
                'Hozircha faol muammo kategoriyalari mavjud emas.',
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
            'Kategoriya tanlang',
            $mode === 'guest'
                ? "Guest murojaat uchun muammo kategoriyasini tanlang."
                : "Yangi murojaat uchun muammo kategoriyasini tanlang.",
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
                'Kategoriya topilmadi',
                'Tanlangan kategoriya faol emas yoki topilmadi.',
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
                'Telefon raqam',
                "Kategoriya: {$category->name}\nMurojaatni qabul qilishdan oldin telefon raqamingizni yuboring.\nFormat: +998 99 999 99 99\nBekor qilish uchun /cancel yuboring.",
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
            'Tavsif yozing',
            "Kategoriya: {$category->name}\nMuammoni kamida 30 belgida yozing. Bekor qilish uchun /cancel yuboring.",
        ));
    }

    protected function collectGuestPhone(string $chatId, string $text, array $state): void
    {
        $phone = $this->normalizeUzbekPhone($text);

        if (! $phone) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                "Telefon raqam noto'g'ri",
                "Telefon raqamini +998 99 999 99 99 formatida yuboring yoki pastdagi tugma orqali raqamni ulashing.\nBekor qilish uchun /cancel yuboring.",
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
            'Tavsif yozing',
            "Telefon: {$phone}\nEndi muammoni kamida 30 belgida yozing. Bekor qilish uchun /cancel yuboring.",
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
                'Tavsif qisqa',
                "Iltimos, muammoni kamida 30 belgida yozing. Bekor qilish uchun /cancel yuboring.",
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
                'Telefon raqamim: '.$this->phoneForComment($state['requester_phone']),
                true,
            );
        }

        Cache::forget($this->stateKey($chatId));
        Cache::forever($this->guestCreatedKey($chatId), $ticket->id);

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Guest murojaat yuborildi',
            "Murojaat raqami: {$ticket->reference}\nTracking kod: {$trackingCode}\nHolatni tekshirish uchun shu ikki qiymatni saqlab qo'ying.\n\nDoimiy kabinet va to'liq imkoniyatlar uchun saytdan ro'yxatdan o'ting.",
            route('register'),
            [
                [
                    ['text' => "Holatni tekshirish", 'callback_data' => 'guest:track'],
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
                'Tavsif qisqa',
                "Iltimos, muammoni kamida 30 belgida yozing. Bekor qilish uchun /cancel yuboring.",
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
            'Murojaat yuborildi',
            "Murojaat raqami: {$ticket->reference}\nHolat: {$ticket->status->label()}",
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
            'Guest holatini tekshirish',
            "Murojaat raqami va tracking kodni bitta xabarda yuboring.\nMasalan: RTT-20260525-0001 ABCD1234\nBekor qilish uchun /cancel.",
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
                'Topilmadi',
                "Murojaat raqami yoki tracking kod noto'g'ri. Qayta urinib ko'ring yoki /cancel yuboring.",
            ));

            return;
        }

        Cache::forget($this->stateKey($chatId));

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Murojaat holati',
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
            ? "Sizda hali murojaatlar yo'q."
            : $tickets->map(fn (Ticket $ticket): string => $this->ticketLine($ticket))->implode("\n\n");

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Murojaatlarim',
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

        $body = $tickets->isEmpty()
            ? "Sizga biriktirilgan faol murojaat yo'q."
            : $tickets->map(fn (Ticket $ticket): string => $this->ticketLine($ticket))->implode("\n\n");

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Mening vazifalarim',
            $body,
            route('executor.tickets.index'),
            $this->menuButtons($user),
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
                $overdueOnly ? 'Kechikkan murojaatlar' : "Bo'sh murojaatlar",
                $overdueOnly ? "Qabul qilish mumkin bo'lgan kechikkan murojaat yo'q." : "Qabul qilish mumkin bo'lgan bo'sh murojaat yo'q.",
                route('executor.tickets.index'),
                $this->menuButtons($user),
            ));

            return;
        }

        $buttons = $tickets->map(fn (Ticket $ticket): array => [
            ['text' => "Olish: {$ticket->reference}", 'callback_data' => 'executor:claim:'.$ticket->id],
        ])->values()->all();

        $this->bot->sendMessage($chatId, new TelegramMessage(
            $overdueOnly ? 'Kechikkan murojaatlar' : "Bo'sh murojaatlar",
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
                'Murojaat olinmadi',
                'Bu murojaat topilmadi yoki siz uchun ochiq emas.',
                null,
                $this->menuButtons($user),
            ));

            return;
        }

        try {
            $updated = $this->ticketService->claimForExecutor($ticket, $user, 'Telegram orqali qabul qilindi.');
        } catch (\Throwable $exception) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                'Murojaat olinmadi',
                $exception->getMessage(),
                null,
                $this->menuButtons($user),
            ));

            return;
        }

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Murojaat qabul qilindi',
            $this->ticketSummary($updated),
            route('executor.tickets.show', $updated),
            $this->menuButtons($user),
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
            ? "Siz operator sifatida yaratgan murojaatlar hali yo'q."
            : $tickets->map(fn (Ticket $ticket): string => $this->ticketLine($ticket))->implode("\n\n");

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Operator murojaatlari',
            $body,
            route('operator.tickets.index'),
            [
                [
                    ['text' => 'Saytda yaratish', 'url' => route('operator.tickets.create')],
                ],
                ...$this->menuButtons($user),
            ],
        ));
    }

    protected function sendAdminSummary(string $chatId, ?User $user): void
    {
        if (! $user?->hasSystemRole(UserRole::Admin)) {
            $this->sendMenu($chatId, $user);

            return;
        }

        $lines = [
            'Yangi: '.Ticket::query()->where('status', TicketStatus::New->value)->count(),
            'Jarayonda: '.Ticket::query()->where('status', TicketStatus::InProgress->value)->count(),
            'Kechikkan: '.Ticket::query()->where('status', TicketStatus::Overdue->value)->count(),
            'Bajarilgan: '.Ticket::query()->where('status', TicketStatus::Completed->value)->count(),
            'Tasdiq kutayotgan foydalanuvchilar: '.User::query()->whereNull('approved_at')->where('is_active', true)->count(),
        ];

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Admin xulosasi',
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
            ? "Kechikkan murojaat yo'q."
            : $tickets->map(fn (Ticket $ticket): string => $this->ticketLine($ticket))->implode("\n\n");

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Kechikkan murojaatlar',
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
            ? "Tasdiq kutayotgan foydalanuvchi yo'q."
            : $pending->map(fn (User $pendingUser): string => "{$pendingUser->name}\n{$pendingUser->email}\n{$pendingUser->created_at?->format('d.m.Y H:i')}")->implode("\n\n");

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Foydalanuvchilar',
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
            'Oy: '.$monthStart->translatedFormat('F Y'),
            'Yakunlangan: '.$completed,
            'Kechikib yakunlangan: '.$overdueCompleted,
            'Faol murojaatlar: '.Ticket::query()->whereIn('status', [
                TicketStatus::New->value,
                TicketStatus::Assigned->value,
                TicketStatus::InProgress->value,
                TicketStatus::Returned->value,
                TicketStatus::Overdue->value,
            ])->count(),
        ];

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Rahbar xulosasi',
            implode("\n", $lines),
            route('manager.dashboard'),
            $this->menuButtons($user),
        ));
    }

    protected function sendRegisterOffer(string $chatId): void
    {
        $this->bot->sendMessage($chatId, new TelegramMessage(
            "Ro'yxatdan o'ting",
            "Bu chatda guest murojaat yuborish imkoniyati bir marta ishlaydi. Keyingi murojaatlarni kabinet orqali yuborish va kuzatish uchun saytdan ro'yxatdan o'ting.",
            route('register'),
            [
                [
                    ['text' => 'Guest holatini tekshirish', 'callback_data' => 'guest:track'],
                ],
            ],
        ));
    }

    protected function ticketLine(Ticket $ticket): string
    {
        return implode("\n", array_filter([
            "{$ticket->reference} - {$ticket->status->label()}",
            'Kategoriya: '.($ticket->category?->name ?: '-'),
            'Muhimlik: '.$ticket->priority->label(),
            'Muddat: '.$ticket->deadlineLabel(),
        ]));
    }

    protected function ticketSummary(Ticket $ticket): string
    {
        return implode("\n", [
            'Raqam: '.$ticket->reference,
            'Holat: '.$ticket->status->label(),
            'Kategoriya: '.($ticket->category?->name ?: '-'),
            'Muhimlik: '.$ticket->priority->label(),
            'Qabul qilingan: '.$ticket->receivedAtLabel(),
            'Berilgan muddat: '.$ticket->slaDurationLabel(),
            'Tugash muddati: '.$ticket->deadlineLabel(),
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
            [
                "O'zbekcha",
                'Русский',
            ],
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
                    'text' => 'Telefon raqamni ulash',
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
        $isRussian = $this->telegramLocale($chatId) === 'ru';

        return match ($key) {
            'main' => $isRussian ? 'Главное меню' : 'Asosiy menyu',
            'settings' => $isRussian ? 'Настройки' : 'Sozlamalar',
            'language' => $isRussian ? 'Сменить язык' : 'Tilni almashtirish',
            'logout' => $isRussian ? 'Выйти из аккаунта' : 'Hisobdan chiqish',
            default => $key,
        };
    }

    protected function isMainMenuText(string $text): bool
    {
        return $this->matchesKeyboardText($text, ['Asosiy menyu', 'Главное меню']);
    }

    protected function isSettingsText(string $text): bool
    {
        return $this->matchesKeyboardText($text, ['Sozlamalar', 'Настройки']);
    }

    protected function isLanguageText(string $text): bool
    {
        return $this->matchesKeyboardText($text, ['Tilni almashtirish', 'Сменить язык']);
    }

    protected function isLogoutText(string $text): bool
    {
        return $this->matchesKeyboardText($text, ['Hisobdan chiqish', 'Выйти из аккаунта']);
    }

    protected function isUzbekLanguageChoice(string $text): bool
    {
        return $this->matchesKeyboardText($text, ["O'zbekcha", 'Uzbek', 'Uzbekcha']);
    }

    protected function isRussianLanguageChoice(string $text): bool
    {
        return $this->matchesKeyboardText($text, ['Русский', 'Russian', 'Ruscha']);
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
        $locale = Cache::get($this->localeKey($chatId), 'uz');

        return in_array($locale, ['uz', 'ru'], true) ? $locale : 'uz';
    }

    protected function setTelegramLocale(string $chatId, string $locale): void
    {
        Cache::forever($this->localeKey($chatId), in_array($locale, ['uz', 'ru'], true) ? $locale : 'uz');
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
            'Saytga ulash',
            "Avval saytga kiring, Sozlamalar bo'limini oching va Telegram botni ochish tugmasini bosing. Bot akkauntingizni avtomatik ulaydi.",
            null,
            $this->menuButtons(null, $chatId),
        ));
    }

    protected function handleUnlink(string $chatId): void
    {
        $user = $this->userByChat($chatId);

        if (! $user) {
            $this->bot->sendMessage($chatId, new TelegramMessage(
                'Akkaunt topilmadi',
                "Bu Telegram chat hali hech bir akkauntga ulanmagan.",
                null,
                $this->menuButtons(null, $chatId),
            ));

            return;
        }

        $user->forceFill([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_link_token' => Str::random(48),
            'telegram_notifications_enabled' => true,
            'telegram_linked_at' => null,
        ])->save();

        $this->bot->sendMessage($chatId, new TelegramMessage(
            'Telegram uzildi',
            "Bu chatga tizim xabarlari yuborilmaydi. Qayta ulash uchun saytdagi Sozlamalar bo'limidan Telegram botni oching.",
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
                    ['text' => 'Guest murojaat yuborish', 'callback_data' => 'guest:create'],
                ];
            }

            $buttons[] = [
                ['text' => 'Guest holatini tekshirish', 'callback_data' => 'guest:track'],
            ];
            $buttons[] = [
                ['text' => "Ro'yxatdan o'tish", 'url' => route('register')],
            ];

            return $buttons;
        }

        $buttons = [];

        if ($user->hasSystemRole(UserRole::Requester)) {
            $buttons[] = [
                ['text' => 'Murojaatlarim', 'callback_data' => 'requester:tickets'],
                ['text' => 'Yangi murojaat', 'callback_data' => 'requester:create'],
            ];
        }

        if ($user->hasSystemRole(UserRole::Executor)) {
            $buttons[] = [
                ['text' => 'Mening vazifalarim', 'callback_data' => 'executor:tasks'],
            ];
            $buttons[] = [
                ['text' => "Bo'shlar", 'callback_data' => 'executor:available'],
                ['text' => 'Kechikkanlar', 'callback_data' => 'executor:overdue'],
            ];
        }

        if ($user->hasSystemRole(UserRole::Operator)) {
            $buttons[] = [
                ['text' => 'Operator murojaatlari', 'callback_data' => 'operator:tickets'],
                ['text' => 'Saytda yaratish', 'url' => route('operator.tickets.create')],
            ];
        }

        if ($user->hasSystemRole(UserRole::Admin)) {
            $buttons[] = [
                ['text' => 'Admin xulosa', 'callback_data' => 'admin:summary'],
                ['text' => 'Kechikkanlar', 'callback_data' => 'admin:overdue'],
            ];
            $buttons[] = [
                ['text' => 'Foydalanuvchilar', 'callback_data' => 'admin:users'],
                ['text' => 'Saytda ochish', 'url' => route('admin.dispatch.tickets')],
            ];
        }

        if ($user->hasSystemRole(UserRole::Manager)) {
            $buttons[] = [
                ['text' => 'Rahbar xulosa', 'callback_data' => 'manager:summary'],
                ['text' => 'Dashboard', 'url' => route('manager.dashboard')],
            ];
        }

        $notificationButton = $this->notificationsEnabled($user)
            ? ['text' => "Xabarnomani o'chirish", 'callback_data' => 'notifications:toggle']
            : ['text' => 'Xabarnomani yoqish', 'callback_data' => 'notifications:toggle'];

        $buttons[] = [
            ['text' => "Profilni ko'rish", 'callback_data' => 'profile'],
            $notificationButton,
        ];

        return $buttons;
    }

    protected function userByChat(string $chatId): ?User
    {
        return User::query()
            ->with('department')
            ->where('telegram_chat_id', $chatId)
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
