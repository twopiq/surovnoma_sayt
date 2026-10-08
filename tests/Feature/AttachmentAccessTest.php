<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_attachments_are_stored_on_private_disk_and_only_authorized_users_can_download(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);

        $requester = User::query()->where('email', 'requester@rtt.local')->firstOrFail();

        $this->actingAs($requester)->post(route('tickets.store'), [
            'category_id' => Category::query()->firstOrFail()->id,
            'description' => "Bu murojaat fayllarning yopiq diskda saqlanishini tekshirish uchun yetarlicha uzun tavsifdir.",
            'attachments' => [UploadedFile::fake()->create('pasport.pdf', 10, 'application/pdf')],
        ])->assertSessionHasNoErrors();

        $attachment = TicketAttachment::query()->latest('id')->firstOrFail();

        $this->assertSame('local', $attachment->disk);
        Storage::disk('local')->assertExists($attachment->path);
        Storage::disk('public')->assertMissing($attachment->path);

        $this->actingAs($requester)->get(route('attachments.download', $attachment))
            ->assertOk()
            ->assertDownload('pasport.pdf');

        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();
        $this->actingAs($admin)->get(route('attachments.download', $attachment))->assertOk();

        $stranger = User::factory()->create(['approved_at' => now(), 'is_active' => true]);
        $stranger->assignRole(UserRole::Requester->value);
        $this->actingAs($stranger)->get(route('attachments.download', $attachment))->assertForbidden();

        $manager = User::query()->where('email', 'manager@rtt.local')->firstOrFail();
        $this->actingAs($manager)->get(route('attachments.download', $attachment))->assertForbidden();

        auth()->logout();
        $this->get(route('attachments.download', $attachment))->assertRedirect(route('login'));
    }

    public function test_command_moves_public_attachments_to_private_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);

        $ticket = Ticket::query()->firstOrFail();
        Storage::disk('public')->put("tickets/{$ticket->id}/old.pdf", 'eski');

        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'disk' => 'public',
            'path' => "tickets/{$ticket->id}/old.pdf",
            'original_name' => 'old.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 4,
            'context' => 'request',
        ]);

        $this->artisan('attachments:make-private')->assertSuccessful();

        $this->assertSame('local', $attachment->fresh()->disk);
        Storage::disk('local')->assertExists("tickets/{$ticket->id}/old.pdf");
        Storage::disk('public')->assertMissing("tickets/{$ticket->id}/old.pdf");
    }
}
