<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\DashboardWidget;
use App\Models\Ticket;
use App\Services\DashboardReportService;
use App\Support\TableExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppDashboardController extends Controller
{
    public const PERIODS = [
        'today' => 'Bugun',
        '7d' => '7 kun',
        '30d' => '30 kun',
        'month' => 'Oy',
        'range' => 'Oraliq',
    ];

    public function __construct(protected DashboardReportService $reports)
    {
    }

    public function __invoke(Request $request): View
    {
        $viewer = $request->user();
        abort_unless($viewer && $viewer->canAccessAppDashboard(), 403);

        $isAdmin = $viewer->hasRole(UserRole::Admin->value);
        $managerPreview = $isAdmin && $request->query('view') === 'manager';
        $visibility = DashboardWidget::managerVisibility();
        $widgets = ($isAdmin && ! $managerPreview)
            ? array_keys(DashboardWidget::CATALOG)
            : array_keys(array_filter($visibility));

        [$period, $start, $end] = $this->period($request);
        $filters = $this->filters($request);
        $compare = $request->query('compare', '1') !== '0';

        return view('app.dashboard', [
            'report' => $this->reports->report($start, $end, $filters, $widgets, $compare),
            'widgets' => $widgets,
            'visibility' => $visibility,
            'canManageWidgets' => $isAdmin && ! $managerPreview,
            'isAdmin' => $isAdmin,
            'managerPreview' => $managerPreview,
            'period' => $period,
            'periods' => self::PERIODS,
            'start' => $start,
            'end' => $end,
            'filters' => $filters,
            'compare' => $compare,
            'filterOptions' => $this->reports->filterOptions(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $viewer = $request->user();
        abort_unless($viewer && $viewer->canAccessAppDashboard(), 403);

        [, $start, $end] = $this->period($request);
        $format = $request->query('format') === 'csv' ? 'csv' : 'excel';
        $query = $this->reports->exportQuery($start, $end, $this->filters($request))
            ->with(['assignedDepartment:id,name', 'assignedExecutor:id,name', 'category:id,name'])
            ->latest();

        $rows = (function () use ($query): \Generator {
            foreach ($query->cursor() as $ticket) {
                /** @var Ticket $ticket */
                yield [
                    $ticket->reference,
                    $ticket->requester_name,
                    $ticket->status->label(),
                    $ticket->priority->label(),
                    $ticket->category?->name ?? '-',
                    $ticket->assignedDepartment?->name ?? '-',
                    $ticket->assignedExecutor?->name ?? '-',
                    $ticket->receivedAtLabel(),
                    $ticket->deadline_at?->format('d.m.Y H:i') ?? '-',
                    $ticket->completed_at?->format('d.m.Y H:i') ?? '-',
                ];
            }
        })();

        return TableExport::download(
            $format,
            'murojaatlar-hisoboti-'.$start->format('Ymd').'-'.$end->format('Ymd'),
            'Murojaatlar hisoboti',
            ['Raqam', 'Murojaatchi', 'Holat', 'Muhimlik', 'Kategoriya', "Bo'lim", 'Ijrochi', 'Qabul qilingan', 'Muddat', 'Bajarilgan'],
            $rows,
            ['Davr' => $start->format('d.m.Y').' – '.$end->format('d.m.Y'), 'Eksport qilingan vaqt' => now()],
        );
    }

    public function toggleWidget(Request $request, string $widget): RedirectResponse
    {
        abort_unless(array_key_exists($widget, DashboardWidget::CATALOG), 404);

        $visible = DashboardWidget::toggle($widget);
        $label = DashboardWidget::CATALOG[$widget][0];

        return back()->with('status', $visible ? "«{$label}» rahbarga ko'rinadi." : "«{$label}» rahbardan yashirildi.");
    }

    /** @return array{0: string, 1: Carbon, 2: Carbon} */
    protected function period(Request $request): array
    {
        $period = array_key_exists((string) $request->query('period'), self::PERIODS) ? (string) $request->query('period') : '30d';
        $end = now()->endOfDay();

        if ($period === 'range') {
            try {
                $from = Carbon::parse((string) $request->query('from'))->startOfDay();
                $to = Carbon::parse((string) $request->query('to'))->endOfDay();

                if ($from->lte($to) && $from->diffInDays($to) <= 366) {
                    return [$period, $from, $to];
                }
            } catch (\Throwable) {
                // sana hali tanlanmagan yoki noto'g'ri — oraliq maydonlari oxirgi 30 kun bilan to'ldiriladi
            }

            return [$period, now()->subDays(29)->startOfDay(), $end];
        }

        $start = match ($period) {
            'today' => now()->startOfDay(),
            '7d' => now()->subDays(6)->startOfDay(),
            'month' => now()->startOfMonth(),
            default => now()->subDays(29)->startOfDay(),
        };

        return [$period, $start, $end];
    }

    protected function filters(Request $request): array
    {
        return collect(['department_id', 'category_id', 'executor_id'])
            ->mapWithKeys(fn (string $key) => [$key => (int) $request->query($key) ?: null])
            ->filter()
            ->all();
    }
}
