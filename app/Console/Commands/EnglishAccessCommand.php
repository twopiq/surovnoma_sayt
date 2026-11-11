<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class EnglishAccessCommand extends Command
{
    protected $signature = 'english:access {login? : Foydalanuvchi logini yoki emaili} {--revoke : Ruxsatni olib tashlash}';

    protected $description = '"Ingliz tili" bo‘limiga kirish ruxsatini beradi, olib tashlaydi yoki ro‘yxatini ko‘rsatadi';

    public function handle(): int
    {
        $login = $this->argument('login');

        if ($login === null) {
            $holders = User::query()->where('can_access_english', true)->get(['name', 'login', 'email']);
            $holders->isEmpty()
                ? $this->info('Hozircha hech kimda ruxsat yo‘q.')
                : $this->table(['Ism', 'Login', 'Email'], $holders->map->only(['name', 'login', 'email'])->all());

            return self::SUCCESS;
        }

        $user = User::query()->where('login', $login)->orWhere('email', $login)->first();

        if (! $user) {
            $this->error("Foydalanuvchi topilmadi: {$login}");

            return self::FAILURE;
        }

        $grant = ! $this->option('revoke');
        $user->forceFill(['can_access_english' => $grant])->save();
        $this->info($grant ? "Ruxsat berildi: {$user->name} ({$user->login})" : "Ruxsat olib tashlandi: {$user->name} ({$user->login})");

        return self::SUCCESS;
    }
}
