<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurgeTicketsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_removes_tickets_with_related_records_and_files_but_keeps_everything_else(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $ticket = Ticket::query()->firstOrFail();
        Storage::disk('local')->put('tickets/test.txt', 'x');
        TicketAttachment::query()->create(['ticket_id' => $ticket->id, 'disk' => 'local', 'path' => 'tickets/test.txt', 'original_name' => 'test.txt', 'mime_type' => 'text/plain', 'size_bytes' => 1]);
        $ticket->comments()->create(['body' => 'izoh', 'user_id' => User::query()->value('id')]);

        $users = User::query()->count();
        $departments = Department::query()->count();
        $categories = Category::query()->count();
        $this->assertGreaterThan(0, Ticket::query()->count());

        // Rad etilsa hech narsa o'chmaydi.
        $this->artisan('tickets:purge-all', ['--no-backup' => true])
            ->expectsConfirmation("Yuqoridagilarning hammasi o'chirilsinmi?", 'no')
            ->assertFailed();
        $this->assertGreaterThan(0, Ticket::query()->count());

        $this->artisan('tickets:purge-all', ['--no-backup' => true])
            ->expectsConfirmation("Yuqoridagilarning hammasi o'chirilsinmi?", 'yes')
            ->assertSuccessful();

        foreach (['tickets', 'ticket_comments', 'ticket_attachments', 'ticket_status_histories', 'ticket_assignments', 'ticket_return_requests', 'ticket_dynamic_field_values'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), "{$table} bo'shamadi");
        }
        Storage::disk('local')->assertMissing('tickets/test.txt');
        $this->assertSame($users, User::query()->count());
        $this->assertSame($departments, Department::query()->count());
        $this->assertSame($categories, Category::query()->count());

        // Ikkinchi marta ishga tushsa — o'chiradigan narsa yo'q.
        $this->artisan('tickets:purge-all')->expectsOutputToContain("O'chiriladigan murojaat yo'q.")->assertSuccessful();
    }
}
