<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login')->nullable()->after('name');
        });

        $users = DB::table('users')->select('id', 'name')->orderBy('id')->get();
        $usedLogins = [];

        foreach ($users as $user) {
            $base = Str::of($user->name)
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '.')
                ->trim('.')
                ->value();

            $base = $base !== '' ? $base : 'user';
            $candidate = $base;
            $suffix = 1;

            while (in_array($candidate, $usedLogins, true) || DB::table('users')->where('login', $candidate)->where('id', '!=', $user->id)->exists()) {
                $candidate = "{$base}{$suffix}";
                $suffix++;
            }

            DB::table('users')->where('id', $user->id)->update([
                'login' => $candidate,
            ]);

            $usedLogins[] = $candidate;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('login');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['login']);
            $table->dropColumn('login');
        });
    }
};
