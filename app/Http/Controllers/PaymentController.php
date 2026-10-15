<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends HospitalResourceController
{
    protected string $model = Payment::class;

    protected string $title = 'Payment';

    protected array $with = ['invoice.patient', 'patient', 'receivedBy'];

    protected array $rules = [
        'invoice_id' => ['required', 'exists:invoices,id'],
        'amount' => ['required', 'numeric', 'gt:0'],
        'payment_date' => ['required', 'date'],
        'payment_method' => ['required', 'string'],
        'reference' => ['nullable', 'string'],
        'received_by' => ['nullable', 'exists:users,id'],
        'notes' => ['nullable', 'string'],
    ];

    public function index(): View
    {
        $payments = Payment::query()
            ->with($this->with)
            ->when(! auth()->user()?->canAccessPharmacy(), function ($query): void {
                $query->whereHas('invoice', fn ($invoiceQuery) => $invoiceQuery->where('source', '!=', 'PHARMACY_POS'));
            })
            ->latest()
            ->paginate(12);

        return view('payments.index', [
            'records' => $payments,
            'title' => $this->title,
            'route' => $this->routeName(),
        ]);
    }

    public function show(string $record): View
    {
        $payment = $this->find($record);
        abort_unless($payment->invoice?->source !== 'PHARMACY_POS' || auth()->user()?->canAccessPharmacy(), 403);

        return parent::show($record);
    }

    protected function optionsFor(string $field): ?Collection
    {
        if ($field === 'invoice_id' && ! auth()->user()?->canAccessPharmacy()) {
            return Invoice::query()
                ->where('source', '!=', 'PHARMACY_POS')
                ->orderByDesc('invoice_date')
                ->pluck('invoice_number', 'id');
        }

        return parent::optionsFor($field);
    }

    protected function formData(): array
    {
        return [
            'invoiceAmountMap' => Invoice::query()
                ->when(! auth()->user()?->canAccessPharmacy(), fn ($query) => $query->where('source', '!=', 'PHARMACY_POS'))
                ->withSum('payments', 'amount')
                ->get(['id', 'invoice_number', 'grand_total'])
                ->mapWithKeys(fn (Invoice $invoice): array => [
                    $invoice->id => [
                        'invoice_number' => $invoice->invoice_number,
                        'total_amount' => (float) $invoice->grand_total,
                        'due_amount' => max(0, (float) $invoice->grand_total - (float) ($invoice->payments_sum_amount ?? 0)),
                    ],
                ]),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules);
        $validated['received_by'] = auth()->id();

        $invoice = Invoice::with('payments', 'patient')->findOrFail($validated['invoice_id']);
        abort_unless($invoice->source !== 'PHARMACY_POS' || auth()->user()?->canAccessPharmacy(), 403);
        if ($invoice->status === 'CANCELLED') {
            return back()->withErrors([
                'invoice_id' => 'Payments cannot be recorded for a cancelled invoice.',
            ])->withInput();
        }
        $validated['reference'] = filled($validated['reference'] ?? null) ? $validated['reference'] : $invoice->invoice_number;

        $maxPayment = InvoiceCalculationService::maximumPaymentAllowed($invoice->grand_total, $invoice->payments()->sum('amount'));

        if ($validated['amount'] > $maxPayment) {
            $extra = number_format((float) $validated['amount'] - (float) $maxPayment, 2);

            return back()->withErrors([
                'amount' => 'This amount is extra in payment. The payment exceeds the invoice balance by '.$extra.'.',
            ])->withInput();
        }

        $payment = DB::transaction(function () use ($validated, $invoice): Payment {
            $newPayment = Payment::create([
                'invoice_id' => $validated['invoice_id'],
                'patient_id' => $invoice->patient_id,
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? null,
                'received_by' => $validated['received_by'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $totalPaid = $invoice->payments()->sum('amount');
            $calculation = InvoiceCalculationService::calculate([], 0, 0, $totalPaid);
            $invoice->paid_amount = $totalPaid;
            $invoice->due_amount = max(0.0, $invoice->grand_total - $totalPaid);
            $invoice->status = InvoiceCalculationService::paymentStatusFor($totalPaid, $invoice->grand_total);
            $invoice->save();

            return $newPayment;
        });

        return redirect()->route('payments.show', $payment)->with('success', 'Payment recorded.');
    }

    public function update(Request $request, string $record): RedirectResponse
    {
        $validated = $request->validate($this->rules);
        $validated['received_by'] = auth()->id();

        $payment = $this->find($record);
        $invoice = $payment->invoice()->with('payments')->firstOrFail();
        abort_unless($invoice->source !== 'PHARMACY_POS' || auth()->user()?->canAccessPharmacy(), 403);
        if ((int) $validated['invoice_id'] !== (int) $payment->invoice_id) {
            return back()->withErrors([
                'invoice_id' => 'A payment cannot be reassigned to another invoice.',
            ])->withInput();
        }
        if ($invoice->status === 'CANCELLED') {
            return back()->withErrors([
                'invoice_id' => 'Payments on a cancelled invoice cannot be changed.',
            ])->withInput();
        }
        $validated['reference'] = filled($validated['reference'] ?? null) ? $validated['reference'] : $invoice->invoice_number;

        $totalPaid = $invoice->payments()->whereKeyNot($payment->id)->sum('amount') + $validated['amount'];
        $maxPayment = InvoiceCalculationService::maximumPaymentAllowed($invoice->grand_total, $invoice->payments()->whereKeyNot($payment->id)->sum('amount'));

        if ($validated['amount'] > $maxPayment + ($payment->amount ?? 0)) {
            $extra = number_format((float) $validated['amount'] - (float) ($maxPayment + ($payment->amount ?? 0)), 2);

            return back()->withErrors([
                'amount' => 'This amount is extra in payment. The payment exceeds the invoice balance by '.$extra.'.',
            ])->withInput();
        }

        $payment->fill([
            'invoice_id' => $validated['invoice_id'],
            'patient_id' => $invoice->patient_id,
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'],
            'payment_method' => $validated['payment_method'],
            'reference' => $validated['reference'] ?? null,
            'received_by' => $validated['received_by'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        DB::transaction(function () use ($payment, $invoice): void {
            $payment->save();
            $totalPaid = $invoice->payments()->sum('amount');
            $invoice->paid_amount = $totalPaid;
            $invoice->due_amount = max(0.0, $invoice->grand_total - $totalPaid);
            $invoice->status = InvoiceCalculationService::paymentStatusFor($totalPaid, $invoice->grand_total);
            $invoice->save();
        });

        return redirect()->route('payments.show', $payment)->with('success', 'Payment updated.');
    }

    public function destroy(string $record): RedirectResponse
    {
        $payment = $this->find($record);
        abort_unless($payment->invoice?->source !== 'PHARMACY_POS' || auth()->user()?->canAccessPharmacy(), 403);
        if ($payment->invoice?->status === 'CANCELLED') {
            return back()->withErrors([
                'payment' => 'Payments on a cancelled invoice cannot be removed.',
            ]);
        }

        DB::transaction(function () use ($payment): void {
            $invoice = $payment->invoice()->with('payments')->firstOrFail();
            $payment->delete();
            $totalPaid = $invoice->payments()->sum('amount');
            $invoice->paid_amount = $totalPaid;
            $invoice->due_amount = max(0.0, $invoice->grand_total - $totalPaid);
            $invoice->status = InvoiceCalculationService::paymentStatusFor($totalPaid, $invoice->grand_total);
            $invoice->save();
        });

        return redirect()->route('payments.index')->with('success', 'Payment removed.');
    }
}
