<?php

use App\Models\User;

it('returns the shared dashboard payload for regular users', function () {
    $user = User::factory()->create([
        'name' => 'Dr. Smith',
        'role' => 'Doctor',
    ]);

    $this->actingAs($user)
        ->getJson(route('dashboard.data'))
        ->assertOk()
        ->assertJsonPath('user.name', 'Dr. Smith')
        ->assertJsonPath('user.role', 'Doctor')
        ->assertJsonStructure([
            'stats' => [
                '*' => ['title', 'value', 'icon', 'tone'],
            ],
            'recentPatients',
            'todayAppointments',
            'recentInvoices',
            'recentPrescriptions',
            'lowStockMedicines',
            'patientsPerMonth',
        ]);
});

it('blocks admin-only finance data for non-admin users', function () {
    $user = User::factory()->create(['role' => 'Receptionist']);

    $this->actingAs($user)
        ->getJson(route('dashboard.admin'))
        ->assertStatus(403);
});

it('allows admins to access finance analytics', function () {
    $user = User::factory()->create(['role' => 'Admin']);

    $this->actingAs($user)
        ->getJson(route('dashboard.admin'))
        ->assertOk()
        ->assertJsonStructure([
            'summary',
            'revenueSeries',
            'departmentBreakdown',
            'topUnpaidInvoices',
        ]);
});

it('allows non-admins to manage records in their assigned modules', function () {
    $user = User::factory()->create(['role' => 'Receptionist']);

    $this->actingAs($user)
        ->get(route('doctors.index'))
        ->assertOk()
        ->assertSee('Add Doctor');

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee('Add User');

    $this->get(route('patients.index'))->assertOk();

    $this->get(route('doctors.create'))->assertOk();
    $this->get(route('users.create'))->assertOk();
});
