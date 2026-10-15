<?php

namespace App\Http\Controllers;

use App\Models\Department;

class DepartmentController extends HospitalResourceController
{
    protected string $model = Department::class;

    protected string $title = 'Department';

    protected array $with = ['doctors'];

    protected array $rules = [
        'name' => [
            'required',
            'string',
            'max:255',
        ],
        'description' => [
            'nullable',
            'string',
        ],
        'status' => [
            'required',
            'string',
            'max:30',
        ],
    ];
}
