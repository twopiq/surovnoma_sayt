<?php

namespace Tests\Feature;

use App\Models\GuestBlock;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Rus/ingliz tilida har bir rol uchun barcha sahifalar ochiladi va ko'rinadigan matnda o'zbekcha so'z qolmaganligi
 * tekshiriladi. Foydalanuvchi kiritgan ma'lumotlar (ism, kategoriya, tavsif...) bundan mustasno.
 */
class NoUzbekLeakTest extends TestCase
{
    use RefreshDatabase;

    /** O'zbek tiliga xos so'zlar (kichik harf). Inglizcha/ruscha matnda uchramaydi. */
    public const WORDS = [
        'murojaat', 'murojaatlar', 'murojaatni', 'murojaatlarim', 'ijrochi', 'ijrochilar', 'foydalanuvchi', 'foydalanuvchilar',
        'holat', 'holati', 'holatlar', 'sozlamalar', 'saqlash', 'yuborish', 'tanlang', 'barchasi', 'hammasi', 'hozircha',
        'yangi', 'uchun', 'bilan', 'yoki', 'kerak', 'mumkin', 'emas', 'qilish', 'qiling', 'kiriting', 'ochish', 'yopish',
        'sahifa', 'sahifasi', 'sana', 'vaqt', 'vaqti', 'kun', 'kuni', 'kunlar', 'soat', 'daqiqa', 'muddat', 'muddati',
        'izoh', 'izohlar', 'sabab', 'tasdiqlash', 'tasdiqlangan', 'faol', 'nofaol', 'jami', 'qidirish', 'tozalash',
        'yuklab', 'olish', 'fayl', 'fayllar', 'kategoriya', 'muhimlik', 'arxiv', 'tarix', 'tizim', 'bildirishnoma',
        'bildirishnomalar', 'ogohlantirish', 'zahira', 'tiklash', 'parol', 'ism', 'familiya', 'lavozim', 'telefon',
        'kirish', 'chiqish', 'qaytarish', 'rad', 'etildi', 'etish', 'bajarildi', 'bajarilgan', 'yopildi', 'kechikkan',
        'jarayonda', 'taqsimlandi', 'shoshilinch', 'yuqori', 'belgilanmagan', 'topilmadi', 'mavjud', 'tanlanmagan',
        'ta', 'tasi', 'birlik', 'yuklama', 'bosh', 'rahbar', 'mehmon', 'qurilma', 'bloklash', 'xato', 'xatolar',
        'yaxshi', 'loglar', 'hisobot', 'odamlar', 'ish', 'dam', 'bayram', 'haftalik', 'kunlik', 'oylik', 'yillik',
        'eksport', 'ko\'rish', 'bo\'lim', 'bo\'limlar', 'yo\'q', 'o\'chirish', 'o\'zgartirish', 'ro\'yxat', 'ro\'yxatdan',
        'qo\'shish', 'to\'liq', 'so\'nggi', 'ko\'proq', 'o\'rta', 'ma\'lumot', 'ma\'lumotlar', 'boshqa', 'hali', 'endi',
        'agar', 'iltimos', 'bor', 'yoqilgan', 'o\'chirilgan', 'dushanba', 'seshanba', 'chorshanba', 'payshanba', 'juma',
        'shanba', 'yakshanba', 'kecha', 'bugun', 'ertaga', 'hafta', 'oy', 'yil', 'qoldi', 'kechikdi', 'ga', 'da', 'dan',
    ];

    private const ROLE_PAGES = [
        'admin' => [
            '/app/home', '/app/dashboard', '/app/settings', '/admin/dispatch', '/admin/dispatch/tickets', '/admin/dispatch/tickets?ticket={ticket}',
            '/admin/dispatch/archive', '/admin/dispatch/archive?ticket={closed}', '/admin/dispatch/deadlines', '/admin/dispatch/work-schedule',
            '/admin/dispatch/status/new', '/admin/dispatch/{ticket}', '/admin/users', '/admin/users?tab=rejected', '/admin/users/list',
            '/admin/users/list?user={user}', '/admin/users/profile?user={user}', '/admin/users/create', '/admin/guest-blocks', '/admin/guest-blocks?status=all',
            '/admin/guest-blocks/settings', '/admin/settings/sla', '/admin/backups', '/admin/system/health', '/admin/system/logs',
            '/admin/system/logs?tab=server', '/admin/categories', '/admin/departments', '/notifications', '/profile', '/app/home?ticket={ticket}',
        ],
        'manager' => ['/manager/dashboard', '/app/dashboard', '/notifications', '/profile'],
        'executor' => ['/app/home', '/executor/tickets', '/executor/tickets/archive', '/executor/tickets/{ticket}', '/notifications', '/profile'],
        'operator' => ['/operator/tickets', '/operator/tickets/create', '/operator/tickets/{opticket}', '/notifications', '/profile'],
        'requester' => ['/tickets', '/tickets/create', '/tickets/{reqticket}', '/notifications', '/profile'],
        'guest' => ['/', '/login', '/register', '/forgot-password', '/guest/create', '/guest/track', '/_errors/404', '/_errors/403', '/_errors/419', '/_errors/429', '/_errors/500', '/_errors/503'],
    ];

    public function test_no_uzbek_text_in_other_languages(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->addSampleData();

        $userData = $this->userEnteredStrings();
        $problems = [];

        foreach (['en', 'ru'] as $locale) {
            foreach (self::ROLE_PAGES as $role => $pages) {
                foreach ($pages as $page) {
                    $url = $this->fillUrl($page);
                    $this->flushSession();
                    $this->withSession(['locale' => $locale]);

                    if ($role !== 'guest') {
                        $user = User::query()->where('email', "{$role}@rtt.local")->firstOrFail();
                        $user->forceFill(['locale' => $locale])->save();
                        $this->actingAs($user);
                    } else {
                        auth()->logout();
                    }

                    $response = $this->get($url);

                    if (! in_array($response->getStatusCode(), [200, 403, 404, 419, 429, 500, 503], true)) {
                        $problems[] = "[{$locale}] {$role} {$url}: HTTP {$response->getStatusCode()}";

                        continue;
                    }

                    foreach ($this->uzbekFragments((string) $response->getContent(), $userData) as $fragment) {
                        $problems[$fragment] = "[{$locale}] {$role} {$url}: «{$fragment}»";
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($problems)), "O'zbekcha qolgan matnlar:\n".implode("\n", array_unique($problems)));
    }

    private function addSampleData(): void
    {
        GuestBlock::query()->create([
            'scope' => GuestBlock::SCOPE_IP, 'reason' => 'ip_daily', 'ip' => '10.0.0.9', 'attempts_count' => 7,
            'description' => 'Limit: 5 ta murojaat', 'user_agent' => 'curl/8.0', 'blocked_at' => now(), 'last_attempt_at' => now(),
        ]);
    }

    private function fillUrl(string $page): string
    {
        $executor = User::query()->where('email', 'executor@rtt.local')->first();
        $requester = User::query()->where('email', 'requester@rtt.local')->first();
        $operator = User::query()->where('email', 'operator@rtt.local')->first();

        return strtr($page, [
            '{ticket}' => (string) (Ticket::query()->where('assigned_executor_id', $executor?->id)->value('id') ?? Ticket::query()->value('id')),
            '{closed}' => (string) Ticket::query()->value('id'),
            '{reqticket}' => (string) (Ticket::query()->where('requester_id', $requester?->id)->value('id') ?? 0),
            '{opticket}' => (string) (Ticket::query()->where('operator_id', $operator?->id)->value('id') ?? 0),
            '{user}' => (string) $executor?->id,
        ]);
    }

    /** Foydalanuvchi kiritgan ma'lumotlar — tarjima qilinmaydi. */
    private function userEnteredStrings(): array
    {
        $columns = [
            'users' => ['name', 'job_title', 'email', 'login'], 'departments' => ['name', 'code'], 'categories' => ['name', 'description'],
            'tickets' => ['title', 'description', 'requester_name', 'requester_department', 'requester_job_title', 'rejection_reason'],
            'ticket_comments' => ['body'], 'ticket_attachments' => ['original_name'], 'sla_profiles' => ['name'],
            'holidays' => ['name'], 'ticket_status_histories' => ['note'],
        ];
        $values = [];

        foreach ($columns as $table => $cols) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($cols as $col) {
                if (Schema::hasColumn($table, $col)) {
                    $values = array_merge($values, DB::table($table)->whereNotNull($col)->pluck($col)->map(fn ($v) => (string) $v)->all());
                }
            }
        }

        $values = array_filter(array_unique($values), fn ($v) => mb_strlen($v) > 1);
        usort($values, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return $values;
    }

    private function uzbekFragments(string $html, array $userData): array
    {
        $html = preg_replace('#<(script|style|svg|template)\b.*?</\1>#si', ' ', $html);
        $html = preg_replace('#<(option)\b[^>]*value="[^"]*"[^>]*>#i', ' ', $html);
        $text = html_entity_decode(strip_tags(str_replace('>', '> ', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        foreach (['placeholder', 'title', 'aria-label', 'alt'] as $attr) {
            preg_match_all('/\s'.$attr.'="([^"]*)"/', $html, $m);
            $text .= "\n".html_entity_decode(implode("\n", $m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        foreach ($userData as $value) {
            $text = str_replace($value, ' ', $text);
        }

        $found = [];

        foreach (preg_split('/\n|\s{2,}/u', $text) as $line) {
            $line = trim($line);
            $words = preg_split("/[^\\p{L}'ʼ‘’]+/u", mb_strtolower($line), -1, PREG_SPLIT_NO_EMPTY);

            foreach ($words as $word) {
                $word = str_replace(['ʼ', '‘', '’'], "'", trim($word, "'"));

                if (in_array($word, self::WORDS, true) || preg_match("/^[a-z]+(o'|g')[a-z]*$/", $word) && ! in_array($word, ["don't", "can't", "won't"], true)) {
                    $found[] = mb_substr($line, 0, 120);
                    break;
                }
            }
        }

        return array_unique($found);
    }
}
