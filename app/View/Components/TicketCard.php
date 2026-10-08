<?php

namespace App\View\Components;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\View\View;

class TicketCard extends Component
{
    private const FILE_TYPES = [
        'img' => ['label' => 'Rasm', 'long' => 'rasm', 'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp']],
        'pdf' => ['label' => 'PDF', 'long' => 'PDF', 'extensions' => ['pdf']],
        'doc' => ['label' => 'Word', 'long' => 'Word fayl', 'extensions' => ['doc', 'docx']],
        'other' => ['label' => 'Fayl', 'long' => 'fayl', 'extensions' => []],
    ];

    public string $tone;

    public string $pill;

    public string $subject;

    public array $files;

    public int $filesTotal;

    public function __construct(
        public Ticket $ticket,
        public bool $showStatus = true,
    ) {
        [$this->tone, $this->pill] = $this->deadlineState();
        $this->subject = $this->resolveSubject();
        $this->files = $this->groupFiles();
        $this->filesTotal = array_sum(array_column($this->files, 'count'));
    }

    public function render(): View
    {
        return view('components.ticket-card');
    }

    public function filesTitle(): string
    {
        return __('Biriktirilgan').': '.collect($this->files)
            ->map(fn (array $file) => $file['count'].' '.__($file['long']))
            ->implode(', ');
    }

    private function deadlineState(): array
    {
        $status = $this->ticket->status;

        if (in_array($status, [TicketStatus::Completed, TicketStatus::Closed], true)) {
            return ['done', __('Bajarilgan')];
        }

        if ($status === TicketStatus::Rejected) {
            return ['rej', __('Rad etilgan')];
        }

        if ($status === TicketStatus::Cancelled) {
            return ['rej', __('Bekor qilingan')];
        }

        $deadline = $this->ticket->deadline_at;

        if (! $deadline) {
            return ['none', __('Muddat belgilanmagan')];
        }

        $minutes = (int) abs(now()->diffInMinutes($deadline));

        if ($deadline->isPast()) {
            return ['late', __(':time kechikdi', ['time' => $this->humanDuration($minutes)])];
        }

        return [$minutes < 24 * 60 ? 'soon' : 'ok', __(':time qoldi', ['time' => $this->humanDuration($minutes)])];
    }

    private function humanDuration(int $minutes): string
    {
        return match (true) {
            $minutes < 60 => __(':n daqiqa', ['n' => max($minutes, 1)]),
            $minutes < 24 * 60 => __(':n soat', ['n' => intdiv($minutes, 60)]),
            default => __(':n kun', ['n' => intdiv($minutes, 24 * 60)]),
        };
    }

    private function resolveSubject(): string
    {
        $title = trim((string) $this->ticket->title);

        if ($title !== '') {
            return $title;
        }

        $description = trim((string) $this->ticket->description);

        return $description !== '' ? Str::limit($description, 90) : __('Mavzu kiritilmagan');
    }

    private function groupFiles(): array
    {
        $counts = [];

        foreach ($this->ticket->attachments as $attachment) {
            $extension = strtolower(pathinfo((string) $attachment->original_name, PATHINFO_EXTENSION));
            $type = collect(self::FILE_TYPES)->search(fn (array $meta) => in_array($extension, $meta['extensions'], true)) ?: 'other';
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }

        return collect(self::FILE_TYPES)
            ->filter(fn (array $meta, string $type) => isset($counts[$type]))
            ->map(fn (array $meta, string $type) => [
                'type' => $type,
                'count' => $counts[$type],
                'long' => $meta['long'],
                'text' => $counts[$type] > 1 ? __($meta['label']).' ×'.$counts[$type] : __($meta['label']),
            ])
            ->values()
            ->all();
    }
}
