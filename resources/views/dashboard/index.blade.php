<x-layout title="Dashboard">
    <div class="container-fluid py-4 px-3 px-lg-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4">
            <div>
                <div class="text-uppercase small text-primary fw-semibold">Overview</div>
                <h1 class="h3 mb-1">Good morning, {{ auth()->user()->name }}</h1>
                <div class="text-muted small">{{ now()->format('d M Y') }}</div>
            </div>

            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2">
                    {{ ucfirst(auth()->user()->role ?? 'User') }}
                </span>
                @if (auth()->user()->canAccessModule('appointments'))
                    <a href="{{ route('appointments.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i>New Appointment
                    </a>
                @endif
            </div>
        </div>

        <div id="dashboard-skeleton" class="row g-3">
            @for ($i = 0; $i < 6; $i++)
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="placeholder-glow mb-2">
                                <span class="placeholder col-7"></span>
                            </div>
                            <div class="placeholder-glow">
                                <span class="placeholder col-5"></span>
                            </div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>

        <div id="dashboard-content" class="d-none">
            <div id="statsRow" class="row g-3 mb-4"></div>

            <div class="row g-4 mb-4">
                @if (auth()->user()->isAdmin())
                <div class="col-xl-8" id="revenueCardWrapper">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body chart-card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-semibold mb-0">Revenue Overview</h6>
                                <span class="badge bg-light text-dark">Last 12 months</span>
                            </div>
                            <canvas id="revenueChart" class="compact-chart" height="180"></canvas>
                        </div>
                    </div>
                </div>
                @endif

                @if (auth()->user()->canAccessModule('patients'))
                    <div class="col-xl-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body chart-card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-semibold mb-0">Patients per month</h6>
                            </div>
                            <canvas id="patientsChart" class="compact-chart" height="180"></canvas>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <div class="row g-4 mb-4">
                @if (auth()->user()->canAccessModule('patients'))
                    <div class="col-xl-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-semibold mb-0">Recent patients</h6>
                                <a href="{{ route('patients.index') }}" class="btn btn-link btn-sm text-primary p-0">View all</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Age</th>
                                            <th>Gender</th>
                                            <th>Registered</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recentPatientsBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    </div>
                @endif

                @if (auth()->user()->canAccessModule('invoices'))
                    <div class="col-xl-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-semibold mb-0">Recent invoices</h6>
                                <a href="{{ route('invoices.index') }}" class="btn btn-link btn-sm text-primary p-0">View all</a>
                            </div>
                            <div id="recentInvoicesList" class="d-grid gap-2"></div>
                        </div>
                    </div>
                    </div>
                @endif
            </div>

            <div class="row g-4 mb-4">
                @if (auth()->user()->canAccessModule('appointments'))
                    <div class="col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-semibold mb-0">Today's appointments</h6>
                                <a href="{{ route('appointments.index') }}" class="btn btn-link btn-sm text-primary p-0">View all</a>
                            </div>
                            <div id="appointmentsList" class="d-grid gap-2"></div>
                        </div>
                    </div>
                    </div>
                @endif

                @if (auth()->user()->canAccessModule('medicines'))
                    <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-semibold mb-0">Low stock medicines</h6>
                            </div>
                            <div id="lowStockList" class="d-grid gap-2"></div>
                        </div>
                    </div>
                    </div>
                @endif

                @if (auth()->user()->canAccessModule('patients') || auth()->user()->canAccessModule('appointments') || auth()->user()->canAccessModule('prescriptions') || auth()->user()->canAccessModule('invoices'))
                    <div class="col-lg-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-0">Quick actions</h6>
                            <div class="d-grid gap-2 mt-3">
                                @if (auth()->user()->canAccessModule('patients'))
                                    <a href="{{ route('patients.create') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-person-plus me-1"></i>Add Patient</a>
                                @endif
                                @if (auth()->user()->canAccessModule('appointments'))
                                    <a href="{{ route('appointments.create') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-calendar-plus me-1"></i>New Appointment</a>
                                @endif
                                @if (auth()->user()->canAccessModule('prescriptions'))
                                    <a href="{{ route('prescriptions.create') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-earmark-medical me-1"></i>New Prescription</a>
                                @endif
                                @if (auth()->user()->canAccessModule('invoices'))
                                    <a href="{{ route('invoices.create') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-receipt me-1"></i>New Invoice</a>
                                @endif
                            </div>
                        </div>
                    </div>
                    </div>
                @endif
            </div>

            @if (auth()->user()->canAccessModule('prescriptions'))
                <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-semibold mb-0">Recent prescriptions</h6>
                                <a href="{{ route('prescriptions.index') }}" class="btn btn-link btn-sm text-primary p-0">View all</a>
                            </div>
                            <div id="recentPrescriptionsList" class="d-grid gap-2"></div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if (auth()->user()->isAdmin())
                <div class="row g-4 mb-4 d-none" id="adminSecondaryRow">
                <div class="col-xl-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body compact-detail-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-semibold mb-0">Revenue by department</h6>
                            </div>
                            <canvas id="departmentChart" class="short-chart" height="120"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body compact-detail-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-semibold mb-0">Top unpaid invoices</h6>
                            </div>
                            <div id="topUnpaidInvoicesList" class="d-grid gap-2 compact-list"></div>
                        </div>
                    </div>
                </div>
                </div>
            @endif
        </div>
    </div>

    <style>
        .chart-card-body {
            padding-bottom: 0.75rem;
        }

        .compact-chart {
            height: 180px !important;
            max-height: 180px;
        }

        .short-chart {
            height: 150px !important;
            max-height: 150px;
        }

        .compact-detail-card {
            padding-top: 0.9rem;
            padding-bottom: 0.9rem;
        }

        .compact-list .border {
            padding: 0.65rem 0.75rem !important;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const userRole = '{{ auth()->user()->role ?? 'User' }}';
            const dataUrl = '{{ route('dashboard.data') }}';
            const adminUrl = '{{ route('dashboard.admin') }}';
            const statsRow = document.getElementById('statsRow');
            const skeleton = document.getElementById('dashboard-skeleton');
            const content = document.getElementById('dashboard-content');
            const recentPatientsBody = document.getElementById('recentPatientsBody');
            const recentInvoicesList = document.getElementById('recentInvoicesList');
            const appointmentsList = document.getElementById('appointmentsList');
            const lowStockList = document.getElementById('lowStockList');
            const topUnpaidInvoicesList = document.getElementById('topUnpaidInvoicesList');
            const recentPrescriptionsList = document.getElementById('recentPrescriptionsList');

            let revenueChart = null;
            let patientChart = null;
            let departmentChart = null;

            const formatMoney = (value) => {
                const number = Number(value ?? 0);
                return new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'USD',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(number);
            };

            const formatStatusBadge = (status) => {
                const normalized = String(status || '').toUpperCase();
                let className = 'bg-secondary';

                if (normalized === 'PAID' || normalized === 'COMPLETED') {
                    className = 'bg-success';
                } else if (normalized === 'PARTIAL' || normalized === 'PENDING') {
                    className = 'bg-warning text-dark';
                } else if (normalized === 'UNPAID' || normalized === 'CANCELLED') {
                    className = 'bg-danger';
                }

                return `<span class="badge ${className}">${status || '—'}</span>`;
            };

            const emptyState = (message) => `<div class="text-muted small border rounded p-3 text-center">${message}</div>`;

            function renderStats(stats) {
                statsRow.innerHTML = stats.map((stat) => `
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge rounded-pill bg-${stat.tone}-subtle text-${stat.tone} p-2">
                                        <i class="bi ${stat.icon}"></i>
                                    </span>
                                    <small class="text-muted">${stat.trend || ''}</small>
                                </div>
                                <div class="text-muted small">${stat.title}</div>
                                <div class="fs-4 fw-bold mt-1">${stat.value}</div>
                            </div>
                        </div>
                    </div>
                `).join('');
            }

            function renderRecentPatients(items) {
                if (!items || !items.length) {
                    recentPatientsBody.innerHTML = `<tr><td colspan="5">${emptyState('No recent patients yet')}</td></tr>`;
                    return;
                }

                recentPatientsBody.innerHTML = items.map((patient) => `
                    <tr>
                        <td>${patient.name}</td>
                        <td>${patient.age ?? '—'}</td>
                        <td>${patient.gender ?? '—'}</td>
                        <td>${patient.registered_at}</td>
                        <td>${formatStatusBadge(patient.status)}</td>
                    </tr>
                `).join('');
            }

            function renderRecentInvoices(items) {
                if (!items || !items.length) {
                    recentInvoicesList.innerHTML = emptyState('No invoices yet');
                    return;
                }

                recentInvoicesList.innerHTML = items.map((invoice) => `
                    <div class="border rounded p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">${invoice.invoice_number}</span>
                            ${formatStatusBadge(invoice.status)}
                        </div>
                        <div class="small text-muted mt-1">${invoice.patient_name}</div>
                        ${invoice.amount ? `<div class="fw-semibold text-primary mt-2">${invoice.amount}</div>` : ''}
                    </div>
                `).join('');
            }

            function renderAppointments(items) {
                if (!items || !items.length) {
                    appointmentsList.innerHTML = emptyState('No appointments scheduled for today');
                    return;
                }

                appointmentsList.innerHTML = items.map((appointment) => `
                    <div class="border rounded p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">${appointment.time}</span>
                            ${formatStatusBadge(appointment.status)}
                        </div>
                        <div class="small text-muted mt-2">${appointment.patient}</div>
                        <div class="small text-muted">${appointment.doctor}</div>
                    </div>
                `).join('');
            }

            function renderLowStock(items) {
                if (!items || !items.length) {
                    lowStockList.innerHTML = emptyState('No low stock alerts');
                    return;
                }

                lowStockList.innerHTML = items.map((medicine) => `
                    <div class="border rounded p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">${medicine.name}</span>
                            <span class="badge bg-danger-subtle text-danger">${medicine.quantity} left</span>
                        </div>
                        <div class="small text-muted mt-1">Expiry: ${medicine.expiry_date}</div>
                    </div>
                `).join('');
            }

            function buildPatientsChart(data) {
                const labels = data.map((entry) => entry.label || '');
                const values = data.map((entry) => Number(entry.value || 0));

                if (patientChart) {
                    patientChart.destroy();
                }

                patientChart = new Chart(document.getElementById('patientsChart'), {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Patients',
                            data: values,
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.12)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                    },
                });
            }

            function renderPrescriptions(items) {
                if (!items || !items.length) {
                    recentPrescriptionsList.innerHTML = emptyState('No prescriptions yet');
                    return;
                }

                recentPrescriptionsList.innerHTML = items.map((item) => `
                    <div class="border rounded p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">${item.prescription_number}</span>
                            <span class="badge bg-light text-dark">${item.status}</span>
                        </div>
                        <div class="small text-muted mt-2">${item.patient}</div>
                        <div class="small text-muted">${item.doctor}</div>
                        <div class="small text-muted">${item.date}</div>
                    </div>
                `).join('');
            }

            function renderRevenueChart(data) {
                const labels = data.map((entry) => entry.label || '');
                const values = data.map((entry) => Number(entry.value || 0));

                if (revenueChart) {
                    revenueChart.destroy();
                }

                revenueChart = new Chart(document.getElementById('revenueChart'), {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Revenue',
                            data: values,
                            backgroundColor: '#0d6efd',
                            borderRadius: 6,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { ticks: { callback: (value) => '$' + Number(value).toLocaleString() } } },
                    },
                });
            }

            function renderDepartmentChart(data) {
                const labels = data.map((entry) => entry.label || '');
                const values = data.map((entry) => Number(entry.value || 0));

                if (departmentChart) {
                    departmentChart.destroy();
                }

                departmentChart = new Chart(document.getElementById('departmentChart'), {
                    type: 'doughnut',
                    data: {
                        labels,
                        datasets: [{
                            data: values,
                            backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6c757d', '#6610f2'],
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } },
                    },
                });
            }

            function renderTopUnpaidInvoices(items) {
                if (!items || !items.length) {
                    topUnpaidInvoicesList.innerHTML = emptyState('No unpaid invoices');
                    return;
                }

                topUnpaidInvoicesList.innerHTML = items.map((invoice) => `
                    <div class="border rounded p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">${invoice.invoice_number}</span>
                            <span class="text-danger fw-semibold">${invoice.amount_due}</span>
                        </div>
                        <div class="small text-muted mt-1">${invoice.patient_name}</div>
                        <div class="small text-muted">Due: ${invoice.due_date}</div>
                    </div>
                `).join('');
            }

            async function loadDashboard() {
                const response = await fetch(dataUrl, { headers: { Accept: 'application/json' } });
                const data = await response.json();

                renderStats(data.stats || []);
                if (recentPatientsBody) renderRecentPatients(data.recentPatients || []);
                if (recentInvoicesList) renderRecentInvoices(data.recentInvoices || []);
                if (appointmentsList) renderAppointments(data.todayAppointments || []);
                if (lowStockList) renderLowStock(data.lowStockMedicines || []);
                if (recentPrescriptionsList) renderPrescriptions(data.recentPrescriptions || []);
                if (document.getElementById('patientsChart')) {
                    buildPatientsChart(data.patientsPerMonth || []);
                }

                if (userRole.toLowerCase() === 'admin') {
                    const adminResponse = await fetch(adminUrl, { headers: { Accept: 'application/json' } });
                    if (adminResponse.ok) {
                        const admin = await adminResponse.json();
                        renderRevenueChart(admin.revenueSeries || []);
                        renderDepartmentChart(admin.departmentBreakdown || []);
                        renderTopUnpaidInvoices(admin.topUnpaidInvoices || []);
                        document.getElementById('adminSecondaryRow').classList.remove('d-none');
                    }
                }

                document.getElementById('dashboard-skeleton').classList.add('d-none');
                document.getElementById('dashboard-content').classList.remove('d-none');
            }

            loadDashboard().catch(() => {
                skeleton.classList.add('d-none');
                content.classList.remove('d-none');
                content.innerHTML = emptyState('Dashboard data could not be loaded. Please refresh the page.');
            });
        });
    </script>
</x-layout>
