<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Notifications\TicketStatusNotification;
use App\Support\DatabaseBackupManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Sinov davridagi barcha murojaatlarni o'chiradi: izohlar, fayllar, holat tarixi, biriktirishlar,
 * qaytarish so'rovlari, dinamik maydon qiymatlari va murojaat bildirishnomalari bilan birga.
 * Foydalanuvchilar, bo'limlar, kategoriyalar, SLA va boshqa sozlamalar o'zgarmaydi.
 * Audit jurnali saqlanadi (SECURITY_ROUTE: audit yozuvlari faqat tizim tomonidan o'chiriladi).
 * Murojaat ID raqamlari qayta 1 dan boshlanmaydi: eski Telegram xabarlaridagi tugmalar yangi murojaatlarga tushib qolmasin.
 */
class PurgeTicketsCommand extends Command
{
    protected $signature = 'tickets:purge-all
        {--force : Tasdiq so\'ramasdan o\'chirish}
        {--no-backup : Oldindan zahira nusxa olinmasin (disk to\'la bo\'lsa)}';

    protected $description = 'Barcha murojaatlarni ularga bog\'liq yozuvlar va fayllar bilan o\'chiradi (foydalanuvchilar va sozlamalar qoladi)';

    /** Murojaatga bog'liq jadvallar: avval bolalari, oxirida murojaatlar o'zi. */
    private const TABLES = [
        'ticket_dynamic_field_values' => "Dinamik maydon qiymatlari",
        'ticket_return_requests' => "Qaytarish so'rovlari",
        'ticket_assignments' => 'Biriktirishlar',
        'ticket_status_histories' => 'Holat tarixi',
        'ticket_comments' => 'Izohlar',
        'ticket_attachments' => 'Fayllar (bazadagi yozuv)',
        'tickets' => 'Murojaatlar',
    ];

    public function handle(DatabaseBackupManager $backups): int
    {
        $counts = collect(self::TABLES)->map(fn ($label, $table) => DB::table($table)->count());
        $notifications = DB::table('notifications')->where('type', TicketStatusNotification::class)->count();
        $files = TicketAttachment::query()->get(['disk', 'path']);

        $this->table(["Nima o'chadi", 'Soni'], [
            ...collect(self::TABLES)->map(fn ($label, $table) => [$label, $counts[$table]])->values()->all(),
            ['Murojaat bildirishnomalari', $notifications],
            ['Diskdagi fayllar', $files->count()],
        ]);

        if ($counts['tickets'] === 0 && $notifications === 0 && $files->isEmpty()) {
            $this->info("O'chiriladigan murojaat yo'q.");

            return self::SUCCESS;
        }

        $this->warn("Foydalanuvchilar, bo'limlar, kategoriyalar va sozlamalar o'zgarmaydi. Bu amalni qaytarib bo'lmaydi.");

        if (! $this->option('force') && ! $this->confirm("Yuqoridagilarning hammasi o'chirilsinmi?", false)) {
            $this->line('Bekor qilindi. Hech narsa o\'chirilmadi.');

            return self::FAILURE;
        }

        if (! $this->option('no-backup')) {
            try {
                $backup = $backups->backup(label: 'before-tickets-purge');
                $this->info("Zahira nusxa olindi: {$backup['path']}");
            } catch (Throwable $e) {
                $this->error("Zahira nusxa olinmadi, hech narsa o'chirilmadi: {$e->getMessage()}");
                $this->line("Disk to'la bo'lsa, avval joy bo'shating yoki --no-backup bilan ishga tushiring.");

                return self::FAILURE;
            }
        }

        DB::transaction(function () {
            DB::table('notifications')->where('type', TicketStatusNotification::class)->delete();

            foreach (array_keys(self::TABLES) as $table) {
                DB::table($table)->delete();
            }
        });

        // Fayllar baza tozalangandan keyin o'chiriladi: baza xato bersa, fayllar joyida qoladi.
        $missing = 0;
        foreach ($files as $file) {
            try {
                Storage::disk($file->disk ?: 'local')->delete($file->path) ?: $missing++;
            } catch (Throwable) {
                $missing++;
            }
        }

        $this->info('Murojaatlar o\'chirildi: '.$counts['tickets'].' ta. Fayllar: '.($files->count() - $missing).' ta o\'chirildi'.($missing ? ", {$missing} tasi topilmadi" : '').'.');

        // SQLite fayli qatorlar o'chirilganda o'zi kichraymaydi.
        if (DB::connection()->getDriverName() === 'sqlite') {
            try {
                DB::statement('VACUUM');
                $this->info('Baza fayli siqildi (VACUUM).');
            } catch (Throwable $e) {
                $this->warn("Baza faylini siqib bo'lmadi: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
