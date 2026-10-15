<?php

namespace App\Services;

use App\Models\Invoice;

class InvoiceCalculationService
{
    /**
     * @param  array<int, array{quantity?: int|float|string, unit_price?: int|float|string}>  $items
     * @return array<string, float|string>
     */
    public static function calculate(array $items, float|int|string $discount = 0, float|int|string $taxPercent = 0, float|int|string $paidAmount = 0): array
    {
        $subtotal = 0.0;

        foreach ($items as $item) {
            $quantity = self::normalizeMoney($item['quantity'] ?? 0);
            $unitPrice = self::normalizeMoney($item['unit_price'] ?? 0);
            $subtotal += ($quantity * $unitPrice);
        }

        $discount = self::normalizeMoney($discount);
        $taxPercent = self::normalizeMoney($taxPercent);
        $paidAmount = self::normalizeMoney($paidAmount);

        $amountAfterDiscount = max(0.0, $subtotal - $discount);
        $taxAmount = ($amountAfterDiscount * $taxPercent) / 100;
        $grandTotal = $amountAfterDiscount + $taxAmount;
        $dueAmount = max(0.0, $grandTotal - $paidAmount);

        $status = self::paymentStatusFor($paidAmount, $grandTotal);

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'amount_after_discount' => round($amountAfterDiscount, 2),
            'tax_amount' => round($taxAmount, 2),
            'tax_percent' => round($taxPercent, 2),
            'grand_total' => round($grandTotal, 2),
            'paid_amount' => round($paidAmount, 2),
            'due_amount' => round($dueAmount, 2),
            'status' => $status,
        ];
    }

    public static function paymentStatusFor(float|int|string $paidAmount, float|int|string $grandTotal): string
    {
        $paidAmount = self::normalizeMoney($paidAmount);
        $grandTotal = self::normalizeMoney($grandTotal);

        if ($paidAmount == 0.0) {
            return 'UNPAID';
        }

        if ($paidAmount > 0.0 && $paidAmount < $grandTotal) {
            return 'PARTIAL';
        }

        return 'PAID';
    }

    public static function maximumPaymentAllowed(float|int|string $grandTotal, float|int|string $paidAmount): float
    {
        return max(0.0, round(self::normalizeMoney($grandTotal) - self::normalizeMoney($paidAmount), 2));
    }

    public static function recalculateInvoice(Invoice $invoice): void
    {
        $items = $invoice->items()->get()->map(fn ($item): array => [
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
        ])->all();

        $calculation = self::calculate(
            $items,
            $invoice->discount ?? 0,
            $invoice->tax_percent ?? 0,
            $invoice->payments()->sum('amount')
        );

        $invoice->subtotal = $calculation['subtotal'];
        $invoice->discount = $calculation['discount'];
        $invoice->tax = $calculation['tax_amount'];
        $invoice->tax_amount = $calculation['tax_amount'];
        $invoice->total = $calculation['grand_total'];
        $invoice->grand_total = $calculation['grand_total'];
        $invoice->paid_amount = $calculation['paid_amount'];
        $invoice->due_amount = $calculation['due_amount'];
        $invoice->status = $calculation['status'];
        $invoice->save();
    }

    public static function normalizeMoney(float|int|string $value): float
    {
        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return 0.0;
            }
        }

        return round((float) $value, 2);
    }
}
