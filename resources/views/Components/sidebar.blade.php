@php
    $links = [
        ['dashboard', 'Dashboard', 'bi-grid', 'dashboard'],
        ['patients.index', 'Patients', 'bi-people', 'patients'],
        ['doctors.index', 'Doctors', 'bi-person-badge', 'doctors'],
        ['departments.index', 'Departments', 'bi-building', 'departments'],
        ['appointments.index', 'Appointments', 'bi-calendar-check', 'appointments'],
        ['prescriptions.index', 'Prescriptions', 'bi-file-earmark-medical', 'prescriptions'],
        ['medicines.index', 'Medicines', 'bi-capsule', 'medicines'],
        ['pharmacy.pos', 'Pharmacy POS', 'bi-basket', 'pharmacy'],
        ['pharmacy.sales.index', 'Pharmacy Sales', 'bi-receipt-cutoff', 'pharmacy'],
        ['pharmacy.reports.daily', 'Daily Pharmacy Report', 'bi-clipboard-data', 'pharmacy'],
        ['invoices.index', 'Invoices', 'bi-receipt', 'invoices'],
        ['payments.index', 'Payments', 'bi-credit-card', 'payments'],
        ['users.index', 'Users', 'bi-person-gear', 'users'],
    ];
@endphp
<aside class="app-sidebar bg-primary text-white p-3 d-none d-md-block" style="width:260px;">
    <a href="{{ route('dashboard') }}" class="text-white text-decoration-none d-block mb-4">
        <span class="fs-4 fw-bold"><i class="bi bi-hospital me-2"></i>Haldoor</span>
        <small class="d-block text-white-50">Hospital Management System</small>
    </a>
    <nav class="nav flex-column gap-1">
        @foreach($links as [$route, $name, $icon, $module])
            @if (auth()->user()?->canAccessModule($module))
            <a class="nav-link text-white {{ request()->routeIs($route === 'dashboard' ? 'dashboard' : str($route)->before('.').'.*') ? 'bg-white bg-opacity-25 rounded' : '' }}" href="{{ route($route) }}">
                <i class="bi {{ $icon }} me-2"></i>{{ $name }}
            </a>
            @endif
        @endforeach
    </nav>
</aside>

<style>
    .app-sidebar {
        position: sticky;
        top: 0;
        height: 100vh;
        align-self: flex-start;
        overflow-y: auto;
    }
</style>
