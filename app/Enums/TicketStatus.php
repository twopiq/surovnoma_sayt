<?php

namespace App\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Returned = 'returned';
    case Overdue = 'overdue';
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
            self::Overdue => 'Kechikkan',
            self::Completed => 'Bajarildi',
            self::Closed => 'Yopildi',
            self::Rejected => 'Rad etildi',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Closed, self::Rejected => 'bg-slate-100 text-slate-800 ring-slate-300',
            self::Assigned => 'bg-yellow-100 text-yellow-900 ring-yellow-300',
            self::InProgress => 'bg-orange-100 text-orange-900 ring-orange-300',
            self::New, self::Returned, self::Overdue => 'bg-red-100 text-red-800 ring-red-300',
            self::Completed => 'bg-emerald-100 text-emerald-900 ring-emerald-300',
        };
    }

    public function textClasses(): string
    {
        return match ($this) {
            self::Closed, self::Rejected => 'text-slate-500',
            self::Assigned => 'text-yellow-500',
            self::InProgress => 'text-orange-500',
            self::New, self::Returned, self::Overdue => 'text-red-600',
            self::Completed => 'text-emerald-500',
        };
    }

    public function boardClasses(): string
    {
        return match ($this) {
            self::Closed, self::Rejected => 'border-slate-300 bg-slate-100/80',
            self::Assigned => 'border-yellow-200 bg-yellow-50/70',
            self::InProgress => 'border-orange-200 bg-orange-50/70',
            self::New, self::Returned, self::Overdue => 'border-red-200 bg-red-50/70',
            self::Completed => 'border-emerald-200 bg-emerald-50/70',
        };
    }

    /**
     * Dizayn tizimidagi holat guruhi: CSS klasslari (.status--*, .status-col--*, .status-text--*) shu nom bilan.
     */
    public function tone(): string
    {
        return match ($this) {
            self::New, self::Returned, self::Overdue => 'new',
            self::Assigned => 'assigned',
            self::InProgress => 'in-progress',
            self::Completed => 'completed',
            self::Closed, self::Rejected => 'closed',
        };
    }

    public function badgeCssClass(): string
    {
        return 'status-badge status--'.$this->tone();
    }

    public function columnCssClass(): string
    {
        return 'status-col--'.$this->tone();
    }

    public function textCssClass(): string
    {
        return 'status-text--'.$this->tone();
    }

    /** Badge foni (yorug' mavzu) — CSS o'zgaruvchisi ishlamaydigan joylar uchun (KPI API). */
    public function paletteColor(): string
    {
        return match ($this->tone()) {
            'new' => '#FDE4E1',
            'assigned' => '#FDF0C9',
            'in-progress' => '#FDE6D3',
            'completed' => '#D7F0DF',
            'closed' => '#E6E5E0',
        };
    }

    public function paletteForegroundColor(): string
    {
        return match ($this->tone()) {
            'new' => '#8F1D12',
            'assigned' => '#6B4700',
            'in-progress' => '#8A3A07',
            'completed' => '#11582C',
            'closed' => '#3F4247',
        };
    }

    public function paletteDotColor(): string
    {
        return match ($this->tone()) {
            'new' => '#D92D20',
            'assigned' => '#9A6700',
            'in-progress' => '#C2410C',
            'completed' => '#15803D',
            'closed' => '#6B6E75',
        };
    }

    public function badgeStyle(): string
    {
        return "background-color: {$this->paletteColor()}; color: {$this->paletteForegroundColor()}; --tw-ring-color: {$this->paletteDotColor()};";
    }
}
