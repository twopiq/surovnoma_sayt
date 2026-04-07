<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_requester_cannot_open_admin_dispatch(): void
    {
        $this->seed(DatabaseSeeder::class);

        $requester = User::query()->where('email', 'requester@rtt.local')->firstOrFail();

        $response = $this->actingAs($requester)->get(route('admin.dispatch.index'));

        $response->assertForbidden();
    }

    public function test_manager_dashboard_is_available_to_manager(): void
    {
        $this->seed(DatabaseSeeder::class);

        $manager = User::query()->where('email', 'manager@rtt.local')->firstOrFail();
        $manager->syncRoles([UserRole::Manager->value]);

        $response = $this->actingAs($manager)->get(route('manager.dashboard'));

        $response->assertOk();
    }
}
