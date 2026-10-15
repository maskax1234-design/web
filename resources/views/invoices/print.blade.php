@php
    $statusLabel = match (strtoupper((string) $invoice->status)) {
        'PAID' => 'Paid',
        'PARTIAL', 'PARTIALLY PAID', 'PARTIAL PAID' => 'Partially Paid',
        default => 'Unpaid',
    };

    $formatMoney = fn ($value): string => number_format((float) ($value ?? 0), 2);
    $patientName = trim(($invoice->patient->first_name ?? '').' '.($invoice->patient->last_name ?? '')) ?: '—';
    $patientPhone = $invoice->patient->phone ?? '—';
    $patientAddress = $invoice->patient->address ?? '—';
    $appointmentText = $invoice->appointment ? ($invoice->appointment->appointment_number.' · '.($invoice->appointment->appointment_date?->format('d M Y') ?? '—')) : 'Not linked';
    $prescriptionText = $invoice->prescription ? ($invoice->prescription->prescription_number.' · '.($invoice->prescription->prescription_date?->format('d M Y') ?? '—')) : 'Not linked';
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        :root {
            --ink: #111827;
            --muted: #6b7280;
            --line: #e5e7eb;
            --panel: #f8fafc;
            --accent: #0f172a;
            --success: #166534;
            --danger: #b91c1c;
            --bg: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.4;
        }

        .invoice-shell {
            max-width: 860px;
            margin: 20px auto;
            padding: 18px;
            background: #fff;
        }

        .no-print {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 14px;
        }

        .primary-btn,
        .secondary-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid var(--line);
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            background: #fff;
            color: var(--ink);
        }

        .primary-btn {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            padding-bottom: 14px;
            border-bottom: 2px solid var(--line);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .brand-mark {
            width: 52px;
            height: 52px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: var(--panel);
            border: 1px solid var(--line);
            font-weight: 700;
            font-size: 20px;
            color: var(--accent);
        }

        .brand-title {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .brand-text {
            margin: 2px 0 0;
            color: var(--muted);
            font-size: 12px;
        }

        .invoice-meta {
            text-align: right;
            min-width: 230px;
        }

        .invoice-title {
            margin: 0;
            font-size: 24px;
            letter-spacing: 0.06em;
            color: var(--accent);
        }

        .invoice-meta-row {
            margin-top: 5px;
            font-size: 12px;
            color: var(--muted);
        }

        .invoice-meta-row strong {
            color: var(--ink);
        }

        .status-chip {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            background: rgba(15, 23, 42, 0.08);
            color: var(--ink);
        }

        .panel {
            margin-top: 16px;
            border: 1px solid var(--line);
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }

        .panel-header {
            padding: 10px 14px;
            border-bottom: 1px solid var(--line);
            font-weight: 700;
            background: var(--panel);
        }

        .panel-body {
            padding: 14px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .meta-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .meta-list li {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 6px 0;
            border-bottom: 1px solid var(--line);
        }

        .meta-list li:last-child {
            border-bottom: none;
        }

        .meta-list span {
            color: var(--muted);
        }

        .meta-list strong {
            text-align: right;
            font-weight: 600;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .items-table th,
        .items-table td {
            padding: 8px 7px;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
            text-align: left;
        }

        .items-table thead th {
            background: var(--panel);
            font-size: 10px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .items-table tbody tr:nth-child(even) {
            background: rgba(148, 163, 184, 0.04);
        }

        .money {
            text-align: right;
            white-space: nowrap;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1.3fr 0.7fr;
            gap: 16px;
            margin-top: 16px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 7px 0;
            border-bottom: 1px solid var(--line);
            color: var(--muted);
        }

        .summary-table td:last-child {
            text-align: right;
            color: var(--ink);
            font-weight: 600;
            white-space: nowrap;
        }

        .summary-table tr.grand-row td {
            border-bottom: none;
            font-size: 15px;
            font-weight: 700;
            color: var(--ink);
        }

        .summary-table tr.grand-row td:last-child {
            color: var(--accent);
        }

        .payment-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .payment-list li {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 7px 0;
            border-bottom: 1px solid var(--line);
        }

        .payment-list li:last-child {
            border-bottom: none;
        }

        .footer {
            margin-top: 18px;
            padding-top: 12px;
            border-top: 2px solid var(--line);
            display: flex;
            justify-content: space-between;
            gap: 14px;
            color: var(--muted);
            font-size: 11px;
        }

        .signature-box {
            min-width: 180px;
            text-align: center;
            border-top: 1px solid var(--line);
            padding-top: 8px;
        }

        @media (max-width: 720px) {
            .invoice-shell {
                padding: 12px;
                margin: 0;
            }

            .invoice-header,
            .info-grid,
            .summary-grid,
            .footer {
                display: block;
            }

            .invoice-meta {
                text-align: left;
                margin-top: 18px;
            }

            .brand {
                align-items: flex-start;
            }

            .items-table {
                display: block;
                overflow-x: auto;
                white-space: normal;
            }
        }

        @media print {
            @page {
                size: A4;
                margin: 12mm;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            .invoice-shell {
                max-width: none;
                margin: 0;
                padding: 0;
            }

            body {
                font-size: 10px;
                line-height: 1.25;
            }

            .invoice-title {
                font-size: 20px;
            }

            .brand-title {
                font-size: 17px;
            }

            .brand-mark {
                width: 42px;
                height: 42px;
                font-size: 17px;
            }

            .panel {
                margin-top: 10px;
            }

            .panel-header {
                padding: 7px 10px;
            }

            .panel-body {
                padding: 9px;
            }

            .items-table th,
            .items-table td {
                padding: 5px 4px;
            }

            .summary-grid {
                gap: 10px;
                margin-top: 10px;
            }

            .summary-table td,
            .payment-list li {
                padding: 5px 0;
            }

            .summary-table tr.grand-row td {
                font-size: 13px;
            }

            .footer {
                margin-top: 12px;
                padding-top: 8px;
                font-size: 9px;
            }

            .panel,
            .invoice-header,
            .footer {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .items-table thead {
                display: table-header-group;
            }

            .items-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-shell">
        <div class="no-print">
            <button type="button" class="primary-btn" onclick="window.print()">Print Invoice</button>
            <a href="{{ route('invoices.show', $invoice) }}" class="secondary-btn">Back to Invoice</a>
        </div>

        <header class="invoice-header">
            <div class="brand">
                <div class="brand-mark">HH</div>
                <div>
                    <h1 class="brand-title">Hospital managemen system</h1>
                    <p class="brand-text">456 Wellness Avenue, City Centre<br>+234 800 000 0000 · support@haldoor.example</p>
                </div>
            </div>

            <div class="invoice-meta">
                <h2 class="invoice-title">PATIENT INVOICE</h2>
                <div class="invoice-meta-row"><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</div>
                <div class="invoice-meta-row"><strong>Invoice Date:</strong> {{ $invoice->invoice_date?->format('d M Y') ?? '—' }}</div>
                <div class="invoice-meta-row"><strong>Status:</strong> <span class="status-chip">{{ $statusLabel }}</span></div>
            </div>
        </header>

        <section class="panel">
            <div class="panel-header">Patient Information</div>
            <div class="panel-body">
                <div class="info-grid">
                    <div>
                        <ul class="meta-list">
                            <li><span>Patient</span><strong>{{ $patientName }}</strong></li>
                            <li><span>Patient ID</span><strong>{{ $invoice->patient?->patient_number ?? '—' }}</strong></li>
                            <li><span>Phone</span><strong>{{ $patientPhone }}</strong></li>
                            <li><span>Address</span><strong>{{ $patientAddress }}</strong></li>
                        </ul>
                    </div>
                    <div>
                        <ul class="meta-list">
                            <li><span>Appointment</span><strong>{{ $appointmentText }}</strong></li>
                            <li><span>Prescription</span><strong>{{ $prescriptionText }}</strong></li>
                            <li><span>Doctor</span><strong>{{ ($invoice->prescription?->doctor) ? ($invoice->prescription->doctor->first_name.' '.$invoice->prescription->doctor->last_name) : (($invoice->appointment?->doctor) ? ($invoice->appointment->doctor->first_name.' '.$invoice->appointment->doctor->last_name) : '—') }}</strong></li>
                            <li><span>Notes</span><strong>{{ $invoice->notes ?: '—' }}</strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">Medicines &amp; Services</div>
            <div class="panel-body" style="padding:0;">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 7%;">No.</th>
                            <th style="width: 28%;">Medicine / Service</th>
                            <th style="width: 23%;">Description / Dosage</th>
                            <th style="width: 10%;">Qty</th>
                            <th style="width: 12%;" class="money">Unit Price</th>
                            <th style="width: 10%;" class="money">Discount</th>
                            <th style="width: 10%;" class="money">Tax</th>
                            <th style="width: 12%;" class="money">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoice->items as $index => $item)
                            @php
                                $medicine = $item->medicine;
                                $pivotItem = $invoice->prescription?->medicines?->firstWhere('id', $medicine?->id);
                                $dosage = $pivotItem?->pivot?->dosage ?? ($medicine?->strength ? 'Strength: '.$medicine->strength : null);
                                $frequency = $pivotItem?->pivot?->frequency ?? null;
                                $duration = $pivotItem?->pivot?->duration ?? null;
                                $instructions = $pivotItem?->pivot?->instructions ?? null;
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $medicine?->name ?? 'Medical service' }}</td>
                                <td>
                                    @php
                                        $detailParts = array_filter([$dosage, $frequency, $duration, $instructions], fn ($part) => filled($part));
                                    @endphp
                                    {{ $detailParts ? implode(' · ', $detailParts) : '—' }}
                                </td>
                                <td>{{ $item->quantity }}</td>
                                <td class="money">{{ $formatMoney($item->unit_price) }}</td>
                                <td class="money">{{ $formatMoney(0) }}</td>
                                <td class="money">{{ $formatMoney(0) }}</td>
                                <td class="money">{{ $formatMoney($item->total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="padding: 24px; color: var(--muted); text-align:center;">No invoice items available.</td>
                            </tr>
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
                        <tr>
                            <td>Subtotal</td>
                            <td>{{ $formatMoney($invoice->subtotal) }}</td>
                        </tr>
                        <tr>
                            <td>Discount</td>
                            <td>{{ $formatMoney($invoice->discount) }}</td>
                        </tr>
                        <tr>
                            <td>Tax Percentage</td>
                            <td>{{ $formatMoney($invoice->tax_percent) }}%</td>
                        </tr>
                        <tr>
                            <td>Tax Amount</td>
                            <td>{{ $formatMoney($invoice->tax_amount) }}</td>
                        </tr>
                        <tr>
                            <td>Total Before Charges</td>
                            <td>{{ $formatMoney($invoice->total) }}</td>
                        </tr>
                        <tr class="grand-row">
                            <td>Grand Total</td>
                            <td>{{ $formatMoney($invoice->grand_total) }}</td>
                        </tr>
                        <tr>
                            <td>Amount Paid</td>
                            <td>{{ $formatMoney($invoice->paid_amount) }}</td>
                        </tr>
                        <tr>
                            <td>Balance Due</td>
                            <td>{{ $formatMoney($invoice->due_amount) }}</td>
                        </tr>
                    </table>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">Payment Details</div>
                <div class="panel-body">
                    <ul class="payment-list">
                        @forelse ($invoice->payments as $payment)
                            <li>
                                <div>
                                    <strong>{{ $payment->payment_number }}</strong><br>
                                    <small>{{ $payment->payment_date?->format('d M Y') ?? '—' }}</small>
                                </div>
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
            <div>
                <strong>Thank you for choosing  Hospital Management system .</strong><br>
                This invoice is a billing document and is not a substitute for medical instructions.
            </div>
            <div class="signature-box">
                <div>Authorized Signature</div>
            </div>
            <div style="text-align: right;">
                Printed on {{ now()->format('d M Y, H:i') }}
            </div>
        </footer>
    </div>
    <script>
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
