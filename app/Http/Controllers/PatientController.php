<?php

namespace App\Http\Controllers;

use App\Models\Patient;

class PatientController extends HospitalResourceController
{
    protected string $model = Patient::class;

    protected string $title = 'Patient';

    protected array $with = [
        'appointments',
        'prescriptions',
        'invoices',
    ];

    protected array $rules = [
        'first_name' => ['required', 'string', 'max:100'],
        'last_name' => ['required', 'string', 'max:100'],
        'gender' => ['required', 'string', 'max:20'],
        'date_of_birth' => ['required', 'date'],
        'phone' => ['required', 'string', 'max:32'],
        'email' => ['nullable', 'email'],
        'address' => ['nullable', 'string', 'max:255'],
        'emergency_contact' => ['nullable', 'string', 'max:100'],
        'blood_group' => ['nullable', 'string', 'max:10'],
        'allergies' => ['nullable', 'string'],
        'medical_history' => ['nullable', 'string'],
        'status' => ['required', 'string', 'max:30'],
    ];
}
