@php
    $formatMoney = fn ($value): string => number_format((float) ($value ?? 0), 2);
    $paymentTotal = $invoice->payments->sum('amount');
    $totalQuantity = $invoice->items->sum('quantity');
    $patientName = $invoice->patient
        ? trim($invoice->patient->first_name.' '.$invoice->patient->last_name)
        : null;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pharmacy Sales Receipt {{ $invoice->invoice_number }}</title>
    <style>
        :root {
            --ink: #111827;
            --muted: #6b7280;
            --line: #e5e7eb;
            --panel: #f8fafc;
            --accent: #0f172a;
            --bg: #fff;
        }

        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--ink); font: 13px/1.4 Arial, Helvetica, sans-serif; }
        .receipt-shell { max-width: 860px; margin: 20px auto; padding: 18px; background: #fff; }
        .no-print { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 14px; }
        .primary-btn, .secondary-btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px; background: #fff; color: var(--ink); font-size: 12px; font-weight: 600; text-decoration: none; cursor: pointer; }
        .primary-btn { border-color: var(--accent); background: var(--accent); color: #fff; }
        .receipt-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding-bottom: 14px; border-bottom: 2px solid var(--line); }
        .brand { display: flex; align-items: center; gap: 14px; min-width: 0; }
        .brand-mark { width: 52px; height: 52px; display: grid; place-items: center; flex: 0 0 auto; border: 1px solid var(--line); border-radius: 10px; background: var(--panel); color: var(--accent); font-size: 20px; font-weight: 700; }
        .brand-title { margin: 0; font-size: 20px; font-weight: 700; }
        .brand-text { margin: 2px 0 0; color: var(--muted); font-size: 12px; }
        .receipt-meta { min-width: 230px; text-align: right; }
        .receipt-title { margin: 0; color: var(--accent); font-size: 22px; letter-spacing: .04em; }
        .receipt-meta-row { margin-top: 5px; color: var(--muted); font-size: 12px; }
        .receipt-meta-row strong { color: var(--ink); }
        .status-chip { display: inline-block; padding: 4px 8px; border-radius: 999px; background: rgba(15, 23, 42, .08); color: var(--ink); font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .panel { margin-top: 16px; overflow: hidden; border: 1px solid var(--line); border-radius: 8px; background: #fff; }
        .panel-header { padding: 10px 14px; border-bottom: 1px solid var(--line); background: var(--panel); font-weight: 700; }
        .panel-body { padding: 14px; }
        .info-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
        .meta-list { margin: 0; padding: 0; list-style: none; }
        .meta-list li { display: flex; justify-content: space-between; gap: 10px; padding: 6px 0; border-bottom: 1px solid var(--line); }
        .meta-list li:last-child { border-bottom: 0; }
        .meta-list span { color: var(--muted); }
        .meta-list strong { text-align: right; font-weight: 600; }
        .items-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .items-table th, .items-table td { padding: 8px 7px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; overflow-wrap: anywhere; }
        .items-table thead th { background: var(--panel); color: var(--muted); font-size: 10px; letter-spacing: .04em; text-transform: uppercase; }
        .items-table tbody tr:nth-child(even) { background: rgba(148, 163, 184, .04); }
        .money { text-align: right !important; white-space: nowrap; }
        .summary-grid { display: grid; grid-template-columns: 1.3fr .7fr; gap: 16px; margin-top: 16px; }
        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-table td { padding: 7px 0; border-bottom: 1px solid var(--line); color: var(--muted); }
        .summary-table td:last-child { color: var(--ink); font-weight: 600; text-align: right; white-space: nowrap; }
        .summary-table tr.grand-row td { border-bottom: 0; color: var(--ink); font-size: 15px; font-weight: 700; }
        .summary-table tr.grand-row td:last-child { color: var(--accent); }
        .payment-list { margin: 0; padding: 0; list-style: none; }
        .payment-list li { display: flex; justify-content: space-between; gap: 10px; padding: 7px 0; border-bottom: 1px solid var(--line); }
        .payment-list li:last-child { border-bottom: 0; }
        .footer { display: flex; justify-content: space-between; gap: 14px; margin-top: 18px; padding-top: 12px; border-top: 2px solid var(--line); color: var(--muted); font-size: 11px; }
        .signature-box { min-width: 180px; padding-top: 8px; border-top: 1px solid var(--line); text-align: center; }
        .void { color: #b91c1c; font-weight: 700; }

        @media (max-width: 720px) {
            .receipt-shell { margin: 0; padding: 12px; }
            .receipt-header, .info-grid, .summary-grid, .footer { display: block; }
            .receipt-meta { margin-top: 18px; text-align: left; }
            .brand { align-items: flex-start; }
            .items-table { display: block; overflow-x: auto; white-space: normal; }
        }

        @media print {
            @page { size: A4; margin: 12mm; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; font-size: 10px; line-height: 1.25; }
            .no-print { display: none !important; }
            .receipt-shell { max-width: none; margin: 0; padding: 0; }
            .receipt-title { font-size: 20px; }
            .brand-title { font-size: 17px; }
            .brand-mark { width: 42px; height: 42px; font-size: 17px; }
            .panel { margin-top: 10px; break-inside: avoid; page-break-inside: avoid; }
            .panel-header { padding: 7px 10px; }
            .panel-body { padding: 9px; }
            .items-table th, .items-table td { padding: 5px 4px; }
            .summary-grid { gap: 10px; margin-top: 10px; }
            .summary-table td, .payment-list li { padding: 5px 0; }
            .summary-table tr.grand-row td { font-size: 13px; }
            .footer { margin-top: 12px; padding-top: 8px; font-size: 9px; break-inside: avoid; page-break-inside: avoid; }
            .items-table thead { display: table-header-group; }
            .items-table tr { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <main class="receipt-shell">
        <div class="no-print">
            <button type="button" class="primary-btn" onclick="window.print()">Print Pharmacy Receipt</button>
            <a href="{{ route('pharmacy.sales.show', $invoice) }}" class="secondary-btn">Back to Sale</a>
        </div>

        <header class="receipt-header">
            <div class="brand">
                <div class="brand-mark">HH</div>
                <div>
                    <h1 class="brand-title">Haldoor Hospital</h1>
                    <p class="brand-text">456 Wellness Avenue, City Centre<br>+234 800 000 0000 · support@haldoor.example</p>
                </div>
            </div>
            <div class="receipt-meta">
                <h2 class="receipt-title">PHARMACY SALES RECEIPT</h2>
                <div class="receipt-meta-row"><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</div>
                <div class="receipt-meta-row"><strong>Date:</strong> {{ $invoice->created_at?->format('d M Y, H:i') ?? '—' }}</div>
                <div class="receipt-meta-row"><strong>Status:</strong> <span class="status-chip {{ $invoice->status === 'CANCELLED' ? 'void' : '' }}">{{ $invoice->status }}</span></div>
            </div>
        </header>

        <section class="panel">
            <div class="panel-header">Sale Information</div>
            <div class="panel-body">
                <div class="info-grid">
                    <ul class="meta-list">
                        <li><span>Cashier</span><strong>{{ $invoice->cashier?->name ?? '—' }}</strong></li>
                        <li><span>Patient</span><strong>{{ $patientName ?: 'Walk-in / OTC' }}</strong></li>
                        @if ($invoice->patient)
                            <li><span>Patient ID</span><strong>{{ $invoice->patient->patient_number }}</strong></li>
                            @if ($invoice->patient->phone)
                                <li><span>Phone</span><strong>{{ $invoice->patient->phone }}</strong></li>
                            @endif
                        @endif
                    </ul>
                    <ul class="meta-list">
                        <li><span>Invoice date</span><strong>{{ $invoice->invoice_date?->format('d M Y') ?? '—' }}</strong></li>
                        <li><span>Items</span><strong>{{ $invoice->items->count() }}</strong></li>
                        <li><span>Total quantity</span><strong>{{ $totalQuantity }}</strong></li>
                        <li><span>Prescription</span><strong>{{ $invoice->prescription?->prescription_number ?? 'Not linked' }}</strong></li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">Medicines Sold</div>
            <div class="panel-body" style="padding:0;">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width:6%;">No.</th>
                            <th style="width:30%;">Medicine</th>
                            <th style="width:22%;">Code / Batch</th>
                            <th style="width:9%;">Qty</th>
                            <th style="width:15%;" class="money">Unit Price</th>
                            <th style="width:18%;" class="money">Line Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoice->items as $index => $item)
                            @php
                                $movements = $invoice->stockMovements
                                    ->where('invoice_item_id', $item->id)
                                    ->where('movement_type', 'SALE')
                                    ->whereNull('reversed_at');
                                $batches = $movements->map(fn ($movement) => $movement->batch?->batch_number)->filter()->unique()->join(', ');
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->medicine?->name ?? 'Medicine unavailable' }}<small style="display:block;color:var(--muted);">Unit/form: {{ $item->medicine?->dosage_form }} {{ $item->medicine?->strength }}</small></td>
                                <td>{{ $item->medicine?->medicine_code ?? '—' }}<small style="display:block;color:var(--muted);">{{ $batches ?: '—' }}</small></td>
                                <td>{{ $item->quantity }}</td>
                                <td class="money">{{ $formatMoney($item->unit_price) }}</td>
                                <td class="money">{{ $formatMoney($item->total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="padding:24px;color:var(--muted);text-align:center;">No sale items available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="summary-grid">
            <section class="panel">
                <div class="panel-header">Financial Summary</div>
                <div class="panel-body">
                    <table class="summary-table">
                        <tr><td>Subtotal</td><td>{{ $formatMoney($invoice->subtotal) }}</td></tr>
                        <tr><td>Discount</td><td>{{ $formatMoney($invoice->discount) }}</td></tr>
                        <tr><td>Tax Percentage</td><td>{{ $formatMoney($invoice->tax_percent) }}%</td></tr>
                        <tr><td>Tax Amount</td><td>{{ $formatMoney($invoice->tax_amount) }}</td></tr>
                        <tr class="grand-row"><td>Amount Payable</td><td>{{ $formatMoney($invoice->grand_total) }}</td></tr>
                        <tr><td>Amount Paid</td><td>{{ $formatMoney($paymentTotal) }}</td></tr>
                        <tr><td>Balance Due</td><td>{{ $formatMoney($invoice->due_amount) }}</td></tr>
                        @if ($invoice->pharmacyRefund)
                            <tr><td>Refund Issued</td><td>{{ $formatMoney($invoice->pharmacyRefund->amount) }}</td></tr>
                        @endif
                    </table>
                </div>
            </section>
            <section class="panel">
                <div class="panel-header">Payment Details</div>
                <div class="panel-body">
                    <ul class="payment-list">
                        @forelse ($invoice->payments as $payment)
                            <li>
                                <div><strong>{{ $payment->payment_method }}</strong><br><small>{{ $payment->payment_number }} · {{ $payment->payment_date?->format('d M Y') ?? '—' }}</small></div>
                                <div>{{ $formatMoney($payment->amount) }}</div>
                            </li>
                        @empty
                            <li><div>No payments captured yet.</div></li>
                        @endforelse
                    </ul>
                </div>
            </section>
        </div>

        <footer class="footer">
            <div><strong>Thank you for choosing Haldoor Hospital.</strong><br>Please retain this pharmacy receipt for your records.</div>
            <div class="signature-box">Authorized Signature</div>
            <div style="text-align:right;">Printed on {{ now()->format('d M Y, H:i') }}</div>
        </footer>
    </main>
</body>
</html>
