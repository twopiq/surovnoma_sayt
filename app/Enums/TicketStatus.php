<?php

namespace App\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Returned = 'returned';
    case Completed = 'completed';
    case Closed = 'closed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Yangi',
            self::Assigned => 'Taqsimlandi',
            self::InProgress => 'Jarayonda',
            self::Returned => 'Qaytarildi',
            self::Completed => 'Bajarildi',
            self::Closed => 'Yopildi',
            self::Rejected => 'Rad etildi',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::New => 'bg-sky-100 text-sky-800 ring-sky-200',
            self::Assigned => 'bg-indigo-100 text-indigo-800 ring-indigo-200',
            self::InProgress => 'bg-amber-100 text-amber-800 ring-amber-200',
            self::Returned => 'bg-orange-100 text-orange-800 ring-orange-200',
            self::Completed => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            self::Closed => 'bg-slate-200 text-slate-800 ring-slate-300',
            self::Rejected => 'bg-rose-100 text-rose-800 ring-rose-200',
        };
    }

    public function textClasses(): string
    {
        return match ($this) {
            self::New => 'text-sky-700',
            self::Assigned => 'text-indigo-700',
            self::InProgress => 'text-amber-700',
            self::Returned => 'text-orange-700',
            self::Completed => 'text-emerald-700',
            self::Closed => 'text-slate-700',
            self::Rejected => 'text-rose-700',
        };
    }

    public function boardClasses(): string
    {
        return match ($this) {
            self::New => 'border-sky-200 bg-sky-50/70',
            self::Assigned => 'border-indigo-200 bg-indigo-50/70',
            self::InProgress => 'border-amber-200 bg-amber-50/70',
            self::Returned => 'border-orange-200 bg-orange-50/70',
            self::Completed => 'border-emerald-200 bg-emerald-50/70',
            self::Closed => 'border-slate-300 bg-slate-100/80',
            self::Rejected => 'border-rose-200 bg-rose-50/70',
        };
    }
}
