<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_dynamic_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dynamic_field_id')->constrained()->cascadeOnDelete();
            $table->longText('value')->nullable();
            $table->timestamps();

            $table->unique(['ticket_id', 'dynamic_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_dynamic_field_values');
    }
};
