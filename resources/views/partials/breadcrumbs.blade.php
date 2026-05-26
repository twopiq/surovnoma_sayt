@php
    $crumbs = [];

    $add = function (string $label, ?string $url = null) use (&$crumbs): void {
        $crumbs[] = [
            'label' => $label,
            'url' => $url,
        ];
    };

    if (request()->routeIs('admin.users.*')) {
        $add('Admin', route('dashboard'));
        $add('Users', route('admin.users.list'));

        if (request()->routeIs('admin.users.index')) {
            $add('Registration approvals');
        } elseif (request()->routeIs('admin.users.recent')) {
            $add('Recent registrations');
        } elseif (request()->routeIs('admin.users.create')) {
            $add('Add user');
        } elseif (request()->routeIs('admin.users.profile')) {
            $add('User profile');
        } else {
            $add('User management');
        }
    } elseif (request()->routeIs('admin.dispatch.*')) {
        $add('Admin', route('dashboard'));
        $add('Ticket management', route('admin.dispatch.tickets'));

        if (request()->routeIs('admin.dispatch.index')) {
            $add('Dispatch board');
        } elseif (request()->routeIs('admin.dispatch.archive')) {
            $add('Archive');
        } elseif (request()->routeIs('admin.dispatch.status')) {
            $add('Status');
        } elseif (request()->routeIs('admin.dispatch.show')) {
            $add('Ticket card');
        } elseif (request()->routeIs('admin.dispatch.deadlines')) {
            $add('Deadline settings');
        } elseif (request()->routeIs('admin.dispatch.work-schedule')) {
            $add('Work schedule');
        } else {
            $add('Tickets');
        }
    } elseif (request()->routeIs('admin.departments.*')) {
        $add('Admin', route('dashboard'));
        $add('Departments');
    } elseif (request()->routeIs('admin.categories.*')) {
        $add('Admin', route('dashboard'));
        $add('Categories');
    } elseif (request()->routeIs('admin.sla.*')) {
        $add('Admin', route('dashboard'));
        $add('SLA settings');
    } elseif (request()->routeIs('app.dashboard')) {
        $add('Home', route('app.home'));
        $add('Dashboard');
    } elseif (request()->routeIs('app.home')) {
        $add('Home');
    } elseif (request()->routeIs('app.settings')) {
        $add('Home', route('app.home'));
        $add('Settings');
    } elseif (request()->routeIs('manager.dashboard*')) {
        $add('Home', route('app.home'));
        $add('Manager dashboard');
    } elseif (request()->routeIs('executor.tickets.*')) {
        $add('Home', route('app.home'));
        $add('Executor tickets', route('executor.tickets.index'));

        if (request()->routeIs('executor.tickets.archive')) {
            $add('Archive');
        } elseif (request()->routeIs('executor.tickets.show')) {
            $add('Ticket card');
        }
    } elseif (request()->routeIs('operator.tickets.*')) {
        $add('Home', route('app.home'));
        $add('Operator tickets', route('operator.tickets.index'));

        if (request()->routeIs('operator.tickets.create')) {
            $add('Create ticket');
        } elseif (request()->routeIs('operator.tickets.show')) {
            $add('Ticket card');
        }
    } elseif (request()->routeIs('tickets.*')) {
        $add('Home', route('app.home'));
        $add('Tickets', route('tickets.index'));

        if (request()->routeIs('tickets.create')) {
            $add('Create ticket');
        } elseif (request()->routeIs('tickets.show')) {
            $add('Ticket card');
        }
    } elseif (request()->routeIs('notifications.*')) {
        $add('Home', route('app.home'));
        $add('Bildirishnomalar');
    } elseif (request()->routeIs('profile.edit')) {
        $add('Home', route('app.home'));
        $add('Profile');
    } elseif (request()->routeIs('dashboard')) {
        $add('Admin');
    }
@endphp

@if (count($crumbs) > 0)
    <nav class="flex flex-wrap items-center justify-end gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
        @foreach ($crumbs as $index => $crumb)
            @if ($index > 0)
                <span class="text-slate-300">/</span>
            @endif

            @if ($crumb['url'] && ! $loop->last)
                <a href="{{ $crumb['url'] }}" class="font-medium transition hover:text-cyan-700">{{ $crumb['label'] }}</a>
            @else
                <span class="{{ $loop->last ? 'font-semibold text-cyan-700' : 'font-medium' }}">{{ $crumb['label'] }}</span>
            @endif
        @endforeach
    </nav>
@endif
