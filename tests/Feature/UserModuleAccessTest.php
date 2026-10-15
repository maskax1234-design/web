<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates users with only the module permissions selected by an administrator', function () {
    $admin = User::factory()->create(['role' => 'Admin']);

    $this->actingAs($admin)
        ->get(route('users.create'))
        ->assertOk()
        ->assertSee('Module Access Permissions')
        ->assertSee('Pharmacy POS, Sales and Reports')
        ->assertSee('Users and Staff');

    $this->post(route('users.store'), [
        'name' => 'Pharmacy Cashier',
        'email' => 'cashier@example.test',
        'phone' => '555-0101',
        'role' => 'Cashier',
        'status' => 'Active',
        'password' => 'secure-password',
        'module_access' => ['pharmacy', 'medicines'],
    ])->assertRedirect();

    $user = User::query()->where('email', 'cashier@example.test')->sole();
    expect($user->module_access)->toBe(['pharmacy', 'medicines'])
        ->and($user->canAccessModule('pharmacy'))->toBeTrue()
        ->and($user->canAccessModule('invoices'))->toBeFalse();

    $this->actingAs($user)
        ->get(route('pharmacy.pos'))
        ->assertOk();
    $this->get(route('patients.index'))->assertForbidden();
    $this->get(route('dashboard'))->assertForbidden();
});

it('synchronizes unchecked permissions and keeps the password unchanged on user edits', function () {
    $admin = User::factory()->create(['role' => 'Admin']);
    $user = User::factory()->create([
        'role' => 'Receptionist',
        'status' => 'Active',
        'module_access' => ['dashboard', 'patients'],
        'password' => 'original-password',
    ]);

    $this->actingAs($admin)
        ->get(route('users.edit', $user))
        ->assertOk()
        ->assertSee('Module Access Permissions')
        ->assertSee('id="module-patients"', false)
        ->assertSee('value="patients"', false);

    $this->post(route('users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'phone' => $user->phone,
        'role' => $user->role,
        'status' => $user->status,
        'password' => '',
    ])->assertRedirect(route('users.show', $user));

    $user->refresh();
    expect($user->module_access)->toBe([])
        ->and(Hash::check('original-password', $user->password))->toBeTrue();

    $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
});

it('hides unauthorized navigation and dashboard data while denying direct module URLs', function () {
    $user = User::factory()->create([
        'role' => 'Receptionist',
        'module_access' => ['dashboard', 'patients'],
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Patients')
        ->assertDontSee('Pharmacy POS')
        ->assertDontSee(route('appointments.index'));

    $this->getJson(route('dashboard.data'))
        ->assertOk()
        ->assertJsonPath('recentInvoices', [])
        ->assertJsonPath('todayAppointments', [])
        ->assertJsonPath('recentPrescriptions', [])
        ->assertJsonPath('lowStockMedicines', [])
        ->assertJsonPath('patientsPerMonth', []);

    $this->get(route('pharmacy.pos'))->assertForbidden();
    $this->getJson(route('pharmacy.medicines'))->assertForbidden();
    $this->get(route('doctors.create'))->assertForbidden();
    $this->post(route('patients.store'), [])->assertSessionHasErrors();
});

it('lets non-admin users manage assigned modules without granting elevated access', function () {
    $admin = User::factory()->create([
        'role' => 'Admin',
        'module_access' => [],
    ]);
    $userManager = User::factory()->create([
        'role' => 'Receptionist',
        'module_access' => ['patients', 'users'],
    ]);

    $this->actingAs($admin)->get(route('pharmacy.pos'))->assertOk();

    $this->actingAs($userManager)
        ->post(route('users.store'), [
            'name' => 'Managed User',
            'email' => 'managed-user@example.test',
            'role' => 'Admin',
            'status' => 'Active',
            'password' => 'secure-password',
            'module_access' => ['users'],
        ])
        ->assertSessionHasErrors('role');

    $this->actingAs($userManager)
        ->post(route('users.store'), [
            'name' => 'Managed User',
            'email' => 'managed-user@example.test',
            'role' => 'Doctor',
            'status' => 'Active',
            'password' => 'secure-password',
            'module_access' => ['users', 'pharmacy'],
        ])
        ->assertSessionHasErrors('module_access.1');

    $this->actingAs($userManager)
        ->post(route('users.store'), [
            'name' => 'Managed User',
            'email' => 'managed-user@example.test',
            'role' => 'Doctor',
            'status' => 'Active',
            'password' => 'secure-password',
            'module_access' => ['users'],
        ])
        ->assertRedirect();

    $managedUser = User::query()->where('email', 'managed-user@example.test')->sole();
    expect($managedUser->role)->toBe('Doctor')
        ->and($managedUser->module_access)->toBe(['users']);

    $managedUser->update(['module_access' => ['users', 'payments']]);
    $this->actingAs($userManager)
        ->post(route('users.update', $managedUser), [
            'name' => $managedUser->name,
            'email' => $managedUser->email,
            'role' => 'Admin',
            'status' => 'Active',
            'password' => '',
            'module_access' => ['users'],
        ])
        ->assertSessionHasErrors('role');

    $this->actingAs($userManager)
        ->post(route('users.update', $managedUser), [
            'name' => $managedUser->name,
            'email' => $managedUser->email,
            'role' => 'Doctor',
            'status' => 'Active',
            'password' => '',
            'module_access' => ['users'],
        ])
        ->assertRedirect(route('users.show', $managedUser));
    expect($managedUser->fresh()->module_access)->toBe(['payments', 'users']);

    $this->get(route('users.destroy', $managedUser))
        ->assertRedirect(route('users.index'));
    expect(User::query()->whereKey($managedUser->id)->exists())->toBeFalse();
});

it('allows users with doctor module access to create, update, and delete doctor records', function () {
    $doctorManager = User::factory()->create([
        'role' => 'Doctor',
        'module_access' => ['doctors'],
    ]);
    $department = Department::create(['name' => 'Cardiology']);

    $doctorData = [
        'first_name' => 'Amira',
        'last_name' => 'Hassan',
        'gender' => 'Female',
        'phone' => '555-0200',
        'email' => 'amira.hassan@example.test',
        'specialization' => 'Cardiology',
        'department_id' => $department->id,
        'qualification' => 'MD',
        'license_number' => 'LIC-2026-01',
        'experience' => 8,
        'status' => 'Active',
    ];

    $this->actingAs($doctorManager)
        ->post(route('doctors.store'), $doctorData)
        ->assertRedirect();

    $doctor = Doctor::query()->where('email', $doctorData['email'])->sole();

    $this->get(route('doctors.edit', $doctor))->assertOk();
    $this->post(route('doctors.update', $doctor), $doctorData)
        ->assertRedirect(route('doctors.show', $doctor));
    $this->get(route('doctors.destroy', $doctor))
        ->assertRedirect(route('doctors.index'));

    expect(Doctor::query()->whereKey($doctor->id)->exists())->toBeFalse();
    $this->get(route('users.create'))->assertForbidden();
});

it('grants pharmacy access after login when an administrator assigned pharmacy module control', function () {
    $cashier = User::factory()->create([
        'role' => 'Cashier',
        'status' => 'Active',
        'module_access' => array_keys(User::availableModules()),
        'password' => 'cashier-password',
    ]);

    $this->post(route('login'), [
        'email' => $cashier->email,
        'password' => 'cashier-password',
    ])->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Pharmacy POS');
    $this->get(route('pharmacy.pos'))->assertOk();
    $this->getJson(route('pharmacy.medicines'))->assertOk();
});

it('uses explicit pharmacy module permission consistently across eligible roles', function (string $role) {
    $user = User::factory()->create([
        'role' => $role,
        'status' => 'Active',
        'module_access' => ['dashboard', 'pharmacy'],
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Pharmacy POS');
    $this->get(route('pharmacy.pos'))->assertOk();
    $this->getJson(route('pharmacy.medicines'))->assertOk();
})->with([
    'cashier' => 'Cashier',
    'pharmacist' => 'Pharmacist',
    'pharmacy technician' => 'Pharmacy Technician',
    'accountant' => 'Accountant',
]);

it('denies pharmacy routes and hides the pharmacy menu when pharmacy permission is absent', function () {
    $user = User::factory()->create([
        'role' => 'Cashier',
        'status' => 'Active',
        'module_access' => ['dashboard', 'invoices'],
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Pharmacy POS');
    $this->get(route('pharmacy.pos'))->assertForbidden();
    $this->getJson(route('pharmacy.medicines'))->assertForbidden();
});

it('does not disclose pharmacy invoices through dashboard data without pharmacy access', function () {
    $invoice = Invoice::create([
        'invoice_date' => today(),
        'subtotal' => 25,
        'total' => 25,
        'grand_total' => 25,
        'due_amount' => 25,
        'status' => 'UNPAID',
        'source' => 'PHARMACY_POS',
    ]);
    $user = User::factory()->create([
        'role' => 'Cashier',
        'module_access' => ['dashboard', 'invoices'],
    ]);

    $this->actingAs($user)
        ->getJson(route('dashboard.data'))
        ->assertOk()
        ->assertJsonPath('recentInvoices', [])
        ->assertDontSee($invoice->invoice_number);
});

it('protects the final active administrator from demotion, suspension, or deletion', function () {
    $admin = User::factory()->create(['role' => 'Admin', 'status' => 'Active']);

    $this->actingAs($admin)
        ->post(route('users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'Receptionist',
            'status' => 'Active',
            'password' => '',
            'module_access' => ['dashboard'],
        ])
        ->assertSessionHasErrors('role');

    $this->get(route('users.destroy', $admin))
        ->assertSessionHasErrors('role');

    expect($admin->fresh()->role)->toBe('Admin')
        ->and($admin->fresh()->status)->toBe('Active');
});

it('rejects module identifiers that are not part of the HMS', function () {
    $admin = User::factory()->create(['role' => 'Admin']);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Invalid Module User',
            'email' => 'invalid-module@example.test',
            'role' => 'Receptionist',
            'status' => 'Active',
            'password' => 'secure-password',
            'module_access' => ['laboratory'],
        ])
        ->assertSessionHasErrors('module_access.0');

    expect(User::query()->where('email', 'invalid-module@example.test')->exists())->toBeFalse();
});

it('gives self-registered users dashboard access only and cannot accept permission input', function () {
    $this->post(route('register'), [
        'name' => 'Self Registered',
        'email' => 'self@example.test',
        'password' => 'secure-password',
        'role' => 'Admin',
        'module_access' => ['users', 'patients'],
    ])->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'self@example.test')->sole();
    expect($user->role)->toBe('Receptionist')
        ->and($user->module_access)->toBe(['dashboard']);

    $this->actingAs($user)->get(route('patients.index'))->assertForbidden();
});
