<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->index()->after('availability_status');
            $table->string('telegram_username')->nullable()->after('telegram_chat_id');
            $table->string('telegram_link_token', 80)->nullable()->unique()->after('telegram_username');
            $table->timestamp('telegram_linked_at')->nullable()->after('telegram_link_token');
            $table->boolean('telegram_notifications_enabled')->default(true)->after('telegram_linked_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['telegram_link_token']);
            $table->dropIndex(['telegram_chat_id']);
            $table->dropColumn([
                'telegram_chat_id',
                'telegram_username',
                'telegram_link_token',
                'telegram_linked_at',
                'telegram_notifications_enabled',
            ]);
        });
    }
};
