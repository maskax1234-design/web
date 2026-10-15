<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PharmacyRefund;
use App\Models\PharmacyStockBatch;
use App\Models\PharmacyStockMovement;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Support\Str;

function pharmacyPatient(): Patient
{
    return Patient::create([
        'first_name' => 'Nora',
        'last_name' => 'Jones',
        'gender' => 'Female',
        'date_of_birth' => '1990-01-01',
        'phone' => '555-0100',
        'status' => 'Active',
    ]);
}

function pharmacyMedicine(int $quantity = 10, string $price = '10.00'): Medicine
{
    return Medicine::create([
        'name' => 'Pharmacy POS Test Medicine',
        'generic_name' => 'Test medicine',
        'category' => 'Analgesic',
        'dosage_form' => 'Tablet',
        'strength' => '250 mg',
        'quantity' => $quantity,
        'unit_price' => $price,
        'expiry_date' => '2030-01-01',
        'status' => 'Active',
    ]);
}

function pharmacySalePayload(Patient $patient, Medicine $medicine, int $quantity = 2): array
{
    return [
        'patient_id' => $patient->id,
        'items' => [
            ['medicine_id' => $medicine->id, 'quantity' => $quantity],
        ],
        'discount' => '1.00',
        'tax_percent' => '10.00',
        'amount_paid' => '10.00',
        'payment_method' => 'Cash',
        'pos_token' => (string) Str::uuid(),
    ];
}

it('completes a sale using server prices, cents-based totals, stock batches, and partial payments', function () {
    $cashier = User::factory()->create(['role' => 'Pharmacist']);
    $patient = pharmacyPatient();
    $medicine = pharmacyMedicine(5, '10.00');
    $payload = pharmacySalePayload($patient, $medicine);
    $payload['items'][0]['unit_price'] = '0.01';
    $payload['subtotal'] = '0.02';

    $this->actingAs($cashier)
        ->post(route('pharmacy.sales.store'), $payload)
        ->assertRedirect();

    $invoice = Invoice::query()->where('source', 'PHARMACY_POS')->sole();

    $this->get(route('pharmacy.sales.show', $invoice))
        ->assertOk()
        ->assertSee('Items sold')
        ->assertSee($medicine->name);
    $this->get(route('pharmacy.sales.index'))->assertOk()->assertSee($invoice->invoice_number);
    $this->get(route('pharmacy.reports.daily', ['date' => today()->toDateString()]))
        ->assertOk()
        ->assertSee('Daily Pharmacy Report');

    expect((float) $invoice->subtotal)->toBe(20.0)
        ->and((float) $invoice->discount)->toBe(1.0)
        ->and((float) $invoice->tax_amount)->toBe(1.9)
        ->and((float) $invoice->grand_total)->toBe(20.9)
        ->and((float) $invoice->paid_amount)->toBe(10.0)
        ->and((float) $invoice->due_amount)->toBe(10.9)
        ->and($invoice->status)->toBe('PARTIAL')
        ->and($invoice->cashier_id)->toBe($cashier->id)
        ->and($medicine->fresh()->quantity)->toBe(3)
        ->and($invoice->items()->sole()->unit_price)->toBe('10.00')
        ->and(Payment::query()->where('invoice_id', $invoice->id)->sole()->received_by)->toBe($cashier->id)
        ->and(PharmacyStockMovement::query()->where('invoice_id', $invoice->id)->where('movement_type', 'SALE')->sum('quantity'))->toBe(2);
});

it('completes a walk-in sale without a patient or prescription', function () {
    $cashier = User::factory()->create(['role' => 'Pharmacist']);
    $medicine = pharmacyMedicine(5, '10.00');

    $this->actingAs($cashier)
        ->get(route('pharmacy.pos'))
        ->assertOk()
        ->assertSee('Patient')
        ->assertSee('(optional)')
        ->assertSee('Walk-in / over-the-counter sale.');

    $this->post(route('pharmacy.sales.store'), [
        'items' => [
            ['medicine_id' => $medicine->id, 'quantity' => 2],
        ],
        'discount' => '0.00',
        'tax_percent' => '0.00',
        'amount_paid' => '20.00',
        'payment_method' => 'Cash',
        'pos_token' => (string) Str::uuid(),
    ])->assertRedirect();

    $invoice = Invoice::query()->where('source', 'PHARMACY_POS')->sole();
    $payment = Payment::query()->where('invoice_id', $invoice->id)->sole();

    $this->get(route('pharmacy.sales.show', $invoice))
        ->assertOk()
        ->assertSee('Walk-in / OTC');
    $this->get(route('pharmacy.sales.receipt', $invoice))
        ->assertOk()
        ->assertSee('Walk-in / OTC')
        ->assertSee('PHARMACY SALES RECEIPT')
        ->assertSee('Cash')
        ->assertSee('Total quantity')
        ->assertSee($medicine->medicine_code);

    expect($invoice->patient_id)->toBeNull()
        ->and($invoice->prescription_id)->toBeNull()
        ->and($payment->patient_id)->toBeNull()
        ->and($medicine->fresh()->quantity)->toBe(3)
        ->and(Medicine::query()->count())->toBe(1)
        ->and(PharmacyStockMovement::query()->where('invoice_id', $invoice->id)->where('movement_type', 'SALE')->sum('quantity'))->toBe(2);
});

it('uses FEFO batch allocation and does not include expired batches in available stock', function () {
    $cashier = User::factory()->create(['role' => 'Pharmacist']);
    $patient = pharmacyPatient();
    $medicine = pharmacyMedicine(12);
    $firstBatch = PharmacyStockBatch::create([
        'medicine_id' => $medicine->id,
        'batch_number' => 'EARLIEST-EXPIRY',
        'quantity_received' => 2,
        'quantity_available' => 2,
        'expiry_date' => '2027-01-01',
    ]);
    $secondBatch = PharmacyStockBatch::create([
        'medicine_id' => $medicine->id,
        'batch_number' => 'LATER-EXPIRY',
        'quantity_received' => 3,
        'quantity_available' => 3,
        'expiry_date' => '2028-01-01',
    ]);
    PharmacyStockBatch::create([
        'medicine_id' => $medicine->id,
        'batch_number' => 'EXPIRED',
        'quantity_received' => 7,
        'quantity_available' => 7,
        'expiry_date' => '2020-01-01',
    ]);
    $payload = pharmacySalePayload($patient, $medicine, 3);
    $payload['discount'] = '0';
    $payload['tax_percent'] = '0';
    $payload['amount_paid'] = '0';
    $payload['payment_method'] = '';

    $this->actingAs($cashier)->post(route('pharmacy.sales.store'), $payload)->assertRedirect();

    expect($firstBatch->fresh()->quantity_available)->toBe(0)
        ->and($secondBatch->fresh()->quantity_available)->toBe(2)
        ->and($medicine->fresh()->quantity)->toBe(9);
});

it('rejects a sale with insufficient stock without creating an invoice or payment', function () {
    $cashier = User::factory()->create(['role' => 'Pharmacist']);
    $patient = pharmacyPatient();
    $medicine = pharmacyMedicine(2);
    $payload = pharmacySalePayload($patient, $medicine, 3);

    $this->actingAs($cashier)
        ->from(route('pharmacy.pos'))
        ->post(route('pharmacy.sales.store'), $payload)
        ->assertSessionHasErrors('items');

    expect(Invoice::query()->where('source', 'PHARMACY_POS')->count())->toBe(0)
        ->and(Payment::query()->count())->toBe(0)
        ->and($medicine->fresh()->quantity)->toBe(2);
});

it('prevents overselling when competing requests consume the remaining stock', function () {
    $cashier = User::factory()->create(['role' => 'Pharmacist']);
    $patient = pharmacyPatient();
    $medicine = pharmacyMedicine(3);
    $firstSale = pharmacySalePayload($patient, $medicine, 2);
    $secondSale = pharmacySalePayload($patient, $medicine, 2);

    $this->actingAs($cashier)
        ->post(route('pharmacy.sales.store'), $firstSale)
        ->assertRedirect();

    $this->from(route('pharmacy.pos'))
        ->post(route('pharmacy.sales.store'), $secondSale)
        ->assertSessionHasErrors('items');

    expect($medicine->fresh()->quantity)->toBe(1)
        ->and(Invoice::query()->where('source', 'PHARMACY_POS')->count())->toBe(1);
});

it('treats a retried POS token as the original sale and does not charge or deduct stock twice', function () {
    $cashier = User::factory()->create(['role' => 'Pharmacist']);
    $patient = pharmacyPatient();
    $medicine = pharmacyMedicine(5);
    $payload = pharmacySalePayload($patient, $medicine);

    $this->actingAs($cashier)->post(route('pharmacy.sales.store'), $payload)->assertRedirect();
    $this->post(route('pharmacy.sales.store'), $payload)->assertRedirect();

    expect(Invoice::query()->where('source', 'PHARMACY_POS')->count())->toBe(1)
        ->and(Payment::query()->count())->toBe(1)
        ->and($medicine->fresh()->quantity)->toBe(3);
});

it('edits an unpaid sale and records a stock return before the replacement deduction', function () {
    $cashier = User::factory()->create(['role' => 'Pharmacist']);
    $patient = pharmacyPatient();
    $medicine = pharmacyMedicine(10);
    $payload = pharmacySalePayload($patient, $medicine, 2);
    $payload['amount_paid'] = '0.00';
    $payload['payment_method'] = '';

    $this->actingAs($cashier)->post(route('pharmacy.sales.store'), $payload)->assertRedirect();
    $invoice = Invoice::query()->where('source', 'PHARMACY_POS')->sole();

    $this->get(route('pharmacy.sales.edit', $invoice))
        ->assertOk()
        ->assertSee('Save Sale Changes')
        ->assertSee($medicine->name);
    $this->get(route('pharmacy.sales.index'))->assertSee('Edit');

    $this->put(route('pharmacy.sales.update', $invoice), [
        'patient_id' => $patient->id,
        'items' => [['medicine_id' => $medicine->id, 'quantity' => 4]],
        'discount' => '2.00',
        'tax_percent' => '10.00',
    ])->assertRedirect(route('pharmacy.sales.show', $invoice));

    $invoice->refresh();
    expect($invoice->status)->toBe('UNPAID')
        ->and((float) $invoice->subtotal)->toBe(40.0)
        ->and((float) $invoice->discount)->toBe(2.0)
        ->and((float) $invoice->tax_amount)->toBe(3.8)
        ->and((float) $invoice->grand_total)->toBe(41.8)
        ->and($invoice->items()->sole()->quantity)->toBe(4)
        ->and($medicine->fresh()->quantity)->toBe(6)
        ->and(PharmacyStockMovement::query()->where('invoice_id', $invoice->id)->where('movement_type', 'SALE')->whereNull('reversed_at')->sum('quantity'))->toBe(4)
        ->and(PharmacyStockMovement::query()->where('invoice_id', $invoice->id)->where('movement_type', 'SALE')->whereNotNull('reversed_at')->sum('quantity'))->toBe(2)
        ->and(PharmacyStockMovement::query()->where('invoice_id', $invoice->id)->where('movement_type', 'EDIT_RETURN')->sum('quantity'))->toBe(2);
});

it('rejects editing paid sales and rolls back inventory when an updated sale exceeds available stock', function () {
    $cashier = User::factory()->create(['role' => 'Pharmacist']);
    $patient = pharmacyPatient();
    $medicine = pharmacyMedicine(5);
    $paidSale = pharmacySalePayload($patient, $medicine, 1);
    $paidSale['amount_paid'] = '5.00';
    $this->actingAs($cashier)->post(route('pharmacy.sales.store'), $paidSale)->assertRedirect();
    $paidInvoice = Invoice::query()->where('source', 'PHARMACY_POS')->sole();
    $this->get(route('pharmacy.sales.edit', $paidInvoice))->assertForbidden();

    $unpaidSale = pharmacySalePayload($patient, $medicine, 2);
    $unpaidSale['amount_paid'] = '0.00';
    $unpaidSale['payment_method'] = '';
    $this->post(route('pharmacy.sales.store'), $unpaidSale)->assertRedirect();
    $unpaidInvoice = Invoice::query()->where('source', 'PHARMACY_POS')->where('status', 'UNPAID')->sole();

    $this->from(route('pharmacy.sales.edit', $unpaidInvoice))
        ->put(route('pharmacy.sales.update', $unpaidInvoice), [
            'patient_id' => $patient->id,
            'items' => [['medicine_id' => $medicine->id, 'quantity' => 5]],
            'discount' => '0.00',
            'tax_percent' => '0.00',
        ])
        ->assertSessionHasErrors('items');

    expect($medicine->fresh()->quantity)->toBe(2)
        ->and($unpaidInvoice->fresh()->items()->sole()->quantity)->toBe(2)
        ->and(PharmacyStockMovement::query()->where('invoice_id', $unpaidInvoice->id)->where('movement_type', 'SALE')->whereNull('reversed_at')->sum('quantity'))->toBe(2)
        ->and(PharmacyStockMovement::query()->where('invoice_id', $unpaidInvoice->id)->where('movement_type', 'EDIT_RETURN')->count())->toBe(0);
});

it('voids an edited sale without restoring its already-reversed movements twice', function () {
    $admin = User::factory()->create(['role' => 'Admin']);
    $patient = pharmacyPatient();
    $medicine = pharmacyMedicine(10);
    $payload = pharmacySalePayload($patient, $medicine, 2);
    $payload['amount_paid'] = '0.00';
    $payload['payment_method'] = '';

    $this->actingAs($admin)->post(route('pharmacy.sales.store'), $payload)->assertRedirect();
    $invoice = Invoice::query()->where('source', 'PHARMACY_POS')->sole();
    $this->get(route('pharmacy.sales.index'))->assertSee('Delete / void');
    $this->put(route('pharmacy.sales.update', $invoice), [
        'patient_id' => $patient->id,
        'items' => [['medicine_id' => $medicine->id, 'quantity' => 3]],
        'discount' => '0.00',
        'tax_percent' => '0.00',
    ])->assertRedirect();

    $this->post(route('pharmacy.sales.cancel', $invoice), ['reason' => 'Incorrect sale entered'])
        ->assertRedirect(route('pharmacy.sales.show', $invoice));

    expect($medicine->fresh()->quantity)->toBe(10)
        ->and(PharmacyStockMovement::query()->where('invoice_id', $invoice->id)->where('movement_type', 'RETURN')->sum('quantity'))->toBe(3)
        ->and($invoice->fresh()->status)->toBe('CANCELLED');
});

it('cancels a pharmacy sale once, issues a full refund, and restores the sold batches', function () {
    $admin = User::factory()->create(['role' => 'Admin']);
    $patient = pharmacyPatient();
    $medicine = pharmacyMedicine(5);
    $payload = pharmacySalePayload($patient, $medicine);

    $this->actingAs($admin)->post(route('pharmacy.sales.store'), $payload)->assertRedirect();
    $invoice = Invoice::query()->where('source', 'PHARMACY_POS')->sole();

    $this->post(route('pharmacy.sales.cancel', $invoice), ['reason' => 'Customer returned all items'])
        ->assertRedirect(route('pharmacy.sales.show', $invoice));

    $invoice->refresh();
    expect($invoice->status)->toBe('CANCELLED')
        ->and((float) $invoice->due_amount)->toBe(0.0)
        ->and((float) $invoice->paid_amount)->toBe(0.0)
        ->and((float) $invoice->pharmacyRefund->amount)->toBe(10.0)
        ->and($medicine->fresh()->quantity)->toBe(5)
        ->and(PharmacyRefund::query()->where('invoice_id', $invoice->id)->count())->toBe(1)
        ->and(PharmacyStockMovement::query()->where('invoice_id', $invoice->id)->where('movement_type', 'RETURN')->sum('quantity'))->toBe(2);

    $this->from(route('pharmacy.sales.show', $invoice))
        ->post(route('pharmacy.sales.cancel', $invoice), ['reason' => 'Duplicate refund'])
        ->assertSessionHasErrors('invoice');

    expect(PharmacyRefund::query()->where('invoice_id', $invoice->id)->count())->toBe(1)
        ->and(Payment::query()->where('invoice_id', $invoice->id)->count())->toBe(1);
});

it('restricts pharmacy actions by module permission rather than role', function () {
    $patient = pharmacyPatient();
    $medicine = pharmacyMedicine();
    $invoice = Invoice::create([
        'patient_id' => $patient->id,
        'invoice_date' => today(),
        'subtotal' => 10,
        'total' => 10,
        'grand_total' => 10,
        'due_amount' => 10,
        'source' => 'PHARMACY_POS',
        'status' => 'UNPAID',
    ]);
    $payment = Payment::create([
        'invoice_id' => $invoice->id,
        'patient_id' => $patient->id,
        'amount' => 1,
        'payment_date' => today(),
        'payment_method' => 'Cash',
    ]);

    $this->actingAs(User::factory()->create([
        'role' => 'Receptionist',
        'module_access' => ['dashboard', 'invoices', 'payments'],
    ]))
        ->get(route('pharmacy.pos'))
        ->assertForbidden();

    $this->get(route('invoices.show', $invoice))->assertForbidden();
    $this->get(route('invoices.print', $invoice))->assertForbidden();
    $this->get(route('payments.show', $payment))->assertForbidden();
    $this->get(route('invoices.index'))->assertDontSee($invoice->invoice_number);
    $this->get(route('payments.index'))->assertDontSee($payment->payment_number);
    $this->get(route('dashboard.data'))->assertDontSee($invoice->invoice_number);

    $this->actingAs(User::factory()->create([
        'role' => 'Pharmacist',
        'module_access' => ['dashboard', 'pharmacy'],
    ]))
        ->post(route('pharmacy.sales.cancel', $invoice), ['reason' => 'Not authorized'])
        ->assertRedirect(route('pharmacy.sales.show', $invoice));

    expect($invoice->fresh()->status)->toBe('CANCELLED')
        ->and($medicine->fresh()->quantity)->toBe($medicine->quantity);
});

it('allows pharmacy-module users to receive stock and shows the pharmacy receipt', function () {
    $pharmacyUser = User::factory()->create([
        'role' => 'Pharmacist',
        'module_access' => ['pharmacy'],
    ]);
    $patient = pharmacyPatient();
    $medicine = pharmacyMedicine(2);

    $this->actingAs($pharmacyUser)
        ->post(route('pharmacy.stock.receive'), [
            'medicine_id' => $medicine->id,
            'batch_number' => 'BATCH-NEW-01',
            'quantity' => 8,
            'expiry_date' => '2030-05-01',
        ])
        ->assertRedirect(route('pharmacy.pos'));

    expect($medicine->fresh()->quantity)->toBe(10)
        ->and(PharmacyStockBatch::query()->where('medicine_id', $medicine->id)->where('batch_number', 'BATCH-NEW-01')->sole()->quantity_available)->toBe(8);

    $cashier = User::factory()->create(['role' => 'Cashier']);
    $payload = pharmacySalePayload($patient, $medicine, 1);
    $payload['amount_paid'] = '0.00';
    $payload['payment_method'] = '';
    $this->actingAs($cashier)->post(route('pharmacy.sales.store'), $payload)->assertRedirect();
    $invoice = Invoice::query()->where('source', 'PHARMACY_POS')->sole();

    $this->get(route('pharmacy.sales.receipt', $invoice))
        ->assertOk()
        ->assertSee('Print Pharmacy Receipt')
        ->assertSee($invoice->invoice_number)
        ->assertSee('Balance Due');
});

it('deducts and reverses batch stock when an existing prescription invoice is edited', function () {
    $user = User::factory()->create(['role' => 'Receptionist']);
    $patient = pharmacyPatient();
    $department = Department::create(['name' => 'Pharmacy POS Test Department', 'status' => 'Active']);
    $doctor = Doctor::create([
        'first_name' => 'Ada',
        'last_name' => 'Doctor',
        'gender' => 'Female',
        'phone' => '555-0101',
        'email' => 'pharmacy-pos-doctor@example.test',
        'specialization' => 'General Medicine',
        'department_id' => $department->id,
        'qualification' => 'MD',
        'license_number' => 'PHARMACY-POS-LIC-1',
        'experience' => 5,
        'status' => 'Active',
    ]);
    $prescription = Prescription::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'prescription_date' => today(),
        'status' => 'Active',
    ]);
    $medicine = pharmacyMedicine(10, '2.50');
    $prescription->medicines()->attach($medicine->id, [
        'dosage' => '1 tablet',
        'frequency' => 'Twice daily',
        'duration' => '3 days',
        'quantity' => 2,
        'unit_price' => 2.50,
        'total' => 5,
    ]);

    $this->actingAs($user)
        ->post(route('invoices.store'), [
            'prescription_id' => $prescription->id,
            'invoice_date' => today()->toDateString(),
            'discount' => 0,
            'tax_percent' => 0,
        ])
        ->assertRedirect();

    $invoice = Invoice::query()->where('prescription_id', $prescription->id)->sole();
    expect($invoice->source)->toBe('PRESCRIPTION')
        ->and($medicine->fresh()->quantity)->toBe(8);

    $this->post(route('invoices.update', $invoice), [
        'prescription_id' => $prescription->id,
        'invoice_date' => today()->toDateString(),
        'discount' => 0,
        'tax_percent' => 0,
    ])->assertRedirect();

    expect($medicine->fresh()->quantity)->toBe(8)
        ->and(PharmacyStockMovement::query()->where('invoice_id', $invoice->id)->where('movement_type', 'DISPENSE')->whereNotNull('reversed_at')->sum('quantity'))->toBe(2)
        ->and(PharmacyStockMovement::query()->where('invoice_id', $invoice->id)->where('movement_type', 'DISPENSE_RETURN')->sum('quantity'))->toBe(2);
});

it('shows low stock and expiry data on the POS and limits searches to active stocked items', function () {
    $cashier = User::factory()->create(['role' => 'Pharmacist']);
    $patient = pharmacyPatient();
    $medicine = pharmacyMedicine(2);
    $batch = PharmacyStockBatch::create([
        'medicine_id' => $medicine->id,
        'batch_number' => 'EXPIRING-01',
        'quantity_received' => 2,
        'quantity_available' => 2,
        'expiry_date' => today()->addDays(30),
    ]);
    PharmacyStockMovement::create([
        'medicine_id' => $medicine->id,
        'batch_id' => $batch->id,
        'movement_type' => 'RECEIPT',
        'quantity' => 2,
    ]);

    $this->actingAs($cashier)
        ->get(route('pharmacy.pos'))
        ->assertOk()
        ->assertSee('Expiring within 90 days')
        ->assertSee('Receive full amount');

    $this->getJson(route('pharmacy.medicines', ['q' => $medicine->medicine_code]))
        ->assertOk()
        ->assertJsonFragment(['code' => $medicine->medicine_code])
        ->assertJsonFragment(['number' => 'EXPIRING-01']);

    $this->getJson(route('pharmacy.patients', ['q' => 'Nora Jones']))
        ->assertOk()
        ->assertJsonFragment(['id' => $patient->id, 'number' => $patient->patient_number]);
});

it('searches existing HMS medicines with legacy stock and sells against the same medicine record', function () {
    $cashier = User::factory()->create(['role' => 'Pharmacist']);
    $patient = pharmacyPatient();
    $medicine = pharmacyMedicine(5);

    $this->actingAs($cashier)
        ->getJson(route('pharmacy.medicines', ['q' => $medicine->medicine_code]))
        ->assertOk()
        ->assertJsonPath('0.id', $medicine->id)
        ->assertJsonPath('0.stock', 5)
        ->assertJsonPath('0.batches.0.number', 'LEGACY-'.$medicine->id);

    $payload = pharmacySalePayload($patient, $medicine, 2);
    $payload['discount'] = '0';
    $payload['tax_percent'] = '0';
    $payload['amount_paid'] = '0';
    $payload['payment_method'] = '';

    $this->post(route('pharmacy.sales.store'), $payload)->assertRedirect();

    expect($medicine->fresh()->quantity)->toBe(3)
        ->and(PharmacyStockBatch::query()->where('medicine_id', $medicine->id)->sole()->batch_number)->toBe('LEGACY-'.$medicine->id)
        ->and(PharmacyStockMovement::query()->where('medicine_id', $medicine->id)->where('movement_type', 'SALE')->sum('quantity'))->toBe(2);
});

it('renders medicine select values using their labels instead of numeric indexes', function () {
    $admin = User::factory()->create(['role' => 'Admin']);

    $this->actingAs($admin)
        ->get(route('medicines.create'))
        ->assertOk()
        ->assertSee('<option value="Analgesic"', false)
        ->assertSee('<option value="Tablet"', false)
        ->assertSee('<option value="Active"', false);
});
