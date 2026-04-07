<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function log(?int $userId, string $event, string $description, ?Model $auditable = null, array $properties = []): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'event' => $event,
            'description' => $description,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'properties' => $properties,
        ]);
    }
}
