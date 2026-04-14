<?php

use App\Enums\ExternalStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('channel', 30);
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('assigned_executor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('sla_profile_id')->nullable();
            $table->string('requester_name');
            $table->string('requester_email')->nullable();
            $table->string('requester_phone', 30)->nullable();
            $table->string('requester_department')->nullable();
            $table->string('requester_job_title')->nullable();
            $table->string('title')->nullable();
            $table->longText('description');
            $table->string('priority')->default(TicketPriority::Medium->value);
            $table->string('status')->default(TicketStatus::New->value);
            $table->string('external_status')->default(ExternalStatus::Accepted->value);
            $table->string('tracking_code_hash')->nullable();
            $table->string('tracking_code_last_four', 4)->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index('deadline_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
