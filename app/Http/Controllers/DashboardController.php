<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        return view('dashboard.index', [
            'user' => $user,
            'isAdmin' => $user?->isAdmin() ?? false,
            'role' => $user?->role ?? 'User',
        ]);
    }

    public function data(): JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json($this->buildSharedPayload($user));
    }

    public function admin(): JsonResponse
    {
        $user = auth()->user();

        abort_unless($user && $user->isAdmin(), 403, 'This dashboard section is restricted to administrators.');

        return response()->json($this->buildAdminPayload());
    }

    protected function buildSharedPayload(User $user): array
    {
        $baseStats = [];
        if ($user->canAccessModule('patients')) {
            $baseStats[] = ['title' => 'Total patients', 'value' => Patient::count(), 'icon' => 'bi-people', 'tone' => 'primary', 'trend' => '+12% vs last month'];
        }
        if ($user->canAccessModule('doctors')) {
            $baseStats[] = ['title' => 'Total doctors', 'value' => Doctor::count(), 'icon' => 'bi-person-badge', 'tone' => 'success', 'trend' => '+8% vs last month'];
        }
        if ($user->canAccessModule('departments')) {
            $baseStats[] = ['title' => 'Departments', 'value' => Department::count(), 'icon' => 'bi-building', 'tone' => 'info', 'trend' => '+2 this quarter'];
        }
        if ($user->canAccessModule('appointments')) {
            $baseStats[] = ['title' => "Today's appointments", 'value' => Appointment::whereDate('appointment_date', today())->count(), 'icon' => 'bi-calendar2-check', 'tone' => 'warning', 'trend' => 'Live schedule'];
        }
        if ($user->canAccessModule('prescriptions')) {
            $baseStats[] = ['title' => 'Prescriptions', 'value' => Prescription::count(), 'icon' => 'bi-file-earmark-medical', 'tone' => 'primary', 'trend' => '+6% vs last month'];
        }
        if ($user->canAccessModule('medicines')) {
            $baseStats[] = ['title' => 'Medicines', 'value' => Medicine::count(), 'icon' => 'bi-capsule', 'tone' => 'success', 'trend' => 'Stock overview'];
        }

        if ($user->isAdmin()) {
            $adminStats = [
                ['title' => 'Total revenue', 'value' => '$'.number_format((float) Payment::sum('amount'), 2), 'icon' => 'bi-coin', 'tone' => 'dark', 'trend' => 'All time'],
                ['title' => 'This month', 'value' => '$'.number_format((float) Payment::whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('amount'), 2), 'icon' => 'bi-calendar-month', 'tone' => 'primary', 'trend' => 'Current month'],
                ['title' => 'Total due', 'value' => '$'.number_format((float) Invoice::sum('due_amount'), 2), 'icon' => 'bi-exclamation-triangle', 'tone' => 'warning', 'trend' => 'Outstanding'],
                ['title' => 'Paid vs unpaid', 'value' => '$'.number_format((float) Invoice::sum('paid_amount'), 2).' / $'.number_format((float) Invoice::sum('due_amount'), 2), 'icon' => 'bi-bar-chart', 'tone' => 'success', 'trend' => 'Billed overview'],
            ];

            $baseStats = array_merge($baseStats, $adminStats);
        }

        return [
            'user' => [
                'name' => $user->name,
                'role' => $user->role,
                'isAdmin' => $user->isAdmin(),
            ],
            'stats' => $baseStats,
            'recentPatients' => $user->canAccessModule('patients') ? Patient::query()
                ->select(['id', 'first_name', 'last_name', 'gender', 'date_of_birth', 'status', 'created_at'])
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(function (Patient $patient): array {
                    return [
                        'id' => $patient->id,
                        'name' => trim($patient->first_name.' '.$patient->last_name),
                        'age' => $patient->date_of_birth ? Carbon::parse($patient->date_of_birth)->age : null,
                        'gender' => $patient->gender ?? '—',
                        'registered_at' => $patient->created_at?->format('d M Y') ?? '—',
                        'status' => $patient->status ?? 'Active',
                    ];
                })
                ->all() : [],
            'recentInvoices' => $user->canAccessModule('invoices') ? Invoice::query()
                ->with('patient')
                ->when(! $user->canAccessModule('pharmacy'), fn ($query) => $query->where('source', '!=', 'PHARMACY_POS'))
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(function (Invoice $invoice) use ($user): array {
                    $status = strtoupper((string) $invoice->status);
                    $amount = $invoice->grand_total ?? $invoice->total ?? 0;

                    return [
                        'invoice_number' => $invoice->invoice_number,
                        'patient_name' => $invoice->patient ? trim($invoice->patient->first_name.' '.$invoice->patient->last_name) : '—',
                        'status' => $status,
                        'status_class' => match ($status) {
                            'PAID' => 'bg-success',
                            'PARTIAL' => 'bg-warning text-dark',
                            default => 'bg-danger',
                        },
                        'amount' => $user->canSeeInvoiceAmounts() ? '$'.number_format((float) $amount, 2) : null,
                    ];
                })
                ->all() : [],
            'todayAppointments' => $user->canAccessModule('appointments') ? Appointment::query()
                ->with(['patient', 'doctor'])
                ->whereDate('appointment_date', today())
                ->orderBy('appointment_time')
                ->get()
                ->map(function (Appointment $appointment): array {
                    return [
                        'time' => $appointment->appointment_time ? Carbon::parse($appointment->appointment_time)->format('H:i') : '—',
                        'patient' => $appointment->patient ? trim($appointment->patient->first_name.' '.$appointment->patient->last_name) : '—',
                        'doctor' => $appointment->doctor ? 'Dr. '.trim($appointment->doctor->first_name.' '.$appointment->doctor->last_name) : '—',
                        'status' => $appointment->status ?? 'Scheduled',
                        'status_class' => match (strtoupper((string) ($appointment->status ?? 'Scheduled'))) {
                            'COMPLETED' => 'bg-success',
                            'PENDING' => 'bg-warning text-dark',
                            'CANCELLED' => 'bg-danger',
                            default => 'bg-primary',
                        },
                    ];
                })
                ->all() : [],
            'recentPrescriptions' => $user->canAccessModule('prescriptions') ? Prescription::query()
                ->with(['patient', 'doctor'])
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(function (Prescription $prescription): array {
                    return [
                        'prescription_number' => $prescription->prescription_number,
                        'patient' => $prescription->patient ? trim($prescription->patient->first_name.' '.$prescription->patient->last_name) : '—',
                        'doctor' => $prescription->doctor ? 'Dr. '.trim($prescription->doctor->first_name.' '.$prescription->doctor->last_name) : '—',
                        'date' => $prescription->prescription_date?->format('d M Y') ?? '—',
                        'status' => $prescription->status ?? 'Active',
                    ];
                })
                ->all() : [],
            'lowStockMedicines' => $user->canAccessModule('medicines') ? Medicine::query()
                ->where('quantity', '<=', 10)
                ->orderBy('quantity', 'asc')
                ->limit(5)
                ->get()
                ->map(fn (Medicine $medicine): array => [
                    'name' => $medicine->name,
                    'quantity' => (int) $medicine->quantity,
                    'expiry_date' => $medicine->expiry_date?->format('d M Y') ?? '—',
                    'status' => $medicine->status ?? 'Active',
                ])
                ->all() : [],
            'patientsPerMonth' => $user->canAccessModule('patients') ? Patient::query()
                ->selectRaw("strftime('%Y-%m', created_at) as month, COUNT(*) as total")
                ->whereRaw("created_at >= date('now', '-11 months')")
                ->groupBy(DB::raw("strftime('%Y-%m', created_at)"))
                ->orderBy('month')
                ->get()
                ->map(function ($row): array {
                    $month = Carbon::createFromFormat('Y-m', $row->month ?? now()->format('Y-m'));

                    return [
                        'label' => $month->format('M'),
                        'value' => (int) $row->total,
                    ];
                })
                ->all() : [],
        ];
    }

    protected function buildAdminPayload(): array
    {
        $revenueSeries = Payment::query()
            ->selectRaw("strftime('%Y-%m', payment_date) as month, SUM(amount) as total")
            ->whereNotNull('payment_date')
            ->groupBy(DB::raw("strftime('%Y-%m', payment_date)"))
            ->orderBy('month')
            ->limit(12)
            ->get()
            ->map(function ($row): array {
                $month = $row->month ? Carbon::createFromFormat('Y-m', $row->month) : now();

                return [
                    'label' => $month->format('M Y'),
                    'value' => (float) $row->total,
                ];
            })
            ->all();

        $departmentBreakdown = Invoice::query()
            ->with('appointment.department')
            ->select(['id', 'invoice_number', 'grand_total', 'appointment_id'])
            ->whereNotNull('appointment_id')
            ->get()
            ->groupBy(fn (Invoice $invoice): string => $invoice->appointment?->department?->name ?? 'General')
            ->map(function ($items, $department): array {
                return [
                    'label' => $department,
                    'value' => (float) $items->sum(fn (Invoice $invoice): float => (float) ($invoice->grand_total ?? 0)),
                ];
            })
            ->values()
            ->all();

        $topUnpaidInvoices = Invoice::query()
            ->with('patient')
            ->where('status', '!=', 'PAID')
            ->orderByDesc('due_amount')
            ->limit(5)
            ->get()
            ->map(function (Invoice $invoice): array {
                return [
                    'invoice_number' => $invoice->invoice_number,
                    'patient_name' => $invoice->patient ? trim($invoice->patient->first_name.' '.$invoice->patient->last_name) : '—',
                    'amount_due' => '$'.number_format((float) ($invoice->due_amount ?? 0), 2),
                    'due_date' => $invoice->invoice_date?->addDays(14)?->format('d M Y') ?? '—',
                ];
            })
            ->all();

        return [
            'summary' => [
                'totalRevenue' => '$'.number_format((float) Payment::sum('amount'), 2),
                'thisMonthRevenue' => '$'.number_format((float) Payment::whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('amount'), 2),
                'totalDue' => '$'.number_format((float) Invoice::sum('due_amount'), 2),
                'paidAmount' => '$'.number_format((float) Invoice::sum('paid_amount'), 2),
                'unpaidAmount' => '$'.number_format((float) Invoice::sum('due_amount'), 2),
                'totalUsers' => User::count(),
            ],
            'revenueSeries' => $revenueSeries,
            'departmentBreakdown' => $departmentBreakdown,
            'topUnpaidInvoices' => $topUnpaidInvoices,
        ];
    }
}
