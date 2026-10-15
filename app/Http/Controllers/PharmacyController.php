<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelPharmacySaleRequest;
use App\Http\Requests\ReceivePharmacyStockRequest;
use App\Http\Requests\StorePharmacySaleRequest;
use App\Http\Requests\UpdatePharmacySaleRequest;
use App\Models\Invoice;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PharmacyRefund;
use App\Models\PharmacyStockBatch;
use App\Services\PharmacyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PharmacyController extends Controller
{
    public function __construct(private readonly PharmacyService $pharmacy) {}

    public function index(): View
    {
        return $this->renderPos();
    }

    public function edit(Invoice $invoice): View
    {
        abort_unless(
            $invoice->source === 'PHARMACY_POS'
                && $invoice->status === 'UNPAID'
                && (float) $invoice->paid_amount === 0.0
                && ! $invoice->payments()->exists(),
            403,
            'Only unpaid pharmacy sales with no payment transactions can be edited.',
        );

        return $this->renderPos($invoice);
    }

    private function renderPos(?Invoice $editingInvoice = null): View
    {
        $editingInvoice?->load('items.medicine');
        $sellableBatchFilter = fn ($query) => $query
            ->where('quantity_available', '>', 0)
            ->where(fn ($dateQuery) => $dateQuery->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', today()));
        $oldItemInput = old('items', $editingInvoice?->items
            ->map(fn ($item): array => ['medicine_id' => $item->medicine_id, 'quantity' => $item->quantity])
            ->all() ?? []);
        $oldItems = collect(is_array($oldItemInput) ? $oldItemInput : [])
            ->filter(fn (mixed $item): bool => is_array($item)
                && isset($item['medicine_id'], $item['quantity'])
                && is_numeric($item['medicine_id'])
                && filter_var($item['quantity'], FILTER_VALIDATE_INT) !== false);
        $oldMedicines = Medicine::query()
            ->whereKey($oldItems->pluck('medicine_id')->unique()->values())
            ->with(['stockBatches' => $sellableBatchFilter])
            ->get(['id', 'medicine_code', 'name', 'generic_name', 'category', 'dosage_form', 'strength', 'quantity', 'unit_price', 'expiry_date'])
            ->keyBy('id');
        $initialCart = $oldItems->map(function (array $item) use ($oldMedicines, $editingInvoice): ?array {
            /** @var Medicine|null $medicine */
            $medicine = $oldMedicines->get((int) $item['medicine_id']);

            if (! $medicine) {
                return null;
            }

            $batches = $medicine->stockBatches->map(fn (PharmacyStockBatch $batch): array => [
                'number' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date?->format('Y-m-d'),
                'quantity' => $batch->quantity_available,
            ]);

            if ($batches->isEmpty() && (int) $medicine->quantity > 0
                && (! $medicine->expiry_date || $medicine->expiry_date->greaterThanOrEqualTo(today()))) {
                $batches->push([
                    'number' => 'LEGACY-'.$medicine->id,
                    'expiry_date' => $medicine->expiry_date?->format('Y-m-d'),
                    'quantity' => (int) $medicine->quantity,
                ]);
            }

            $reservedQuantity = $editingInvoice?->items
                ->firstWhere('medicine_id', $medicine->id)?->quantity ?? 0;

            return [
                'id' => $medicine->id,
                'code' => $medicine->medicine_code,
                'name' => $medicine->name,
                'generic_name' => $medicine->generic_name,
                'category' => $medicine->category,
                'dosage_form' => $medicine->dosage_form,
                'strength' => $medicine->strength,
                'unit_price' => $medicine->unit_price,
                'stock' => (int) $batches->sum('quantity') + (int) $reservedQuantity,
                'batches' => $batches->all(),
                'quantity' => max(1, (int) $item['quantity']),
            ];
        })->filter()->values()->all();
        $oldPatientId = old('patient_id', $editingInvoice?->patient_id);
        $oldPatient = filter_var($oldPatientId, FILTER_VALIDATE_INT) !== false
            ? Patient::query()->find($oldPatientId)
            : null;

        return view('pharmacy.pos', [
            'transactionToken' => (string) Str::uuid(),
            'initialCart' => $initialCart,
            'oldPatient' => $oldPatient,
            'editingInvoice' => $editingInvoice,
            'reservedStock' => $editingInvoice?->items
                ->groupBy('medicine_id')
                ->map(fn ($items): int => (int) $items->sum('quantity'))
                ->all() ?? [],
            'categories' => Medicine::query()
                ->whereNotNull('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
            'activeMedicines' => Medicine::query()
                ->where('status', 'Active')
                ->orderBy('name')
                ->get(['id', 'name', 'medicine_code']),
            'lowStock' => Medicine::query()
                ->where('status', 'Active')
                ->withSum(['stockBatches as available_stock' => $sellableBatchFilter], 'quantity_available')
                ->orderBy('available_stock')
                ->limit(50)
                ->get(['id', 'name', 'medicine_code'])
                ->filter(fn (Medicine $medicine): bool => (int) ($medicine->available_stock ?? 0) <= 10)
                ->take(6),
            'expiringBatches' => PharmacyStockBatch::query()
                ->with('medicine:id,name,medicine_code')
                ->where('quantity_available', '>', 0)
                ->whereBetween('expiry_date', [today(), today()->addDays(90)])
                ->orderBy('expiry_date')
                ->limit(6)
                ->get(),
        ]);
    }

    public function medicines(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim((string) ($validated['q'] ?? ''));
        $sellableBatchFilter = fn ($query) => $query
            ->where('quantity_available', '>', 0)
            ->where(fn ($dateQuery) => $dateQuery->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', today()));

        $medicines = Medicine::query()
            ->where('status', 'Active')
            ->where(function ($stockQuery) use ($sellableBatchFilter): void {
                $stockQuery->whereHas('stockBatches', $sellableBatchFilter)
                    ->orWhere(function ($legacyQuery): void {
                        $legacyQuery->whereDoesntHave('stockBatches')
                            ->where('quantity', '>', 0)
                            ->where(fn ($dateQuery) => $dateQuery->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', today()));
                    });
            })
            ->with(['stockBatches' => function ($query) use ($sellableBatchFilter): void {
                $sellableBatchFilter($query);
                $query->orderByRaw('expiry_date IS NULL')
                    ->orderBy('expiry_date')
                    ->orderBy('id');
            }])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($match) use ($search): void {
                    $match->where('medicine_code', $search)
                        ->orWhere('name', 'like', '%'.$search.'%')
                        ->orWhere('generic_name', 'like', '%'.$search.'%');
                });
            })
            ->when($validated['category'] ?? null, fn ($query, string $category) => $query->where('category', $category))
            ->orderBy('name')
            ->limit(40)
            ->get(['id', 'medicine_code', 'name', 'generic_name', 'category', 'dosage_form', 'strength', 'quantity', 'unit_price', 'expiry_date']);

        return response()->json($medicines->map(function (Medicine $medicine): array {
            $batches = $medicine->stockBatches->map(fn (PharmacyStockBatch $batch): array => [
                'number' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date?->format('Y-m-d'),
                'quantity' => $batch->quantity_available,
            ]);

            if ($batches->isEmpty()) {
                $batches->push([
                    'number' => 'LEGACY-'.$medicine->id,
                    'expiry_date' => $medicine->expiry_date?->format('Y-m-d'),
                    'quantity' => (int) $medicine->quantity,
                ]);
            }

            return [
                'id' => $medicine->id,
                'code' => $medicine->medicine_code,
                'name' => $medicine->name,
                'generic_name' => $medicine->generic_name,
                'category' => $medicine->category,
                'dosage_form' => $medicine->dosage_form,
                'strength' => $medicine->strength,
                'unit_price' => $medicine->unit_price,
                'stock' => (int) $batches->sum('quantity'),
                'batches' => $batches->all(),
            ];
        }));
    }

    public function patients(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ]);
        $search = trim($validated['q']);
        $nameParts = preg_split('/\s+/', $search, 2) ?: [];

        $patients = Patient::query()
            ->where('status', 'Active')
            ->where(function ($query) use ($search, $nameParts): void {
                $query->where('patient_number', $search)
                    ->orWhere('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%');
                if (count($nameParts) === 2) {
                    $query->orWhere(function ($fullName) use ($nameParts): void {
                        $fullName->where('first_name', 'like', '%'.$nameParts[0].'%')
                            ->where('last_name', 'like', '%'.$nameParts[1].'%');
                    })->orWhere(function ($reverseName) use ($nameParts): void {
                        $reverseName->where('first_name', 'like', '%'.$nameParts[1].'%')
                            ->where('last_name', 'like', '%'.$nameParts[0].'%');
                    });
                }
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(20)
            ->get(['id', 'patient_number', 'first_name', 'last_name']);

        return response()->json($patients->map(fn (Patient $patient): array => [
            'id' => $patient->id,
            'number' => $patient->patient_number,
            'name' => trim($patient->first_name.' '.$patient->last_name),
        ]));
    }

    public function store(StorePharmacySaleRequest $request): RedirectResponse
    {
        $invoice = $this->pharmacy->completeSale($request->validated(), $request->user());

        return redirect()
            ->route('pharmacy.sales.show', $invoice)
            ->with('success', 'Pharmacy sale completed.');
    }

    public function update(UpdatePharmacySaleRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->pharmacy->updateSale($invoice, $request->validated(), $request->user());

        return redirect()
            ->route('pharmacy.sales.show', $invoice)
            ->with('success', 'Unpaid pharmacy sale updated and stock movements recorded.');
    }

    public function sales(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $sales = Invoice::query()
            ->with(['patient:id,patient_number,first_name,last_name', 'cashier:id,name', 'pharmacyRefund'])
            ->withCount('payments')
            ->where('source', 'PHARMACY_POS')
            ->when($validated['from'] ?? null, fn ($query, string $date) => $query->whereDate('invoice_date', '>=', $date))
            ->when($validated['to'] ?? null, fn ($query, string $date) => $query->whereDate('invoice_date', '<=', $date))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('pharmacy.sales', compact('sales'));
    }

    public function show(Invoice $invoice): View
    {
        abort_unless($invoice->source === 'PHARMACY_POS', 404);

        $invoice->load([
            'patient',
            'cashier',
            'items.medicine',
            'payments.receivedBy',
            'stockMovements.batch',
            'pharmacyRefund.refundedBy',
        ]);

        return view('pharmacy.sale', compact('invoice'));
    }

    public function receipt(Invoice $invoice): View
    {
        abort_unless($invoice->source === 'PHARMACY_POS', 404);

        $invoice->load(['patient', 'prescription', 'cashier', 'items.medicine', 'payments', 'stockMovements.batch', 'pharmacyRefund']);

        return view('pharmacy.receipt', compact('invoice'));
    }

    public function cancel(CancelPharmacySaleRequest $request, Invoice $invoice): RedirectResponse
    {
        $refund = $this->pharmacy->cancelSale($invoice, $request->user(), $request->validated('reason'));

        return redirect()
            ->route('pharmacy.sales.show', $invoice)
            ->with('success', 'Sale cancelled and '.$refund->amount.' refunded. Stock was returned to its original batches.');
    }

    public function receive(ReceivePharmacyStockRequest $request): RedirectResponse
    {
        $batch = $this->pharmacy->receiveBatch(
            (int) $request->validated('medicine_id'),
            $request->validated('batch_number'),
            (int) $request->validated('quantity'),
            $request->validated('expiry_date'),
            $request->user(),
            $request->validated('notes'),
        );

        return redirect()
            ->route('pharmacy.pos')
            ->with('success', 'Received '.$batch->quantity_received.' units in batch '.$batch->batch_number.'.');
    }

    public function dailyReport(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
        ]);
        $date = $validated['date'] ?? today()->toDateString();

        $sales = Invoice::query()
            ->with(['patient:id,first_name,last_name'])
            ->withSum(['payments as paid_today' => fn ($query) => $query->whereDate('payment_date', $date)], 'amount')
            ->where('source', 'PHARMACY_POS')
            ->whereDate('invoice_date', $date)
            ->latest('created_at')
            ->get();
        $payments = Payment::query()
            ->whereDate('payment_date', $date)
            ->whereHas('invoice', fn ($query) => $query->where('source', 'PHARMACY_POS'))
            ->sum('amount');
        $refunds = PharmacyRefund::query()
            ->whereDate('refunded_at', $date)
            ->whereHas('invoice', fn ($query) => $query->where('source', 'PHARMACY_POS'))
            ->sum('amount');

        return view('pharmacy.daily-report', [
            'date' => $date,
            'sales' => $sales,
            'grossSales' => $sales->where('status', '!=', 'CANCELLED')->sum('grand_total'),
            'payments' => $payments,
            'refunds' => $refunds,
        ]);
    }
}
