<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 20);
            $table->string('reason', 40);
            $table->string('description')->nullable();
            $table->string('ip', 45)->nullable()->index();
            $table->string('device_id', 64)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 32)->nullable()->index();
            $table->unsignedInteger('attempts_count')->default(1);
            $table->timestamp('blocked_at');
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('unblocked_at')->nullable()->index();
            $table->foreignId('unblocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_blocks');
    }
};
