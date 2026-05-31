<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\Ticket;
use BackedEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KpiTicketsController extends KpiBaseController
{
    private const PER_PAGE = 50;

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->userFromRequest($request)) {
            return response()->json(['message' => 'Avtorizatsiya talab qilinadi.'], 401);
        }

        $query = Ticket::query()->with([
            'assignedExecutor:id,name',
            'assignedDepartment:id,name',
            'category:id,name',
        ]);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }

        if ($departmentId = $request->query('department_id')) {
            $query->where('assigned_department_id', $departmentId);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $paginated = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE, ['*'], 'page', (int) ($request->query('page', 1)));

        $items = collect($paginated->items())->map(function (Ticket $t): array {
            $status   = $this->enumValue($t->status);
            $priority = $this->enumValue($t->priority);

            return [
                'dbId'          => $t->id,
                'id'            => $t->reference ?: "#{$t->id}",
                'customer'      => $t->requester_name ?: "Noma'lum mijoz",
                'phone'         => $t->requester_phone ?: '-',
                'location'      => $t->requester_department ?: '-',
                'title'         => $t->title ?: 'Murojaat',
                'assignee'      => $t->assignedExecutor?->name ?: 'Biriktirilmagan',
                'department'    => $t->assignedDepartment?->name ?: '-',
                'category'      => $t->category?->name ?: '-',
                'priority'      => $priority,
                'priorityLabel' => $this->priorityLabel($priority),
                'status'        => $status,
                'statusLabel'   => $this->statusLabel($status),
                'date'          => $t->created_at?->format('Y-m-d H:i:s') ?: '',
                'deadline'      => $t->deadline_at?->format('Y-m-d H:i:s'),
                'isOverdue'     => $t->deadline_at && $t->deadline_at->isPast()
                    && ! in_array($status, [TicketStatus::Completed->value, TicketStatus::Closed->value, TicketStatus::Rejected->value]),
            ];
        })->values();

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($d) => ['id' => $d->id, 'name' => $d->name])
            ->values();

        return response()->json([
            'items'        => $items,
            'total'        => $paginated->total(),
            'per_page'     => $paginated->perPage(),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
            'departments'  => $departments,
        ]);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            TicketStatus::New->value        => 'Yangi',
            TicketStatus::Assigned->value   => 'Taqsimlandi',
            TicketStatus::InProgress->value => 'Jarayonda',
            TicketStatus::Returned->value   => 'Qaytarildi',
            TicketStatus::Overdue->value    => 'Kechikkan',
            TicketStatus::Completed->value  => 'Bajarildi',
            TicketStatus::Closed->value     => 'Yopildi',
            TicketStatus::Rejected->value   => 'Rad etildi',
            default                         => $status,
        };
    }

    private function priorityLabel(string $priority): string
    {
        return match ($priority) {
            TicketPriority::Low->value        => 'Past',
            TicketPriority::Medium->value     => "O'rta",
            TicketPriority::High->value       => 'Yuqori',
            TicketPriority::Urgent->value     => 'Shoshilinch',
            TicketPriority::Unassigned->value => 'Belgilanmagan',
            default                           => $priority,
        };
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof BackedEnum ? (string) $value->value : (string) $value;
    }
}
