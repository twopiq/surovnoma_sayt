<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_deleted_only_with_exact_login_phrase(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();
        $executor = User::query()->where('email', 'executor@rtt.local')->firstOrFail();
        $ticket = Ticket::query()->where('assigned_executor_id', $executor->id)->firstOrFail();

        $this->actingAs($admin)->get(route('admin.users.profile', ['user' => $executor->id]))
            ->assertOk()
            ->assertSee('Xavfli zona')
            ->assertSee($executor->login.' delete user');

        foreach (['', 'delete user', $executor->login, $executor->login.' delete', strtoupper($executor->login).' delete user', 'boshqa.login delete user'] as $wrong) {
            $this->actingAs($admin)
                ->delete(route('admin.users.destroy', $executor), ['confirmation' => $wrong])
                ->assertSessionHasErrors('delete');

            $this->assertModelExists($executor);
        }

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $executor), ['confirmation' => $executor->login.' delete user'])
            ->assertRedirect(route('admin.users.list'));

        $this->assertModelMissing($executor);
        $this->assertModelExists($ticket);
        $this->assertNull($ticket->fresh()->assigned_executor_id);
        $this->assertDatabaseHas('audit_logs', ['event' => 'user.deleted', 'user_id' => $admin->id]);
    }

    public function test_admin_cannot_delete_self_and_others_cannot_delete(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();
        $manager = User::query()->where('email', 'manager@rtt.local')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin), ['confirmation' => $admin->login.' delete user'])
            ->assertSessionHasErrors('delete');
        $this->assertModelExists($admin);

        $this->actingAs($manager)
            ->delete(route('admin.users.destroy', $admin), ['confirmation' => $admin->login.' delete user'])
            ->assertForbidden();
        $this->assertModelExists($admin);
    }
}
