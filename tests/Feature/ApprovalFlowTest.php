<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_pending_requester_account(): void
    {
        $department = Department::query()->create([
            'name' => 'Test Department',
            'code' => 'TDEP',
            'is_active' => true,
        ]);

        $response = $this->post('/register', [
            'name' => 'New Requester',
            'email' => 'new@example.test',
            'phone' => '+998900000001',
            'job_title' => 'Teacher',
            'department_id' => $department->id,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('pending-approval'));

        $user = User::query()->where('email', 'new@example.test')->firstOrFail();

        $this->assertNull($user->approved_at);
        $this->assertTrue($user->hasRole(UserRole::Requester->value));
    }

    public function test_unapproved_user_is_redirected_to_pending_approval(): void
    {
        Role::findOrCreate(UserRole::Requester->value, 'web');

        $user = User::factory()->create([
            'approved_at' => null,
            'is_active' => true,
        ]);
        $user->assignRole(UserRole::Requester->value);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('pending-approval'));
    }
}
