@php
    // Static demonstration data (frontend only – not connected to the database).
    $stats = [
        ['label' => 'Total Employees', 'value' => '248', 'trend' => '+12 this month', 'icon' => 'users', 'tone' => 'bg-brand-50 text-brand-700'],
        ['label' => 'Total Departments', 'value' => '14', 'trend' => '2 added this year', 'icon' => 'building', 'tone' => 'bg-ocean-50 text-ocean-700'],
        ['label' => 'Total Records', 'value' => '3,842', 'trend' => '+8.4% vs last month', 'icon' => 'document', 'tone' => 'bg-brand-50 text-brand-700'],
        ['label' => 'Pending Approvals', 'value' => '27', 'trend' => '9 need attention', 'icon' => 'clock', 'tone' => 'bg-sun-100 text-sun-700'],
    ];

    $monthly = [
        ['m' => 'Jun', 'v' => 220], ['m' => 'Jul', 'v' => 285], ['m' => 'Aug', 'v' => 260],
        ['m' => 'Sep', 'v' => 340], ['m' => 'Oct', 'v' => 395], ['m' => 'Nov', 'v' => 430],
    ];
    $maxMonthly = max(array_column($monthly, 'v'));

    $statuses = [
        ['label' => 'Approved', 'value' => 62, 'color' => '#1f7d46'],
        ['label' => 'In review', 'value' => 21, 'color' => '#2563eb'],
        ['label' => 'Pending', 'value' => 12, 'color' => '#f5b301'],
        ['label' => 'Rejected', 'value' => 5, 'color' => '#cbd5e1'],
    ];
    $stop = 0;
    $gradient = [];
    foreach ($statuses as $s) {
        $gradient[] = $s['color'].' '.$stop.'% '.($stop + $s['value']).'%';
        $stop += $s['value'];
    }

    $departments = [
        ['name' => 'Human Resources', 'docs' => 812],
        ['name' => 'Finance', 'docs' => 764],
        ['name' => 'Legal & Compliance', 'docs' => 603],
        ['name' => 'Operations', 'docs' => 548],
        ['name' => 'Research', 'docs' => 497],
        ['name' => 'IT Services', 'docs' => 318],
    ];
    $maxDept = max(array_column($departments, 'docs'));

    $activities = [
        ['icon' => 'upload', 'tone' => 'bg-brand-50 text-brand-700', 'who' => 'Ayesha Khan', 'what' => 'uploaded Annual Budget 2027.xlsx', 'time' => '12 min ago'],
        ['icon' => 'check-circle', 'tone' => 'bg-ocean-50 text-ocean-700', 'who' => 'Bilal Ahmed', 'what' => 'approved Vendor Contract – Q4', 'time' => '1 hour ago'],
        ['icon' => 'pencil', 'tone' => 'bg-sun-100 text-sun-700', 'who' => 'Sana Malik', 'what' => 'edited HR Policy Handbook', 'time' => '3 hours ago'],
        ['icon' => 'users', 'tone' => 'bg-brand-50 text-brand-700', 'who' => 'Usman Tariq', 'what' => 'joined the Research department', 'time' => 'Yesterday'],
        ['icon' => 'document', 'tone' => 'bg-ocean-50 text-ocean-700', 'who' => 'Hina Raza', 'what' => 'shared Compliance Audit Report', 'time' => 'Yesterday'],
    ];

    $documents = [
        ['name' => 'Annual Budget 2027', 'dept' => 'Finance', 'owner' => 'Ayesha Khan', 'date' => '22 Nov 2026', 'status' => 'Pending', 'badge' => 'badge-yellow'],
        ['name' => 'Vendor Contract – Q4', 'dept' => 'Legal & Compliance', 'owner' => 'Bilal Ahmed', 'date' => '21 Nov 2026', 'status' => 'Approved', 'badge' => 'badge-green'],
        ['name' => 'HR Policy Handbook', 'dept' => 'Human Resources', 'owner' => 'Sana Malik', 'date' => '20 Nov 2026', 'status' => 'In review', 'badge' => 'badge-blue'],
        ['name' => 'Compliance Audit Report', 'dept' => 'Legal & Compliance', 'owner' => 'Hina Raza', 'date' => '19 Nov 2026', 'status' => 'Approved', 'badge' => 'badge-green'],
        ['name' => 'Research Grant Proposal', 'dept' => 'Research', 'owner' => 'Usman Tariq', 'date' => '18 Nov 2026', 'status' => 'Pending', 'badge' => 'badge-yellow'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('Dashboard') }}</h1>
    </x-slot>

    <div class="space-y-6">

        <!-- Welcome -->
        <section class="relative overflow-hidden rounded-xl bg-brand-800 px-6 py-7 sm:px-8">
            <div class="pointer-events-none absolute -right-10 -top-16 h-56 w-56 rounded-full bg-brand-700/60"></div>
            <div class="pointer-events-none absolute -bottom-20 right-32 hidden h-48 w-48 rounded-full bg-ocean-700/30 sm:block"></div>
            <div class="relative flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-sun-300">{{ now()->format('l, j F Y') }}</p>
                    <h2 class="mt-1 text-2xl font-bold text-white">Welcome back, {{ Auth::user()->name }}!</h2>
                    <p class="mt-1.5 max-w-xl text-sm text-brand-100/80">You have <span class="font-semibold text-white">27 pending approvals</span> and <span class="font-semibold text-white">5 new records</span> waiting for you today.</p>
                </div>
                <a href="{{ route('profile.edit') }}" class="btn bg-sun-500 text-brand-950 hover:bg-sun-400 focus-visible:ring-sun-400">
                    <x-icon name="user" class="h-[18px] w-[18px]" /> My profile
                </a>
            </div>
        </section>

        <!-- Summary cards -->
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $s)
                <div class="card p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ $s['label'] }}</p>
                            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $s['value'] }}</p>
                        </div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $s['tone'] }}"><x-icon name="{{ $s['icon'] }}" class="h-6 w-6" /></span>
                    </div>
                    <p class="mt-4 flex items-center gap-1.5 border-t border-slate-100 pt-3 text-xs text-slate-500">
                        <x-icon name="trending-up" class="h-4 w-4 text-brand-600" /> {{ $s['trend'] }}
                    </p>
                </div>
            @endforeach
        </section>

        <!-- Charts row -->
        <section class="grid gap-6 xl:grid-cols-3">
            <!-- Monthly documents -->
            <div class="card xl:col-span-2">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Data Statistics</h3>
                        <p class="card-subtitle">Records added in the last 6 months</p>
                    </div>
                    <span class="badge-green">+95% growth</span>
                </div>
                <div class="card-body">
                    <div class="flex h-56 items-end gap-3 sm:gap-6">
                        @foreach ($monthly as $m)
                            <div class="flex h-full flex-1 flex-col items-center justify-end gap-2">
                                <span class="text-xs font-semibold text-slate-600">{{ $m['v'] }}</span>
                                <div class="w-full max-w-14 rounded-t-lg {{ $loop->last ? 'bg-brand-600' : 'bg-brand-200' }} transition hover:bg-brand-500" style="height: {{ round($m['v'] / $maxMonthly * 80) }}%"></div>
                                <span class="text-xs text-slate-500">{{ $m['m'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Status donut -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Approval Status</h3>
                        <p class="card-subtitle">All records by status</p>
                    </div>
                </div>
                <div class="card-body flex flex-col items-center gap-6 sm:flex-row xl:flex-col 2xl:flex-row">
                    <div class="relative h-40 w-40 shrink-0 rounded-full" style="background: conic-gradient({{ implode(', ', $gradient) }})">
                        <div class="absolute inset-5 flex flex-col items-center justify-center rounded-full bg-white">
                            <span class="text-2xl font-bold text-slate-900">62%</span>
                            <span class="text-xs text-slate-500">Approved</span>
                        </div>
                    </div>
                    <ul class="w-full space-y-3 text-sm">
                        @foreach ($statuses as $s)
                            <li class="flex items-center justify-between gap-3">
                                <span class="flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $s['color'] }}"></span>{{ $s['label'] }}</span>
                                <span class="font-semibold text-slate-900">{{ $s['value'] }}%</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

        <!-- Department analytics + Activities -->
        <section class="grid gap-6 xl:grid-cols-3">
            <div class="card xl:col-span-2">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Department-wise Analytics</h3>
                        <p class="card-subtitle">Records stored per department</p>
                    </div>
                </div>
                <div class="card-body space-y-5">
                    @foreach ($departments as $d)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-700">{{ $d['name'] }}</span>
                                <span class="text-slate-500">{{ number_format($d['docs']) }}</span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $loop->index % 2 ? 'bg-ocean-500' : 'bg-brand-500' }}" style="width: {{ round($d['docs'] / $maxDept * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Recent Activities</h3>
                        <p class="card-subtitle">What's happening across teams</p>
                    </div>
                </div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($activities as $a)
                        <li class="flex gap-3 px-5 py-3.5 sm:px-6">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $a['tone'] }}"><x-icon name="{{ $a['icon'] }}" class="h-[18px] w-[18px]" /></span>
                            <div class="min-w-0 text-sm">
                                <p class="text-slate-700"><span class="font-semibold text-slate-900">{{ $a['who'] }}</span> {{ $a['what'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $a['time'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        <!-- Recent documents -->
        <section class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Recent Records</h3>
                    <p class="card-subtitle">Latest data added to the system</p>
                </div>
                <a href="#" class="btn-secondary !px-3 !py-2">View all</a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Record</th>
                            <th>Department</th>
                            <th class="hidden md:table-cell">Owner</th>
                            <th class="hidden sm:table-cell">Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documents as $d)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500"><x-icon name="document" class="h-[18px] w-[18px]" /></span>
                                        <span class="font-medium text-slate-900">{{ $d['name'] }}</span>
                                    </div>
                                </td>
                                <td>{{ $d['dept'] }}</td>
                                <td class="hidden md:table-cell">{{ $d['owner'] }}</td>
                                <td class="hidden whitespace-nowrap sm:table-cell">{{ $d['date'] }}</td>
                                <td><span class="{{ $d['badge'] }}">{{ $d['status'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
