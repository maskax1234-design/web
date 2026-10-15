@php
    $paymentMethods = ['Cash', 'Card', 'Bank Transfer', 'Mobile Money', 'Insurance', 'Cheque'];
@endphp

<x-form.select
    name="invoice_id"
    label="Invoice"
    :options="$selectOptions['invoice_id'] ?? collect()"
    :value="$record?->invoice_id"
    :required="true"
/>

<x-form.input
    name="amount"
    label="Amount"
    type="number"
    step="0.01"
    :min="0"
    :value="$record?->amount"
    :required="true"
/>

<x-form.input
    name="payment_date"
    label="Payment Date"
    type="date"
    :value="$record?->payment_date ?? now()->toDateString()"
    :required="true"
/>

<x-form.select name="payment_method" label="Payment Method" :options="$paymentMethods" :value="$record?->payment_method" :required="true" />
<x-form.input name="reference" label="Reference" :value="$record?->reference" placeholder="Transaction or cheque number" />

<x-form.select
    name="received_by"
    label="Received By"
    :options="$selectOptions['received_by'] ?? collect()"
    :value="$record?->received_by ?? auth()->id()"
    placeholder="Not recorded"
/>

<x-form.textarea name="notes" label="Notes" :value="$record?->notes" :rows="2" />

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const invoiceSelect = document.querySelector('select[name="invoice_id"]');
        const amountInput = document.querySelector('input[name="amount"]');
        const referenceInput = document.querySelector('input[name="reference"]');

        if (!invoiceSelect || !amountInput) {
            return;
        }

        const invoiceAmountMap = @json($invoiceAmountMap ?? []);

        const fillAmountFromInvoice = () => {
            const invoice = invoiceAmountMap[invoiceSelect.value] ?? null;

            amountInput.value = invoice ? Number(invoice.due_amount).toFixed(2) : '';

            if (referenceInput && !referenceInput.value.trim()) {
                referenceInput.value = invoice?.invoice_number ?? '';
            }
        };

        invoiceSelect.addEventListener('change', fillAmountFromInvoice);

        if (invoiceSelect.value && !amountInput.value) {
            fillAmountFromInvoice();
        }
    });
</script>