<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_access_english')->default(false)->after('can_access_app_dashboard');
        });

        Schema::create('english_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('description', 500)->nullable();
            $table->json('cards');
            $table->float('best_time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_sets');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('can_access_english');
        });
    }
};
