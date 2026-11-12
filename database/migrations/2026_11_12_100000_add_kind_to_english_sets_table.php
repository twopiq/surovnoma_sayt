<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('english_sets', function (Blueprint $table) {
            // words — oddiy so'zlar; verbs — fe'llar (har kartada uch shakl: term, v2, v3)
            $table->string('kind', 10)->default('words')->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('english_sets', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
