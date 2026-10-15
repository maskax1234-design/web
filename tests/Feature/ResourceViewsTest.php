<?php

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Support\Facades\Route;

function seedRecord(string $modelClass): object
{
    return match ($modelClass) {
        Patient::class => Patient::firstOrCreate(
            ['patient_number' => 'P-1001'],
            [
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'gender' => 'Female',
                'date_of_birth' => '1815-12-10',
                'phone' => '5550001111',
                'email' => 'ada@example.test',
                'status' => 'Active',
            ]
        ),
        Department::class => Department::firstOrCreate(
            ['name' => 'Cardiology'],
            ['description' => 'Heart care department', 'status' => 'Active']
        ),
        Doctor::class => Doctor::firstOrCreate(
            ['doctor_number' => 'D-1001'],
            [
                'first_name' => 'Grace',
                'last_name' => 'Hopper',
                'gender' => 'Female',
                'phone' => '5550002222',
                'email' => 'grace@example.test',
                'specialization' => 'Cardiology',
                'department_id' => Department::firstOrCreate(['name' => 'Cardiology'], ['description' => 'Heart care department', 'status' => 'Active'])->id,
                'qualification' => 'MD',
                'license_number' => 'LIC-1',
                'experience' => 12,
                'status' => 'Active',
            ]
        ),
        Appointment::class => Appointment::firstOrCreate(
            ['appointment_number' => 'A-1001'],
            [
                'patient_id' => Patient::firstOrCreate(['patient_number' => 'P-1001'], ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'gender' => 'Female', 'date_of_birth' => '1815-12-10', 'phone' => '5550001111', 'email' => 'ada@example.test', 'status' => 'Active'])->id,
                'doctor_id' => Doctor::firstOrCreate(['doctor_number' => 'D-1001'], ['first_name' => 'Grace', 'last_name' => 'Hopper', 'gender' => 'Female', 'phone' => '5550002222', 'email' => 'grace@example.test', 'specialization' => 'Cardiology', 'department_id' => Department::firstOrCreate(['name' => 'Cardiology'], ['description' => 'Heart care department', 'status' => 'Active'])->id, 'qualification' => 'MD', 'license_number' => 'LIC-1', 'experience' => 12, 'status' => 'Active'])->id,
                'department_id' => Department::firstOrCreate(['name' => 'Cardiology'], ['description' => 'Heart care department', 'status' => 'Active'])->id,
                'appointment_date' => '2026-01-05',
                'appointment_time' => '09:30:00',
                'reason' => 'Chest pain',
                'status' => 'Scheduled',
            ]
        ),
        Medicine::class => Medicine::firstOrCreate(
            ['medicine_code' => 'MED-1'],
            [
                'name' => 'Aspirin',
                'generic_name' => 'Acetylsalicylic acid',
                'dosage_form' => 'Tablet',
                'quantity' => 100,
                'unit_price' => 2.50,
                'expiry_date' => '2028-01-01',
                'status' => 'Active',
            ]
        ),
        Prescription::class => Prescription::firstOrCreate(
            ['prescription_number' => 'RX-1001'],
            [
                'patient_id' => Patient::firstOrCreate(['patient_number' => 'P-1001'], ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'gender' => 'Female', 'date_of_birth' => '1815-12-10', 'phone' => '5550001111', 'email' => 'ada@example.test', 'status' => 'Active'])->id,
                'doctor_id' => Doctor::firstOrCreate(['doctor_number' => 'D-1001'], ['first_name' => 'Grace', 'last_name' => 'Hopper', 'gender' => 'Female', 'phone' => '5550002222', 'email' => 'grace@example.test', 'specialization' => 'Cardiology', 'department_id' => Department::firstOrCreate(['name' => 'Cardiology'], ['description' => 'Heart care department', 'status' => 'Active'])->id, 'qualification' => 'MD', 'license_number' => 'LIC-1', 'experience' => 12, 'status' => 'Active'])->id,
                'prescription_date' => '2026-01-07',
                'status' => 'Active',
            ]
        ),
        Invoice::class => Invoice::firstOrCreate(
            ['invoice_number' => 'INV-1001'],
            [
                'patient_id' => Patient::firstOrCreate(['patient_number' => 'P-1001'], ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'gender' => 'Female', 'date_of_birth' => '1815-12-10', 'phone' => '5550001111', 'email' => 'ada@example.test', 'status' => 'Active'])->id,
                'appointment_id' => Appointment::firstOrCreate(['appointment_number' => 'A-1001'], ['patient_id' => Patient::firstOrCreate(['patient_number' => 'P-1001'], ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'gender' => 'Female', 'date_of_birth' => '1815-12-10', 'phone' => '5550001111', 'email' => 'ada@example.test', 'status' => 'Active'])->id, 'doctor_id' => Doctor::firstOrCreate(['doctor_number' => 'D-1001'], ['first_name' => 'Grace', 'last_name' => 'Hopper', 'gender' => 'Female', 'phone' => '5550002222', 'email' => 'grace@example.test', 'specialization' => 'Cardiology', 'department_id' => Department::firstOrCreate(['name' => 'Cardiology'], ['description' => 'Heart care department', 'status' => 'Active'])->id, 'qualification' => 'MD', 'license_number' => 'LIC-1', 'experience' => 12, 'status' => 'Active'])->id, 'department_id' => Department::firstOrCreate(['name' => 'Cardiology'], ['description' => 'Heart care department', 'status' => 'Active'])->id, 'appointment_date' => '2026-01-05', 'appointment_time' => '09:30:00', 'reason' => 'Chest pain', 'status' => 'Scheduled'])->id,
                'invoice_date' => '2026-01-08',
                'subtotal' => 100,
                'discount' => 0,
                'tax' => 0,
                'total' => 100,
                'paid_amount' => 0,
                'due_amount' => 100,
                'status' => 'Unpaid',
            ]
        ),
        Payment::class => Payment::firstOrCreate(
            ['payment_number' => 'PAY-1001'],
            [
                'invoice_id' => Invoice::firstOrCreate(['invoice_number' => 'INV-1001'], ['patient_id' => Patient::firstOrCreate(['patient_number' => 'P-1001'], ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'gender' => 'Female', 'date_of_birth' => '1815-12-10', 'phone' => '5550001111', 'email' => 'ada@example.test', 'status' => 'Active'])->id, 'appointment_id' => Appointment::firstOrCreate(['appointment_number' => 'A-1001'], ['patient_id' => Patient::firstOrCreate(['patient_number' => 'P-1001'], ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'gender' => 'Female', 'date_of_birth' => '1815-12-10', 'phone' => '5550001111', 'email' => 'ada@example.test', 'status' => 'Active'])->id, 'doctor_id' => Doctor::firstOrCreate(['doctor_number' => 'D-1001'], ['first_name' => 'Grace', 'last_name' => 'Hopper', 'gender' => 'Female', 'phone' => '5550002222', 'email' => 'grace@example.test', 'specialization' => 'Cardiology', 'department_id' => Department::firstOrCreate(['name' => 'Cardiology'], ['description' => 'Heart care department', 'status' => 'Active'])->id, 'qualification' => 'MD', 'license_number' => 'LIC-1', 'experience' => 12, 'status' => 'Active'])->id, 'department_id' => Department::firstOrCreate(['name' => 'Cardiology'], ['description' => 'Heart care department', 'status' => 'Active'])->id, 'appointment_date' => '2026-01-05', 'appointment_time' => '09:30:00', 'reason' => 'Chest pain', 'status' => 'Scheduled'])->id, 'invoice_date' => '2026-01-08', 'subtotal' => 100, 'discount' => 0, 'tax' => 0, 'total' => 100, 'paid_amount' => 0, 'due_amount' => 100, 'status' => 'Unpaid'])->id,
                'amount' => 50,
                'payment_date' => '2026-01-09',
                'payment_method' => 'Cash',
                'received_by' => User::firstOrCreate(['email' => 'admin@example.test'], ['name' => 'Test Admin', 'password' => bcrypt('password'), 'role' => 'Admin', 'status' => 'Active'])->id,
            ]
        ),
        User::class => User::firstOrCreate(
            ['email' => 'admin@example.test'],
            ['name' => 'Test Admin', 'password' => bcrypt('password'), 'role' => 'Admin', 'status' => 'Active']
        ),
        default => throw new InvalidArgumentException('Unsupported model class: '.$modelClass),
    };
}

dataset('resources', [
    'patients' => [Patient::class, 'patients'],
    'doctors' => [Doctor::class, 'doctors'],
    'departments' => [Department::class, 'departments'],
    'appointments' => [Appointment::class, 'appointments'],
    'medicines' => [Medicine::class, 'medicines'],
    'prescriptions' => [Prescription::class, 'prescriptions'],
    'invoices' => [Invoice::class, 'invoices'],
    'payments' => [Payment::class, 'payments'],
    'users' => [User::class, 'users'],
]);

it('exposes only the nine required resource modules', function () {
    $requiredRoutes = ['patients.index', 'doctors.index', 'departments.index', 'appointments.index', 'medicines.index', 'prescriptions.index', 'invoices.index', 'payments.index', 'users.index'];
    $removedRoutes = ['admissions.index', 'medical-records.index', 'diagnoses.index', 'lab-tests.index', 'lab-results.index', 'rooms.index'];

    foreach ($requiredRoutes as $route) {
        expect(Route::has($route))->toBeTrue();
    }

    foreach ($removedRoutes as $route) {
        expect(Route::has($route))->toBeFalse();
    }
});

it('renders each required page', function (string $modelClass, string $route) {
    $record = seedRecord($modelClass);
    $admin = User::factory()->create(['role' => 'Admin']);

    $this->actingAs($admin)
        ->get(route("{$route}.index"))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route("{$route}.create"))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route("{$route}.show", $record))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route("{$route}.edit", $record))
        ->assertOk();
})->with('resources');

it('requires authentication for protected module routes', function (string $modelClass, string $route) {
    seedRecord($modelClass);

    $this->get(route("{$route}.index"))
        ->assertRedirect(route('showLogin'));
})->with('resources');

it('generates automatic sequential custom identifiers for the supported modules', function () {
    $department = Department::create(['name' => 'Neurology', 'status' => 'Active']);
    $patient = Patient::create([
        'first_name' => 'Alan',
        'last_name' => 'Turing',
        'gender' => 'Male',
        'date_of_birth' => '1912-06-23',
        'phone' => '5550007777',
        'email' => 'alan@example.test',
        'status' => 'Active',
    ]);
    $doctor = Doctor::create([
        'first_name' => 'Margaret',
        'last_name' => 'Hamilton',
        'gender' => 'Female',
        'phone' => '5550008888',
        'email' => 'margaret@example.test',
        'specialization' => 'Neurology',
        'department_id' => $department->id,
        'qualification' => 'MD',
        'license_number' => 'LIC-2',
        'experience' => 10,
        'status' => 'Active',
    ]);
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'department_id' => $department->id,
        'appointment_date' => '2026-03-10',
        'appointment_time' => '11:00:00',
        'reason' => 'Consultation',
        'status' => 'Scheduled',
    ]);
    $medicine = Medicine::create([
        'name' => 'Ibuprofen',
        'generic_name' => 'Ibuprofen',
        'dosage_form' => 'Tablet',
        'quantity' => 50,
        'unit_price' => 4.50,
        'expiry_date' => '2029-01-01',
        'status' => 'Active',
    ]);
    $prescription = Prescription::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'prescription_date' => '2026-03-11',
        'status' => 'Active',
    ]);
    $invoice = Invoice::create([
        'patient_id' => $patient->id,
        'appointment_id' => $appointment->id,
        'invoice_date' => '2026-03-11',
        'subtotal' => 100,
        'discount' => 0,
        'tax' => 0,
        'total' => 100,
        'due_amount' => 100,
        'status' => 'Unpaid',
    ]);
    $payment = Payment::create([
        'invoice_id' => $invoice->id,
        'amount' => 100,
        'payment_date' => '2026-03-12',
        'payment_method' => 'Cash',
        'received_by' => User::factory()->create()->id,
    ]);

    expect($patient->patient_number)->toMatch('/^PAT-\d{4}$/');
    expect($doctor->doctor_number)->toMatch('/^DOC-\d{4}$/');
    expect($department->department_number)->toMatch('/^DEP-\d{4}$/');
    expect($appointment->appointment_number)->toMatch('/^APT-\d{4}$/');
    expect($medicine->medicine_code)->toMatch('/^MED-\d{4}$/');
    expect($prescription->prescription_number)->toMatch('/^PRE-\d{4}$/');
    expect($invoice->invoice_number)->toMatch('/^INV-\d{4}$/');
    expect($payment->payment_number)->toMatch('/^PAY-\d{4}$/');
});

it('prevents overlapping 25-minute bookings for the same doctor on the same date', function () {
    $department = Department::create(['name' => 'Neurology', 'status' => 'Active']);
    $patient = Patient::create([
        'first_name' => 'Alan',
        'last_name' => 'Turing',
        'gender' => 'Male',
        'date_of_birth' => '1912-06-23',
        'phone' => '5550007777',
        'email' => 'alan@example.test',
        'status' => 'Active',
    ]);
    $doctor = Doctor::create([
        'first_name' => 'Margaret',
        'last_name' => 'Hamilton',
        'gender' => 'Female',
        'phone' => '5550008888',
        'email' => 'margaret@example.test',
        'specialization' => 'Neurology',
        'department_id' => $department->id,
        'qualification' => 'MD',
        'license_number' => 'LIC-3',
        'experience' => 10,
        'status' => 'Active',
    ]);

    Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'department_id' => $department->id,
        'appointment_date' => '2026-04-10',
        'appointment_time' => '17:00:00',
        'reason' => 'Follow-up',
        'status' => 'Scheduled',
    ]);

    $this->actingAs(User::factory()->create(['role' => 'Admin']))
        ->post(route('appointments.store'), [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'appointment_date' => '2026-04-10',
            'appointment_time' => '17:10:00',
            'reason' => 'New consultation',
            'status' => 'Scheduled',
        ])
        ->assertSessionHas('error', 'This doctor is already booked during this time. Each appointment takes 25 minutes. Please choose another time.');
});

it('allows the same doctor to book the same time on a different date', function () {
    $department = Department::create(['name' => 'Neurology', 'status' => 'Active']);
    $patient = Patient::create([
        'first_name' => 'Alan',
        'last_name' => 'Turing',
        'gender' => 'Male',
        'date_of_birth' => '1912-06-23',
        'phone' => '5550007777',
        'email' => 'alan2@example.test',
        'status' => 'Active',
    ]);
    $doctor = Doctor::create([
        'first_name' => 'Margaret',
        'last_name' => 'Hamilton',
        'gender' => 'Female',
        'phone' => '5550008888',
        'email' => 'margaret2@example.test',
        'specialization' => 'Neurology',
        'department_id' => $department->id,
        'qualification' => 'MD',
        'license_number' => 'LIC-4',
        'experience' => 10,
        'status' => 'Active',
    ]);

    Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'department_id' => $department->id,
        'appointment_date' => '2026-04-10',
        'appointment_time' => '17:00:00',
        'reason' => 'Follow-up',
        'status' => 'Scheduled',
    ]);

    $this->actingAs(User::factory()->create(['role' => 'Admin']))
        ->post(route('appointments.store'), [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'department_id' => $department->id,
            'appointment_date' => '2026-04-11',
            'appointment_time' => '17:00:00',
            'reason' => 'Next consultation',
            'status' => 'Scheduled',
        ])
        ->assertRedirect(route('appointments.show', Appointment::query()->latest('id')->first()));
});

it('does not render editable number inputs in the create forms', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('patients.create'))
        ->assertOk()
        ->assertDontSee('name="patient_number"')
        ->assertDontSee('name="doctor_number"')
        ->assertDontSee('name="appointment_number"')
        ->assertDontSee('name="invoice_number"')
        ->assertDontSee('name="payment_number"')
        ->assertDontSee('name="prescription_number"')
        ->assertDontSee('name="medicine_code"')
        ->assertDontSee('name="department_number"');
});

it('defaults payment date to today and provides outstanding invoice amounts', function () {
    $invoice = seedRecord(Invoice::class);
    $invoice->update(['grand_total' => 100]);
    $invoice->payments()->create([
        'amount' => 25,
        'payment_date' => '2026-10-06',
        'payment_method' => 'Cash',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('payments.create'))
        ->assertOk()
        ->assertSee('name="payment_date"', false)
        ->assertSee('value="'.now()->toDateString().'"', false)
        ->assertSee('const invoiceAmountMap =', false)
        ->assertSee('"due_amount":75', false);
});

it('shows the linked prescription, medicine subtotals, and invoice total', function () {
    $invoice = seedRecord(Invoice::class);
    $prescription = seedRecord(Prescription::class);
    $medicine = seedRecord(Medicine::class);

    $invoice->update([
        'prescription_id' => $prescription->id,
        'subtotal' => 20,
        'discount' => 2,
        'tax_percent' => 5,
        'tax' => 0.90,
        'total' => 18.90,
    ]);

    $invoice->items()->create([
        'medicine_id' => $medicine->id,
        'quantity' => 4,
        'unit_price' => 5,
        'total' => 20,
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('invoices.show', $invoice))
        ->assertOk()
        ->assertSee($prescription->prescription_number)
        ->assertSee($medicine->name)
        ->assertSee('Prescription Items')
        ->assertSee('Subtotal')
        ->assertSee('20.00')
        ->assertSee('Total')
        ->assertSee('18.90');
});

it('provides the selected prescription subtotal for the invoice total preview', function () {
    $prescription = seedRecord(Prescription::class);
    $medicine = seedRecord(Medicine::class);
    $prescription->medicines()->attach($medicine->id, [
        'dosage' => '1 tablet',
        'frequency' => 'Once daily',
        'duration' => '7 days',
        'quantity' => 4,
        'unit_price' => 2.50,
        'total' => 10,
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('invoices.create'))
        ->assertOk()
        ->assertSee('id="invoice-total-preview"', false)
        ->assertSee('readonly', false)
        ->assertSee('"subtotal":10', false)
        ->assertSee('Updates when you select a prescription or change discount and tax.');
});

it('records the authenticated user as the payment receiver', function () {
    $user = User::factory()->create();
    $invoice = seedRecord(Invoice::class);
    $invoice->update(['grand_total' => 100, 'paid_amount' => 0, 'due_amount' => 100, 'status' => 'Unpaid']);

    $this->actingAs($user)
        ->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'amount' => 25,
            'payment_date' => '2026-10-06',
            'payment_method' => 'Cash',
            'reference' => 'REF-001',
            'notes' => 'Received by logged in cashier',
        ])
        ->assertRedirect();

    $payment = Payment::query()->latest()->first();

    expect($payment)->not->toBeNull()
        ->and($payment->received_by)->toBe($user->id);
});

it('auto-generates the payment reference from the selected invoice', function () {
    $invoice = seedRecord(Invoice::class);
    $invoice->update(['invoice_number' => 'INV-9001', 'grand_total' => 120, 'paid_amount' => 0, 'due_amount' => 120, 'status' => 'Unpaid']);

    $this->actingAs(User::factory()->create())
        ->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'amount' => 40,
            'payment_date' => '2026-10-06',
            'payment_method' => 'Cash',
        ])
        ->assertRedirect();

    $payment = Payment::query()->latest()->first();

    expect($payment->reference)->toBe('INV-9001');
});

it('shows an extra payment message when payment exceeds the invoice balance', function () {
    $invoice = seedRecord(Invoice::class);
    $invoice->update(['grand_total' => 100, 'paid_amount' => 0, 'due_amount' => 100, 'status' => 'Unpaid']);

    $this->actingAs(User::factory()->create())
        ->from(route('payments.create'))
        ->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'amount' => 150,
            'payment_date' => '2026-10-06',
            'payment_method' => 'Cash',
        ])
        ->assertSessionHasErrors('amount')
        ->assertSessionHas('errors', fn ($errors) => str_contains($errors->get('amount')[0], 'extra'));
});

it('allows deleting a doctor when appointments and prescriptions still reference it', function () {
    $doctor = seedRecord(Doctor::class);

    Appointment::query()->create([
        'appointment_number' => 'A-2001',
        'patient_id' => seedRecord(Patient::class)->id,
        'doctor_id' => $doctor->id,
        'department_id' => $doctor->department_id,
        'appointment_date' => '2026-02-05',
        'appointment_time' => '10:00:00',
        'reason' => 'Follow-up',
        'status' => 'Scheduled',
    ]);

    Prescription::query()->create([
        'prescription_number' => 'RX-2001',
        'patient_id' => seedRecord(Patient::class)->id,
        'doctor_id' => $doctor->id,
        'prescription_date' => '2026-02-06',
        'status' => 'Active',
    ]);

    $doctor->delete();

    expect(Doctor::query()->whereKey($doctor->id)->exists())->toBeFalse();
    expect(Appointment::query()->where('doctor_id', $doctor->id)->exists())->toBeFalse();
    expect(Prescription::query()->where('doctor_id', $doctor->id)->exists())->toBeFalse();
});

it('keeps the dashboard and sidebar focused on the nine modules', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Patients')
        ->assertSee('Doctors')
        ->assertSee('Appointments')
        ->assertDontSee('Admissions')
        ->assertDontSee('Medical Records')
        ->assertDontSee('Lab Tests')
        ->assertDontSee('Rooms');
});
