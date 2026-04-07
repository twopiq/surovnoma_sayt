<?php

use App\Enums\TicketPriority;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('priority')->default(TicketPriority::Medium->value)->unique();
            $table->unsignedInteger('duration_minutes');
            $table->unsignedInteger('warning_minutes')->default(60);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_profiles');
    }
};
