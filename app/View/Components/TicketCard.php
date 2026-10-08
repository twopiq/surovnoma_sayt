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
        return 'Biriktirilgan: '.collect($this->files)
            ->map(fn (array $file) => $file['count'].' '.$file['long'])
            ->implode(', ');
    }

    private function deadlineState(): array
    {
        $status = $this->ticket->status;

        if (in_array($status, [TicketStatus::Completed, TicketStatus::Closed], true)) {
            return ['done', 'Bajarilgan'];
        }

        if ($status === TicketStatus::Rejected) {
            return ['rej', 'Rad etilgan'];
        }

        $deadline = $this->ticket->deadline_at;

        if (! $deadline) {
            return ['none', 'Muddat belgilanmagan'];
        }

        $minutes = (int) abs(now()->diffInMinutes($deadline));

        if ($deadline->isPast()) {
            return ['late', $this->humanDuration($minutes).' kechikdi'];
        }

        return [$minutes < 24 * 60 ? 'soon' : 'ok', $this->humanDuration($minutes).' qoldi'];
    }

    private function humanDuration(int $minutes): string
    {
        return match (true) {
            $minutes < 60 => max($minutes, 1).' daqiqa',
            $minutes < 24 * 60 => intdiv($minutes, 60).' soat',
            default => intdiv($minutes, 24 * 60).' kun',
        };
    }

    private function resolveSubject(): string
    {
        $title = trim((string) $this->ticket->title);

        if ($title !== '') {
            return $title;
        }

        $description = trim((string) $this->ticket->description);

        return $description !== '' ? Str::limit($description, 90) : 'Mavzu kiritilmagan';
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
                'text' => $counts[$type] > 1 ? $meta['label'].' ×'.$counts[$type] : $meta['label'],
            ])
            ->values()
            ->all();
    }
}
