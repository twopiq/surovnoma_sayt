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
            self::Closed, self::Rejected => 'bg-slate-100 text-slate-800 ring-slate-300',
            self::Assigned => 'bg-yellow-100 text-yellow-900 ring-yellow-300',
            self::InProgress => 'bg-orange-100 text-orange-900 ring-orange-300',
            self::New, self::Returned => 'bg-red-100 text-red-800 ring-red-300',
            self::Completed => 'bg-emerald-100 text-emerald-900 ring-emerald-300',
        };
    }

    public function textClasses(): string
    {
        return match ($this) {
            self::Closed, self::Rejected => 'text-slate-500',
            self::Assigned => 'text-yellow-500',
            self::InProgress => 'text-orange-500',
            self::New, self::Returned => 'text-red-600',
            self::Completed => 'text-emerald-500',
        };
    }

    public function boardClasses(): string
    {
        return match ($this) {
            self::Closed, self::Rejected => 'border-slate-300 bg-slate-100/80',
            self::Assigned => 'border-yellow-200 bg-yellow-50/70',
            self::InProgress => 'border-orange-200 bg-orange-50/70',
            self::New, self::Returned => 'border-red-200 bg-red-50/70',
            self::Completed => 'border-emerald-200 bg-emerald-50/70',
        };
    }

    public function paletteColor(): string
    {
        return match ($this) {
            self::Closed, self::Rejected => '#8F8F8F',
            self::Assigned => '#FFE900',
            self::InProgress => '#FDA13F',
            self::New, self::Returned => '#E53D00',
            self::Completed => '#40F99B',
        };
    }

    public function paletteSoftColor(): string
    {
        return match ($this) {
            self::Closed, self::Rejected => '#8F8F8F26',
            self::Assigned => '#FFE90033',
            self::InProgress => '#FDA13F2E',
            self::New, self::Returned => '#E53D0026',
            self::Completed => '#40F99B26',
        };
    }

    public function paletteRingColor(): string
    {
        return match ($this) {
            self::Closed, self::Rejected => '#8F8F8F80',
            self::Assigned => '#FFE90099',
            self::InProgress => '#FDA13F99',
            self::New, self::Returned => '#E53D0080',
            self::Completed => '#40F99B80',
        };
    }

    public function paletteForegroundColor(): string
    {
        return match ($this) {
            self::New, self::Returned => '#FFFFFF',
            default => '#07120F',
        };
    }

    public function badgeStyle(): string
    {
        return "background-color: {$this->paletteColor()}; color: {$this->paletteForegroundColor()}; --tw-ring-color: {$this->paletteRingColor()};";
    }

    public function textStyle(): string
    {
        return "color: {$this->paletteColor()};";
    }

    public function boardStyle(): string
    {
        return "background-color: {$this->paletteSoftColor()} !important; border-color: {$this->paletteRingColor()} !important;";
    }
}
