<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    public function download(Request $request, TicketAttachment $attachment, AuditService $audit): StreamedResponse
    {
        $user = $request->user();

        abort_unless($this->canAccess($user, $attachment), 403);

        $disk = Storage::disk($attachment->disk ?: 'local');
        abort_unless($disk->exists($attachment->path), 404);

        $audit->log($user->id, 'attachment.downloaded', __('Fayl yuklab olindi: :name', ['name' => $attachment->original_name], 'uz'), $attachment, [
            'ticket_id' => $attachment->ticket_id,
            'ip' => $request->ip(),
        ]);

        return $disk->download($attachment->path, $attachment->original_name, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function canAccess(User $user, TicketAttachment $attachment): bool
    {
        $ticket = $attachment->ticket;

        if (! $ticket) {
            return false;
        }

        return $user->hasRole(UserRole::Admin->value)
            || ($user->hasRole(UserRole::Requester->value) && $ticket->requester_id === $user->id)
            || ($user->hasRole(UserRole::Operator->value) && $ticket->operator_id === $user->id)
            || ($user->hasRole(UserRole::Executor->value) && $ticket->canExecutorAccess($user));
    }
}
