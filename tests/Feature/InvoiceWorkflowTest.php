<?php

use App\Services\InvoiceCalculationService;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;

it('calculates invoice values with the required formulas', function () {
    $result = InvoiceCalculationService::calculate([
        ['quantity' => 2, 'unit_price' => 3.00],
        ['quantity' => 1, 'unit_price' => 5.00],
        ['quantity' => 2, 'unit_price' => 2.00],
    ], 10, 5, 0);

    expect($result)->toMatchArray([
        'subtotal' => 15.00,
        'discount' => 10.00,
        'amount_after_discount' => 5.00,
        'tax_amount' => 0.25,
        'grand_total' => 5.25,
        'paid_amount' => 0.00,
        'due_amount' => 5.25,
        'status' => 'UNPAID',
    ]);
});

it('calculates payment status and highest allowed payment amount', function () {
    $status = InvoiceCalculationService::paymentStatusFor(80, 100);
    $limit = InvoiceCalculationService::maximumPaymentAllowed(100, 80);

    expect($status)->toBe('PARTIAL');
    expect($limit)->toBe(20.00);
});

it('reduces medicine stock when a prescription is created', function () {
    $user = User::factory()->create();
    $department = Department::create(['name' => 'Cardiology', 'status' => 'Active']);
    $patient = Patient::create([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'gender' => 'Female',
        'date_of_birth' => '1815-12-10',
        'phone' => '5550001111',
        'status' => 'Active',
    ]);
    $doctor = Doctor::create([
        'first_name' => 'Grace',
        'last_name' => 'Hopper',
        'gender' => 'Female',
        'phone' => '5550002222',
        'email' => 'grace@example.test',
        'specialization' => 'Cardiology',
        'department_id' => $department->id,
        'qualification' => 'MD',
        'license_number' => 'LIC-1003',
        'experience' => 12,
        'status' => 'Active',
    ]);
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'department_id' => $department->id,
        'appointment_date' => '2026-10-07',
        'appointment_time' => '09:30:00',
        'reason' => 'Consultation',
        'status' => 'Scheduled',
    ]);
    $medicine = Medicine::create([
        'name' => 'Aspirin',
        'dosage_form' => 'Tablet',
        'quantity' => 25,
        'unit_price' => 2.50,
        'expiry_date' => '2028-01-01',
        'status' => 'Active',
    ]);

    $this->actingAs($user)
        ->post(route('prescriptions.store'), [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_id' => $appointment->id,
            'prescription_date' => '2026-10-07',
            'instructions' => 'Take with water',
            'status' => 'Active',
            'medicine_ids' => [$medicine->id],
            'medicine_details' => [
                $medicine->id => [
                    'dosage' => '1 tablet',
                    'frequency' => 'Once daily',
                    'duration' => '7 days',
                    'quantity' => 7,
                    'instructions' => 'After breakfast',
                ],
            ],
        ])
        ->assertRedirect();

    $medicine->refresh();

    expect($medicine->quantity)->toBe(18);
});

it('prevents a prescription from being used in more than one invoice', function () {
    $user = User::factory()->create();
    $department = Department::create(['name' => 'Cardiology', 'status' => 'Active']);
    $patient = Patient::create([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'gender' => 'Female',
        'date_of_birth' => '1815-12-10',
        'phone' => '5550001111',
        'status' => 'Active',
    ]);
    $doctor = Doctor::create([
        'first_name' => 'Grace',
        'last_name' => 'Hopper',
        'gender' => 'Female',
        'phone' => '5550002222',
        'email' => 'grace@example.test',
        'specialization' => 'Cardiology',
        'department_id' => $department->id,
        'qualification' => 'MD',
        'license_number' => 'LIC-1002',
        'experience' => 12,
        'status' => 'Active',
    ]);
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'department_id' => $department->id,
        'appointment_date' => '2026-10-07',
        'appointment_time' => '09:30:00',
        'reason' => 'Consultation',
        'status' => 'Scheduled',
    ]);
    $prescription = Prescription::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'appointment_id' => $appointment->id,
        'prescription_date' => '2026-10-07',
        'status' => 'Active',
    ]);
    $medicine = Medicine::create([
        'name' => 'Aspirin',
        'dosage_form' => 'Tablet',
        'quantity' => 50,
        'unit_price' => 2.50,
        'expiry_date' => '2028-01-01',
        'status' => 'Active',
    ]);
    $prescription->medicines()->attach($medicine->id, [
        'dosage' => '1 tablet',
        'frequency' => 'Once daily',
        'duration' => '7 days',
        'quantity' => 7,
        'unit_price' => 2.50,
        'total' => 17.50,
    ]);

    Invoice::create([
        'patient_id' => $patient->id,
        'prescription_id' => $prescription->id,
        'appointment_id' => $appointment->id,
        'invoice_date' => '2026-10-07',
        'subtotal' => 17.50,
        'discount' => 0,
        'tax' => 0,
        'tax_percent' => 0,
        'total' => 17.50,
        'grand_total' => 17.50,
        'paid_amount' => 0,
        'due_amount' => 17.50,
        'status' => 'UNPAID',
    ]);

    $this->actingAs($user)
        ->post(route('invoices.store'), [
            'prescription_id' => $prescription->id,
            'invoice_date' => '2026-10-08',
            'discount' => 0,
            'tax_percent' => 0,
        ])
        ->assertSessionHasErrors('prescription_id');
});

it('hides medicines with zero or negative stock from the prescription form', function () {
    $user = User::factory()->create();

    $availableMedicine = Medicine::create([
        'name' => 'Paracetamol',
        'dosage_form' => 'Tablet',
        'quantity' => 5,
        'unit_price' => 1.50,
        'expiry_date' => '2028-01-01',
        'status' => 'Active',
    ]);

    $outOfStockMedicine = Medicine::create([
        'name' => 'Amoxicillin',
        'dosage_form' => 'Capsule',
        'quantity' => 0,
        'unit_price' => 2.00,
        'expiry_date' => '2028-01-01',
        'status' => 'Active',
    ]);

    $this->actingAs($user)
        ->get(route('prescriptions.create'))
        ->assertOk()
        ->assertSee($availableMedicine->name)
        ->assertDontSee($outOfStockMedicine->name);
});

it('sets invoice patient and appointment from the selected prescription', function () {
    $user = User::factory()->create();
    $department = Department::create(['name' => 'Cardiology', 'status' => 'Active']);
    $patient = Patient::create([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'gender' => 'Female',
        'date_of_birth' => '1815-12-10',
        'phone' => '5550001111',
        'status' => 'Active',
    ]);
    $doctor = Doctor::create([
        'first_name' => 'Grace',
        'last_name' => 'Hopper',
        'gender' => 'Female',
        'phone' => '5550002222',
        'email' => 'grace@example.test',
        'specialization' => 'Cardiology',
        'department_id' => $department->id,
        'qualification' => 'MD',
        'license_number' => 'LIC-1001',
        'experience' => 12,
        'status' => 'Active',
    ]);
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'department_id' => $department->id,
        'appointment_date' => '2026-10-07',
        'appointment_time' => '09:30:00',
        'reason' => 'Consultation',
        'status' => 'Scheduled',
    ]);
    $prescription = Prescription::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'appointment_id' => $appointment->id,
        'prescription_date' => '2026-10-07',
        'status' => 'Active',
    ]);
    $medicine = Medicine::create([
        'name' => 'Aspirin',
        'dosage_form' => 'Tablet',
        'quantity' => 50,
        'unit_price' => 2.50,
        'expiry_date' => '2028-01-01',
        'status' => 'Active',
    ]);
    $prescription->medicines()->attach($medicine->id, [
        'dosage' => '1 tablet',
        'frequency' => 'Once daily',
        'duration' => '7 days',
        'quantity' => 7,
        'unit_price' => 2.50,
        'total' => 17.50,
    ]);

    $this->actingAs($user)
        ->post(route('invoices.store'), [
            'prescription_id' => $prescription->id,
            'invoice_date' => '2026-10-07',
            'discount' => 0,
            'tax_percent' => 0,
        ])
        ->assertRedirect();

    $invoice = Invoice::query()->firstOrFail();

    expect($invoice->patient_id)->toBe($patient->id)
        ->and($invoice->appointment_id)->toBe($appointment->id);
});
