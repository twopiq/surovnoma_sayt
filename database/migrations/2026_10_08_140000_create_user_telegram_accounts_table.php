<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin uchun qo'shimcha Telegram akkauntlari (asosiysi users.telegram_chat_id da qoladi).
     */
    public function up(): void
    {
        Schema::create('user_telegram_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('chat_id', 64);
            $table->string('username')->nullable();
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'chat_id']);
            $table->index('chat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_telegram_accounts');
    }
};
