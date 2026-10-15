<?php

namespace App\Http\Controllers;

use App\Models\Doctor;

class DoctorController extends HospitalResourceController
{
    protected string $model = Doctor::class;

    protected string $title = 'Doctor';

    protected array $with = [
        'department',
        'appointments',
        'prescriptions',
    ];

    protected array $rules = [
        'first_name' => ['required', 'string'],
        'last_name' => ['required', 'string'],
        'gender' => ['required', 'string'],
        'phone' => ['required', 'string'],
        'email' => ['required', 'email'],
        'specialization' => ['required', 'string'],
        'department_id' => ['required', 'exists:departments,id'],
        'qualification' => ['required', 'string'],
        'license_number' => ['required', 'string'],
        'experience' => ['required', 'integer', 'min:0'],
        'status' => ['required', 'string'],
    ];
}
