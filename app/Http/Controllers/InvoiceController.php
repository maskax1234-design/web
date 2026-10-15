<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Prescription;
use App\Services\InvoiceCalculationService;
use App\Services\PharmacyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvoiceController extends HospitalResourceController
{
    public function __construct(private readonly PharmacyService $pharmacy) {}

    protected string $model = Invoice::class;

    protected string $title = 'Invoice';

    protected array $with = ['patient', 'prescription', 'appointment', 'items.medicine', 'payments'];

    protected array $rules = [
        'patient_id' => ['nullable', 'exists:patients,id'],
        'prescription_id' => ['required', 'exists:prescriptions,id'],
        'appointment_id' => ['nullable', 'exists:appointments,id'],
        'invoice_date' => ['required', 'date'],
        'discount' => ['nullable', 'numeric', 'min:0'],
        'tax_percent' => ['nullable', 'numeric', 'min:0'],
        'notes' => ['nullable', 'string'],
        'invoice_items' => ['nullable', 'array'],
    ];

    public function index(): View
    {
        return view('invoices.index', [
            'records' => Invoice::query()
                ->with($this->with)
                ->where('source', '!=', 'PHARMACY_POS')
                ->latest()
                ->paginate(12),
            'title' => $this->title,
            'route' => $this->routeName(),
        ]);
    }

    protected function formData(): array
    {
        return [
            'prescriptionMap' => Prescription::query()
                ->with('medicines:id,unit_price')
                ->get(['id', 'patient_id', 'appointment_id'])
                ->mapWithKeys(function (Prescription $prescription): array {
                    $subtotal = InvoiceCalculationService::calculate(
                        $prescription->medicines->map(fn ($medicine): array => [
                            'quantity' => (float) $medicine->pivot->quantity,
                            'unit_price' => (float) $medicine->unit_price,
                        ])->all()
                    )['subtotal'];

                    return [
                        $prescription->id => [
                            'patient_id' => $prescription->patient_id,
                            'appointment_id' => $prescription->appointment_id,
                            'subtotal' => $subtotal,
                        ],
                    ];
                }),
        ];
    }

    public function print(Invoice $invoice): View
    {
        abort_unless($invoice->source !== 'PHARMACY_POS' || auth()->user()?->canAccessPharmacy(), 403);

        $invoice->load([
            'patient',
            'prescription.patient',
            'prescription.doctor',
            'prescription.medicines',
            'appointment.patient',
            'appointment.doctor',
            'items.medicine',
            'payments',
        ]);

        return view('invoices.print', [
            'invoice' => $invoice,
        ]);
    }

    public function show(string $record): View
    {
        $invoice = Invoice::query()->findOrFail($record);
        abort_unless($invoice->source !== 'PHARMACY_POS' || auth()->user()?->canAccessPharmacy(), 403);

        return parent::show($record);
    }

    public function edit(string $record): View
    {
        abort_if(Invoice::query()->whereKey($record)->value('source') === 'PHARMACY_POS', 403, 'Pharmacy POS sales must be managed through the pharmacy sales screen.');

        return parent::edit($record);
    }

    public function destroy(string $record): RedirectResponse
    {
        abort_if(
            in_array(Invoice::query()->whereKey($record)->value('source'), ['PHARMACY_POS', 'PRESCRIPTION'], true),
            403,
            'Invoices with stock movements cannot be deleted.',
        );

        return parent::destroy($record);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());
        $this->ensurePrescriptionIsAvailable($validated['prescription_id'] ?? null);

        $prescription = isset($validated['prescription_id']) ? Prescription::with('medicines')->findOrFail($validated['prescription_id']) : null;

        if ($prescription) {
            $validated['patient_id'] = $prescription->patient_id;
            $validated['appointment_id'] = $prescription->appointment_id;
        }

        $items = $this->resolveInvoiceItems($validated, $prescription);
        $calculation = InvoiceCalculationService::calculate(
            $items,
            $validated['discount'] ?? 0,
            $validated['tax_percent'] ?? 0,
            0,
        );

        if (($validated['discount'] ?? 0) > $calculation['subtotal']) {
            return back()->withErrors([
                'discount' => 'Discount cannot be greater than the subtotal.',
            ])->withInput();
        }

        $user = $request->user();
        $invoice = DB::transaction(function () use ($validated, $items, $calculation, $user): Invoice {
            $invoice = Invoice::create([
                'patient_id' => $validated['patient_id'],
                'prescription_id' => $validated['prescription_id'] ?? null,
                'appointment_id' => $validated['appointment_id'] ?? null,
                'invoice_date' => $validated['invoice_date'],
                'subtotal' => $calculation['subtotal'],
                'discount' => $calculation['discount'],
                'tax' => $calculation['tax_amount'],
                'tax_percent' => $calculation['tax_percent'],
                'tax_amount' => $calculation['tax_amount'],
                'total' => $calculation['grand_total'],
                'grand_total' => $calculation['grand_total'],
                'paid_amount' => 0,
                'due_amount' => $calculation['grand_total'],
                'status' => $calculation['status'],
                'notes' => $validated['notes'] ?? null,
                'source' => 'PRESCRIPTION',
            ]);

            foreach ($items as $item) {
                $invoiceItem = $invoice->items()->create([
                    'medicine_id' => $item['medicine_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
                $this->pharmacy->deductPrescriptionItem($invoiceItem, $user);
            }

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created.');
    }

    public function update(Request $request, string $record): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        $invoice = $this->find($record);
        abort_if($invoice->source === 'PHARMACY_POS', 403, 'Pharmacy POS sales cannot be edited through the standard invoice form.');
        $this->ensurePrescriptionIsAvailable($validated['prescription_id'] ?? null, $invoice);

        $prescription = isset($validated['prescription_id']) && $validated['prescription_id'] ? Prescription::with('medicines')->findOrFail($validated['prescription_id']) : null;

        if ($prescription) {
            $validated['patient_id'] = $prescription->patient_id;
            $validated['appointment_id'] = $prescription->appointment_id;
        }

        $items = $this->resolveInvoiceItems($validated, $prescription, $invoice);
        $calculation = InvoiceCalculationService::calculate(
            $items,
            $validated['discount'] ?? 0,
            $validated['tax_percent'] ?? 0,
            (float) ($invoice->payments()->sum('amount') ?? 0),
        );

        if (($validated['discount'] ?? 0) > $calculation['subtotal']) {
            return back()->withErrors([
                'discount' => 'Discount cannot be greater than the subtotal.',
            ])->withInput();
        }

        $user = $request->user();
        $updated = DB::transaction(function () use ($invoice, $validated, $items, $calculation, $user): Invoice {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($invoice->source === 'PRESCRIPTION') {
                $this->pharmacy->reversePrescriptionDispense($invoice, $user);
            }

            $invoice->fill([
                'patient_id' => $validated['patient_id'],
                'prescription_id' => $validated['prescription_id'] ?? null,
                'appointment_id' => $validated['appointment_id'] ?? null,
                'invoice_date' => $validated['invoice_date'],
                'subtotal' => $calculation['subtotal'],
                'discount' => $calculation['discount'],
                'tax' => $calculation['tax_amount'],
                'tax_percent' => $calculation['tax_percent'],
                'tax_amount' => $calculation['tax_amount'],
                'total' => $calculation['grand_total'],
                'grand_total' => $calculation['grand_total'],
                'paid_amount' => $calculation['paid_amount'],
                'due_amount' => $calculation['due_amount'],
                'status' => $calculation['status'],
                'notes' => $validated['notes'] ?? null,
                'source' => 'PRESCRIPTION',
            ]);
            $invoice->save();

            $invoice->items()->delete();

            foreach ($items as $item) {
                $invoiceItem = $invoice->items()->create([
                    'medicine_id' => $item['medicine_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
                $this->pharmacy->deductPrescriptionItem($invoiceItem, $user);
            }

            return $invoice->fresh(['items.medicine', 'payments']);
        });

        return redirect()->route('invoices.show', $updated)->with('success', 'Invoice updated.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validationRules(): array
    {
        return array_merge($this->rules, [
            'invoice_date' => ['required', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax_percent' => ['nullable', 'numeric', 'min:0'],
            'invoice_items' => ['nullable', 'array'],
            'invoice_items.*.medicine_id' => ['required', 'exists:medicines,id'],
            'invoice_items.*.quantity' => ['required', 'integer', 'min:1'],
            'invoice_items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, array{medicine_id: int, quantity: int, unit_price: float}>
     */
    private function ensurePrescriptionIsAvailable(?int $prescriptionId, ?Invoice $currentInvoice = null): void
    {
        if (! $prescriptionId) {
            return;
        }

        $query = Invoice::query()->where('prescription_id', $prescriptionId);

        if ($currentInvoice) {
            $query->whereKeyNot($currentInvoice->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'prescription_id' => 'This prescription is already used in another invoice. One prescription can be linked to only one invoice.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, array{medicine_id: int, quantity: int, unit_price: float}>
     */
    private function resolveInvoiceItems(array $validated, ?Prescription $prescription = null, ?Invoice $invoice = null): array
    {
        $items = [];

        if ($prescription) {
            foreach ($prescription->medicines as $medicine) {
                $pivot = $medicine->pivot ?? null;
                $quantity = (int) ($pivot->quantity ?? 1);
                $unitPrice = (float) ($medicine->unit_price ?? 0);

                $items[] = [
                    'medicine_id' => (int) $medicine->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ];
            }

            return $items;
        }

        foreach ($validated['invoice_items'] ?? [] as $item) {
            $items[] = [
                'medicine_id' => (int) $item['medicine_id'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
            ];
        }

        if (empty($items) && $invoice) {
            foreach ($invoice->items as $item) {
                $items[] = [
                    'medicine_id' => (int) $item->medicine_id,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                ];
            }
        }

        return $items;
    }
}
