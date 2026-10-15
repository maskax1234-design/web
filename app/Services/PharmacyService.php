<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Medicine;
use App\Models\Payment;
use App\Models\PharmacyRefund;
use App\Models\PharmacyStockBatch;
use App\Models\PharmacyStockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PharmacyService
{
    private const MAX_AMOUNT_CENTS = 999999999999;

    /**
     * @param  array{patient_id?: int|null, items: array<int, array{medicine_id: int, quantity: int}>, discount?: numeric-string|int|float|null, tax_percent?: numeric-string|int|float|null, amount_paid?: numeric-string|int|float|null, payment_method?: string|null, pos_token: string}  $data
     */
    public function completeSale(array $data, User $cashier): Invoice
    {
        $existingInvoice = Invoice::query()
            ->where('pos_token', $data['pos_token'])
            ->first();

        if ($existingInvoice) {
            if ($existingInvoice->cashier_id !== $cashier->id) {
                throw ValidationException::withMessages([
                    'pos_token' => 'This transaction token has already been used.',
                ]);
            }

            return $existingInvoice;
        }

        return DB::transaction(function () use ($data, $cashier): Invoice {
            $medicineIds = collect($data['items'])
                ->pluck('medicine_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->sort()
                ->values();

            $medicines = Medicine::query()
                ->whereKey($medicineIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($medicines->count() !== $medicineIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected medicines are no longer available.',
                ]);
            }

            $items = [];

            foreach ($data['items'] as $item) {
                /** @var Medicine $medicine */
                $medicine = $medicines->get((int) $item['medicine_id']);

                if (strtolower((string) $medicine->status) !== 'active') {
                    throw ValidationException::withMessages([
                        'items' => $medicine->name.' is not active for sale.',
                    ]);
                }

                $quantity = (int) $item['quantity'];
                $batches = $this->sellableBatches($medicine);
                $available = (int) $batches->sum('quantity_available');

                if ($quantity > $available) {
                    throw ValidationException::withMessages([
                        'items' => 'Insufficient stock for '.$medicine->name.'. Available: '.$available.'.',
                    ]);
                }

                $priceCents = $this->toCents((string) $medicine->unit_price);
                $lineCents = $priceCents * $quantity;

                if ($lineCents > self::MAX_AMOUNT_CENTS) {
                    throw ValidationException::withMessages([
                        'items' => 'The sale amount exceeds the supported invoice limit.',
                    ]);
                }

                $items[] = [
                    'medicine' => $medicine,
                    'quantity' => $quantity,
                    'unit_price_cents' => $priceCents,
                    'line_total_cents' => $lineCents,
                    'batches' => $batches,
                ];
            }

            $subtotalCents = array_sum(array_column($items, 'line_total_cents'));

            if ($subtotalCents > self::MAX_AMOUNT_CENTS) {
                throw ValidationException::withMessages([
                    'items' => 'The sale amount exceeds the supported invoice limit.',
                ]);
            }

            $discountCents = $this->toCents((string) ($data['discount'] ?? '0'));
            $taxBasisPoints = $this->toCents((string) ($data['tax_percent'] ?? '0'));

            if ($discountCents > $subtotalCents) {
                throw ValidationException::withMessages([
                    'discount' => 'Discount cannot be greater than the subtotal.',
                ]);
            }

            $taxableCents = $subtotalCents - $discountCents;
            $taxCents = intdiv(($taxableCents * $taxBasisPoints) + 5000, 10000);
            $grandTotalCents = $taxableCents + $taxCents;
            $paidCents = $this->toCents((string) ($data['amount_paid'] ?? '0'));

            if ($grandTotalCents > self::MAX_AMOUNT_CENTS || $paidCents > $grandTotalCents) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Amount paid cannot be greater than the invoice total.',
                ]);
            }

            $invoice = Invoice::create([
                'patient_id' => $data['patient_id'] ?? null,
                'invoice_date' => today(),
                'subtotal' => $this->fromCents($subtotalCents),
                'discount' => $this->fromCents($discountCents),
                'tax' => $this->fromCents($taxCents),
                'tax_percent' => $this->fromCents($taxBasisPoints),
                'tax_amount' => $this->fromCents($taxCents),
                'total' => $this->fromCents($grandTotalCents),
                'grand_total' => $this->fromCents($grandTotalCents),
                'paid_amount' => $this->fromCents($paidCents),
                'due_amount' => $this->fromCents($grandTotalCents - $paidCents),
                'status' => $paidCents === 0 ? 'UNPAID' : ($paidCents < $grandTotalCents ? 'PARTIAL' : 'PAID'),
                'source' => 'PHARMACY_POS',
                'cashier_id' => $cashier->id,
                'pos_token' => $data['pos_token'],
            ]);

            foreach ($items as $item) {
                /** @var Medicine $medicine */
                $medicine = $item['medicine'];
                /** @var Collection<int, PharmacyStockBatch> $batches */
                $batches = $item['batches'];

                $invoiceItem = $invoice->items()->create([
                    'medicine_id' => $medicine->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $this->fromCents($item['unit_price_cents']),
                    'total' => $this->fromCents($item['line_total_cents']),
                ]);

                $remaining = $item['quantity'];

                foreach ($batches as $batch) {
                    if ($remaining === 0) {
                        break;
                    }

                    $deducted = min($remaining, (int) $batch->quantity_available);
                    $batch->quantity_available -= $deducted;
                    $batch->save();

                    PharmacyStockMovement::create([
                        'medicine_id' => $medicine->id,
                        'batch_id' => $batch->id,
                        'invoice_id' => $invoice->id,
                        'invoice_item_id' => $invoiceItem->id,
                        'user_id' => $cashier->id,
                        'movement_type' => 'SALE',
                        'quantity' => $deducted,
                        'notes' => 'Pharmacy POS sale '.$invoice->invoice_number,
                    ]);

                    $remaining -= $deducted;
                }

                $medicine->quantity -= $item['quantity'];
                $medicine->save();
            }

            if ($paidCents > 0) {
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'patient_id' => $invoice->patient_id,
                    'amount' => $this->fromCents($paidCents),
                    'payment_date' => today(),
                    'payment_method' => $data['payment_method'],
                    'reference' => $invoice->invoice_number,
                    'received_by' => $cashier->id,
                ]);
            }

            return $invoice->load(['patient', 'items.medicine', 'payments']);
        }, 3);
    }

    /**
     * @param  array{patient_id?: int|null, items: array<int, array{medicine_id: int, quantity: int}>, discount?: numeric-string|int|float|null, tax_percent?: numeric-string|int|float|null}  $data
     */
    public function updateSale(Invoice $invoice, array $data, User $user): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $user): Invoice {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($lockedInvoice->source !== 'PHARMACY_POS'
                || $lockedInvoice->status !== 'UNPAID'
                || (float) $lockedInvoice->paid_amount > 0
                || $lockedInvoice->payments()->exists()) {
                throw ValidationException::withMessages([
                    'invoice' => 'Only unpaid pharmacy sales with no payment transactions can be edited.',
                ]);
            }

            $originalMovements = PharmacyStockMovement::query()
                ->where('invoice_id', $lockedInvoice->id)
                ->where('movement_type', 'SALE')
                ->whereNull('reversed_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $medicineIds = collect($data['items'])
                ->pluck('medicine_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->merge($originalMovements->pluck('medicine_id')->map(fn (mixed $id): int => (int) $id))
                ->unique()
                ->sort()
                ->values();
            $medicines = Medicine::query()
                ->whereKey($medicineIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $requestedIds = collect($data['items'])->pluck('medicine_id')->map(fn (mixed $id): int => (int) $id)->unique();

            if ($medicines->count() !== $medicineIds->count() || $requestedIds->diff($medicines->keys())->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected medicines are no longer available.',
                ]);
            }

            $batches = PharmacyStockBatch::query()
                ->whereKey($originalMovements->pluck('batch_id')->unique()->sort()->values())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($originalMovements as $movement) {
                $batch = $batches->get($movement->batch_id);
                $medicine = $medicines->get($movement->medicine_id);

                if (! $batch || ! $medicine) {
                    throw ValidationException::withMessages([
                        'items' => 'The original sale inventory could not be reconciled safely.',
                    ]);
                }

                $batch->quantity_available += $movement->quantity;
                $batch->save();
                $medicine->quantity += $movement->quantity;
                $medicine->save();

                $movement->reversed_at = now();
                $movement->save();

                PharmacyStockMovement::create([
                    'medicine_id' => $medicine->id,
                    'batch_id' => $batch->id,
                    'invoice_id' => $lockedInvoice->id,
                    'invoice_item_id' => $movement->invoice_item_id,
                    'user_id' => $user->id,
                    'movement_type' => 'EDIT_RETURN',
                    'quantity' => $movement->quantity,
                    'notes' => 'Stock returned while editing sale '.$lockedInvoice->invoice_number,
                ]);
            }

            $items = [];
            foreach ($data['items'] as $item) {
                /** @var Medicine $medicine */
                $medicine = $medicines->get((int) $item['medicine_id']);

                if (strtolower((string) $medicine->status) !== 'active') {
                    throw ValidationException::withMessages([
                        'items' => $medicine->name.' is not active for sale.',
                    ]);
                }

                $quantity = (int) $item['quantity'];
                $sellableBatches = $this->sellableBatches($medicine);
                $available = (int) $sellableBatches->sum('quantity_available');

                if ($quantity > $available) {
                    throw ValidationException::withMessages([
                        'items' => 'Insufficient stock for '.$medicine->name.'. Available: '.$available.'.',
                    ]);
                }

                $priceCents = $this->toCents((string) $medicine->unit_price);
                $lineCents = $priceCents * $quantity;

                if ($lineCents > self::MAX_AMOUNT_CENTS) {
                    throw ValidationException::withMessages([
                        'items' => 'The sale amount exceeds the supported invoice limit.',
                    ]);
                }

                $items[] = [
                    'medicine' => $medicine,
                    'quantity' => $quantity,
                    'unit_price_cents' => $priceCents,
                    'line_total_cents' => $lineCents,
                    'batches' => $sellableBatches,
                ];
            }

            $subtotalCents = array_sum(array_column($items, 'line_total_cents'));
            $discountCents = $this->toCents((string) ($data['discount'] ?? '0'));
            $taxBasisPoints = $this->toCents((string) ($data['tax_percent'] ?? '0'));

            if ($subtotalCents > self::MAX_AMOUNT_CENTS) {
                throw ValidationException::withMessages([
                    'items' => 'The sale amount exceeds the supported invoice limit.',
                ]);
            }

            if ($discountCents > $subtotalCents) {
                throw ValidationException::withMessages([
                    'discount' => 'Discount cannot be greater than the subtotal.',
                ]);
            }

            $taxCents = intdiv((($subtotalCents - $discountCents) * $taxBasisPoints) + 5000, 10000);
            $grandTotalCents = $subtotalCents - $discountCents + $taxCents;

            if ($grandTotalCents > self::MAX_AMOUNT_CENTS) {
                throw ValidationException::withMessages([
                    'discount' => 'The sale amount exceeds the supported invoice limit.',
                ]);
            }

            $lockedInvoice->items()->delete();
            $lockedInvoice->patient_id = $data['patient_id'] ?? null;
            $lockedInvoice->subtotal = $this->fromCents($subtotalCents);
            $lockedInvoice->discount = $this->fromCents($discountCents);
            $lockedInvoice->tax = $this->fromCents($taxCents);
            $lockedInvoice->tax_percent = $this->fromCents($taxBasisPoints);
            $lockedInvoice->tax_amount = $this->fromCents($taxCents);
            $lockedInvoice->total = $this->fromCents($grandTotalCents);
            $lockedInvoice->grand_total = $this->fromCents($grandTotalCents);
            $lockedInvoice->paid_amount = '0.00';
            $lockedInvoice->due_amount = $this->fromCents($grandTotalCents);
            $lockedInvoice->status = 'UNPAID';
            $lockedInvoice->save();

            foreach ($items as $item) {
                /** @var Medicine $medicine */
                $medicine = $item['medicine'];
                $invoiceItem = $lockedInvoice->items()->create([
                    'medicine_id' => $medicine->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $this->fromCents($item['unit_price_cents']),
                    'total' => $this->fromCents($item['line_total_cents']),
                ]);

                $remaining = $item['quantity'];
                foreach ($item['batches'] as $batch) {
                    if ($remaining === 0) {
                        break;
                    }

                    $deducted = min($remaining, (int) $batch->quantity_available);
                    $batch->quantity_available -= $deducted;
                    $batch->save();

                    PharmacyStockMovement::create([
                        'medicine_id' => $medicine->id,
                        'batch_id' => $batch->id,
                        'invoice_id' => $lockedInvoice->id,
                        'invoice_item_id' => $invoiceItem->id,
                        'user_id' => $user->id,
                        'movement_type' => 'SALE',
                        'quantity' => $deducted,
                        'notes' => 'Pharmacy POS sale edit '.$lockedInvoice->invoice_number,
                    ]);

                    $remaining -= $deducted;
                }

                $medicine->quantity -= $item['quantity'];
                $medicine->save();
            }

            return $lockedInvoice->load(['patient', 'items.medicine', 'payments']);
        }, 3);
    }

    public function receiveBatch(
        int $medicineId,
        string $batchNumber,
        int $quantity,
        string $expiryDate,
        User $receivedBy,
        ?string $notes = null,
    ): PharmacyStockBatch {
        return DB::transaction(function () use ($medicineId, $batchNumber, $quantity, $expiryDate, $receivedBy, $notes): PharmacyStockBatch {
            $medicine = Medicine::query()
                ->lockForUpdate()
                ->findOrFail($medicineId);

            $this->ensureOpeningBatch($medicine);

            if ($medicine->stockBatches()->where('batch_number', $batchNumber)->exists()) {
                throw ValidationException::withMessages([
                    'batch_number' => 'This batch number already exists for the selected medicine.',
                ]);
            }

            if ((int) $medicine->quantity + $quantity > 4294967295) {
                throw ValidationException::withMessages([
                    'quantity' => 'The stock quantity exceeds the supported limit.',
                ]);
            }

            $batch = $medicine->stockBatches()->create([
                'batch_number' => $batchNumber,
                'quantity_received' => $quantity,
                'quantity_available' => $quantity,
                'expiry_date' => $expiryDate,
                'received_by' => $receivedBy->id,
                'notes' => $notes,
            ]);

            PharmacyStockMovement::create([
                'medicine_id' => $medicine->id,
                'batch_id' => $batch->id,
                'user_id' => $receivedBy->id,
                'movement_type' => 'RECEIPT',
                'quantity' => $quantity,
                'notes' => $notes ?: 'Stock batch received.',
            ]);

            $medicine->quantity += $quantity;
            $medicine->save();

            return $batch;
        }, 3);
    }

    public function deductPrescriptionItem(InvoiceItem $item, User $user): void
    {
        DB::transaction(function () use ($item, $user): void {
            $medicine = Medicine::query()->lockForUpdate()->findOrFail($item->medicine_id);

            if (strtolower((string) $medicine->status) !== 'active') {
                throw ValidationException::withMessages([
                    'prescription_id' => $medicine->name.' is not active for sale.',
                ]);
            }

            $batches = $this->sellableBatches($medicine);
            $remaining = (int) $item->quantity;
            $available = (int) $batches->sum('quantity_available');

            if ($remaining > $available) {
                throw ValidationException::withMessages([
                    'prescription_id' => 'Insufficient stock for '.$medicine->name.'. Available: '.$available.'.',
                ]);
            }

            foreach ($batches as $batch) {
                if ($remaining === 0) {
                    break;
                }

                $deducted = min($remaining, (int) $batch->quantity_available);
                $batch->quantity_available -= $deducted;
                $batch->save();

                PharmacyStockMovement::create([
                    'medicine_id' => $medicine->id,
                    'batch_id' => $batch->id,
                    'invoice_id' => $item->invoice_id,
                    'invoice_item_id' => $item->id,
                    'user_id' => $user->id,
                    'movement_type' => 'DISPENSE',
                    'quantity' => $deducted,
                    'notes' => 'Prescription invoice stock dispense.',
                ]);

                $remaining -= $deducted;
            }

            $medicine->quantity -= (int) $item->quantity;
            $medicine->save();
        }, 3);
    }

    public function reversePrescriptionDispense(Invoice $invoice, User $user): void
    {
        DB::transaction(function () use ($invoice, $user): void {
            $movements = PharmacyStockMovement::query()
                ->where('invoice_id', $invoice->id)
                ->where('movement_type', 'DISPENSE')
                ->whereNull('reversed_at')
                ->orderBy('id')
                ->get();

            $medicines = Medicine::query()
                ->whereKey($movements->pluck('medicine_id')->unique()->sort()->values())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $batches = PharmacyStockBatch::query()
                ->whereKey($movements->pluck('batch_id')->unique()->sort()->values())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($movements as $movement) {
                /** @var PharmacyStockBatch $batch */
                $batch = $batches->get($movement->batch_id);
                /** @var Medicine $medicine */
                $medicine = $medicines->get($movement->medicine_id);
                $batch->quantity_available += $movement->quantity;
                $batch->save();
                $medicine->quantity += $movement->quantity;
                $medicine->save();

                $movement->reversed_at = now();
                $movement->save();

                PharmacyStockMovement::create([
                    'medicine_id' => $medicine->id,
                    'batch_id' => $batch->id,
                    'invoice_id' => $invoice->id,
                    'invoice_item_id' => $movement->invoice_item_id,
                    'user_id' => $user->id,
                    'movement_type' => 'DISPENSE_RETURN',
                    'quantity' => $movement->quantity,
                    'notes' => 'Reversed prescription invoice stock dispense.',
                ]);
            }
        }, 3);
    }

    public function adjustQuantity(Medicine $medicine, int $targetQuantity, User $user): void
    {
        DB::transaction(function () use ($medicine, $targetQuantity, $user): void {
            $lockedMedicine = Medicine::query()->lockForUpdate()->findOrFail($medicine->id);
            $this->ensureOpeningBatch($lockedMedicine);
            $currentQuantity = (int) $lockedMedicine->stockBatches()->sum('quantity_available');

            if ($targetQuantity === $currentQuantity) {
                $lockedMedicine->quantity = $currentQuantity;
                $lockedMedicine->save();

                return;
            }

            if ($targetQuantity > $currentQuantity) {
                $increase = $targetQuantity - $currentQuantity;
                $batch = $lockedMedicine->stockBatches()->create([
                    'batch_number' => 'ADJ-'.now()->format('YmdHis').'-'.str()->random(5),
                    'quantity_received' => $increase,
                    'quantity_available' => $increase,
                    'expiry_date' => $lockedMedicine->expiry_date?->toDateString(),
                    'received_by' => $user->id,
                    'notes' => 'Opening medicine form quantity increase.',
                ]);

                PharmacyStockMovement::create([
                    'medicine_id' => $lockedMedicine->id,
                    'batch_id' => $batch->id,
                    'user_id' => $user->id,
                    'movement_type' => 'ADJUSTMENT_IN',
                    'quantity' => $increase,
                    'notes' => 'Quantity adjusted through the medicine form.',
                ]);
            } else {
                $decrease = $currentQuantity - $targetQuantity;
                $batches = $lockedMedicine->stockBatches()
                    ->where('quantity_available', '>', 0)
                    ->orderByRaw('expiry_date IS NULL')
                    ->orderBy('expiry_date')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                foreach ($batches as $batch) {
                    if ($decrease === 0) {
                        break;
                    }

                    $adjusted = min($decrease, (int) $batch->quantity_available);
                    $batch->quantity_available -= $adjusted;
                    $batch->save();

                    PharmacyStockMovement::create([
                        'medicine_id' => $lockedMedicine->id,
                        'batch_id' => $batch->id,
                        'user_id' => $user->id,
                        'movement_type' => 'ADJUSTMENT_OUT',
                        'quantity' => $adjusted,
                        'notes' => 'Quantity adjusted through the medicine form.',
                    ]);

                    $decrease -= $adjusted;
                }
            }

            $lockedMedicine->quantity = $targetQuantity;
            $lockedMedicine->save();
        }, 3);
    }

    public function cancelSale(Invoice $invoice, User $user, string $reason): PharmacyRefund
    {
        return DB::transaction(function () use ($invoice, $user, $reason): PharmacyRefund {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($lockedInvoice->source !== 'PHARMACY_POS') {
                throw ValidationException::withMessages([
                    'invoice' => 'Only pharmacy POS sales can be cancelled here.',
                ]);
            }

            if ($lockedInvoice->status === 'CANCELLED' || $lockedInvoice->pharmacyRefund()->exists()) {
                throw ValidationException::withMessages([
                    'invoice' => 'This pharmacy sale has already been cancelled.',
                ]);
            }

            $saleMovements = PharmacyStockMovement::query()
                ->where('invoice_id', $lockedInvoice->id)
                ->where('movement_type', 'SALE')
                ->whereNull('reversed_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $medicines = Medicine::query()
                ->whereKey($saleMovements->pluck('medicine_id')->unique()->sort()->values())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $batches = PharmacyStockBatch::query()
                ->whereKey($saleMovements->pluck('batch_id')->unique()->sort()->values())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($saleMovements as $movement) {
                /** @var PharmacyStockBatch $batch */
                $batch = $batches->get($movement->batch_id);
                /** @var Medicine $medicine */
                $medicine = $medicines->get($movement->medicine_id);
                $batch->quantity_available += $movement->quantity;
                $batch->save();
                $medicine->quantity += $movement->quantity;
                $medicine->save();

                $movement->reversed_at = now();
                $movement->save();

                PharmacyStockMovement::create([
                    'medicine_id' => $medicine->id,
                    'batch_id' => $batch->id,
                    'invoice_id' => $lockedInvoice->id,
                    'invoice_item_id' => $movement->invoice_item_id,
                    'user_id' => $user->id,
                    'movement_type' => 'RETURN',
                    'quantity' => $movement->quantity,
                    'notes' => 'Stock returned for cancelled sale '.$lockedInvoice->invoice_number,
                ]);
            }

            $refundAmount = $lockedInvoice->payments()
                ->get(['amount'])
                ->sum(fn (Payment $payment): int => $this->toCents((string) $payment->amount));
            $refund = PharmacyRefund::create([
                'invoice_id' => $lockedInvoice->id,
                'amount' => $this->fromCents($refundAmount),
                'reason' => $reason,
                'refunded_by' => $user->id,
                'refunded_at' => now(),
            ]);

            $lockedInvoice->status = 'CANCELLED';
            $lockedInvoice->paid_amount = 0;
            $lockedInvoice->due_amount = 0;
            $lockedInvoice->save();

            return $refund;
        }, 3);
    }

    /**
     * @return Collection<int, PharmacyStockBatch>
     */
    private function sellableBatches(Medicine $medicine): Collection
    {
        $this->ensureOpeningBatch($medicine);

        return $medicine->stockBatches()
            ->where('quantity_available', '>', 0)
            ->where(function ($query): void {
                $query->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', today());
            })
            ->orderByRaw('expiry_date IS NULL')
            ->orderBy('expiry_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    private function ensureOpeningBatch(Medicine $medicine): void
    {
        if ($medicine->stockBatches()->exists()) {
            return;
        }

        $quantity = (int) $medicine->quantity;
        $batch = $medicine->stockBatches()->create([
            'batch_number' => 'LEGACY-'.$medicine->id,
            'quantity_received' => $quantity,
            'quantity_available' => $quantity,
            'expiry_date' => $medicine->expiry_date?->toDateString(),
            'notes' => 'Legacy medicine quantity imported on first inventory operation.',
        ]);

        if ($quantity > 0) {
            PharmacyStockMovement::create([
                'medicine_id' => $medicine->id,
                'batch_id' => $batch->id,
                'movement_type' => 'OPENING',
                'quantity' => $quantity,
                'notes' => 'Legacy medicine quantity imported on first inventory operation.',
            ]);
        }
    }

    private function toCents(string $amount): int
    {
        $amount = trim($amount);

        if (! preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $amount)) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Amounts must be non-negative with no more than two decimal places.',
            ]);
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function fromCents(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
