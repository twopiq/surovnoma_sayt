<?php

use App\Support\LoginSuggester;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * 20 belgidan uzun yoki noto'g'ri formatdagi loginlarni F.I. asosidagi qisqa loginga almashtiradi.
     * O'zgarishlar log faylga yoziladi (foydalanuvchiga yangi loginini aytish uchun).
     */
    public function up(): void
    {
        DB::table('users')->select(['id', 'name', 'login'])->orderBy('id')->get()
            ->filter(fn ($user) => ! is_string($user->login) || ! LoginSuggester::isValid($user->login))
            ->each(function ($user): void {
                $new = LoginSuggester::suggest((string) $user->name, 1, $user->id)[0];

                DB::table('users')->where('id', $user->id)->update(['login' => $new]);

                Log::info('Login qisqartirildi', ['user_id' => $user->id, 'old' => $user->login, 'new' => $new]);
            });
    }

    public function down(): void
    {
        // Eski uzun loginlar qaytarilmaydi (log faylda saqlangan).
    }
};
